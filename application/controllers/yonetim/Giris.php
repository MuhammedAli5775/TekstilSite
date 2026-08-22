<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Yönetim girişi / çıkışı.
 */
class Giris extends Admin_Controller
{
    public function index()
    {
        if ($this->auth_admin->logged_in()) { redirect('yonetim/dashboard'); }
        $data['sayfa_basligi'] = 'Giriş';
        $this->render_bare('yonetim/giris/index', $data);
    }

    public function giris_yap()
    {
        // LIX: IP bazlı brute-force kilidi — yönetim ucu daha önce hiç korunmuyordu
        // (en değerli hedef). Feed'in IP-sayaç deseni; oturum-bazlı kilit yoktu.
        $this->load->model('giris_koruma_model');
        $ip = (string) $this->input->ip_address();
        if ($this->giris_koruma_model->bloklu_mu('yonetim', $ip)) {
            $this->session->set_flashdata('hata', 'Çok fazla başarısız deneme. Lütfen ' . $this->giris_koruma_model->kalan_dakika('yonetim', $ip) . ' dk sonra tekrar deneyin.');
            redirect('yonetim/giris');
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-posta', 'trim|required|valid_email');
        $this->form_validation->set_rules('sifre', 'Şifre', 'trim|required');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', 'Lütfen e-posta ve şifrenizi girin.');
            redirect('yonetim/giris');
        }
        $res = $this->auth_admin->dogrula($this->input->post('email'), $this->input->post('sifre'));
        if (! $res['ok']) {
            $this->giris_koruma_model->deneme_kaydet('yonetim', $ip);   // LIX: IP sayacı
            $this->session->set_flashdata('hata', $res['mesaj']);
            redirect('yonetim/giris');
        }
        $this->giris_koruma_model->deneme_temizle('yonetim', $ip);   // LIX: parola doğru → IP sıfır

        // LXIII: TOTP'li hesapta parola doğru olsa bile oturum AÇILMAZ —
        // kod adımı beklenir (totp_bekliyor). Ara durumda yonetici_id yoktur.
        $y = $res['yonetici'];
        if (! empty($y->totp_secret)) {
            $donus = (string) $this->input->post('donus');
            $donus = ($donus !== '' && strpos($donus, '//') === FALSE && preg_match('#^[A-Za-z0-9/_?=&.%-]+$#', $donus) === 1) ? $donus : '';
            $this->session->set_userdata(array('totp_bekliyor' => (int) $y->id, 'totp_deneme' => 0, 'totp_donus' => $donus));
            redirect('yonetim/giris/totp');
        }

        $this->auth_admin->oturum_ac($y);
        $donus = $this->input->post('donus');
        // Yalnız güvenli göreli yol — 'https:/evil.com' tek-slash baypası kapanır (XXVIII):
        // karakter beyaz listesi (':' yok) + '//' yasak.
        if ($donus && strpos($donus, '//') === FALSE && preg_match('#^[A-Za-z0-9/_?=&.%-]+$#', $donus) === 1) {
            redirect(ltrim($donus, '/'));
        }
        redirect('yonetim/dashboard');
    }

    /**
     * LXIII: TOTP kod adımı — parolası doğrulanmış oturum için.
     * Kurtarma kodu (10 hane A-F0-9) da kabul edilir; tek kullanımlıktır.
     * 5 hatalı denemede bekleyen durum düşer → yeniden parola gerekir.
     */
    public function totp()
    {
        $bekleyen = (int) $this->session->userdata('totp_bekliyor');
        if (! $bekleyen) { redirect('yonetim/giris'); }

        if (strtolower((string) $this->input->method()) !== 'post') {
            $data['sayfa_basligi'] = 'İki Adımlı Doğrulama';
            $this->render_bare('yonetim/giris/totp', $data);
            return;
        }

        $y   = $this->yonetici_model->get($bekleyen);
        $kod = trim((string) $this->input->post('kod'));
        $this->load->library('totp');
        $gecerli = ($y && ! empty($y->totp_secret)) ? $this->totp->gecerli($y->totp_secret, $kod) : FALSE;
        if (! $gecerli && $y && preg_match('/^[A-F0-9]{10}$/i', $kod)) {
            $gecerli = $this->yonetici_model->kurtarma_kullan($y->id, $kod);
        }

        if (! $gecerli) {
            $deneme = (int) $this->session->userdata('totp_deneme') + 1;
            if ($deneme >= 5) {
                $this->session->unset_userdata(array('totp_bekliyor', 'totp_deneme', 'totp_donus'));
                $this->session->set_flashdata('hata', 'Çok fazla hatalı kod. Yeniden giriş yapın.');
                redirect('yonetim/giris');
            }
            $this->session->set_userdata('totp_deneme', $deneme);
            $this->session->set_flashdata('hata', 'Kod hatalı veya süresi geçti.');
            redirect('yonetim/giris/totp');
        }

        $donus = (string) $this->session->userdata('totp_donus');
        $this->auth_admin->oturum_ac($y);
        redirect(($donus !== '' && strpos($donus, '//') === FALSE && preg_match('#^[A-Za-z0-9/_?=&.%-]+$#', $donus) === 1) ? ltrim($donus, '/') : 'yonetim/dashboard');
    }

