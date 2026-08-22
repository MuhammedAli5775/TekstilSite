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
        $this->v['meta_title']     = t('ftr_bayi_kayit', 'Bayi Kaydı') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/bayi/kayit');
    }

    public function kayit_kaydet()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('yetkili_ad_soyad', t('odeme_ad_soyad', 'Ad Soyad'), 'trim|required|max_length[120]');
        $this->form_validation->set_rules('firma_adi', t('odeme_firma_unvan', 'Firma Ünvanı'), 'trim|required|max_length[160]');
        $this->form_validation->set_rules('email', t('odeme_eposta', 'E-posta'), 'trim|required|valid_email|max_length[150]');
        $this->form_validation->set_rules('telefon', t('odeme_telefon', 'Telefon'), 'trim|required|max_length[30]');
        $this->form_validation->set_rules('vergi_no', t('odeme_vergi_no', 'Vergi / TC No'), 'trim|max_length[30]');
        $this->form_validation->set_rules('vergi_dairesi', t('bayi_vergi_dairesi', 'Vergi Dairesi'), 'trim|max_length[120]');
        $this->form_validation->set_rules('sifre', t('auth_sifre', 'Şifre'), 'trim|required|min_length[6]|max_length[60]');
        $this->form_validation->set_rules('sifre2', t('auth_sifre_tekrar', 'Şifre Tekrar'), 'trim|required|matches[sifre]');
        $this->form_validation->set_rules('sozlesme', 'Sözleşme', 'trim|required', array('required' => t('val_sozlesme_uyelik', 'Üyelik ve mesafeli satış sözleşmesini onaylayın.')));

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
        $this->session->set_flashdata('bilgi', t('flash_bayi_kayit_ok', 'Kaydınız alındı. Hesabınız onaylandıktan sonra e-posta ve şifrenizle giriş yapabilirsiniz.'));
        redirect('bayi/giris');
    }

    public function giris()
    {
        if ($this->bayi()) { redirect('hesabim'); }
        $this->v['meta_title']     = t('bayi_giris_baslik', 'Bayi Girişi') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->v['donus']          = $this->input->get('donus');
        $this->render('magaza/bayi/giris');
    }

    public function giris_yap()
    {
        // LIX: IP bazlı brute-force kilidi — oturum-kilit katmanına ek; uç-bazlı.
        $this->load->model('giris_koruma_model');
        $ip = (string) $this->input->ip_address();
        if ($this->giris_koruma_model->bloklu_mu('bayi', $ip)) {
            $this->session->set_flashdata('hata', t('flash_limit', 'Çok fazla başarısız deneme. Lütfen %s dk sonra tekrar deneyin.', $this->giris_koruma_model->kalan_dakika('bayi', $ip)));
            redirect('bayi/giris');
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', t('odeme_eposta', 'E-posta'), 'trim|required|valid_email');
        $this->form_validation->set_rules('sifre', t('auth_sifre', 'Şifre'), 'trim|required');

        // brute-force kilidi (session tabanlı)
        $kilit = (int) $this->session->userdata('bayi_kilit');
        if ($kilit && time() < $kilit) {
            $kalan = max(1, (int) ceil(($kilit - time()) / 60));
            $this->session->set_flashdata('hata', t('flash_limit', 'Çok fazla başarısız deneme. Lütfen %s dk sonra tekrar deneyin.', $kalan));
            redirect('bayi/giris');
        }

        if ($this->form_validation->run() === FALSE) { $this->giris(); return; }

        $this->load->model('bayi_model');
        $b = $this->bayi_model->giris_kontrol($this->input->post('email'), $this->input->post('sifre'));
        if (! $b) {
            $this->giris_koruma_model->deneme_kaydet('bayi', $ip);   // LIX: IP sayacı
            $deneme = (int) $this->session->userdata('bayi_deneme') + 1;
            $this->session->set_userdata('bayi_deneme', $deneme);
            if ($deneme >= 5) {
                $this->session->set_userdata('bayi_kilit', time() + 900); // 15 dk kilit
                $this->session->unset_userdata('bayi_deneme');
            }
            $this->session->set_flashdata('hata', t('flash_giris_hatali', 'E-posta veya şifre hatalı.'));
            redirect('bayi/giris');
        }
        if ((int) $b->durum !== 1) {
            $this->session->set_flashdata('hata', t('flash_bayi_onaysiz', 'Hesabınız henüz onaylanmamış. Lütfen bizimle iletişime geçin.'));
            redirect('bayi/giris');
        }

        $this->giris_koruma_model->deneme_temizle('bayi', $ip);   // LIX: başarılı giriş → IP sıfır
        $this->session->unset_userdata(array('bayi_deneme', 'bayi_kilit'));
        $this->bayi_giris_yap($b);   // oturum döner + misafir sepeti transferi içeride
        $this->bayi_model->son_giris_isaretle($b->id);

        $donus = $this->_guvenli_donus($this->input->post('donus'));
        redirect($donus ?: 'hesabim');
    }

    public function cikis()
    {
        $this->bayi_cikis();
        $this->session->set_flashdata('bilgi', t('flash_cikis_ok', 'Çıkış yapıldı.'));
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
