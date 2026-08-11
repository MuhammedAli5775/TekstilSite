<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Paytr_api — PayTR iFrame API entegrasyonu (graceful).
 *
 * Akış: get-token (imzalı) → iframe → sunucudan-sunucuya bildirim (hash doğrulama).
 * Kimlik (merchant_id/key/salt) Ayarlar'da; yoksa hazir()=FALSE, akış bozulmaz.
 * Hash formülleri PayTR iFrame API dokümantasyonundan birebir.
 *
 * (Sınıf adı Paytr_api — controller "Paytr" ile çakışmaması için.)
 */
class Paytr_api
{
    const GET_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Yapılandırılmış mı (merchant_id + key + salt)? */
    public function hazir()
    {
        return ayar('paytr_merchant_id') && ayar('paytr_merchant_key') && ayar('paytr_merchant_salt');
    }

    public function test_modu()
    {
        return (string) ayar('paytr_test') === '1';
    }

    /**
     * Sipariş için PayTR iframe token'ı al.
     * @param object $s  siparis (siparis_no, email, toplam, detaylar)
     * @return array {ok, token} | {ok:false, mesaj}
     */
    public function get_token($s)
    {
        $merchant_id   = ayar('paytr_merchant_id');
        $merchant_key  = ayar('paytr_merchant_key');
        $merchant_salt = ayar('paytr_merchant_salt');
        if (! $this->hazir()) {
            return array('ok' => FALSE, 'mesaj' => 'PayTR kimlikleri eksik.');
        }

        $user_ip        = $this->CI->input->ip_address();
        $merchant_oid   = $s->siparis_no;
        $email          = $s->email ?: ('siparis-' . $s->id . '@' . preg_replace('#^https?://#', '', (string) base_url()));
        $payment_amount = (int) round((float) $s->toplam * 100);   // kuruş

        // user_basket: [[ad, fiyat(kuruş), adet], ...]
        $basket = array();
        foreach ($s->detaylar as $d) {
            $basket[] = array($d->urun_adi, (int) round((float) $d->birim_fiyat * 100), (int) $d->adet);
        }
        $user_basket = base64_encode(json_encode($basket, JSON_UNESCAPED_UNICODE));

        $no_installment  = '0';
        $max_installment = '0';
        $currency        = 'TL';
        $test_mode       = $this->test_modu() ? '1' : '0';
        $base            = rtrim((string) base_url(), '/');
        $merchant_ok_url = $base . '/paytr/basarili/' . $s->id;
        $merchant_fail_url = $base . '/paytr/basarisiz/' . $s->id;

        // hash = base64(hmac_sha256(merchant_id + user_ip + merchant_oid + email +
        //   payment_amount + user_basket + no_installment + max_installment +
        //   currency + test_mode + merchant_salt, merchant_key))
        $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount
                  . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, TRUE));

        $post_val = array(
            'merchant_id'       => $merchant_id,
            'user_ip'           => $user_ip,
            'merchant_oid'      => $merchant_oid,
            'email'             => $email,
            'payment_amount'    => $payment_amount,
            'user_basket'       => $user_basket,
            'no_installment'    => $no_installment,
            'max_installment'   => $max_installment,
            'currency'          => $currency,
            'test_mode'         => $test_mode,
            'merchant_ok_url'   => $merchant_ok_url,
            'merchant_fail_url' => $merchant_fail_url,
            'timeout_limit'     => '30',
            'debug_on'          => '1',
            'hash'              => $paytr_token,
        );

        $yanit = $this->_http_post(self::GET_TOKEN_URL, $post_val);
        if (! $yanit['ok']) {
            log_message('error', 'PayTR get-token bağlantı: ' . $yanit['mesaj']);
            return array('ok' => FALSE, 'mesaj' => 'PayTR bağlantı hatası.');
        }
        $d = json_decode($yanit['govde'], TRUE);
        if (! isset($d['status']) || $d['status'] !== 'success' || empty($d['token'])) {
            log_message('error', 'PayTR get-token reddi: ' . mb_substr((string) $yanit['govde'], 0, 300));
            return array('ok' => FALSE, 'mesaj' => 'PayTR token alınamadı (' . ($d['reason'] ?? 'bilinmeyen') . ').');
        }
        return array('ok' => TRUE, 'token' => $d['token']);
    }

    /**
     * Bildirim callback hash'ini doğrula (timing-safe).
     * hash = base64(hmac_sha256(merchant_oid + merchant_salt + status + total_amount, merchant_key))
     */
    public function callback_dogrula($post)
    {
        $merchant_key  = ayar('paytr_merchant_key');
        $merchant_salt = ayar('paytr_merchant_salt');
        $oid   = (string) ($post['merchant_oid'] ?? '');
        $stat  = (string) ($post['status'] ?? '');
        $total = (string) ($post['total_amount'] ?? '');
        $hash  = (string) ($post['hash'] ?? '');
        if ($oid === '' || $hash === '' || ! $this->hazir()) { return FALSE; }
        $beklenen = base64_encode(hash_hmac('sha256', $oid . $merchant_salt . $stat . $total, $merchant_key, TRUE));
        return hash_equals($bekenen, $hash);
    }

    private function _http_post($url, $params)
    {
        if (! function_exists('curl_init')) { return array('ok' => FALSE, 'mesaj' => 'curl yok.'); }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
        ));
        $govde = curl_exec($ch);
        $hata  = curl_error($ch);
        curl_close($ch);
        if ($govde === FALSE) { return array('ok' => FALSE, 'mesaj' => 'curl: ' . $hata); }
        return array('ok' => TRUE, 'govde' => $govde);
    }
}
