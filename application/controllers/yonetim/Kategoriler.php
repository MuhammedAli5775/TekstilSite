<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kategoriler extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('kategoriler', 'goruntule');
        $this->load->model('kategori_model');
    }

    public function index()
    {
        $duzenle_id = (int) $this->input->get('duzenle');
        $data = array(
            'sayfa_basligi' => 'Kategoriler', 'menu_aktif' => 'kategoriler',
            'agac'          => $this->kategori_model->mg_admin_agac(),
            'ust_kategoriler' => $this->db->where('ust_id', NULL)->order_by('sira', 'ASC')->get('kategoriler')->result(),
            'duzenle'       => $duzenle_id ? $this->kategori_model->mg_getir($duzenle_id) : NULL,
        );
        $this->render('yonetim/kategoriler/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('kategoriler', 'duzenle');
        $id = (int) $this->input->post('id');
        $d = array(
            'ad'    => trim((string) $this->input->post('ad')),
            'ad_en' => trim((string) $this->input->post('ad_en')),   // XXXI: menü çevirisi; boşsa TR fallback
            'ad_ru' => trim((string) $this->input->post('ad_ru')),
            'ad_ar' => trim((string) $this->input->post('ad_ar')),
            'slug'  => trim((string) $this->input->post('slug')),
            'ust_id'=> $this->input->post('ust_id') ? (int) $this->input->post('ust_id') : NULL,
            'durum' => $this->input->post('durum') ? 1 : 0,
            'sira'  => (int) $this->input->post('sira'),
        );
        if ($d['ad'] === '') { show_404(); }
        // kendi kendine üst yapma
        if ($id && $d['ust_id'] === $id) { $d['ust_id'] = NULL; }

        if ($id) {
            $this->kategori_model->mg_guncelle($id, $d);
            $this->auth_admin->audit('kategoriler', 'guncelle', '#' . $id, $d['ad']);
        } else {
            $id = $this->kategori_model->mg_kaydet($d);
            $this->auth_admin->audit('kategoriler', 'ekle', '#' . $id, $d['ad']);
        }
        $this->session->set_flashdata('bilgi', 'Kategori kaydedildi.');
        redirect('yonetim/kategoriler');
    }

    public function sil($id = NULL)
    {
        if (! $id) { redirect('yonetim/kategoriler'); }
        $this->yetki_gerek('kategoriler', 'sil');
        if (! $this->kategori_model->mg_sil_kontrol($id)) {
            $this->session->set_flashdata('hata', 'Kategori silinemedi — alt kategorisi veya ürünü var.');
            redirect('yonetim/kategoriler');
        }
        $this->kategori_model->mg_sil($id);
        $this->auth_admin->audit('kategoriler', 'sil', '#' . $id);
        $this->session->set_flashdata('bilgi', 'Kategori silindi.');
        redirect('yonetim/kategoriler');
    }
}
