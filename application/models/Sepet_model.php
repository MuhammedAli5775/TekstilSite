<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sepet_model — B2B sepet.
 * Sahiplik: Faz 2'de bayi auth yok → oturum_id (session_id) ile izole.
 * Faz 3'te bayi_id devreye girer (_sahip güncellenir).
 */
class Sepet_model extends CI_Model
{
    /** Sahiplik where koşulu (bayi_id öncelikli, yoksa oturum_id). */
    private function _sahip_where()
    {
        $bid = $this->session->userdata('bayi_id');
        if ($bid) { return array('bayi_id' => (int) $bid); }
        $sid = $this->session->session_id ?: session_id();
        return array('bayi_id' => NULL, 'oturum_id' => $sid);
    }

    /** Giriş anında: misafir (oturum) sepetini bayi hesabına taşır.
     *  $eski_sid: giriş oturum ID'sini döndürdüyse (fixation koruması) döndürülmeden
     *  ÖNCEKİ değer — satırlar o anahtarda durur. */
    public function transfer_to_bayi($bayi_id, $eski_sid = NULL)
    {
        $sid = $eski_sid ?: ($this->session->session_id ?: session_id());
        $this->db->where('oturum_id', $sid)
                 ->where('bayi_id IS NULL', NULL, FALSE)
                 ->update('sepet', array('bayi_id' => (int) $bayi_id, 'oturum_id' => NULL));
    }

    /** Oturum ID'si döndüğünde (kullanıcı girişi/çıkışı) misafir sepetini yeni anahtara taşır. */
    public function oturum_tasi($eski_sid, $yeni_sid)
    {
        if (! $this->db->table_exists('sepet')) { return; }
        $this->db->where('oturum_id', $eski_sid)
                 ->where('bayi_id IS NULL', NULL, FALSE)
                 ->update('sepet', array('oturum_id' => $yeni_sid));
    }

    /** Sepetteki toplam adet (header rozeti). */
    public function sayi()
    {
        if (! $this->db->table_exists('sepet')) { return 0; }
        $this->db->select('COALESCE(SUM(adet),0) AS c', FALSE)->where($this->_sahip_where());
        $row = $this->db->get('sepet')->row();
        return $row ? (int) $row->c : 0;
    }

    /** Sepete ekle (MOQ + stok + benzersiz satır kontrolü). */
    public function ekle($urun_id, $varyant_id, $adet)
    {
        if (! $this->db->table_exists('sepet')) { return array('ok' => FALSE, 'mesaj' => 'Veritabanı hazır değil.'); }

        $u = $this->db->select('id, ad, moq, birim_adim, durum')
                      ->where('id', (int) $urun_id)->where('durum', 1)->limit(1)->get('urunler')->row();
        if (! $u) { return array('ok' => FALSE, 'mesaj' => 'Ürün bulunamadı.'); }

        $moq  = (int) $u->moq;
        $adim = max(1, (int) $u->birim_adim);
        $adet = (int) $adet;
        if ($adet < $moq) { $adet = $moq; }
        // XLVI: paket kuralı sunucuda da zorlanır — ızgaraya FLOOR ile oturur
        // (asla yukarı zıplama yok: 9 yazımı adım-6 üründe 12 değil 6 olur).
        $k = intdiv($adet - $moq, $adim);
        $adet = $moq + $k * $adim;

        $varyant = NULL;
        if ($varyant_id) {
            $varyant = $this->db->where('id', (int) $varyant_id)->where('urun_id', (int) $urun_id)->where('durum', 1)->limit(1)->get('urun_varyantlari')->row();
            if (! $varyant) { return array('ok' => FALSE, 'mesaj' => 'Varyant bulunamadı.'); }
        }

        $where = $this->_sahip_where();
        $where['urun_id']    = (int) $urun_id;
        $where['varyant_id'] = $varyant_id ? (int) $varyant_id : NULL;
        $satir = $this->db->where($where)->limit(1)->get('sepet')->row();

        $yeni_adet = $adet + ($satir ? (int) $satir->adet : 0);
        if ($varyant && $yeni_adet > (int) $varyant->stok) {
            return array('ok' => FALSE, 'mesaj' => 'Yetersiz stok. Mevcut: ' . (int) $varyant->stok . ' adet.');
        }

        if ($satir) {
            $this->db->where('id', $satir->id)->update('sepet', array('adet' => $yeni_adet));
        } else {
            $ins = $where;
            $ins['adet'] = $adet;
            $this->db->insert('sepet', $ins);
        }

        return array('ok' => TRUE, 'adet' => $this->sayi(), 'mesaj' => 'Ürün sepete eklendi.');
    }

