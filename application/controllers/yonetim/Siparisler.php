<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Siparisler extends Admin_Controller
{
    private $DURUMLAR = array(
        'onay_bekliyor' => 'Onay bekliyor', 'onaylandi' => 'Onaylandı', 'hazirlaniyor' => 'Hazırlanıyor',
        'kargolandi' => 'Kargolandı', 'teslim_edildi' => 'Teslim edildi', 'iptal' => 'İptal',
        'iade_talep' => 'İade talebi', 'iade_edildi' => 'İade edildi',
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('siparis_model');
    }

    public function index()
    {
        $filtre = array('durum' => $this->input->get('durum'), 'q' => $this->input->get('q'));
        $limit = 20; $sayfa = max(1, (int) $this->input->get('sayfa')); $offset = ($sayfa - 1) * $limit;
        $toplam = $this->siparis_model->mg_admin_liste_say($filtre);

        $data = array(
            'sayfa_basligi'  => 'Siparişler',
            'menu_aktif'     => 'siparisler',
            'siparisler'     => $this->siparis_model->mg_admin_liste($filtre, $limit, $offset),
            'toplam'         => $toplam,
            'filtre'         => $filtre,
            'durumlar'       => $this->DURUMLAR,
            'sayfa'          => $sayfa,
            'sayfa_sayisi'   => max(1, (int) ceil($toplam / $limit)),
        );
        $this->render('yonetim/siparisler/index', $data);
    }

    public function detay($id = NULL)
    {
        if (! $id) {
            $this->session->set_flashdata('hata', 'Sipariş belirtilmedi.');
            redirect('yonetim/siparisler');
        }
        $s = $this->siparis_model->mg_admin_getir($id);
        if (! $s) { show_404(); }
        $data = array(
            'sayfa_basligi'   => 'Sipariş #' . $s->siparis_no,
            'menu_aktif'      => 'siparisler',
            's'               => $s,
            'durumlar'        => $this->DURUMLAR,
            'kargo_firmalari' => $this->db->where('durum', 1)->order_by('ad', 'ASC')->get('kargo_firmalari')->result(),
            'faturalar'       => $this->db->where('siparis_id', (int) $id)->order_by('id', 'DESC')->get('faturalar')->result(),
        );
        $this->render('yonetim/siparisler/detay', $data);
    }

    public function durum_guncelle($id = NULL)
    {
        if (! $id) { redirect('yonetim/siparisler'); }
        $this->yetki_gerek('siparisler', 'duzenle');
        $durum = $this->input->post('durum');
        $notu  = $this->input->post('notu');

        if (! isset($this->DURUMLAR[$durum])) {
            $this->session->set_flashdata('hata', 'Geçersiz durum.');
            redirect('yonetim/siparisler/detay/' . $id);
        }
        // Kargolandı: takip no zorunlu
        if ($durum === 'kargolandi') {
            $takip = trim((string) $this->input->post('kargo_takip_no'));
            $firma = (int) $this->input->post('kargo_firma_id');
            if ($takip === '') {
                $this->session->set_flashdata('hata', 'Kargolandı durumunda takip numarası zorunludur.');
                redirect('yonetim/siparisler/detay/' . $id);
            }
            $this->siparis_model->mg_kargo_guncelle($id, $firma, $takip);
        }

        $this->siparis_model->mg_durum_guncelle($id, $durum, $notu);
        $this->auth_admin->audit('siparisler', 'durum_guncelle', '#' . $id, 'durum=' . $durum);

        // Bayiye durum bildirimi (graceful — SMTP yoksa atlar)
        $de = $this->DURUMLAR[$durum];
        $this->load->library('eposta');
        @$this->eposta->durum_bildirim($id, $de, $notu);

        // SMS durum bildirimi (graceful — pasif/hata akışı bozmaz)
        $this->load->library('sms');
        @$this->sms->durum_bildirim($id, $de, $notu);

        $this->session->set_flashdata('bilgi', 'Sipariş durumu güncellendi: ' . $de);
        redirect('yonetim/siparisler/detay/' . $id);
    }
}
