<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Security — CSRF çerez politikası + ret tanı günlüğü (DEGISIKLIK XXIII).
 *
 * 1) Stok CI3 (system/core/Security.php) CSRF çerezine SABIT SameSite=Strict
 *    basar (yapılandırma yok); oturum çerezi ise Lax'tır. Strict, bazı tarayıcı
 *    akışlarında (BFCache'li geri dönüş, çapraz-site bağlantıyla gelen ilk GET)
 *    sayfaya gömülü hash ile kavanozdaki çerezi senkron dışı bırakabiliyor →
 *    her POST 403 düşüyordu. Lax, CSRF korumasını olduğu gibi korur (çapraz-site
 *    POST'a çerez yine gitmez) ve oturum çereziyle aynı politikadır.
 * 2) csrf_show_error sessizdi — retlerde artık tek satır tanı günlüğü düşer
 *    (çerez/posted var mı, değerler eşleşiyor mu, URI + UA). E3 izlemesine girer.
 */
class MY_Security extends CI_Security
{
    /** csrf_verify $_POST[token]'ı unset ETMEDEN önce yakalanan değer —
     *  stok csrf_verify reddetmeden hemen önce alanı siler; tanı satırı
     *  silinmiş diziyi görüp "posted=YOK" yazardı (ölçüm hatası, XXIV). */
    protected $_tan_posted = NULL;

    /** Token'ı unset'ten önce yakalayıp stok doğrulamaya devreder. */
    public function csrf_verify()
    {
        $name = $this->_csrf_token_name;
        $this->_tan_posted = isset($_POST[$name]) ? (string) $_POST[$name] : NULL;
        return parent::csrf_verify();
    }

    /** CSRF çerezini oturum çereziyle hizalı biçimde basar (SameSite=Lax). */
    public function csrf_set_cookie()
    {
        $expire = time() + $this->_csrf_expire;
        $secure_cookie = (bool) config_item('cookie_secure');

        if ($secure_cookie && ! is_https())
        {
            return FALSE;
        }

        setcookie(
            $this->_csrf_cookie_name,
            $this->_csrf_hash,
            array(
                'expires'  => $expire,
                'path'     => config_item('cookie_path'),
                'domain'   => config_item('cookie_domain'),
                'secure'   => $secure_cookie,
                'httponly' => config_item('cookie_httponly'),
                'samesite' => 'Lax',
            )
        );

        log_message('info', 'CSRF cookie sent (SameSite=Lax)');
        return $this;
    }

    /** Standart 403'ü verir; öncesinde tanı satırı düşer. */
    public function csrf_show_error()
    {
        $name  = $this->_csrf_token_name;
        $cname = $this->_csrf_cookie_name;
        $posted_var = $this->_tan_posted !== NULL;
        $eslesme    = ($posted_var && isset($_COOKIE[$cname]) && hash_equals($this->_tan_posted, (string) $_COOKIE[$cname])) ? 'evet' : 'hayir';
        log_message('error', 'CSRF reddi: cerez=' . (isset($_COOKIE[$cname]) ? 'var' : 'YOK')
            . ' posted=' . ($posted_var ? 'var' : 'YOK')
            . ' eslesme=' . $eslesme
            . ' post_anahtar=' . implode(',', array_keys($_POST))
            . ' icerik_tipi=' . ($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '-'))
            . ' uzunluk=' . ($_SERVER['CONTENT_LENGTH'] ?? ($_SERVER['HTTP_CONTENT_LENGTH'] ?? '-'))
            . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '-')
            . ' ua=' . substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 80));

        parent::csrf_show_error();
    }
}