    /** Sepet satırları (ürün + varyant join, basamak fiyatı hesaplı). */
    public function liste()
    {
        $where = $this->_sahip_where();
        $rows = $this->db->select('s.id AS sepet_id, s.urun_id, s.varyant_id, s.adet,
                                   u.ad, u.slug, u.stok_kodu, u.fiyat, u.eski_fiyat, u.moq, u.birim_adim, u.ana_gorsel, u.kdv,
                                   v.renk, v.beden, v.stok AS varyant_stok')
                         ->from('sepet s')
                         ->join('urunler u', 'u.id = s.urun_id', 'inner')
                         ->join('urun_varyantlari v', 'v.id = s.varyant_id', 'left')
                         ->where($where)
                         ->order_by('s.id', 'ASC')
                         ->get()->result();

        $tiers = $this->_tum_basamaklar();
        $indirim = function_exists('bayi_indirim') ? bayi_indirim() : 0.0;
        $ara_toplam = 0.0;
        foreach ($rows as $r) {
            $r->birim = $this->_birim_fiyat($r->fiyat, $r->adet, $r->urun_id, $tiers, $indirim);
            $r->ara   = $r->birim * $r->adet;
            $ara_toplam += $r->ara;
        }

        // Gosterim para birimi (giris yapmis bayi; misafir = TRY). mg_olustur ile AYNI
        // yuvarlama: once birim yuvarlanir, sonra adetle carpilir. Boylece sepet/odeme
        // gosterimi kaydedilen siparis tutariyla birebir tutarli olur. (para_goster'in
        // toplu-cevirisi 1-2 kurus saptigi icin ara_icin onu kullanmiyoruz.)
        $pb  = function_exists('aktif_para_birimi') ? aktif_para_birimi() : 'TRY';
        $kur = function_exists('kur_getir') ? (float) kur_getir($pb) : 1.0;
        if ($kur <= 0) { $kur = 1.0; }
        $pb_ara = 0.0;
        foreach ($rows as $r) {
            $r->pb  = $pb;
            $r->kur = $kur;
            $birim_pb = ($kur == 1.0) ? round((float) $r->birim, 2) : round((float) $r->birim / $kur, 2);
            $r->birim_pb = $birim_pb;
            $r->ara_pb   = round($birim_pb * (int) $r->adet, 2);
            $pb_ara += $r->ara_pb;
        }

        return array(
            'satirlar'      => $rows,
            'ara_toplam'    => $ara_toplam,    // TRY (kargo esigi / kupon mantigi icin)
            'pb'            => $pb,
            'kur'           => $kur,
            'pb_ara_toplam' => $pb_ara,         // bayi para biriminde (gosterim; siparisle ayni)
        );
    }

    public function guncelle($sepet_id, $adet)
    {
        $where = $this->_sahip_where();
        $where['id'] = (int) $sepet_id;
        $satir = $this->db->where($where)->limit(1)->get('sepet')->row();
        if (! $satir) { return FALSE; }

        $u = $this->db->select('moq, birim_adim')->where('id', $satir->urun_id)->limit(1)->get('urunler')->row();
        $moq  = $u ? (int) $u->moq : 1;
        $adim = max(1, $u ? (int) $u->birim_adim : 1);

        // XLV: sessiz yuvarlama sürprizi kalktı; XLVI: paket kuralı FLOOR ile
        // geri geldi — ızgara (moq + k*adim) dışı miktar yazılamaz ama değer
        // ASLA yukarı zıplamaz (yazılanın izin verilen en büyüğüne iner).
        // Stok tavanı en son uygulanır: kalan son parti ızgara dışı olabilir.
        $adet = max($moq, (int) $adet);
        $k = intdiv($adet - $moq, $adim);
        $adet = $moq + $k * $adim;

        if ($satir->varyant_id) {
            $v = $this->db->select('stok')->where('id', $satir->varyant_id)->limit(1)->get('urun_varyantlari')->row();
            if ($v && $adet > (int) $v->stok) { $adet = (int) $v->stok; }
        }
        if ($adet < $moq) { $adet = $moq; }

        $this->db->where('id', $satir->id)->update('sepet', array('adet' => $adet));
        return $adet;
    }

    public function sil($sepet_id)
    {
        $where = $this->_sahip_where();
        $where['id'] = (int) $sepet_id;
        $this->db->where($where)->delete('sepet');
        return $this->db->affected_rows() > 0;
    }

    public function bosalt()
    {
        $this->db->where($this->_sahip_where())->delete('sepet');
    }

    // ------------------------------------------------------------------
    private function _tum_basamaklar()
    {
        if (! $this->db->table_exists('fiyat_basamaklari')) { return array(); }
        return $this->db->order_by('min_adet', 'ASC')->get('fiyat_basamaklari')->result();
    }

    private function _birim_fiyat($fiyat, $adet, $urun_id, $tiers, $indirim = 0.0)
    {
        $yuzde = 0.0;
        foreach ($tiers as $t) {
            $gecerli = ($t->urun_id === NULL || (int) $t->urun_id === (int) $urun_id);
            if ($gecerli && $adet >= (int) $t->min_adet && (float) $t->indirim_yuzde > $yuzde) {
                $yuzde = (float) $t->indirim_yuzde;
            }
        }
        // adet basamağı indirimi + bayi grup indirimi (basit toplam)
        $toplam_yuzde = $yuzde + (float) $indirim;
        return max(0.0, (float) $fiyat * (1 - $toplam_yuzde / 100));
    }
}
