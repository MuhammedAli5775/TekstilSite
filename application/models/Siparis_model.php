<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Siparis_model — B2B sipariş oluşturma + okuma + durum yönetimi (tek transaction).
 * Server-side tutar · anlık kopya (para birimi+kur snapshot) · stok düşüşü ·
 * hareket logu · durum geçmişi. İptal/iadede stok geri eklenir (_stok_iade_et).
 */
class Siparis_model extends CI_Model
{
    /**
     * Sipariş oluştur (tek transaction).
     * @param array $g teslimat_*, fatura_*, email, firma_adi, vergi_no,
     *                  odeme_yontemi(kod), kargo_firma_id, [bayi_id]
     * @return array {ok, siparis_id, siparis_no, toplam} veya {ok:false, mesaj}
     */
    public function mg_olustur($g)
    {
        $CI =& get_instance();
        $CI->load->model('sepet_model');

        // (1) Sepet (sunucu-taraflı fiyat, TRY bazlı).
        $liste = $CI->sepet_model->liste();
        if (empty($liste['satirlar'])) {
            return array('ok' => FALSE, 'mesaj' => 'Sepetiniz boş.');
        }
        $satirlar = $liste['satirlar'];
        $ara_toplam_try = (float) $liste['ara_toplam'];

        // (2) Sipariş para birimi + kur (snapshot) — sepet/ödeme görüntüsüyle BİREBİR
        // tutarlı (XXXIV): aktif_para_birimi() açıkça seçilmiş teslimat ülkesini
        // kazanır, yoksa bayi hesap para birimini döndürür. Misafir + ülke yok → TRY.
        $bayi_id = ! empty($g['bayi_id']) ? (int) $g['bayi_id'] : NULL;
        $para_birimi = function_exists('aktif_para_birimi') ? strtoupper(trim((string) aktif_para_birimi())) : 'TRY';
        if ($para_birimi === '' || $para_birimi === '0') { $para_birimi = 'TRY'; }
        $kur = 1.0;
        if ($para_birimi !== 'TRY') {
            $pb = $this->db->select('kur_try')->where('kod', $para_birimi)->where('durum', 1)->limit(1)->get('para_birimleri')->row();
            $kur = $pb ? (float) $pb->kur_try : 1.0;
        }
        if ($kur <= 0) { $kur = 1.0; }
        $cevir = function ($try) use ($kur) {
            return $kur == 1.0 ? round((float) $try, 2) : round((float) $try / $kur, 2);
        };

        // (3) Kargo (TRY): ücretsiz kargo eşiği altında düz ücret (desi hesabı yok).
        $esik = (float) ayar('ucretsiz_kargo_esik', 2000);
        $kargo_try = ($ara_toplam_try >= $esik) ? 0.0 : (float) ayar('varsayilan_kargo_ucreti', 0);

        // (4) İşlem ücreti (TRY): ödeme yöntemi ek ücreti. Yalnız AKTİF (durum=1)
        // yöntem kabul edilir — kapalı/bilinmeyen kodla sipariş oluşmaz (POST'a güvenilmez).
        $oy = $this->db->where('kod', $g['odeme_yontemi'])->where('durum', 1)->limit(1)->get('odeme_yontemleri')->row();
        if (! $oy) {
            return array('ok' => FALSE, 'mesaj' => 'Geçersiz ödeme yöntemi.');
        }
        $islem_try = ($oy->ek_ucret_tip === 'yuzde')
            ? $ara_toplam_try * (float) $oy->ek_ucret / 100
            : (float) $oy->ek_ucret;

        $indirim_try = $this->_kupon_indirim($ara_toplam_try); // session kupon (TRY)
        $toplam_try = $ara_toplam_try - $indirim_try + $islem_try + $kargo_try;

        $islem_snap   = $cevir($islem_try);
        $kargo_snap   = $cevir($kargo_try);
        $indirim_snap = $cevir($indirim_try);

        // (5) Transaction.
        $this->db->trans_begin();

        $gecici_no = 'TMP' . bin2hex(random_bytes(4)); // insert_id öncesi benzersiz yer tutucu
        $this->db->insert('siparisler', array(
            'siparis_no'       => $gecici_no,
            'bayi_id'          => $bayi_id,
            'para_birimi'      => $para_birimi,
            'kur'              => $kur,
            'ara_toplam'       => 0, // detaylardan toplanır, aşağıda güncellenir
            'indirim'          => $indirim_snap,
            'islem_ucreti'     => $islem_snap,
            'kargo_ucreti'     => $kargo_snap,
            'toplam'           => 0,
            'odeme_yontemi'    => $g['odeme_yontemi'],
            'odeme_durumu'     => 'bekliyor',
            'durum'            => 'onay_bekliyor',
            'teslimat_ad'      => $g['teslimat_ad'] ?? NULL,
            'teslimat_adres'   => $g['teslimat_adres'] ?? NULL,
            'teslimat_il'      => $g['teslimat_il'] ?? NULL,
            'teslimat_ilce'    => $g['teslimat_ilce'] ?? NULL,
            'teslimat_telefon' => $g['teslimat_telefon'] ?? NULL,
            'email'            => $g['email'] ?? NULL,
            'fatura_ad'        => $g['fatura_ad'] ?? NULL,
            'fatura_adres'     => $g['fatura_adres'] ?? NULL,
            'firma_adi'        => $g['firma_adi'] ?? NULL,
            'vergi_no'         => $g['vergi_no'] ?? NULL,
            'kargo_firma_id'   => ! empty($g['kargo_firma_id']) ? (int) $g['kargo_firma_id'] : NULL,
        ));
        $id = (int) $this->db->insert_id();
        $no = 'SP-' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);

