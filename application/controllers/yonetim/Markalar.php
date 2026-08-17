<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Markalar (yönetim) — marka CRUD (ürün formu marka select'i buradan beslenir).
 */
class Markalar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('markalar', 'goruntule');
        $this->load->model('marka_model');
    }

    /** Liste (arama). */
    public function index()
    {
        $data = array(
            'sayfa_basligi' => 'Markalar',
            'menu_aktif'    => 'markalar',
            'markalar'      => $this->marka_model->liste((string) $this->input->get('q')),
            'q'             => (string) $this->input->get('q'),
        );
        $this->render('yonetim/markalar/index', $data);
    }

    public function ekle()
    {
        $this->_form(NULL);
    }

    public function duzenle($id = NULL)
    {
        if (! $id) { redirect('yonetim/markalar'); }
        $m = $this->marka_model->getir($id);
        if (! $m) { show_404(); }
        $this->_form($m);
    }

    private function _form($m)
    {
        $data = array(
            'sayfa_basligi' => $m ? ('Marka Düzenle: ' . $m->ad) : 'Yeni Marka',
            'menu_aktif'    => 'markalar',
            'm'             => $m,
        );
        $this->render('yonetim/markalar/form', $data);
    }

    /** Ekle / güncelle (POST). */
    public function kaydet()
    {
        $this->yetki_gerek('markalar', 'duzenle');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ad', 'Marka adı', 'trim|required|max_length[120]');
        $id = (int) $this->input->post('id');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', 'Marka adı zorunludur.');
            redirect($id ? 'yonetim/markalar/duzenle/' . $id : 'yonetim/markalar/ekle');
        }

        $logo_yuklenen = $this->_logo_yukle();
        $logo = ($logo_yuklenen !== NULL)
            ? $logo_yuklenen
            : trim((string) $this->input->post('logo'));
        if ($logo === '' && $id) {
            $mevcut = $this->db->where('id', $id)->get('markalar')->row();
            $logo = $mevcut ? $mevcut->logo : '';
        }

        $d = array(
            'ad'    => trim((string) $this->input->post('ad')),
            'slug'  => trim((string) $this->input->post('slug')),
            'logo'  => $logo,
            'durum' => $this->input->post('durum') ? 1 : 0,
        );

        if ($id) {
            $this->marka_model->guncelle($id, $d);
            $this->auth_admin->audit('markalar', 'guncelle', '#' . $id, $d['ad']);
        } else {
            $id = $this->marka_model->kaydet($d);
            $this->auth_admin->audit('markalar', 'ekle', '#' . $id, $d['ad']);
        }
        $this->session->set_flashdata('bilgi', 'Marka kaydedildi.');
        redirect('yonetim/markalar');
    }

    public function sil($id = NULL)
    {
        if (! $id) { redirect('yonetim/markalar'); }
        $this->yetki_gerek('markalar', 'sil');
        if (! $this->marka_model->sil_kontrol($id)) {
            $this->session->set_flashdata('hata', 'Marka silinemedi — bu markaya ait ürünler var.');
            redirect('yonetim/markalar');
        }
        $m = $this->marka_model->getir($id);
        $this->marka_model->sil($id);
        $this->auth_admin->audit('markalar', 'sil', '#' . $id, $m ? $m->ad : '');
        $this->session->set_flashdata('bilgi', 'Marka silindi.');
        redirect('yonetim/markalar');
    }

    /** Native logo yükleme. Başarılı: 'uploads/markalar/x.ext'; yoksa NULL. */
    private function _logo_yukle()
    {
        if (empty($_FILES['logo_dosya']) || $_FILES['logo_dosya']['error'] !== UPLOAD_ERR_OK) { return NULL; }
        $izinli = array('jpg', 'jpeg', 'png', 'webp', 'gif'); // svg YOK — sanitizasyonsuz SVG kendi <script>'ini çalıştırır: stored-XSS vektörü (XXVIII)
        $tmp = $_FILES['logo_dosya']['tmp_name'];
        if (! is_uploaded_file($tmp)) { return NULL; }
        $ext = strtolower(pathinfo($_FILES['logo_dosya']['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, $izinli, TRUE)) { return NULL; }
        if (@getimagesize($tmp) === FALSE) { return NULL; } // gerçek resim
        if ($_FILES['logo_dosya']['size'] > 2 * 1024 * 1024) { return NULL; } // 2MB
        $klasor = FCPATH . 'uploads/markalar/';
        if (! is_dir($klasor)) { @mkdir($klasor, 0775, TRUE); }
        $ad = 'marka_' . bin2hex(random_bytes(5)) . '.' . $ext;
        if (! @move_uploaded_file($tmp, $klasor . $ad)) { return NULL; }
        return 'uploads/markalar/' . $ad;
    }
}