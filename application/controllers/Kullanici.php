<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kullanici — B2C kayıt / giriş / çıkış.
 * Bayi akışının (admin onayı, MOQ, toptan fiyat) sade eşiği: kişisel hesap,
 * kayıt anında aktif, siparişleri e-posta ile eşleşir.
 */
class Kullanici extends Magaza_Controller
{
    public function kayit()
    {
        if ($this->kullanici()) { redirect('hesabim'); }
        $this->v['meta_title']     = t('kul_kayit_title', 'Kullanıcı Kaydı') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/kullanici/kayit');
    }

    public function kayit_kaydet()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ad_soyad', t('odeme_ad_soyad', 'Ad Soyad'), 'trim|required|max_length[120]');
        $this->form_validation->set_rules('kullanici_adi', t('kul_kullanici_adi', 'Kullanıcı Adı'), 'trim|required|alpha_dash|min_length[3]|max_length[30]', array('alpha_dash' => t('val_kuladi_kural', 'Kullanıcı adı yalnızca harf, rakam, tire (-) ve alt çizgi (_) içerebilir.')));
        $this->form_validation->set_rules('email', t('odeme_eposta', 'E-posta'), 'trim|required|valid_email|max_length[150]');
        $this->form_validation->set_rules('telefon', t('odeme_telefon', 'Telefon'), 'trim|max_length[30]');
        $this->form_validation->set_rules('sifre', t('auth_sifre', 'Şifre'), 'trim|required|min_length[6]|max_length[60]');
        $this->form_validation->set_rules('sifre2', t('auth_sifre_tekrar', 'Şifre Tekrar'), 'trim|required|matches[sifre]');
        $this->form_validation->set_rules('sozlesme', 'Sözleşme', 'trim|required', array('required' => t('val_sozlesme_uyelik', 'Üyelik ve mesafeli satış sözleşmesini onaylayın.')));

        if ($this->form_validation->run() === FALSE) { $this->kayit(); return; }

        $this->load->model('kullanici_model');
        $res = $this->kullanici_model->kayit(array(
            'ad_soyad'     => $this->input->post('ad_soyad'),
            'kullanici_adi' => $this->input->post('kullanici_adi'),
            'email'        => $this->input->post('email'),
            'telefon'      => $this->input->post('telefon'),
            'sifre'        => $this->input->post('sifre'),
        ));
        if (! $res['ok']) {
            $this->session->set_flashdata('hata', $res['mesaj']);
            $this->kayit();
            return;
        }

        // Kullanıcı hesabı onay kuyruğu yok — direkt giriş akışına yönlendir.
        $this->session->set_flashdata('bilgi', t('flash_kul_kayit_ok', 'Hesabınız oluşturuldu. E-posta ve şifrenizle giriş yapabilirsiniz.'));
        redirect('kullanici/giris');
    }

    public function giris()
    {
        if ($this->kullanici()) { redirect('hesabim'); }
        $this->v['meta_title']     = t('kul_giris_baslik', 'Kullanıcı Girişi') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->v['donus']          = $this->input->get('donus');
        $this->render('magaza/kullanici/giris');
    }

    public function giris_yap()
    {
        // LIX: IP bazlı brute-force kilidi — oturum-kilit katmanına ek; çerez
        // silen saldırganı atlatamaz. Uç-bazlı sayaç: bayi/yonetim ayrı işler.
        $this->load->model('giris_koruma_model');
        $ip = (string) $this->input->ip_address();
        if ($this->giris_koruma_model->bloklu_mu('kullanici', $ip)) {
            $this->session->set_flashdata('hata', t('flash_limit', 'Çok fazla başarısız deneme. Lütfen %s dk sonra tekrar deneyin.', $this->giris_koruma_model->kalan_dakika('kullanici', $ip)));
            redirect('kullanici/giris');
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', t('odeme_eposta', 'E-posta'), 'trim|required|valid_email');
        $this->form_validation->set_rules('sifre', t('auth_sifre', 'Şifre'), 'trim|required');

        // brute-force kilidi (session tabanlı; bayi kilidinden ayrı sayaç)
        $kilit = (int) $this->session->userdata('kullanici_kilit');
        if ($kilit && time() < $kilit) {
            $kalan = max(1, (int) ceil(($kilit - time()) / 60));
            $this->session->set_flashdata('hata', t('flash_limit', 'Çok fazla başarısız deneme. Lütfen %s dk sonra tekrar deneyin.', $kalan));
            redirect('kullanici/giris');
        }

        if ($this->form_validation->run() === FALSE) { $this->giris(); return; }

        $this->load->model('kullanici_model');
        $k = $this->kullanici_model->giris_kontrol($this->input->post('email'), $this->input->post('sifre'));
        if (! $k) {
            $this->giris_koruma_model->deneme_kaydet('kullanici', $ip);   // LIX: IP sayacı
            $deneme = (int) $this->session->userdata('kullanici_deneme') + 1;
            $this->session->set_userdata('kullanici_deneme', $deneme);
            if ($deneme >= 5) {
                $this->session->set_userdata('kullanici_kilit', time() + 900); // 15 dk kilit
                $this->session->unset_userdata('kullanici_deneme');
            }
            $this->session->set_flashdata('hata', t('flash_giris_hatali', 'E-posta veya şifre hatalı.'));
            redirect('kullanici/giris');
        }
        if ((int) $k->durum !== 1) {
            $this->session->set_flashdata('hata', t('flash_kul_devre_disi', 'Hesabınız devre dışı bırakılmış. Lütfen bizimle iletişime geçin.'));
            redirect('kullanici/giris');
        }

        $this->giris_koruma_model->deneme_temizle('kullanici', $ip);   // LIX: başarılı giriş → IP sıfır
        $this->session->unset_userdata(array('kullanici_deneme', 'kullanici_kilit'));
        $this->kullanici_giris_yap($k);
        $this->kullanici_model->son_giris_isaretle($k->id);

        $donus = $this->_guvenli_donus($this->input->post('donus'));
        redirect($donus ?: 'hesabim');
    }

    public function cikis()
    {
        $this->kullanici_cikis();
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
