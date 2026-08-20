<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sepet extends Magaza_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sepet_model');
    }

    /** Sepet listesi. */
    public function index()
    {
        $data = $this->sepet_model->liste();
        $data['esik'] = (float) ayar('ucretsiz_kargo_esik', 2000);

        $this->v['meta_title']     = t('sepet_baslik', 'Sepetim') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;

        $this->render('magaza/sepet/index', $data);
    }

    /** Sepete ekle (AJAX — ürün detaydan). */
    public function ekle()
    {
        $urun_id    = (int) $this->input->post('urun_id');
        $varyant_id = (int) $this->input->post('varyant_id');
        $adet       = (int) $this->input->post('adet');
        if ($adet < 1) { $adet = 1; }

        $res = $this->sepet_model->ekle($urun_id, $varyant_id ?: NULL, $adet);
        $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    /** Adet güncelle (form POST → sepete dön). */
    public function guncelle($sepet_id = NULL)
    {
        if (! $sepet_id) { redirect('sepet'); }
        $adet = (int) $this->input->post('adet');
        $this->sepet_model->guncelle($sepet_id, $adet);
        $this->session->set_flashdata('bilgi', t('flash_adet_guncellendi', 'Adet güncellendi.'));
        redirect('sepet');
    }

    /** Satır sil. */
    public function sil($sepet_id = NULL)
    {
        if (! $sepet_id) { redirect('sepet'); }
        $this->sepet_model->sil($sepet_id);
        $this->session->set_flashdata('bilgi', t('flash_urun_cikarildi', 'Ürün sepetten çıkarıldı.'));
        redirect('sepet');
    }
}
