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
        $this->load->library('form_validation');
        $this->form_validation->set_rules('email', 'E-posta', 'trim|required|valid_email');
        $this->form_validation->set_rules('sifre', 'Şifre', 'trim|required');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', 'Lütfen e-posta ve şifrenizi girin.');
            redirect('yonetim/giris');
        }
        $res = $this->auth_admin->giris_yap($this->input->post('email'), $this->input->post('sifre'));
        if (! $res['ok']) {
            $this->session->set_flashdata('hata', $res['mesaj']);
            redirect('yonetim/giris');
        }
        $donus = $this->input->post('donus');
        if ($donus && ! preg_match('#^https?://#i', $donus) && strpos($donus, '//') !== 0) {
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
}
