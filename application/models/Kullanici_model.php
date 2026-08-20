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

    public function by_kullanici_adi($kullanici_adi)
    {
        return $this->db->where('kullanici_adi', trim((string) $kullanici_adi))
                        ->limit(1)->get('kullanicilar')->row();
    }

    /** Kullanıcı adı müsait mi? (düzenlemede sahibin kendi adı hariç tutulur) */
    public function kullanici_adi_musait($kullanici_adi, $haric_id = NULL)
    {
        $this->db->where('kullanici_adi', trim((string) $kullanici_adi));
        if ($haric_id) { $this->db->where('id !=', (int) $haric_id); }
        return $this->db->limit(1)->get('kullanicilar')->num_rows() === 0;
    }

    /** Kayıt oluşturma. durum=1: kullanıcı hesabı onay kuyruğu olmadan aktif. */
    public function kayit($d)
    {
        if (! $this->db->table_exists('kullanicilar')) { return array('ok' => FALSE, 'mesaj' => (function_exists('t') ? t('flash_db_hazir_degil', 'Veritabanı hazır değil.') : 'Veritabanı hazır değil.')); }
        if ($this->by_email($d['email'])) { return array('ok' => FALSE, 'mesaj' => (function_exists('t') ? t('flash_eposta_kayitli', 'Bu e-posta adresi zaten kayıtlı.') : 'Bu e-posta adresi zaten kayıtlı.')); }
        if (! empty($d['kullanici_adi']) && $this->by_kullanici_adi($d['kullanici_adi'])) { return array('ok' => FALSE, 'mesaj' => (function_exists('t') ? t('flash_kuladi_alinmis', 'Bu kullanıcı adı alınmış. Farklı bir ad deneyin.') : 'Bu kullanıcı adı alınmış. Farklı bir ad deneyin.')); }

        $ins = array(
            'ad_soyad'     => $d['ad_soyad'],
            'kullanici_adi' => $d['kullanici_adi'] ?? NULL,
            'email'        => strtolower($d['email']),
            'telefon'      => $d['telefon'] ?? NULL,
            'sifre'        => password_hash($d['sifre'], PASSWORD_BCRYPT),
            'durum'        => 1,
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

    public function bilgiler_guncelle($id, $d)
    {
        // E-posta bilinçli olarak yok: sipariş eşleşmesi e-posta üzerinden —
        // değişirse geçmiş siparişler hesaptan kopar (view'da disabled).
        $izinli = array('ad_soyad', 'kullanici_adi', 'telefon');
        $veri = array();
        foreach ($izinli as $k) { if (array_key_exists($k, $d)) { $veri[$k] = $d[$k]; } }
        if ($veri) { $this->db->where('id', (int) $id)->update('kullanicilar', $veri); }
    }

    public function sifre_guncelle($id, $yeni)
    {
        $this->db->where('id', (int) $id)->update('kullanicilar', array('sifre' => password_hash($yeni, PASSWORD_BCRYPT)));
    }

    /* ---------------- Adres defteri (sahiplik izole) ---------------- */
    public function adresler($kullanici_id)
    {
        return $this->db->where('kullanici_id', (int) $kullanici_id)
                        ->order_by('varsayilan', 'DESC')->order_by('id', 'DESC')
                        ->get('kullanicilar_adresleri')->result();
    }

    public function adres_getir($kullanici_id, $id)
    {
        return $this->db->where('kullanici_id', (int) $kullanici_id)
                        ->where('id', (int) $id)->limit(1)->get('kullanicilar_adresleri')->row();
    }

    /** Ekle/güncelle; varsayılan işaretlenirse diğerleri temizlenir. */
    public function adres_kaydet($kullanici_id, $d, $id = NULL)
    {
        $veri = array(
            'ad_soyad'   => $d['ad_soyad'],
            'adres'      => $d['adres'],
            'il'         => $d['il'],
            'ilce'       => $d['ilce'],
            'telefon'    => $d['telefon'],
            'tip'        => in_array($d['tip'] ?? '', array('teslimat','fatura','her_ikisi'), TRUE) ? $d['tip'] : 'her_ikisi',
            'varsayilan' => empty($d['varsayilan']) ? 0 : 1,
        );
        if ($id) {
            $this->db->where('kullanici_id', (int) $kullanici_id)->where('id', (int) $id)
                     ->update('kullanicilar_adresleri', $veri);
        } else {
            $veri['kullanici_id'] = (int) $kullanici_id;
            $this->db->insert('kullanicilar_adresleri', $veri);
            $id = $this->db->insert_id();
        }
        if ($veri['varsayilan']) {
            $this->db->set('varsayilan', 0)
                     ->where('kullanici_id', (int) $kullanici_id)->where('id !=', (int) $id)
                     ->update('kullanicilar_adresleri');
        }
        return (int) $id;
    }

    public function adres_sil($kullanici_id, $id)
    {
        return $this->db->where('kullanici_id', (int) $kullanici_id)
                        ->where('id', (int) $id)
                        ->delete('kullanicilar_adresleri');
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
        // XLVI: ürün adları ürüne linklensin (paylaşımlı zenginleştirici).
        get_instance()->load->model('siparis_model');
        get_instance()->siparis_model->detay_slug_isaretle($s->detaylar);
        return $s;
    }
}
