<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Yonetici_model — admin (yönetici) hesabı + audit log.
 */
class Yonetici_model extends CI_Model
{
    public function by_email($email)
    {
        return $this->db->where('LOWER(email)', strtolower((string) $email))->limit(1)->get('yoneticiler')->row();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('yoneticiler')->row();
    }

    public function son_giris($id)
    {
        $this->db->where('id', (int) $id)->update('yoneticiler', array('son_giris' => date('Y-m-d H:i:s')));
    }

    /** Yönetici parolasını güncelle (LXII — kendi parolasını panelden değiştirir). */
    public function sifre_guncelle($id, $yeni)
    {
        $this->db->where('id', (int) $id)->update('yoneticiler', array('sifre' => password_hash($yeni, PASSWORD_BCRYPT)));
    }

    /** Audit log yaz. */
    public function audit_log($d)
    {
        if (! $this->db->table_exists('yonetici_loglari')) { return; }
        $this->db->insert('yonetici_loglari', array(
            'yonetici_id' => isset($d['yonetici_id']) ? (int) $d['yonetici_id'] : NULL,
            'modul'       => $d['modul'] ?? NULL,
            'islem'       => $d['islem'] ?? NULL,
            'hedef'       => $d['hedef'] ?? NULL,
            'aciklama'    => $d['aciklama'] ?? NULL,
            'ip'          => $d['ip'] ?? NULL,
        ));
    }
}
