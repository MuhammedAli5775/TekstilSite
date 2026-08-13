<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kuponlar (yönetim) — kupon/kampanya kodu CRUD. Checkout tarafında Odeme/Kupon_model uygular.
 */
class Kuponlar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('kuponlar', 'goruntule');
        $this->load->model('kupon_model');
    }

    /** Liste (arama: kod). */
    public function index()
    {
        $data = array(
            'sayfa_basligi' => 'Kuponlar',
            'menu_aktif'    => 'kuponlar',
            'kuponlar'      => $this->kupon_model->liste((string) $this->input->get('q')),
            'q'             => (string) $this->input->get('q'),
        );
        $this->render('yonetim/kuponlar/index', $data);
    }

    public function ekle()
    {
        $this->_form(NULL);
    }

    public function duzenle($id = NULL)
    {
        if (! $id) { redirect('yonetim/kuponlar'); }
        $k = $this->kupon_model->getir($id);
        if (! $k) { show_404(); }
        $this->_form($k);
    }

    private function _form($k)
    {
        $data = array(
            'sayfa_basligi' => $k ? ('Kupon Düzenle: ' . $k->kod) : 'Yeni Kupon',
            'menu_aktif'    => 'kuponlar',
            'k'             => $k,
        );
        $this->render('yonetim/kuponlar/form', $data);
    }

    /** Ekle / güncelle (POST). */
    public function kaydet()
    {
        $this->yetki_gerek('kuponlar', 'duzenle');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('kod', 'Kod', 'trim|required|max_length[60]');
        $this->form_validation->set_rules('tip', 'Tip', 'trim|required|in_list[yuzde,sabit]');
        $this->form_validation->set_rules('deger', 'Değer', 'trim|required|numeric|greater_than_equal_to[0]');
        $id = (int) $this->input->post('id');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', strip_tags(validation_errors()) ?: 'Kod / tip / değer zorunludur.');
            redirect($id ? 'yonetim/kuponlar/duzenle/' . $id : 'yonetim/kuponlar/ekle');
        }

        $dt = function ($v) { $v = trim((string) $v); return ($v === '' ? NULL : str_replace('T', ' ', $v)); };
        $d = array(
            'kod'             => strtoupper(trim((string) $this->input->post('kod'))),
            'aciklama'        => trim((string) $this->input->post('aciklama')),
            'tip'             => $this->input->post('tip'),
            'deger'           => (float) $this->input->post('deger'),
            'min_sepet_tutar' => (float) $this->input->post('min_sepet_tutar'),
            'max_indirim'     => (float) $this->input->post('max_indirim'),
            'baslangic_zaman' => $dt($this->input->post('baslangic_zaman')),
            'bitis_zaman'     => $dt($this->input->post('bitis_zaman')),
            'kullanim_limiti' => (int) $this->input->post('kullanim_limiti'),
            'durum'           => $this->input->post('durum') ? 1 : 0,
        );

        if ($id) {
            $this->kupon_model->guncelle($id, $d);
            $this->auth_admin->audit('kuponlar', 'guncelle', '#' . $id, $d['kod']);
        } else {
            $id = $this->kupon_model->kaydet($d);
            $this->auth_admin->audit('kuponlar', 'ekle', '#' . $id, $d['kod']);
        }
        $this->session->set_flashdata('bilgi', 'Kupon kaydedildi.');
        redirect('yonetim/kuponlar');
    }

    public function sil($id = NULL)
    {
        if (! $id) { redirect('yonetim/kuponlar'); }
        $this->yetki_gerek('kuponlar', 'sil');
        $k = $this->kupon_model->getir($id);
        $this->kupon_model->sil($id);
        $this->auth_admin->audit('kuponlar', 'sil', '#' . $id, $k ? $k->kod : '');
        $this->session->set_flashdata('bilgi', 'Kupon silindi.');
        redirect('yonetim/kuponlar');
    }
}