<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ayar_model — ayarlar (anahtar/değer) KV deposu.
 */
class Ayar_model extends CI_Model
{
    /** Tüm ayarları [anahtar => deger] olarak döndür. */
    public function tum()
    {
        $rows = $this->db->get('ayarlar')->result();
        $out = array();
        foreach ($rows as $r) { $out[$r->anahtar] = $r->deger; }
        return $out;
    }

    /** Bir ayarı upsert eder. */
    public function upsert($anahtar, $deger, $grup = 'genel')
    {
        $mevcut = $this->db->where('anahtar', $anahtar)->count_all_results('ayarlar');
        if ($mevcut) {
            $this->db->where('anahtar', $anahtar)->update('ayarlar', array('deger' => $deger));
        } else {
            $this->db->insert('ayarlar', array('anahtar' => $anahtar, 'deger' => $deger, 'grup' => $grup));
        }
    }
}
