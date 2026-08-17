<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bayi — B2B kayıt / giriş / çıkış.
 */
class Bayi extends Magaza_Controller
{
    public function kayit()
    {
        if ($this->bayi()) { redirect('hesabim'); }
        $this->v['meta_title']     = 'Bayi Kaydı — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/bayi/kayit');
    }

    public function kayit_kaydet()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('yetkili_ad_soyad', 'Ad Soyad', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('firma_adi', 'Firma ünvanı', 'trim|required|max_length[160]');
        $this->form_validation->set_rules('email', 'E-posta', 'trim|required|valid_email|max_length[150]');
        $this->form_validation->set_rules('telefon', 'Telefon', 'trim|required|max_length[30]');
        $this->form_validation->set_rules('vergi_no', 'Vergi / TC no', 'trim|max_length[30]');
        $this->form_validation->set_rules('vergi_dairesi', 'Vergi dairesi', 'trim|max_length[120]');
        $this->form_validation->set_rules('sifre', 'Şifre', 'trim|required|min_length[6]|max_length[60]');
        $this->form_validation->set_rules('sifre2', 'Şifre tekrar', 'trim|required|matches[sifre]');
        $this->form_validation->set_rules('sozlesme', 'Sözleşme', 'trim|required', array('required' => 'Üyelik ve mesafeli satış sözleşmesini onaylayın.'));

        if ($this->form_validation->run() === FALSE) { $this->kayit(); return; }

        $this->load->model('bayi_model');
        $res = $this->bayi_model->kayit(array(
            'yetkili_ad_soyad' => $this->input->post('yetkili_ad_soyad'),
            'firma_adi'        => $this->input->post('firma_adi'),
            'email'            => $this->input->post('email'),
            'telefon'          => $this->input->post('telefon'),
            'vergi_no'         => $this->input->post('vergi_no'),
            'vergi_dairesi'    => $this->input->post('vergi_dairesi'),
            'sifre'            => $this->input->post('sifre'),
        ));
        if (! $res['ok']) {
            $this->session->set_flashdata('hata', $res['mesaj']);
            $this->kayit();
            return;
        }

        // Kayıt alındı — durum=0, admin onayı beklenir (Bayiler panelinden).
        // Otomatik giriş YOK: onaylanmamış hesap kapıyı aşamaz (girişte durum kontrolü).
        $this->session->set_flashdata('bilgi', 'Kaydınız alındı. Hesabınız onaylandıktan sonra e-posta ve şifrenizle giriş yapabilirsiniz.');
        redirect('bayi/giris');
    }

    public function giris()
    {
        if ($this->bayi()) { redirect('hesabim'); }
        $this->v['meta_title']     = 'Bayi Girişi — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->v['donus']          = $this->input->get('donus');
        $this->render('magaza/bayi/giris');
    }

    public function giris_yap()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-posta', 'trim|required|valid_email');
        $this->form_validation->set_rules('sifre', 'Şifre', 'trim|required');

        // brute-force kilidi (session tabanlı)
        $kilit = (int) $this->session->userdata('bayi_kilit');
        if ($kilit && time() < $kilit) {
            $kalan = max(1, (int) ceil(($kilit - time()) / 60));
            $this->session->set_flashdata('hata', 'Çok fazla başarısız deneme. Lütfen ' . $kalan . ' dk sonra tekrar deneyin.');
            redirect('bayi/giris');
        }

        if ($this->form_validation->run() === FALSE) { $this->giris(); return; }

        $this->load->model('bayi_model');
        $b = $this->bayi_model->giris_kontrol($this->input->post('email'), $this->input->post('sifre'));
        if (! $b) {
            $deneme = (int) $this->session->userdata('bayi_deneme') + 1;
            $this->session->set_userdata('bayi_deneme', $deneme);
            if ($deneme >= 5) {
                $this->session->set_userdata('bayi_kilit', time() + 900); // 15 dk kilit
                $this->session->unset_userdata('bayi_deneme');
            }
            $this->session->set_flashdata('hata', 'E-posta veya şifre hatalı.');
            redirect('bayi/giris');
        }
        if ((int) $b->durum !== 1) {
            $this->session->set_flashdata('hata', 'Hesabınız henüz onaylanmamış. Lütfen bizimle iletişime geçin.');
            redirect('bayi/giris');
        }

        $this->session->unset_userdata(array('bayi_deneme', 'bayi_kilit'));
        $this->bayi_giris_yap($b);   // oturum döner + misafir sepeti transferi içeride
        $this->bayi_model->son_giris_isaretle($b->id);

        $donus = $this->_guvenli_donus($this->input->post('donus'));
        redirect($donus ?: 'hesabim');
    }

    public function cikis()
    {
        $this->bayi_cikis();
        $this->session->set_flashdata('bilgi', 'Çıkış yapıldı.');
        redirect('');
    }

    /** Açık-yönlendirme koruması: yalnız site-içi göreli yol döndürür
     *  ('https:/evil.com' tek-slash baypası kapanır — karakter beyaz listesi, XXVIII). */
    private function _guvenli_donus($donus)
    {
        if (! $donus) { return NULL; }
        if (strpos($donus, '//') !== FALSE || preg_match('#^[A-Za-z0-9/_?=&.%-]+$#', $donus) !== 1) { return NULL; }
        return ltrim($donus, '/');
    }
}
