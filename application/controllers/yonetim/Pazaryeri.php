<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pazaryeri (yönetim) — hesap CRUD + ürün eşleştirme + senkron (stok/fiyat, sipariş) + log.
 */
class Pazaryeri extends Admin_Controller
{
    private $PLATFORMLAR = array('trendyol' => 'Trendyol', 'hepsiburada' => 'Hepsiburada', 'n11' => 'N11', 'amazon' => 'Amazon');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pazaryeri_model');
        $this->load->library('pazaryeri_api');
    }

    public function index()
    {
        $duzenle_id = (int) $this->input->get('duzenle');
        $data = array(
            'sayfa_basligi' => 'Pazaryeri Hesapları',
            'menu_aktif'    => 'pazaryeri',
            'hesaplar'      => $this->pazaryeri_model->hesap_liste(),
            'platformlar'   => $this->PLATFORMLAR,
            'duzenle'       => $duzenle_id ? $this->pazaryeri_model->hesap_getir($duzenle_id) : NULL,
        );
        $this->render('yonetim/pazaryeri/index', $data);
    }

    public function hesap_kaydet()
    {
        $this->yetki_gerek('pazaryeri', 'duzenle');
        $platform = (string) $this->input->post('platform');
        if (! isset($this->PLATFORMLAR[$platform])) {
            $this->session->set_flashdata('hata', 'Geçersiz platform.');
            redirect('yonetim/pazaryeri');
        }
        $id = (int) $this->input->post('id');
        $d = array(
            'platform'    => $platform,
            'ad'          => trim((string) $this->input->post('ad')) ?: $this->PLATFORMLAR[$platform],
            'supplier_id' => trim((string) $this->input->post('supplier_id')) ?: NULL,
            'api_key'     => trim((string) $this->input->post('api_key')),
            'api_secret'  => trim((string) $this->input->post('api_secret')),
        );
        if ($id) {
            $this->pazaryeri_model->hesap_guncelle($id, $d);
            $this->auth_admin->audit('pazaryeri', 'guncelle', '#' . $id, $d['ad']);
        } else {
            $id = $this->pazaryeri_model->hesap_ekle($d);
            $this->auth_admin->audit('pazaryeri', 'ekle', '#' . $id, $d['ad']);
        }
        $this->session->set_flashdata('bilgi', 'Hesap kaydedildi.');
        redirect('yonetim/pazaryeri');
    }

    public function detay($id = NULL)
    {
        $id = (int) $id;
        $h = $this->pazaryeri_model->hesap_getir($id);
        if (! $h) { show_404(); }
        $data = array(
            'sayfa_basligi' => 'Pazaryeri: ' . $h->ad,
            'menu_aktif'    => 'pazaryeri',
            'h'             => $h,
            'platformlar'   => $this->PLATFORMLAR,
            'eslesmeler'    => $this->pazaryeri_model->eslesme_liste($id),
            'urunler'       => $this->db->where('durum', 1)->order_by('ad', 'ASC')->get('urunler')->result(),
            'loglar'        => $this->pazaryeri_model->log_liste($id),
        );
        $this->render('yonetim/pazaryeri/detay', $data);
    }

    public function eslesme_kaydet($hesap_id = NULL)
    {
        $this->yetki_gerek('pazaryeri', 'duzenle');
        $urun_id = (int) $this->input->post('urun_id');
        $paz_id  = trim((string) $this->input->post('pazaryeri_urun_id'));
        if (! $urun_id) {
            $this->session->set_flashdata('hata', 'Ürün seçin.');
            redirect('yonetim/pazaryeri/detay/' . (int) $hesap_id);
        }
        $this->pazaryeri_model->eslesme_ekle((int) $hesap_id, $urun_id, $paz_id);
        $this->auth_admin->audit('pazaryeri', 'eslesme', '#' . (int) $hesap_id, 'ürün ' . $urun_id);
        $this->session->set_flashdata('bilgi', 'Eşleştirme kaydedildi.');
        redirect('yonetim/pazaryeri/detay/' . (int) $hesap_id);
    }

    public function eslesme_sil($id = NULL)
    {
        $this->yetki_gerek('pazaryeri', 'duzenle');
        $this->pazaryeri_model->eslesme_sil((int) $id);
        $this->session->set_flashdata('bilgi', 'Eşleştirme kaldırıldı.');
        $ref = $this->input->server('HTTP_REFERER');
        redirect($ref ?: 'yonetim/pazaryeri');
    }

    public function stok_fiyat($id = NULL)
    {
        $this->yetki_gerek('pazaryeri', 'duzenle');
        $res = $this->pazaryeri_api->stok_fiyat_gonder((int) $id);
        $this->session->set_flashdata($res['ok'] ? 'bilgi' : 'hata', 'Stok/Fiyat: ' . $res['mesaj']);
        redirect('yonetim/pazaryeri/detay/' . (int) $id);
    }

    public function siparis_cek($id = NULL)
    {
        $this->yetki_gerek('pazaryeri', 'duzenle');
        $res = $this->pazaryeri_api->siparis_cek((int) $id);
        $this->session->set_flashdata($res['ok'] ? 'bilgi' : 'hata', 'Sipariş: ' . $res['mesaj']);
        redirect('yonetim/pazaryeri/detay/' . (int) $id);
    }

    public function durum($id = NULL)
    {
        $this->yetki_gerek('pazaryeri', 'duzenle');
        $h = $this->pazaryeri_model->hesap_getir((int) $id);
        if ($h) { $this->pazaryeri_model->hesap_durum((int) $id, $h->durum ? 0 : 1); }
        redirect('yonetim/pazaryeri');
    }

    public function sil($id = NULL)
    {
        $this->yetki_gerek('pazaryeri', 'sil');
        $this->pazaryeri_model->hesap_sil((int) $id);
        $this->auth_admin->audit('pazaryeri', 'sil', '#' . (int) $id);
        $this->session->set_flashdata('bilgi', 'Hesap silindi.');
        redirect('yonetim/pazaryeri');
    }
}
