<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dil — mağaza dil seçici (DEGISIKLIK XXIX). TR varsayılan; EN/RU/AR.
 * Seçim oturuma + 1 yıllık çereze yazılır; geri dönüş yalnız aynı-site
 * referera (Sayfa::ref_ic deseni — dış siteye yönlendirme kapalı), yoksa anasayfa.
 */
class Dil extends Magaza_Controller
{
    public function cevir($kod = 'tr')
    {
        $kod = strtolower(trim((string) $kod));
        if (! in_array($kod, array('tr', 'en', 'ru', 'ar'), TRUE)) { $kod = 'tr'; }

        $this->session->set_userdata('dil', $kod);
        $this->input->set_cookie(array(
            'name'   => 'teksil_dil',
            'value'  => $kod,
            'expire' => 31536000, // 1 yıl — kalıcı tercih
            'path'   => '/',
            'secure' => (bool) config_item('cookie_secure'),
        ));

        // Yalnız aynı siteden gelen referer yolu; dış referer yoksayılır.
        $ref = trim((string) $this->input->server('HTTP_REFERER'));
        $hedef = '';
        if ($ref !== '' && ($p = parse_url($ref))
            && isset($p['scheme'], $p['host'])
            && in_array($p['scheme'], array('http', 'https'), TRUE)
            && strcasecmp($p['host'], strtok((string) $this->input->server('HTTP_HOST'), ':')) === 0)
        {
            $hedef = ltrim($p['path'] ?? '', '/') . (isset($p['query']) ? '?' . $p['query'] : '');
        }
        redirect($hedef ?: '');
    }
}
