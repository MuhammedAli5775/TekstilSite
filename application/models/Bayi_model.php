<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bayi_model — B2B bayi (toptancı) hesabı.
 * Kayıt (bcrypt) · giriş kontrolü · bilgiler · sipariş listesi/detay (sahiplik izole).
 */
class Bayi_model extends CI_Model
{
    public function by_email($email)
    {
        return $this->db->where('LOWER(email)', strtolower((string) $email))->limit(1)->get('bayiler')->row();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('bayiler')->row();
    }

    /** Grup indirimi dahil bayi satırı. */
    public function get_indirimli($id)
    {
        return $this->db->select('b.*, g.indirim_yuzde AS grup_indirim')
                        ->from('bayiler b')
                        ->join('bayi_gruplari g', 'g.id = b.grup_id', 'left')
                        ->where('b.id', (int) $id)->limit(1)->get()->row();
    }

    /** Kayıt oluşturma. durum=0: onay admin panelinden (Bayiler > durum) verilir. */
    public function kayit($d)
    {
        if (! $this->db->table_exists('bayiler')) { return array('ok' => FALSE, 'mesaj' => (function_exists('t') ? t('flash_db_hazir_degil', 'Veritabanı hazır değil.') : 'Veritabanı hazır değil.')); }
        if ($this->by_email($d['email'])) { return array('ok' => FALSE, 'mesaj' => (function_exists('t') ? t('flash_eposta_kayitli', 'Bu e-posta adresi zaten kayıtlı.') : 'Bu e-posta adresi zaten kayıtlı.')); }

        $ins = array(
            'grup_id'          => 1,
            'yetkili_ad_soyad' => $d['yetkili_ad_soyad'],
            'firma_adi'        => $d['firma_adi'],
            'email'            => strtolower($d['email']),
            'telefon'          => $d['telefon'] ?? NULL,
            'vergi_no'         => $d['vergi_no'] ?? NULL,
            'vergi_dairesi'    => $d['vergi_dairesi'] ?? NULL,
            'sifre'            => password_hash($d['sifre'], PASSWORD_BCRYPT),
            'para_birimi'      => 'TRY',
            'durum'            => 0, // onay bekliyor — admin onaylayana kadar giriş kapalı
        );
        $this->db->insert('bayiler', $ins);
        return array('ok' => TRUE, 'id' => $this->db->insert_id());
    }

    /** E-posta + şifre doğrula (durum controller'da kontrol edilir). */
    public function giris_kontrol($email, $sifre)
    {
        $b = $this->by_email($email);
        if (! $b) { return NULL; }
        if (! password_verify($sifre, $b->sifre)) { return NULL; }
        return $b;
    }

    public function son_giris_isaretle($id)
    {
        $this->db->where('id', (int) $id)->update('bayiler', array('son_giris' => date('Y-m-d H:i:s')));
    }

    public function bilgiler_guncelle($id, $d)
    {
        $izinli = array('yetkili_ad_soyad', 'telefon', 'vergi_no', 'vergi_dairesi', 'firma_adi');
        $veri = array();
        foreach ($izinli as $k) { if (array_key_exists($k, $d)) { $veri[$k] = $d[$k]; } }
        if ($veri) { $this->db->where('id', (int) $id)->update('bayiler', $veri); }
    }

    public function sifre_guncelle($id, $yeni)
    {
        $this->db->where('id', (int) $id)->update('bayiler', array('sifre' => password_hash($yeni, PASSWORD_BCRYPT)));
    }

    /** Bayi'nin siparişleri (en yeni önce). */
    public function mg_siparisler($bayi_id)
    {
        return $this->db->where('bayi_id', (int) $bayi_id)->order_by('id', 'DESC')->get('siparisler')->result();
    }

    /** Sipariş + kalemler + durum geçmişi (sahiplik kontrolü ile). */
    public function mg_siparis_getir($bayi_id, $siparis_id)
    {
        $s = $this->db->select('s.*, k.ad AS kargo_firma')
                      ->from('siparisler s')
                      ->join('kargo_firmalari k', 'k.id = s.kargo_firma_id', 'left')
                      ->where('s.id', (int) $siparis_id)
                      ->where('s.bayi_id', (int) $bayi_id)
                      ->limit(1)->get()->row();
        if (! $s) { return NULL; }
        $s->detaylar = $this->db->where('siparis_id', (int) $siparis_id)->order_by('id', 'ASC')->get('siparis_detaylari')->result();
        $s->gecmis   = $this->db->where('siparis_id', (int) $siparis_id)->order_by('id', 'ASC')->get('siparis_durum_gecmisi')->result();
        // XLVI: ürün adları ürüne linklensin (paylaşımlı zenginleştirici).
        get_instance()->load->model('siparis_model');
        get_instance()->siparis_model->detay_slug_isaretle($s->detaylar);
        return $s;
    }

    // ------------------------------------------------------------------
    // YÖNETİM (admin) metotları
    // ------------------------------------------------------------------
    public function mg_admin_liste($f, $limit, $offset)
    {
        $this->db->select('b.*, g.ad AS grup_ad, g.indirim_yuzde')->from('bayiler b')->join('bayi_gruplari g', 'g.id = b.grup_id', 'left');
        $this->_admin_filtre($f);
        return $this->db->order_by('b.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();
    }

    public function mg_admin_liste_say($f)
    {
        $this->db->from('bayiler b');
        $this->_admin_filtre($f);
        return $this->db->count_all_results();
    }

    private function _admin_filtre($f)
    {
        if (! empty($f['q'])) {
            $this->db->group_start()->like('b.firma_adi', $f['q'])->or_like('b.email', $f['q'])->or_like('b.yetkili_ad_soyad', $f['q'])->group_end();
        }
        if (isset($f['durum']) && $f['durum'] !== '') { $this->db->where('b.durum', (int) $f['durum']); }
    }

    public function mg_admin_getir($id)
    {
        return $this->db->select('b.*, g.ad AS grup_ad, g.indirim_yuzde')
                        ->from('bayiler b')->join('bayi_gruplari g', 'g.id = b.grup_id', 'left')
                        ->where('b.id', (int) $id)->limit(1)->get()->row();
    }

    public function mg_durum_guncelle($id, $durum)
    {
        $this->db->where('id', (int) $id)->update('bayiler', array('durum' => (int) $durum));
    }

    public function mg_grup_guncelle($id, $grup_id)
    {
        $this->db->where('id', (int) $id)->update('bayiler', array('grup_id' => (int) $grup_id));
    }

    public function gruplar()
    {
        return $this->db->order_by('id', 'ASC')->get('bayi_gruplari')->result();
    }

    public function bayi_siparis_ozet($bayi_id)
    {
        return $this->db->select('COUNT(*) AS adet, COALESCE(SUM(toplam * kur),0) AS ciro', FALSE)
                        ->where('bayi_id', (int) $bayi_id)->get('siparisler')->row();
    }
}
