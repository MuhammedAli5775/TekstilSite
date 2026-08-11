<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api_anahtar_model — B2B XML/REST feed erişim anahtarları.
 *
 * Güvenlik: ham anahtar ASLA plaintext saklanmaz; yalnızca sha256(hash) yazılır.
 * Doğrulama, gelen ham key'in sha256'ü ile hash kolonunu eşleştirir (sorgulanabilir).
 * Ham anahtar yalnızca Api_anahtar_model::olustur() dönüşünde (admin'e tek sefer) görünür.
 */
class Api_anahtar_model extends CI_Model
{
    /** Ham anahtarı doğrula (hash eşleşmesi + durum=1) → satır veya NULL. */
    public function dogrula($ham_key)
    {
        $ham_key = trim((string) $ham_key);
        if ($ham_key === '') { return NULL; }
        return $this->db->where('anahtar_hash', hash('sha256', $ham_key))
                        ->where('durum', 1)
                        ->limit(1)->get('api_anahtarlari')->row();
    }

    /** Başarılı bir kullanımı işaretle (son_kullanim + sayaç, atomik). */
    public function kullanildi($id)
    {
        $this->db->set('son_kullanim', date('Y-m-d H:i:s'))
                 ->set('kullanim_sayisi', 'kullanim_sayisi + 1', FALSE)
                 ->where('id', (int) $id)->update('api_anahtarlari');
    }

    /** Yönetim: liste (bayi firma adıyla). */
    public function liste()
    {
        return $this->db->select('a.*, b.firma_adi')
                        ->from('api_anahtarlari a')
                        ->join('bayiler b', 'b.id = a.bayi_id', 'left')
                        ->order_by('a.id', 'DESC')->get()->result();
    }

    /** Yeni anahtar üret + kaydet. Ham anahtarı döndür (tek sefer gösterilir). */
    public function olustur($ad, $bayi_id)
    {
        $ham  = bin2hex(random_bytes(32));             // 64 hex karakterlik anahtar
        $onek = substr($ham, 0, 8);
        $this->db->insert('api_anahtarlari', array(
            'bayi_id'      => $bayi_id ? (int) $bayi_id : NULL,
            'ad'           => mb_substr(trim((string) $ad), 0, 120) ?: 'Feed anahtarı',
            'onek'         => $onek,
            'anahtar_hash' => hash('sha256', $ham),
            'durum'        => 1,
        ));
        return $this->db->insert_id() ? $ham : NULL;
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('api_anahtarlari')->row();
    }

    public function durum($id, $durum)
    {
        $this->db->where('id', (int) $id)->update('api_anahtarlari', array('durum' => $durum ? 1 : 0));
    }

    public function sil($id)
    {
        $this->db->where('id', (int) $id)->delete('api_anahtarlari');
    }
}
