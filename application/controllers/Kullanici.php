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
        $this->v['meta_title']     = 'Kullanıcı Kaydı — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/kullanici/kayit');
    }

    public function kayit_kaydet()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ad_soyad', 'Ad Soyad', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('email', 'E-posta', 'trim|required|valid_email|max_length[150]');
        $this->form_validation->set_rules('telefon', 'Telefon', 'trim|max_length[30]');
        $this->form_validation->set_rules('sifre', 'Şifre', 'trim|required|min_length[6]|max_length[60]');
        $this->form_validation->set_rules('sifre2', 'Şifre tekrar', 'trim|required|matches[sifre]');
        $this->form_validation->set_rules('sozlesme', 'Sözleşme', 'trim|required', array('required' => 'Üyelik ve mesafeli satış sözleşmesini onaylayın.'));

        if ($this->form_validation->run() === FALSE) { $this->kayit(); return; }

        $this->load->model('kullanici_model');
        $res = $this->kullanici_model->kayit(array(
            'ad_soyad' => $this->input->post('ad_soyad'),
            'email'    => $this->input->post('email'),
            'telefon'  => $this->input->post('telefon'),
            'sifre'    => $this->input->post('sifre'),
        ));
        if (! $res['ok']) {
            $this->session->set_flashdata('hata', $res['mesaj']);
            $this->kayit();
            return;
        }

        // Kullanıcı hesabı onay kuyruğu yok — direkt giriş akışına yönlendir.
        $this->session->set_flashdata('bilgi', 'Hesabınız oluşturuldu. E-posta ve şifrenizle giriş yapabilirsiniz.');
        redirect('kullanici/giris');
    }

    public function giris()
    {
        if ($this->kullanici()) { redirect('hesabim'); }
        $this->v['meta_title']     = 'Kullanıcı Girişi — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->v['donus']          = $this->input->get('donus');
        $this->render('magaza/kullanici/giris');
    }

    public function giris_yap()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-posta', 'trim|required|valid_email');
        $this->form_validation->set_rules('sifre', 'Şifre', 'trim|required');

        // brute-force kilidi (session tabanlı; bayi kilidinden ayrı sayaç)
        $kilit = (int) $this->session->userdata('kullanici_kilit');
        if ($kilit && time() < $kilit) {
            $kalan = max(1, (int) ceil(($kilit - time()) / 60));
            $this->session->set_flashdata('hata', 'Çok fazla başarısız deneme. Lütfen ' . $kalan . ' dk sonra tekrar deneyin.');
            redirect('kullanici/giris');
        }

        if ($this->form_validation->run() === FALSE) { $this->giris(); return; }

        $this->load->model('kullanici_model');
        $k = $this->kullanici_model->giris_kontrol($this->input->post('email'), $this->input->post('sifre'));
        if (! $k) {
            $deneme = (int) $this->session->userdata('kullanici_deneme') + 1;
            $this->session->set_userdata('kullanici_deneme', $deneme);
            if ($deneme >= 5) {
                $this->session->set_userdata('kullanici_kilit', time() + 900); // 15 dk kilit
                $this->session->unset_userdata('kullanici_deneme');
            }
            $this->session->set_flashdata('hata', 'E-posta veya şifre hatalı.');
            redirect('kullanici/giris');
        }
        if ((int) $k->durum !== 1) {
            $this->session->set_flashdata('hata', 'Hesabınız devre dışı bırakılmış. Lütfen bizimle iletişime geçin.');
            redirect('kullanici/giris');
        }

        $this->session->unset_userdata(array('kullanici_deneme', 'kullanici_kilit'));
        $this->kullanici_giris_yap($k);
        $this->kullanici_model->son_giris_isaretle($k->id);

        $donus = $this->_guvenli_donus($this->input->post('donus'));
        redirect($donus ?: 'hesabim');
    }

    public function cikis()
    {
        $this->kullanici_cikis();
        $this->session->set_flashdata('bilgi', 'Çıkış yapıldı.');
        redirect('');
    }

    /** Açık-yönlendirme koruması: yalnız site-içi göreli yol döndürür. */
    private function _guvenli_donus($donus)
    {
        if (! $donus) { return NULL; }
        if (preg_match('#^https?://#i', $donus)) { return NULL; }
        if (strpos($donus, '//') === 0) { return NULL; }
        return ltrim($donus, '/');
    }
}
