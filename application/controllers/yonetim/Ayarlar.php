<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ayarlar extends Admin_Controller
{
    private $WHITELIST = array(
        'site_adi', 'meta_title', 'meta_description', 'meta_keywords',
        'iletisim_telefon', 'iletisim_eposta', 'iletisim_adres', 'whatsapp',
        'ucretsiz_kargo_esik', 'duyuru_1', 'duyuru_2', 'duyuru_3',
        'arama_index', 'ga_id', 'fb_pixel', 'facebook_domain_verification', 'google_site_verification',
        'smtp_sunucu', 'smtp_port', 'smtp_sifrelem', 'smtp_kullanici', 'smtp_sifre', 'gonderen_eposta',
        'sms_aktif', 'sms_kullanici', 'sms_sifre', 'sms_gonderen',
    );
    private $TOGGLES = array('arama_index', 'sms_aktif');

    public function index()
    {
        $this->load->model('ayar_model');
        $data['sayfa_basligi'] = 'Ayarlar';
        $data['menu_aktif']    = 'ayarlar';
        $data['ayarlar']       = $this->ayar_model->tum();
        $this->render('yonetim/ayarlar/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('ayarlar', 'duzenle');
        $this->load->model('ayar_model');
        foreach ($this->WHITELIST as $k) {
            if (in_array($k, $this->TOGGLES, TRUE)) {
                $v = $this->input->post($k) ? '1' : '0';
            } else {
                $v = $this->input->post($k);
            }
            $this->ayar_model->upsert($k, $v);
        }
        $this->auth_admin->audit('ayarlar', 'kaydet', '', 'Ayarlar güncellendi');
        $this->session->set_flashdata('bilgi', 'Ayarlar kaydedildi.');
        redirect('yonetim/ayarlar');
    }
}
