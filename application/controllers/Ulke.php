<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ulke — teslimat ülkesi seçici (DEGISIKLIK XXXIV). Dil dropdown'unun alt
 * bölümünden seçilir; ürün para birimi ülkeye göre çözülür (aktif_para_birimi:
 * ülke → para_birimleri kur'u). Dil::cevir deseni: oturum + 1 yıllık çerez,
 * yalnız aynı-site referere dönüş, geçersiz kod → 'tr'.
 */
class Ulke extends Magaza_Controller
{
    public function sec($kod = 'tr')
    {
        $kod = strtolower(trim((string) $kod));
        if (! array_key_exists($kod, ulke_listesi())) { $kod = 'tr'; }

        $this->session->set_userdata('teslimat_ulkesi', $kod);
        $this->input->set_cookie(array(
            'name'   => 'teksil_ulke',
            'value'  => $kod,
            'expire' => 31536000, // 1 yıl — kalıcı tercih
            'path'   => '/',
            'secure' => (bool) config_item('cookie_secure'),
        ));

        // Dil::cevir ile aynı: yalnız aynı siteden gelen referer yolu.
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
