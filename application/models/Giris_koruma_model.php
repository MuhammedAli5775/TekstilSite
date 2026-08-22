<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Giris_koruma_model — giriş brute-force koruması (LIX).
 *
 * Feed'in IP-sayaç deseninin (Api_anahtar_model) giriş uçlarına uyarlanması:
 * oturum-bazlı kilit çerez silen saldırganı atlatıyordu; bu katman IP bazlıdır
 * ve uç (tip) bazında ayrışır — admin paneline saldıran müşteri girişini
 * kilitlemez. Oturum-bazlı kilit ikinci katman olarak durur.
 *
 * Yanlış PAROLA denemeleri sayılır (hesap pasif/onaysız gibi kimliği doğru
 * girişler sayılmaz); pencere dolunca sayaç yeniden başlar.
 */
class Giris_koruma_model extends CI_Model
{
    const DENEME_ESIK = 5;    // pencere içinde bu kadar yanlış parola → blok
    const PENCERE     = 900;  // saniye (15 dk)
    const TIPLER      = array('kullanici', 'bayi', 'yonetim');

    /** IP bu uçta kilitli mi? (pencere içinde >= EŞİK yanlış deneme olduysa) */
    public function bloklu_mu($tip, $ip)
    {
        if (! in_array($tip, self::TIPLER, TRUE)) { return FALSE; }
        $row = $this->db->where(array('tip' => $tip, 'ip' => $ip))->limit(1)->get('giris_denemeleri')->row();
        if (! $row || (int) $row->basarisiz < self::DENEME_ESIK) { return FALSE; }
        return strtotime((string) $row->son_deneme) > time() - self::PENCERE;
    }

    /** Blok kalan süresi (dakika) — flash mesajı için. */
    public function kalan_dakika($tip, $ip)
    {
        $row = $this->db->where(array('tip' => $tip, 'ip' => $ip))->limit(1)->get('giris_denemeleri')->row();
        if (! $row) { return 1; }
        return max(1, (int) ceil((strtotime((string) $row->son_deneme) + self::PENCERE - time()) / 60));
    }

    /** Yanlış parola denemesini işaretle; pencere dışındaysa sayaç yeniden başlar. */
    public function deneme_kaydet($tip, $ip)
    {
        if (! in_array($tip, self::TIPLER, TRUE)) { return; }
        $ip = substr(trim((string) $ip), 0, 45);
        if ($ip === '') { return; }
        $simdi = date('Y-m-d H:i:s');
        $row = $this->db->where(array('tip' => $tip, 'ip' => $ip))->limit(1)->get('giris_denemeleri')->row();
        if (! $row) {
            $this->db->insert('giris_denemeleri', array('tip' => $tip, 'ip' => $ip, 'basarisiz' => 1, 'son_deneme' => $simdi));
            return;
        }
        $adet = (strtotime((string) $row->son_deneme) > time() - self::PENCERE)
              ? (int) $row->basarisiz + 1
              : 1;
        $this->db->where(array('tip' => $tip, 'ip' => $ip))
                 ->update('giris_denemeleri', array('basarisiz' => $adet, 'son_deneme' => $simdi));
    }

    /** Başarılı giriş → IP sayacını sıfırla. */
    public function deneme_temizle($tip, $ip)
    {
        if (! in_array($tip, self::TIPLER, TRUE)) { return; }
        $this->db->where(array('tip' => $tip, 'ip' => substr(trim((string) $ip), 0, 45)))
                 ->delete('giris_denemeleri');
    }
}
