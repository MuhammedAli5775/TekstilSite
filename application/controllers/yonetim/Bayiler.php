<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bayiler extends Admin_Controller
{
    private $DURUMLAR = array('0' => 'Onay bekliyor', '1' => 'Aktif', '2' => 'Pasif');

    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('bayiler', 'goruntule');
        $this->load->model('bayi_model');
    }

    public function index()
    {
        $filtre = array('durum' => $this->input->get('durum'), 'q' => $this->input->get('q'));
        $limit = 20; $sayfa = max(1, (int) $this->input->get('sayfa')); $offset = ($sayfa - 1) * $limit;
        $toplam = $this->bayi_model->mg_admin_liste_say($filtre);
        $data = array(
            'sayfa_basligi'  => 'Bayiler',
            'menu_aktif'     => 'bayiler',
            'bayiler'        => $this->bayi_model->mg_admin_liste($filtre, $limit, $offset),
            'toplam'         => $toplam,
            'filtre'         => $filtre,
            'durumlar'       => $this->DURUMLAR,
            'sayfa'          => $sayfa,
            'sayfa_sayisi'   => max(1, (int) ceil($toplam / $limit)),
        );
        $this->render('yonetim/bayiler/index', $data);
    }

    public function detay($id)
    {
        $b = $this->bayi_model->mg_admin_getir($id);
        if (! $b) { show_404(); }
        $data = array(
            'sayfa_basligi' => 'Bayi: ' . ($b->firma_adi ?: $b->yetkili_ad_soyad),
            'menu_aktif'    => 'bayiler',
            'b'             => $b,
            'durumlar'      => $this->DURUMLAR,
            'gruplar'       => $this->bayi_model->gruplar(),
            'ozet'          => $this->bayi_model->bayi_siparis_ozet($id),
            'siparisler'    => $this->db->where('bayi_id', (int) $id)->order_by('id', 'DESC')->limit(10)->get('siparisler')->result(),
        );
        $this->render('yonetim/bayiler/detay', $data);
    }

    public function durum_guncelle($id)
    {
        $this->yetki_gerek('bayiler', 'duzenle');
        $d = (int) $this->input->post('durum');
        if (! isset($this->DURUMLAR[$d])) { show_404(); }
        $this->bayi_model->mg_durum_guncelle($id, $d);
        $this->auth_admin->audit('bayiler', 'durum', '#' . $id, 'durum=' . $this->DURUMLAR[$d]);
        $this->session->set_flashdata('bilgi', 'Bayi durumu güncellendi: ' . $this->DURUMLAR[$d]);
        redirect('yonetim/bayiler/detay/' . $id);
    }

    public function grup_guncelle($id)
    {
        $this->yetki_gerek('bayiler', 'duzenle');
        $g = (int) $this->input->post('grup_id');
        $this->bayi_model->mg_grup_guncelle($id, $g);
        $this->auth_admin->audit('bayiler', 'grup', '#' . $id, 'grup_id=' . $g);
        $this->session->set_flashdata('bilgi', 'Bayi grubu güncellendi.');
        redirect('yonetim/bayiler/detay/' . $id);
    }
}
