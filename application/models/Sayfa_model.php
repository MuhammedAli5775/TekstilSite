<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sayfa_model — CMS sayfaları yönetimi (Query Builder).
 * Slug benzersiz; içerik admin HTML (güvenilir kaynak).
 */
class Sayfa_model extends CI_Model
{
    /** Liste (arama: baslik/slug). */
    public function liste($q = '')
    {
        if (! $this->db->table_exists('sayfalar')) { return array(); }
        $q = trim((string) $q);
        if ($q !== '') {
            $this->db->group_start()->like('baslik', $q)->or_like('slug', $q)->group_end();
        }
        return $this->db->order_by('id', 'ASC')->get('sayfalar')->result();
    }

    /** Tek sayfa. */
    public function getir($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('sayfalar')->row();
    }

    public function kaydet($d)
    {
        $slug = trim((string) ($d['slug'] ?? ''));
        if ($slug === '') { $slug = slug_tr($d['baslik'] ?? ''); }
        $d['slug'] = $this->_slug_benzersiz($slug, 0);
        $this->db->insert('sayfalar', $d);
        return $this->db->insert_id();
    }

    public function guncelle($id, $d)
    {
        if (array_key_exists('slug', $d)) {
            $slug = trim((string) $d['slug']);
            if ($slug === '') { $slug = slug_tr($d['baslik'] ?? ''); }
            $d['slug'] = $this->_slug_benzersiz($slug, (int) $id);
        }
        $this->db->where('id', (int) $id)->update('sayfalar', $d);
    }

    public function durum($id, $durum)
    {
        $this->db->where('id', (int) $id)->update('sayfalar', array('durum' => $durum ? 1 : 0));
    }

    public function sil($id)
    {
        $this->db->where('id', (int) $id)->delete('sayfalar');
    }

    private function _slug_benzersiz($slug, $id)
    {
        $slug = slug_tr($slug);
        if ($slug === '') { $slug = 'sayfa'; }
        $aday = $slug; $i = 1;
        while (TRUE) {
            $this->db->where('slug', $aday);
            if ($id) { $this->db->where('id !=', $id); }
            if ($this->db->count_all_results('sayfalar') == 0) { break; }
            $aday = $slug . '-' . (++$i);
        }
        return $aday;
    }
}
