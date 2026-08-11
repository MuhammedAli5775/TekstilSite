<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Feed (yönetim) — B2B API anahtar yönetimi: üret / listele / durum / sil.
 */
class Feed extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('api_anahtar_model');
    }

    public function index()
    {
        $data = array(
            'sayfa_basligi' => 'API / XML Feed Anahtarları',
            'menu_aktif'    => 'feed',
            'anahtarlar'    => $this->api_anahtar_model->liste(),
            'bayiler'       => $this->db->where('durum', 1)->order_by('firma_adi', 'ASC')->get('bayiler')->result(),
            'yeni_anahtar'  => $this->session->flashdata('yeni_anahtar'),
            'feed_url'      => site_url('feed/urunler'),
        );
        $this->render('yonetim/feed/index', $data);
    }

    public function olustur()
    {
        $this->yetki_gerek('feed', 'duzenle');
        $ad      = trim((string) $this->input->post('ad'));
        $bayi_id = (int) $this->input->post('bayi_id');
        if ($ad === '') { $ad = 'Feed anahtarı'; }

        $ham = $this->api_anahtar_model->olustur($ad, $bayi_id);
        if ($ham) {
            $this->auth_admin->audit('feed', 'olustur', '', $ad);
            $this->session->set_flashdata('yeni_anahtar', $ham);
            $this->session->set_flashdata('bilgi', 'Anahtar oluşturuldu. Ham anahtarı şimdi kopyalayın — bir daha gösterilmeyecek.');
        } else {
            $this->session->set_flashdata('hata', 'Anahtar oluşturulamadı.');
        }
        redirect('yonetim/feed');
    }

    public function durum($id = NULL)
    {
        $this->yetki_gerek('feed', 'duzenle');
        $id = (int) $id;
        $k = $this->api_anahtar_model->get($id);
        if ($k) {
            $this->api_anahtar_model->durum($id, $k->durum ? 0 : 1);
            $this->auth_admin->audit('feed', 'durum', '#' . $id, $k->durum ? 'pasif' : 'aktif');
        }
        redirect('yonetim/feed');
    }

    public function sil($id = NULL)
    {
        $this->yetki_gerek('feed', 'sil');
        $this->api_anahtar_model->sil((int) $id);
        $this->auth_admin->audit('feed', 'sil', '#' . (int) $id);
        $this->session->set_flashdata('bilgi', 'Anahtar silindi — feed erişimi kapandı.');
        redirect('yonetim/feed');
    }
}