        // (6) Detaylar (anlık kopya) + stok düşüşü + hareket logu; ara toplam topla.
        $ara_snap = 0.0;
        foreach ($satirlar as $r) {
            $birim_snap = $cevir((float) $r->birim);          // bayi para biriminde birim
            $ara_satir  = round($birim_snap * (int) $r->adet, 2); // görünümle birebir (birim×adet)
            $ara_snap  += $ara_satir;

            $varyant_bilgi = '';
            if (! empty($r->renk) || ! empty($r->beden)) {
                $varyant_bilgi = trim(((string) ($r->renk ?? '')) . ' / ' . ((string) ($r->beden ?? '')), ' /');
            }

            $this->db->insert('siparis_detaylari', array(
                'siparis_id'    => $id,
                'urun_id'       => ! empty($r->urun_id) ? (int) $r->urun_id : NULL,
                'varyant_id'    => ! empty($r->varyant_id) ? (int) $r->varyant_id : NULL,
                'urun_adi'      => (string) ($r->ad ?? ''),
                'stok_kodu'     => (string) ($r->stok_kodu ?? ''),
                'varyant_bilgi' => $varyant_bilgi !== '' ? $varyant_bilgi : NULL,
                'birim_fiyat'   => $birim_snap,
                'adet'          => (int) $r->adet,
                'kdv'           => (int) ($r->kdv ?? 20),
                'ara_toplam'    => $ara_satir,
            ));

            // Stok varyant düzeyinde tutulur → KOŞULLU düşüm (yarış güvenliği, XXVI):
            // stok yeterli değilse satır etkilenmez → işlem geri alınır, sipariş oluşmaz.
            if (! empty($r->varyant_id)) {
                $v = $this->db->select('stok')->where('id', (int) $r->varyant_id)->limit(1)->get('urun_varyantlari')->row();
                $onceki = $v ? (int) $v->stok : 0;
                $this->db->set('stok', 'stok - ' . (int) $r->adet, FALSE)   // adet (int) cast — enjeksiyon yüzeyi yok
                         ->where('id', (int) $r->varyant_id)
                         ->where('stok >=', (int) $r->adet)
                         ->update('urun_varyantlari');
                if ($this->db->affected_rows() === 0) {
                    $this->db->trans_rollback();
                    return array('ok' => FALSE, 'mesaj' => 'Yetersiz stok: ' . ($r->ad ?? '') . ' (mevcut ' . $onceki . ' adet).');
                }
                $this->db->insert('stok_hareketleri', array(
                    'urun_id'     => ! empty($r->urun_id) ? (int) $r->urun_id : NULL,
                    'varyant_id'  => (int) $r->varyant_id,
                    'tip'         => 'satis',
                    'adet'        => -1 * (int) $r->adet,
                    'onceki_stok' => $onceki,
                    'aciklama'    => 'Siparis ' . $no,
                    'siparis_id'  => $id,
                ));
            }
        }

        $toplam_snap = round($ara_snap + $islem_snap + $kargo_snap - $indirim_snap, 2);
        $this->db->where('id', $id)->update('siparisler', array(
            'siparis_no' => $no,
            'ara_toplam' => $ara_snap,
            'toplam'     => $toplam_snap,
        ));

        // (7) İlk durum geçmişi.
        $this->db->insert('siparis_durum_gecmisi', array(
            'siparis_id' => $id,
            'durum'      => 'onay_bekliyor',
            'notu'       => 'Sipariş oluşturuldu',
            'taraf'      => 'sistem',
        ));

