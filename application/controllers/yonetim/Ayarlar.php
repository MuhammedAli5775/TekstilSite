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
        'paytr_merchant_id', 'paytr_merchant_key', 'paytr_merchant_salt', 'paytr_test',
        'efatura_entegrator', 'efatura_api_url', 'efatura_token', 'efatura_firma_vkn', 'efatura_firma_unvan', 'efatura_test',
    );
    private $TOGGLES = array('arama_index', 'sms_aktif', 'paytr_test', 'efatura_test');

    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('ayarlar', 'goruntule');
    }

    public function index()
    {
        $this->load->model('ayar_model');
        $data['sayfa_basligi'] = 'Ayarlar';
        $data['menu_aktif']    = 'ayarlar';
        $data['ayarlar']       = $this->ayar_model->tum();
        // Pazaryeri kimlikleri ayarlarda değil, hesap tablosunda (Faz A / A5).
        $data['pazaryeri_hesap'] = $this->db->table_exists('pazaryeri_hesaplari')
            ? (int) $this->db->where('durum', 1)->count_all_results('pazaryeri_hesaplari')
            : 0;
        $this->render('yonetim/ayarlar/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('ayarlar', 'duzenle');
        $this->load->model('ayar_model');
        foreach ($this->WHITELIST as $k) {
            if (in_array($k, $this->TOGGLES, TRUE)) {
                $v = $this->input->post($k) ? '1' : '0';
            } elseif ($this->input->post($k) === NULL) {
                // POST'ta hiç gelmeyen alan (formda olmayan ya da kısmi gönderim):
                // mevcut değeri KORU — ezme. Boşaltmak isteyen boş string gönderir.
                // (Bug fix 2026-08-14: whitelist'te olup ayarlar formunda yer almayan
                // meta_title/duyuru_2/duyuru_3 her kayıtta NULL ile eziliyordu.)
                continue;
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
