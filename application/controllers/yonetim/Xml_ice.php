<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xml_ice (yönetim) — tedarikçi XML kaynakları + önizleme + içe aktarım + log.
 * Önizleme/çalıştırma POST gövdesinde xml_metin kabul eder: boşsa kaynak URL'si
 * çekilir (test ve yapıştır-aktar akışı metni doğrudan verir).
 */
class Xml_ice extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('xml_ice', 'goruntule');
        $this->load->model('xml_ice_model');
    }

    public function index()
    {
        $duzenle_id = (int) $this->input->get('duzenle');
        $data = array(
            'sayfa_basligi' => 'XML İçe Aktarım',
            'menu_aktif'    => 'xml_ice',
            'kaynaklar'     => $this->xml_ice_model->kaynak_liste(),
            'kategoriler'   => $this->db->order_by('ad', 'ASC')->get('kategoriler')->result(),
            'duzenle'       => $duzenle_id ? $this->xml_ice_model->kaynak_getir($duzenle_id) : NULL,
        );
        $this->render('yonetim/xml_ice/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('xml_ice', 'duzenle');
        $id  = (int) $this->input->post('id');
        $ad  = trim((string) $this->input->post('ad'));
        $url = trim((string) $this->input->post('url'));
        if ($ad === '' || ! preg_match('#^https?://#i', $url)) {
            $this->session->set_flashdata('hata', 'Ad ve geçerli bir http(s) URL zorunludur.');
            redirect('yonetim/xml_ice');
        }
        $kid = $this->xml_ice_model->kaynak_kaydet($id, array(
            'ad'                     => $ad,
            'url'                    => $url,
            'esleme'                 => (string) $this->input->post('esleme'),
            'varsayilan_kategori_id' => (int) $this->input->post('varsayilan_kategori_id'),
            'fiyat_carpani'          => (string) $this->input->post('fiyat_carpani'),
            'yeni_urun_olustur'      => $this->input->post('yeni_urun_olustur'),
        ));
        if ($kid === FALSE) {
            $this->session->set_flashdata('hata', 'Eşleme JSON geçersiz (anahtar/değer etiket biçiminde olmalı).');
            redirect('yonetim/xml_ice');
        }
        $this->auth_admin->audit('xml_ice', $id ? 'guncelle' : 'ekle', '#' . $kid, $ad);
        $this->session->set_flashdata('bilgi', 'Kaynak kaydedildi.');
        redirect('yonetim/xml_ice');
    }

    public function durum($id = NULL)
    {
        $this->yetki_gerek('xml_ice', 'duzenle');
        $k = $this->xml_ice_model->kaynak_getir((int) $id);
        if ($k) { $this->xml_ice_model->kaynak_durum((int) $id, $k->durum ? 0 : 1); }
        redirect('yonetim/xml_ice');
    }

    public function sil($id = NULL)
    {
        $this->yetki_gerek('xml_ice', 'sil');
        $this->xml_ice_model->kaynak_sil((int) $id);
        $this->auth_admin->audit('xml_ice', 'sil', '#' . (int) $id);
        $this->session->set_flashdata('bilgi', 'Kaynak silindi (loglar da).');
        redirect('yonetim/xml_ice');
    }

    /** Kuru koşu — yazmadan sonuç (transaction rollback). GET: URL'den; POST xml_metin: yapıştırılan. */
    public function onizleme($id = NULL)
    {
        $k = $this->xml_ice_model->kaynak_getir((int) $id);
        if (! $k) { show_404(); }
        $xml_metin = (string) $this->input->post('xml_metin');
        $res = $this->xml_ice_model->ice_aktar($k, FALSE, $xml_metin);
        $data = array(
            'sayfa_basligi' => 'XML Önizleme: ' . $k->ad,
            'menu_aktif'    => 'xml_ice',
            'k'             => $k,
            'res'           => $res,
            'xml_metin'     => $xml_metin,
        );
        $this->render('yonetim/xml_ice/onizleme', $data);
    }

    /** Gerçek içe aktarım — yalnız POST (önizleme formundaki buton). */
    public function calistir($id = NULL)
    {
        $this->yetki_gerek('xml_ice', 'duzenle');
        if ($this->input->method() !== 'post') {
            $this->session->set_flashdata('hata', 'İçe aktarım POST ile yapılır (önizlemeden devam edin).');
            redirect('yonetim/xml_ice');
        }
        $k = $this->xml_ice_model->kaynak_getir((int) $id);
        if (! $k) { show_404(); }
        $res = $this->xml_ice_model->ice_aktar($k, TRUE, (string) $this->input->post('xml_metin'));
        $this->auth_admin->audit('xml_ice', 'ice_aktar', '#' . $k->id, $res['mesaj']);
        $this->session->set_flashdata($res['ok'] ? 'bilgi' : 'hata', $res['mesaj']);
        redirect('yonetim/xml_ice/log/' . (int) $id);
    }

    public function log($id = NULL)
    {
        $k = $this->xml_ice_model->kaynak_getir((int) $id);
        if (! $k) { show_404(); }
        $data = array(
            'sayfa_basligi' => 'XML Log: ' . $k->ad,
            'menu_aktif'    => 'xml_ice',
            'k'             => $k,
            'loglar'        => $this->xml_ice_model->log_liste((int) $id),
        );
        $this->render('yonetim/xml_ice/log', $data);
    }
}
