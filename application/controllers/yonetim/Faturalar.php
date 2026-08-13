<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Faturalar (yönetim) — e-fatura/e-arşiv: listele, siparişten oluştur,
 * durum sorgula, detay, sil. Sağlayıcı entegrasyonu Efatura library'sinde.
 */
class Faturalar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('faturalar', 'goruntule');
        $this->load->model('fatura_model');
        $this->load->library('efatura');
    }

    public function index()
    {
        $f = array('durum' => $this->input->get('durum'), 'q' => $this->input->get('q'));
        $data = array(
            'sayfa_basligi' => 'Faturalar',
            'menu_aktif'    => 'faturalar',
            'faturalar'     => $this->fatura_model->liste($f),
            'filtre'        => $f,
        );
        $this->render('yonetim/faturalar/index', $data);
    }

    /** Siparişten fatura oluştur (POST: tip=efatura|earsiv). */
    public function olustur($siparis_id = NULL)
    {
        $this->yetki_gerek('faturalar', 'duzenle');
        $tip = $this->input->post('tip') === 'efatura' ? 'efatura' : 'earsiv';
        $res = $this->efatura->olustur((int) $siparis_id, $tip);
        if ($res['ok']) {
            $this->auth_admin->audit('faturalar', 'olustur', '#' . ($res['fatura_id'] ?? 0), 'sipariş #' . (int) $siparis_id);
            $this->session->set_flashdata('bilgi', $res['mesaj']);
            redirect('yonetim/faturalar/detay/' . $res['fatura_id']);
        }
        $this->session->set_flashdata('hata', $res['mesaj']);
        redirect('yonetim/siparisler/detay/' . (int) $siparis_id);
    }

    public function detay($id = NULL)
    {
        $f = $this->fatura_model->get($id);
        if (! $f) { show_404(); }
        $this->load->model('siparis_model');
        $data = array(
            'sayfa_basligi' => 'Fatura #' . (int) $f->id,
            'menu_aktif'    => 'faturalar',
            'f'             => $f,
            's'             => $this->siparis_model->mg_admin_getir($f->siparis_id),
        );
        $this->render('yonetim/faturalar/detay', $data);
    }

    /** Asenkron işlem durumunu entegratörden yeniden sorgula. */
    public function yenile($id = NULL)
    {
        $this->yetki_gerek('faturalar', 'duzenle');
        $res = $this->efatura->durum_sorgula((int) $id);
        $this->session->set_flashdata($res['ok'] ? 'bilgi' : 'hata', $res['mesaj']);
        redirect('yonetim/faturalar/detay/' . (int) $id);
    }

    public function sil($id = NULL)
    {
        $this->yetki_gerek('faturalar', 'sil');
        $this->fatura_model->sil((int) $id);
        $this->auth_admin->audit('faturalar', 'sil', '#' . (int) $id);
        $this->session->set_flashdata('bilgi', 'Fatura kaydı silindi.');
        redirect('yonetim/faturalar');
    }
}
