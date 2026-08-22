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
        $res = $this->auth_admin->giris_yap($this->input->post('email'), $this->input->post('sifre'));
        if (! $res['ok']) {
            $this->giris_koruma_model->deneme_kaydet('yonetim', $ip);   // LIX: IP sayacı
            $this->session->set_flashdata('hata', $res['mesaj']);
            redirect('yonetim/giris');
        }
        $this->giris_koruma_model->deneme_temizle('yonetim', $ip);   // LIX: başarılı giriş → IP sıfır
        $donus = $this->input->post('donus');
        // Yalnız güvenli göreli yol — 'https:/evil.com' tek-slash baypası kapanır (XXVIII):
        // karakter beyaz listesi (':' yok) + '//' yasak.
        if ($donus && strpos($donus, '//') === FALSE && preg_match('#^[A-Za-z0-9/_?=&.%-]+$#', $donus) === 1) {
            redirect(ltrim($donus, '/'));
        }
        redirect('yonetim/dashboard');
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
