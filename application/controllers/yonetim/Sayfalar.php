<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sayfalar (yönetim) — CMS sayfaları CRUD (footer/kurumsal içerik).
 */
class Sayfalar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('sayfalar', 'goruntule');
        $this->load->model('sayfa_model');
    }

    /** Liste (arama). */
    public function index()
    {
        $data = array(
            'sayfa_basligi' => 'Sayfalar',
            'menu_aktif'    => 'sayfalar',
            'sayfalar'      => $this->sayfa_model->liste((string) $this->input->get('q')),
            'q'             => (string) $this->input->get('q'),
        );
        $this->render('yonetim/sayfalar/index', $data);
    }

    public function ekle()
    {
        $this->_form(NULL);
    }

    public function duzenle($id = NULL)
    {
        if (! $id) { redirect('yonetim/sayfalar'); }
        $s = $this->sayfa_model->getir($id);
        if (! $s) { show_404(); }
        $this->_form($s);
    }

    private function _form($s)
    {
        $data = array(
            'sayfa_basligi' => $s ? ('Sayfa Düzenle: ' . $s->baslik) : 'Yeni Sayfa',
            'menu_aktif'    => 'sayfalar',
            's'             => $s,
        );
        $this->render('yonetim/sayfalar/form', $data);
    }

    /** Ekle / güncelle (form POST). */
    public function kaydet()
    {
        $this->yetki_gerek('sayfalar', 'duzenle');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('baslik', 'Başlık', 'trim|required|max_length[190]');
        $id = (int) $this->input->post('id');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', 'Başlık zorunludur.');
            redirect($id ? 'yonetim/sayfalar/duzenle/' . $id : 'yonetim/sayfalar/ekle');
        }

        $d = array(
            'baslik'          => trim((string) $this->input->post('baslik')),
            'slug'            => trim((string) $this->input->post('slug')),
            'icerik'          => (string) $this->input->post('icerik'),
            'seo_title'       => trim((string) $this->input->post('seo_title')),
            'seo_description' => trim((string) $this->input->post('seo_description')),
            'durum'           => $this->input->post('durum') ? 1 : 0,
        );

        if ($id) {
            $this->sayfa_model->guncelle($id, $d);
            $this->auth_admin->audit('sayfalar', 'guncelle', '#' . $id, $d['baslik']);
        } else {
            $id = $this->sayfa_model->kaydet($d);
            $this->auth_admin->audit('sayfalar', 'ekle', '#' . $id, $d['baslik']);
        }
        $this->session->set_flashdata('bilgi', 'Sayfa kaydedildi.');
        redirect('yonetim/sayfalar');
    }

    public function sil($id = NULL)
    {
        if (! $id) { redirect('yonetim/sayfalar'); }
        $this->yetki_gerek('sayfalar', 'sil');
        $s = $this->sayfa_model->getir($id);
        $this->sayfa_model->sil($id);
        $this->auth_admin->audit('sayfalar', 'sil', '#' . $id, $s ? $s->baslik : '');
        $this->session->set_flashdata('bilgi', 'Sayfa silindi.');
        redirect('yonetim/sayfalar');
    }
}