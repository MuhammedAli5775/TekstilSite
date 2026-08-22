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

    /* ---------------- TOTP iki adımlı doğrulama (LXIII) ---------------- */

    /** TOTP anahtarını kaydet — 2FA bu andan itibaren etkin. */
    public function totp_kaydet($id, $b32)
    {
        $this->db->where('id', (int) $id)->update('yoneticiler', array('totp_secret' => $b32));
    }

    /** TOTP'yi kapat: anahtar + kurtarma kodları birlikte gider. */
    public function totp_sil($id)
    {
        $this->db->where('id', (int) $id)->update('yoneticiler', array('totp_secret' => NULL));
        $this->db->where('yonetici_id', (int) $id)->delete('yonetici_kurtarma');
    }

    /** N adet tek-kullanımlık kurtarma kodu üret; düz metinleri döndürür (DB'ye yalnız hash). */
    public function kurtarma_uret($id, $adet = 5)
    {
        $this->db->where('yonetici_id', (int) $id)->delete('yonetici_kurtarma');
        $duz = array();
        for ($i = 0; $i < $adet; $i++) {
            $kod = strtoupper(bin2hex(random_bytes(5)));   // 10 hane hex
            $duz[] = $kod;
            $this->db->insert('yonetici_kurtarma', array(
                'yonetici_id' => (int) $id,
                'kod_hash'    => hash('sha256', $kod),
                'kullanildi'  => 0,
                'uretildi'    => date('Y-m-d H:i:s'),
            ));
        }
        return $duz;
    }

    /** Kurtarma kodu geçerli mi? Geçerliyse HARCANIR (tek kullanım). */
    public function kurtarma_kullan($id, $kod)
    {
        $kod = strtoupper(trim((string) $kod));
        if ($kod === '') { return FALSE; }
        $row = $this->db->where(array('yonetici_id' => (int) $id, 'kod_hash' => hash('sha256', $kod), 'kullanildi' => 0))
                        ->limit(1)->get('yonetici_kurtarma')->row();
        if (! $row) { return FALSE; }
        $this->db->where('id', (int) $row->id)->update('yonetici_kurtarma', array('kullanildi' => 1));
        return TRUE;
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
