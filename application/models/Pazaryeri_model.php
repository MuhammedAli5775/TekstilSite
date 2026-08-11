<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pazaryeri_model — pazaryeri hesapları + ürün eşleştirme + senkron log.
 * Hesap kimlikleri (api_key/api_secret) CI Encryption ile şifreli saklanır
 * (plaintext YASAK — workflow.md §2). Ham değer yalnızca API çağrısında çözülür.
 */
class Pazaryeri_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('encryption');
    }

    /* ---------------- Hesaplar ---------------- */
    public function hesap_liste()
    {
        return $this->db->order_by('id', 'DESC')->get('pazaryeri_hesaplari')->result();
    }

    public function hesap_getir($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('pazaryeri_hesaplari')->row();
    }

    /** Kimlikleri çözülmüş hesap (yalnızca API çağrısı için). */
    public function hesap_getir_acik($id)
    {
        $h = $this->hesap_getir($id);
        if (! $h) { return NULL; }
        $h->api_key    = $this->_coz($h->api_key);
        $h->api_secret = $this->_coz($h->api_secret);
        return $h;
    }

    public function hesap_ekle($d)
    {
        $this->db->insert('pazaryeri_hesaplari', array(
            'platform'    => $d['platform'],
            'ad'          => $d['ad'],
            'supplier_id' => $d['supplier_id'] ?? NULL,
            'api_key'     => $this->_sifrele($d['api_key'] ?? ''),
            'api_secret'  => $this->_sifrele($d['api_secret'] ?? ''),
            'durum'       => 1,
        ));
        return $this->db->insert_id();
    }

    public function hesap_guncelle($id, $d)
    {
        $upd = array(
            'platform'    => $d['platform'],
            'ad'          => $d['ad'],
            'supplier_id' => $d['supplier_id'] ?? NULL,
        );
        // Boş gelirse (form alanı boş) mevcutu koru — üzerine yazma.
        if (! empty($d['api_key']))    { $upd['api_key']    = $this->_sifrele($d['api_key']); }
        if (! empty($d['api_secret'])) { $upd['api_secret'] = $this->_sifrele($d['api_secret']); }
        $this->db->where('id', (int) $id)->update('pazaryeri_hesaplari', $upd);
    }

    public function hesap_durum($id, $durum)
    {
        $this->db->where('id', (int) $id)->update('pazaryeri_hesaplari', array('durum' => $durum ? 1 : 0));
    }

    public function hesap_sil($id)
    {
        $this->db->where('id', (int) $id)->delete('pazaryeri_hesaplari'); // eşleştirme/log CASCADE
    }

    public function son_sin_isaretle($id)
    {
        $this->db->where('id', (int) $id)->update('pazaryeri_hesaplari', array('son_sin' => date('Y-m-d H:i:s')));
    }

    /* ---------------- Ürün eşleştirme ---------------- */
    public function eslesme_liste($hesap_id)
    {
        return $this->db->select('e.*, u.ad AS urun_adi, u.stok_kodu, u.fiyat')
                        ->from('pazaryeri_urun_eslestirme e')
                        ->join('urunler u', 'u.id = e.urun_id', 'left')
                        ->where('e.hesap_id', (int) $hesap_id)
                        ->order_by('e.id', 'DESC')->get()->result();
    }

    public function eslesme_ekle($hesap_id, $urun_id, $paz_id)
    {
        $mevcut = $this->db->where('hesap_id', (int) $hesap_id)->where('urun_id', (int) $urun_id)
                           ->limit(1)->get('pazaryeri_urun_eslestirme')->row();
        if ($mevcut) {
            $this->db->where('id', $mevcut->id)
                     ->update('pazaryeri_urun_eslestirme', array('pazaryeri_urun_id' => $paz_id ?: $mevcut->pazaryeri_urun_id, 'durum' => 1));
            return $mevcut->id;
        }
        $this->db->insert('pazaryeri_urun_eslestirme', array(
            'hesap_id'          => (int) $hesap_id,
            'urun_id'           => (int) $urun_id,
            'pazaryeri_urun_id' => $paz_id ?: NULL,
            'durum'             => 1,
        ));
        return $this->db->insert_id();
    }

    public function eslesme_sil($id)
    {
        $this->db->where('id', (int) $id)->delete('pazaryeri_urun_eslestirme');
    }

    /* ---------------- Log ---------------- */
    public function log_ekle($hesap_id, $islem, $durum, $ozet, $hata = NULL)
    {
        $this->db->insert('pazaryeri_loglari', array(
            'hesap_id'    => (int) $hesap_id,
            'islem'       => $islem,
            'durum'       => $durum,
            'ozet'        => mb_substr((string) $ozet, 0, 255),
            'hata_mesaji' => $hata ? mb_substr((string) $hata, 0, 2000) : NULL,
        ));
        return $this->db->insert_id();
    }

    public function log_liste($hesap_id, $limit = 30)
    {
        return $this->db->where('hesap_id', (int) $hesap_id)
                        ->order_by('id', 'DESC')->limit((int) $limit)
                        ->get('pazaryeri_loglari')->result();
    }

    /* ---------------- Şifreleme ---------------- */
    private function _sifrele($v)
    {
        $v = trim((string) $v);
        if ($v === '') { return NULL; }
        $out = $this->encryption->encrypt($v);
        return ($out === FALSE) ? NULL : $out;
    }

    private function _coz($v)
    {
        if (! $v) { return ''; }
        $out = $this->encryption->decrypt($v);
        return ($out === FALSE) ? '' : $out;
    }
}
