<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Marka_model — marka yönetimi (Query Builder). Slug benzersiz.
 */
class Marka_model extends CI_Model
{
    /** Liste (arama: ad). */
    public function liste($q = '')
    {
        if (! $this->db->table_exists('markalar')) { return array(); }
        $q = trim((string) $q);
        if ($q !== '') { $this->db->like('ad', $q); }
        return $this->db->order_by('ad', 'ASC')->get('markalar')->result();
    }

    public function getir($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('markalar')->row();
    }

    public function kaydet($d)
    {
        $slug = trim((string) ($d['slug'] ?? ''));
        if ($slug === '') { $slug = slug_tr($d['ad'] ?? ''); }
        $d['slug'] = $this->_slug_benzersiz($slug, 0);
        $this->db->insert('markalar', $d);
        return $this->db->insert_id();
    }

    public function guncelle($id, $d)
    {
        if (array_key_exists('slug', $d)) {
            $slug = trim((string) $d['slug']);
            if ($slug === '') { $slug = slug_tr($d['ad'] ?? ''); }
            $d['slug'] = $this->_slug_benzersiz($slug, (int) $id);
        }
        $this->db->where('id', (int) $id)->update('markalar', $d);
    }

    /** Silinebilir mi? (silinmemiş) ürünü varsa FALSE. */
    public function sil_kontrol($id)
    {
        $n = $this->db->where('marka_id', (int) $id);
        if ($this->db->field_exists('deleted_at', 'urunler')) {
            $this->db->where('deleted_at IS NULL', NULL, FALSE);
        }
        return $this->db->count_all_results('urunler') === 0;
    }

    public function sil($id)
    {
        $this->db->where('id', (int) $id)->delete('markalar');
    }

    private function _slug_benzersiz($slug, $id)
    {
        $slug = slug_tr($slug);
        if ($slug === '') { $slug = 'marka'; }
        $aday = $slug; $i = 1;
        while (TRUE) {
            $this->db->where('slug', $aday);
            if ($id) { $this->db->where('id !=', $id); }
            if ($this->db->count_all_results('markalar') == 0) { break; }
            $aday = $slug . '-' . (++$i);
        }
        return $aday;
    }
}
