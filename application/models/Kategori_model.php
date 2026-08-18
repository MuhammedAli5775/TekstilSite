<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kategori_model — kategori yönetimi (Query Builder).
 * Mağaza: üst menü, slug ile getir, alt katmanlar, üst yol (breadcrumb).
 * Yönetim: ağaç, düz liste, CRUD, silme kontrolü.
 */
class Kategori_model extends CI_Model
{
    /** Mağaza üst menüsü: üst kategoriler + alt kategorileri. */
    public function mg_menu()
    {
        if (! $this->db->table_exists('kategoriler')) {
            return array();
        }
        $this->load->helper('dil');   // XXXI: kategori adları aktif dilde (çeviri yoksa TR)
        $ust = $this->db->where('ust_id', NULL)
                        ->where('durum', 1)
                        ->order_by('sira', 'ASC')
                        ->get('kategoriler')
                        ->result();

        $menu = array();
        foreach ($ust as $k) {
            $altlar = array();
            $subs = $this->db->where('ust_id', $k->id)
                             ->where('durum', 1)
                             ->order_by('sira', 'ASC')
                             ->limit(8)
                             ->get('kategoriler')
                             ->result();
            foreach ($subs as $s) {
                $altlar[] = array('baslik' => kategori_ad($s), 'url' => site_url('katalog/' . $s->slug));
            }
            $menu[] = array('baslik' => kategori_ad($k), 'url' => site_url('katalog/' . $k->slug), 'altlar' => $altlar);
        }
        return $menu;
    }

    /** Slug'a göre tek kategori (mağaza, yalnız aktif). */
    public function mg_by_slug($slug)
    {
        if (! $this->db->table_exists('kategoriler')) { return NULL; }
        return $this->db->where('slug', $slug)
                        ->where('durum', 1)
                        ->limit(1)
                        ->get('kategoriler')->row();
    }

    /** Bir üst kategorinin aktif alt kategorilerinin id listesi (katalog filtresi için). */
    public function mg_alt_idler($ust_id)
    {
        if (! $this->db->table_exists('kategoriler')) { return array(); }
        $rows = $this->db->select('id')
                         ->where('ust_id', (int) $ust_id)
                         ->where('durum', 1)
                         ->get('kategoriler')->result();
        $out = array();
        foreach ($rows as $r) { $out[] = (int) $r->id; }
        return $out;
    }

    /** Üst yol (breadcrumb): kök → kategori, [ {ad, slug}, ... ] (object). */
    public function mg_ust_yol($kat)
    {
        $this->load->helper('dil');   // XXXI: kirinti adları aktif dilde
        $yol = array();
        $cur = $kat;
        $guvenlik = 0;
        while ($cur && $guvenlik++ < 10) {
            $o = new stdClass();
            $o->ad   = isset($cur->ad) ? kategori_ad($cur) : '';
            $o->slug = isset($cur->slug) ? $cur->slug : '';
            array_unshift($yol, $o);
            if (! empty($cur->ust_id)) {
                $cur = $this->db->where('id', (int) $cur->ust_id)->limit(1)->get('kategoriler')->row();
            } else {
                $cur = NULL;
            }
        }
        return $yol;
    }

    /** Bir üst kategorinin aktif alt kategorileri (katalog yan paneli). */
    public function mg_alt_kategoriler($ust_id)
    {
        if (! $this->db->table_exists('kategoriler')) { return array(); }
        return $this->db->select('id, ad, slug')
                        ->where('ust_id', (int) $ust_id)
                        ->where('durum', 1)
                        ->order_by('sira', 'ASC')
                        ->get('kategoriler')->result();
    }

    // ------------------------------------------------------------------
    // YÖNETİM (admin)
    // ------------------------------------------------------------------
    /** Kategori ağacı: üst kategoriler (->altlar ile). */
    public function mg_admin_agac()
    {
        $ust = $this->db->where('ust_id', NULL)
                        ->order_by('sira', 'ASC')
                        ->get('kategoriler')->result();
        foreach ($ust as $k) {
            $k->altlar = $this->db->where('ust_id', (int) $k->id)
                                  ->order_by('sira', 'ASC')
                                  ->get('kategoriler')->result();
        }
        return $ust;
    }

    /** Düz liste (ürün formu kategori select'i için; üst_id indent göstergesi). */
    public function mg_admin_duz()
    {
        if (! $this->db->table_exists('kategoriler')) { return array(); }
        return $this->db->select('id, ad, slug, ust_id, durum')
                        ->order_by('ust_id IS NULL', 'DESC', FALSE)
                        ->order_by('sira', 'ASC')
                        ->get('kategoriler')->result();
    }

    /** Tek kategori (admin düzenleme). */
    public function mg_getir($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('kategoriler')->row();
    }

    public function mg_kaydet($d)
    {
        $slug = trim((string) ($d['slug'] ?? ''));
        if ($slug === '') { $slug = slug_tr($d['ad'] ?? ''); }
        $d['slug'] = $this->_slug_benzersiz($slug, 0);
        $this->db->insert('kategoriler', $d);
        return $this->db->insert_id();
    }

    public function mg_guncelle($id, $d)
    {
        if (array_key_exists('slug', $d)) {
            $slug = trim((string) $d['slug']);
            if ($slug === '') { $slug = slug_tr($d['ad'] ?? ''); }
            $d['slug'] = $this->_slug_benzersiz($slug, (int) $id);
        }
        $this->db->where('id', (int) $id)->update('kategoriler', $d);
    }

    /** Silinebilir mi? Alt kategorisi veya (silinmemiş) ürünü varsa FALSE. */
    public function mg_sil_kontrol($id)
    {
        $id = (int) $id;
        if ($this->db->where('ust_id', $id)->count_all_results('kategoriler') > 0) { return FALSE; }
        $this->db->where('kategori_id', $id);
        if ($this->db->field_exists('deleted_at', 'urunler')) {
            $this->db->where('deleted_at IS NULL', NULL, FALSE);
        }
        if ($this->db->count_all_results('urunler') > 0) { return FALSE; }
        return TRUE;
    }

    public function mg_sil($id)
    {
        $this->db->where('id', (int) $id)->delete('kategoriler');
    }

    // ------------------------------------------------------------------
    private function _slug_benzersiz($slug, $id)
    {
        $slug = slug_tr($slug);
        if ($slug === '') { $slug = 'kategori'; }
        $aday = $slug; $i = 1;
        while (TRUE) {
            $this->db->where('slug', $aday);
            if ($id) { $this->db->where('id !=', $id); }
            if ($this->db->count_all_results('kategoriler') == 0) { break; }
            $aday = $slug . '-' . (++$i);
        }
        return $aday;
    }
}
