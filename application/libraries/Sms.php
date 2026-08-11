<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sms — Netgsm HTTP API ile SMS bildirimi (graceful).
 *
 * Ayarlar'dan okur: sms_aktif / sms_kullanici / sms_sifre / sms_gonderen.
 * Aktif değilse veya kimlik yoksa gönderilmez; sipariş/durum akışı bozulmaz.
 * Eposta (library) ile paralel desen: siparis_onay + durum_bildirim.
 */
class Sms
{
    protected $CI;
    const API = 'https://api.netgsm.com.tr/sms/send/get/';

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** SMS gönderilebilir mi (aktif + kimlik dolu)? */
    public function hazir()
    {
        return (string) ayar('sms_aktif') === '1'
            && ayar('sms_kullanici')
            && ayar('sms_sifre');
    }

    /**
     * Tek SMS gönder.
     *
     * @param string $gsm   Alıcı GSM (TR; 0/90/çiğrak kabul, normalize edilir)
     * @param string $mesaj Mesaj gövdesi (UTF-8 Türkçe)
     * @return bool
     */
    public function gonder($gsm, $mesaj)
    {
        $gsm   = $this->_gsm($gsm);
        $mesaj = trim((string) $mesaj);
        if ($gsm === '' || $mesaj === '') {
            log_message('error', 'Sms: boş GSM/mesaj — atlandı.');
            return FALSE;
        }
        if (! $this->hazir()) {
            log_message('error', 'Sms: pasif veya kimlik yok — atlandı (graceful).');
            return FALSE;
        }

        $params = array(
            'usercode'  => ayar('sms_kullanici'),
            'password'  => ayar('sms_sifre'),
            'msgheader' => ayar('sms_gonderen') ?: ayar('site_adi', 'TekstilSite'),
            'gsmno'     => $gsm,
            'message'   => $mesaj,
            'dil'       => 'TR',
        );
        $url = self::API . '?' . http_build_query($params);

        if (! function_exists('curl_init')) {
            log_message('error', 'Sms: curl yok — atlandı.');
            return FALSE;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_USERAGENT      => 'TekstilSite/SMS',
        ));
        $yanit = curl_exec($ch);
        $hata  = curl_error($ch);
        curl_close($ch);

        if ($yanit === FALSE) {
            log_message('error', 'Sms: curl/ağ hatası (' . $hata . ').');
            return FALSE;
        }
        // Netgsm: başarılı "00 <msgid>"  |  hata "<kod> <aciklama>"
        $kod = strtok(trim((string) $yanit), " \t\n\r");
        if ($kod === '00') {
            log_message('info', 'Sms: gönderildi -> ' . $gsm . ' (yanıt: ' . mb_substr((string) $yanit, 0, 40) . ').');
            return TRUE;
        }
        log_message('error', 'Sms: Netgsm hatası (yanıt: ' . mb_substr((string) $yanit, 0, 120) . ').');
        return FALSE;
    }

    /** Sipariş alındı SMS'i (müşteri teslimat telefonuna). */
    public function siparis_onay($siparis_id)
    {
        $this->CI->load->model('siparis_model');
        $s = $this->CI->siparis_model->mg_getir($siparis_id);
        if (! $s) { return FALSE; }
        $mesaj = ayar('site_adi', 'TekstilSite') . ': Siparişiniz alındı (#' . $s->siparis_no
               . '). Toplam ' . number_format((float) $s->toplam, 2, ',', '.') . ' TL. '
               . rtrim((string) base_url(), '/') . 'hesabim';
        return $this->gonder($s->teslimat_telefon, $mesaj);
    }

    /** Sipariş durum değişikliği SMS'i (kargolandı'da takip no dahil). */
    public function durum_bildirim($siparis_id, $durum_etiket, $notu = '')
    {
        $this->CI->load->model('siparis_model');
        $s = $this->CI->siparis_model->mg_admin_getir($siparis_id);
        if (! $s) { return FALSE; }
        $mesaj = ayar('site_adi', 'TekstilSite') . ': #' . $s->siparis_no . ' siparişinizin durumu: ' . $durum_etiket . '.';
        if (! empty($s->kargo_takip_no)) {
            $mesaj .= ' Kargo takip: ' . $s->kargo_takip_no . '.';
        }
        return $this->gonder($s->teslimat_telefon, $mesaj);
    }

    /** GSM'i Netgsm formatına getir (90XXXXXXXXXX). Boş/hatalı → ''. */
    private function _gsm($gsm)
    {
        $gsm = preg_replace('/\D/', '', (string) $gsm);
        if ($gsm === '') { return ''; }
        if (strpos($gsm, '90') === 0 && strlen($gsm) > 10) { return $gsm; } // zaten 90...
        if (strpos($gsm, '0') === 0) { return '9' . $gsm; }                 // 0XXX → 90XXX
        if (strlen($gsm) === 10) { return '90' . $gsm; }                    // 5XXXXXXXXX
        return '';
    }
}
