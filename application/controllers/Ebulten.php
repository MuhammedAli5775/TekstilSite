<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ebulten — footer e-bülten aboneliği (LV).
 * kayit: POST-only; CSRF CI tarafından otomatik denetlenir; e-posta doğrulama;
 * INSERT ODKU (id=id) ile çift kayıt engeli — affected_rows 1=yeni, 0=zaten var.
 * Dönüş aynı-site referer sayfasına (Dil::cevir deseni; dış referer yoksayılır).
 */
class Ebulten extends Magaza_Controller
{
    public function kayit()
    {
        if (strtolower((string) $this->input->method()) !== 'post') { redirect(''); }

        $eposta = strtolower(trim((string) $this->input->post('eposta')));
        if (! filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('bulten', t('flash_ebulten_gecersiz', 'Geçerli bir e-posta adresi girin.'));
            $this->_don();
        }

        $this->db->query(
            'INSERT INTO ebulten_aboneler (eposta, dil, kayit_ip) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = id',
            array($eposta, aktif_dil(), (string) $this->input->ip_address())
        );
        $yeni = $this->db->affected_rows() === 1;
        $this->session->set_flashdata('bulten', $yeni
            ? t('flash_ebulten_ok', 'Abone olduğunuz için teşekkürler!')
            : t('flash_ebulten_zaten', 'Bu e-posta zaten abone listesinde.'));

        $this->_don();
    }

    /** Aynı-site referer sayfasına dön; dış referer/yoksa anasayfa (Dil::cevir deseni). */
    private function _don()
    {
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
