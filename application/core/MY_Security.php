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
        log_message('error', 'CSRF reddi: cerez=' . (isset($_COOKIE[$cname]) ? 'var' : 'YOK')
            . ' posted=' . (isset($_POST[$name]) ? 'var' : 'YOK')
            . ' eslesme=' . ((isset($_POST[$name], $_COOKIE[$cname]) && hash_equals((string) $_POST[$name], (string) $_COOKIE[$cname])) ? 'evet' : 'hayir')
            . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '-')
            . ' ua=' . substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 80));

        parent::csrf_show_error();
    }
}