    /**
     * LXIII: İki adımlı doğrulama kurulumu (giriş yapmış yönetici).
     * Aday anahtar oturumda tutulur; DOĞRU kod doğrulanmadan DB'ye yazılmaz.
     * Kurtarma kodları etkinleştirme anında BİR KEZ gösterilir (flash).
     */
    public function totp_kurulum()
    {
        if (! ($this->admin = $this->auth_admin->yonetici())) { redirect('yonetim/giris'); }
        $this->load->library('totp');

        $kayit = $this->yonetici_model->get($this->admin->id);
        $data['mevcut']         = ($kayit && ! empty($kayit->totp_secret));
        $data['sayfa_basligi']  = 'İki Adımlı Doğrulama';
        $data['menu_aktif']     = '';

        if (strtolower((string) $this->input->method()) === 'post') {
            $islem = (string) $this->input->post('islem');

            if ($islem === 'ac') {
                $aday = (string) $this->session->userdata('totp_aday');
                if ($aday !== '' && $this->totp->gecerli($aday, (string) $this->input->post('kod'))) {
                    $this->yonetici_model->totp_kaydet($this->admin->id, $aday);
                    $kodlar = $this->yonetici_model->kurtarma_uret($this->admin->id);
                    $this->session->unset_userdata('totp_aday');
                    $this->session->set_flashdata('kurtarma', implode(' ', $kodlar));
                    $this->session->set_flashdata('bilgi', 'İki adımlı doğrulama etkin. Kurtarma kodlarını ŞİMDİ kaydedin — bir daha gösterilmez.');
                    $this->auth_admin->audit('auth', 'totp_acik', 'self', $this->admin->email . ' 2FA açtı');
                } else {
                    $this->session->set_flashdata('hata', 'Kod hatalı — kimlik doğrulayıcıdaki güncel 6 haneli kodu girin.');
                }
                redirect('yonetim/giris/totp_kurulum');
            }

            if ($islem === 'kapat') {
                $sifre = (string) $this->input->post('sifre');
                $kod   = (string) $this->input->post('kod');
                if ($kayit && password_verify($sifre, $kayit->sifre) && ! empty($kayit->totp_secret) && $this->totp->gecerli($kayit->totp_secret, $kod)) {
                    $this->yonetici_model->totp_sil($this->admin->id);
                    $this->session->set_flashdata('bilgi', 'İki adımlı doğrulama kapatıldı.');
                    $this->auth_admin->audit('auth', 'totp_kapali', 'self', $this->admin->email . ' 2FA kapattı');
                } else {
                    $this->session->set_flashdata('hata', 'Şifre veya kod hatalı — kapatılmadı.');
                }
                redirect('yonetim/giris/totp_kurulum');
            }
            redirect('yonetim/giris/totp_kurulum');
        }

        $data['aday'] = (string) $this->session->userdata('totp_aday');
        if ($data['aday'] === '' && ! $data['mevcut']) {
            $data['aday'] = $this->totp->anahtar_uret();
            $this->session->set_userdata('totp_aday', $data['aday']);
        }
        $data['uri'] = ($data['aday'] !== '') ? $this->totp->uri($data['aday'], $this->admin->email, ayar('site_adi', 'Nesem Tesettür')) : '';
        $this->render('yonetim/giris/totp_kurulum', $data);
    }

    public function cikis()
    {
        $this->auth_admin->cikis();
        $this->session->set_flashdata('bilgi', 'Çıkış yapıldı.');
        redirect('yonetim/giris');
    }

    /**
     * Yönetici kendi parolasını değiştirir (LXII).
     * Seed admin parolası repoda bilinir — canlıda ilk iş bu ekrandan değiştirilir.
     * Not: Admin_Controller guard'ı Giris'te $this->admin doldurmaz — auth_admin'den alınır.
     */
    public function sifre()
    {
        if (! ($this->admin = $this->auth_admin->yonetici())) { redirect('yonetim/giris'); }
        $data['sayfa_basligi'] = 'Yönetici Parolası';
        $data['menu_aktif']    = '';
        $this->render('yonetim/giris/sifre', $data);
    }

    public function sifre_kaydet()
    {
        if (! ($this->admin = $this->auth_admin->yonetici())) { redirect('yonetim/giris'); }
        $this->form_validation->set_rules('eski', 'Mevcut Parola', 'trim|required');
        $this->form_validation->set_rules('yeni', 'Yeni Parola', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('yeni2', 'Yeni Parola (tekrar)', 'trim|required|matches[yeni]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', 'Yeni parola en az 6 karakter olmalı ve iki alan aynı olmalı.');
            redirect('yonetim/giris/sifre');
        }
        $y = $this->yonetici_model->by_email($this->admin->email);
        if (! $y || ! password_verify($this->input->post('eski'), $y->sifre)) {
            $this->session->set_flashdata('hata', 'Mevcut parola hatalı.');
            redirect('yonetim/giris/sifre');
        }
        $this->yonetici_model->sifre_guncelle($y->id, $this->input->post('yeni'));
        $this->yonetici_model->audit_log(array(
            'yonetici_id' => (int) $y->id, 'modul' => 'giris', 'islem' => 'parola_degistir',
            'hedef' => 'self', 'aciklama' => 'Yönetici kendi parolasını değiştirdi', 'ip' => $this->input->ip_address(),
        ));
        $this->session->set_flashdata('bilgi', 'Parolanız güncellendi.');
        redirect('yonetim/giris/sifre');
    }
}
