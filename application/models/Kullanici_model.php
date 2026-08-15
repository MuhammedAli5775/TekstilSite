<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kullanici_model — B2C kullanıcı hesapları.
 * Bayi'den ayrı tablo: firma/vergi yok, kayıt anında aktif (durum=1).
 * Siparişler e-posta ile eşleşir (siparisler.email) — misafir siparişleri de görünür.
 */
class Kullanici_model extends CI_Model
{
    public function get($id)
    {
        if (! $this->db->table_exists('kullanicilar')) { return NULL; }
        return $this->db->where('id', (int) $id)->limit(1)->get('kullanicilar')->row();
    }

    public function by_email($email)
    {
        return $this->db->where('email', strtolower(trim((string) $email)))
                        ->limit(1)->get('kullanicilar')->row();
    }

    /** Kayıt oluşturma. durum=1: kullanıcı hesabı onay kuyruğu olmadan aktif. */
    public function kayit($d)
    {
        if (! $this->db->table_exists('kullanicilar')) { return array('ok' => FALSE, 'mesaj' => 'Veritabanı hazır değil.'); }
        if ($this->by_email($d['email'])) { return array('ok' => FALSE, 'mesaj' => 'Bu e-posta adresi zaten kayıtlı.'); }

        $ins = array(
            'ad_soyad' => $d['ad_soyad'],
            'email'    => strtolower($d['email']),
            'telefon'  => $d['telefon'] ?? NULL,
            'sifre'    => password_hash($d['sifre'], PASSWORD_BCRYPT),
            'durum'    => 1,
        );
        $this->db->insert('kullanicilar', $ins);
        return array('ok' => TRUE, 'id' => $this->db->insert_id());
    }

    /** E-posta + şifre doğrula (durum controller'da kontrol edilir). */
    public function giris_kontrol($email, $sifre)
    {
        $k = $this->by_email($email);
        if (! $k) { return NULL; }
        if (! password_verify($sifre, $k->sifre)) { return NULL; }
        return $k;
    }

    public function son_giris_isaretle($id)
    {
        $this->db->where('id', (int) $id)->update('kullanicilar', array('son_giris' => date('Y-m-d H:i:s')));
    }

    /** Kullanıcının siparişleri — e-posta eşleşmesiyle (misafir siparişleri dahil). */
    public function mg_siparisler($email)
    {
        return $this->db->where('email', strtolower(trim((string) $email)))
                        ->order_by('id', 'DESC')->get('siparisler')->result();
    }

    /** Sipariş + sahiplik kontrolü (e-posta bazlı). */
    public function mg_siparis_getir($email, $siparis_id)
    {
        $s = $this->db->select('s.*, k.ad AS kargo_firma')
                      ->from('siparisler s')
                      ->join('kargo_firmalari k', 'k.id = s.kargo_firma_id', 'left')
                      ->where('s.id', (int) $siparis_id)
                      ->where('s.email', strtolower(trim((string) $email)))
                      ->limit(1)->get()->row();
        if (! $s) { return NULL; }
        $s->detaylar     = $this->db->where('siparis_id', (int) $siparis_id)->get('siparis_detaylari')->result();
        $s->gecmis       = $this->db->where('siparis_id', (int) $siparis_id)->order_by('id', 'ASC')->get('siparis_durum_gecmisi')->result();
        return $s;
    }
}