        // (8) Sepeti boşalt.
        $CI->sepet_model->bosalt();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('ok' => FALSE, 'mesaj' => 'Sipariş oluşturulamadı (veritabanı hatası).');
        }
        $this->db->trans_commit();

        // (9) Kupon kullanım sayacı + session temizliği (indirim > 0 ise say).
        if ($indirim_try > 0 && ($kod_kp = $CI->session->userdata('kupon'))) {
            $CI->load->model('kupon_model');
            $CI->kupon_model->kullan_artir($kod_kp);
        }
        $CI->session->unset_userdata('kupon');

        return array('ok' => TRUE, 'siparis_id' => $id, 'siparis_no' => $no, 'toplam' => $toplam_snap);
    }

    /**
     * Session'daki kupon → TRY indirimi. Geçersizse 0 (sipariş yine de oluşur).
     */
    private function _kupon_indirim($ara_toplam_try)
    {
        $CI =& get_instance();
        $kod = (string) $CI->session->userdata('kupon');
        if ($kod === '' || ! $this->db->table_exists('kuponlar')) { return 0.0; }
        $CI->load->model('kupon_model');
        $res = $CI->kupon_model->dogrula($kod, (float) $ara_toplam_try);
        return $res['ok'] ? (float) $res['indirim'] : 0.0;
    }

    /**
     * Mağaza: tek sipariş (kargo firma adı + detaylar). Sahiplik çağıranın sorumluluğunda
     * (Odeme::basarili session son_siparis_id ile, callback'ler bayi/kimlikle gelir).
     */
    public function mg_getir($id)
    {
        $s = $this->db->select('s.*, kf.ad AS kargo_firma')
                      ->from('siparisler s')
                      ->join('kargo_firmalari kf', 'kf.id = s.kargo_firma_id', 'left')
                      ->where('s.id', (int) $id)->limit(1)->get()->row();
        if (! $s) { return NULL; }
        $s->detaylar = $this->db->where('siparis_id', (int) $id)
                                ->order_by('id', 'ASC')->get('siparis_detaylari')->result();
        $this->detay_slug_isaretle($s->detaylar);
        return $s;
    }

    /**
     * Sipariş detay satırlarına vitrin slug'ı işaretler (XLVI): satıştaki ürünün
     * adı hesabım/sipariş sayfasında ürüne linklenir. Bayi/Kullanıcı modellerinin
     * mg_siparis_getir'leri de aynı zenginleştirmeyi çağırır. Join yerine ikinci
     * sorgu (CI3 QB join-escape tuzağına girmeden); satışta olmayan/silinmiş
     * ürün slug'sız kalır → görünüm düz metin basar.
     */
    public function detay_slug_isaretle($detaylar)
    {
        if (! $detaylar) { return; }
        $urun_idler = array();
        foreach ($detaylar as $d) {
            if ((int) $d->urun_id > 0) { $urun_idler[(int) $d->urun_id] = TRUE; }
        }
        $slug_map = array();
        if ($urun_idler) {
            $rows = $this->db->select('id, slug')
                             ->where_in('id', array_keys($urun_idler))
                             ->where('durum', 1)
                             ->where('deleted_at IS NULL', NULL, FALSE)
                             ->get('urunler')->result();
            foreach ($rows as $r) { $slug_map[(int) $r->id] = $r->slug; }
        }
        foreach ($detaylar as $d) {
            $d->urun_slug = $slug_map[(int) $d->urun_id] ?? NULL;
        }
    }

    /** Yönetim: tek sipariş (bayi + kargo join, detaylar + durum geçmişi). */
    public function mg_admin_getir($id)
    {
        $s = $this->db->select('s.*, b.yetkili_ad_soyad, b.email AS bayi_email, b.telefon AS bayi_telefon,
                                kf.ad AS kargo_firma')
                      ->from('siparisler s')
                      ->join('bayiler b', 'b.id = s.bayi_id', 'left')
                      ->join('kargo_firmalari kf', 'kf.id = s.kargo_firma_id', 'left')
                      ->where('s.id', (int) $id)->limit(1)->get()->row();
        if (! $s) { return NULL; }
        $s->detaylar = $this->db->where('siparis_id', (int) $id)
                                ->order_by('id', 'ASC')->get('siparis_detaylari')->result();
        $s->gecmis   = $this->db->where('siparis_id', (int) $id)
                                ->order_by('id', 'DESC')->get('siparis_durum_gecmisi')->result();
        return $s;
    }

    /** Yönetim: sipariş listesi (filtre + sayfalama). */
    public function mg_admin_liste($filtre, $limit, $offset)
    {
        $this->_admin_filtre($filtre);
        return $this->db->select('s.id, s.siparis_no, s.olusturma_zaman, s.odeme_yontemi, s.durum,
                                  s.toplam, s.para_birimi, s.email, s.teslimat_ad,
                                  b.firma_adi, b.yetkili_ad_soyad, b.email AS bayi_email')
                        ->from('siparisler s')
                        ->join('bayiler b', 'b.id = s.bayi_id', 'left')
                        ->order_by('s.id', 'DESC')
                        ->limit((int) $limit, (int) $offset)
                        ->get()->result();
    }

    public function mg_admin_liste_say($filtre)
    {
        $this->_admin_filtre($filtre);
        return $this->db->from('siparisler s')
                        ->join('bayiler b', 'b.id = s.bayi_id', 'left')
                        ->count_all_results();
    }

    private function _admin_filtre($filtre)
    {
        if (! empty($filtre['durum'])) { $this->db->where('s.durum', $filtre['durum']); }
        if (! empty($filtre['q'])) {
            $q = (string) $filtre['q'];
            $this->db->group_start()
                     ->like('s.siparis_no', $q)
                     ->or_like('b.firma_adi', $q)
                     ->or_like('b.email', $q)
                     ->or_like('s.email', $q)
                     ->group_end();
        }
    }

    /** Kargo firma + takip no güncelle (kargolandı durumunda). */
    public function mg_kargo_guncelle($id, $firma, $takip)
    {
        $this->db->where('id', (int) $id)->update('siparisler', array(
            'kargo_firma_id' => $firma ? (int) $firma : NULL,
            'kargo_takip_no' => $takip !== NULL ? trim((string) $takip) : NULL,
        ));
    }

    /**
     * Durum güncelle + geçmişe yaz. İlk kez iptal/iade'ye geçiliyorsa stoğu geri ekle
     * (çift iade önlenir: eski durum zaten iptal/iade ise tekrar ekleme).
     */
    public function mg_durum_guncelle($id, $durum, $notu)
    {
        $id = (int) $id;
        $s = $this->db->select('durum')->where('id', $id)->limit(1)->get('siparisler')->row();
        if (! $s) { return; }

        $iade_durumlari = array('iptal', 'iade_edildi');
        if (in_array($durum, $iade_durumlari, TRUE) && ! in_array($s->durum, $iade_durumlari, TRUE)) {
            $this->_stok_iade_et($id);
        }

        $this->db->where('id', $id)->update('siparisler', array('durum' => $durum));
        $this->db->insert('siparis_durum_gecmisi', array(
            'siparis_id' => $id,
            'durum'      => $durum,
            'notu'       => ($notu !== NULL && $notu !== '') ? mb_substr(trim((string) $notu), 0, 255) : NULL,
            'taraf'      => 'admin',
        ));
    }

    /**
     * Manuel ödeme işaretleme (havale/kapıda ödeme): odeme_durumu='odendi' + geçmişe
     * yaz. PayTR callback'ten BAĞIMSIZ (admin kararı). İdempotent — zaten ödendiyse
     * dokunma (çift işaretlemede yeni geçmiş satırı eklenmez).
     */
    public function mg_odeme_isaretle($id, $notu = '')
    {
        $id = (int) $id;
        $s = $this->db->select('odeme_durumu, durum')->where('id', $id)->limit(1)->get('siparisler')->row();
        if (! $s || $s->odeme_durumu === 'odendi') { return FALSE; }
        $this->db->where('id', $id)->update('siparisler', array('odeme_durumu' => 'odendi'));
        $this->db->insert('siparis_durum_gecmisi', array(
            'siparis_id' => $id,
            'durum'      => $s->durum,
            'taraf'      => 'admin',
            'notu'       => $notu !== '' ? mb_substr(trim((string) $notu), 0, 255) : 'Ödeme alındı (manuel işaretleme)',
        ));
        return TRUE;
    }

    /**
     * Sipariş detaylarındaki varyantların stoğini geri ekle + iade hareketi yaz.
     * varyant_id'si olmayan (silinmiş/üretilmemiş) satırlarda stok geri eklenemez.
     */
    private function _stok_iade_et($siparis_id)
    {
        $siparis_id = (int) $siparis_id;
        $no_row = $this->db->select('siparis_no')->where('id', $siparis_id)->limit(1)->get('siparisler')->row();
        $no = $no_row ? $no_row->siparis_no : ('#' . $siparis_id);

        $detaylar = $this->db->where('siparis_id', $siparis_id)->get('siparis_detaylari')->result();
        foreach ($detaylar as $d) {
            if (empty($d->varyant_id)) { continue; }
            $v = $this->db->select('stok')->where('id', (int) $d->varyant_id)->limit(1)->get('urun_varyantlari')->row();
            $onceki = $v ? (int) $v->stok : 0;
            $this->db->where('id', (int) $d->varyant_id)
                     ->update('urun_varyantlari', array('stok' => $onceki + (int) $d->adet));
            $this->db->insert('stok_hareketleri', array(
                'urun_id'     => $d->urun_id ? (int) $d->urun_id : NULL,
                'varyant_id'  => (int) $d->varyant_id,
                'tip'         => 'iade',
                'adet'        => (int) $d->adet,
                'onceki_stok' => $onceki,
                'aciklama'    => 'Siparis iptal/iade ' . $no,
                'siparis_id'  => $siparis_id,
            ));
        }
    }
}
