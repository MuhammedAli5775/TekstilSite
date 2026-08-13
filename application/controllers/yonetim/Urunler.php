<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Urunler extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('urunler', 'goruntule');
        $this->load->model(array('urun_model', 'kategori_model'));
    }

    public function index()
    {
        $filtre = array('q' => $this->input->get('q'), 'kategori_id' => $this->input->get('kategori_id'), 'durum' => $this->input->get('durum'));
        $limit = 20; $sayfa = max(1, (int) $this->input->get('sayfa')); $offset = ($sayfa - 1) * $limit;
        $toplam = $this->urun_model->mg_admin_liste_say($filtre);
        $data = array(
            'sayfa_basligi' => 'Ürünler', 'menu_aktif' => 'urunler',
            'urunler'       => $this->urun_model->mg_admin_liste($filtre, $limit, $offset),
            'toplam'        => $toplam, 'filtre' => $filtre,
            'kategoriler'   => $this->kategori_model->mg_admin_duz(), // düz liste (filtre select için)
            'sayfa'         => $sayfa, 'sayfa_sayisi' => max(1, (int) ceil($toplam / $limit)),
        );
        $this->render('yonetim/urunler/index', $data);
    }

    public function ekle()
    {
        $data = array(
            'sayfa_basligi' => 'Yeni Ürün', 'menu_aktif' => 'urunler',
            'u'             => NULL,
            'kategoriler'   => $this->kategori_model->mg_admin_duz(),
            'markalar'      => $this->db->where('durum', 1)->order_by('ad', 'ASC')->get('markalar')->result(),
        );
        $this->render('yonetim/urunler/form', $data);
    }

    public function duzenle($id)
    {
        $u = $this->urun_model->mg_admin_getir($id);
        if (! $u) { show_404(); }
        $data = array(
            'sayfa_basligi' => 'Ürün Düzenle: ' . $u->ad, 'menu_aktif' => 'urunler',
            'u'             => $u,
            'kategoriler'   => $this->kategori_model->mg_admin_duz(),
            'markalar'      => $this->db->where('durum', 1)->order_by('ad', 'ASC')->get('markalar')->result(),
        );
        $this->render('yonetim/urunler/form', $data);
    }

    /** Ekle / güncelle (form gönderimi). */
    public function kaydet()
    {
        $this->yetki_gerek('urunler', 'duzenle');
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ad', 'Ürün adı', 'trim|required|max_length[190]');
        $this->form_validation->set_rules('fiyat', 'Satış fiyatı', 'trim|required|numeric');
        $id = (int) $this->input->post('id');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('hata', 'Lütfen zorunlu alanları doldurun (ad, fiyat).');
            redirect($id ? 'yonetim/urunler/duzenle/' . $id : 'yonetim/urunler/ekle');
        }

        // Stok kodu boşsa ad'dan türet (unique değil — yalnızca kolaylık).
        $stok_kodu = trim((string) $this->input->post('stok_kodu'));
        if ($stok_kodu === '') {
            $stok_kodu = 'TS-' . strtoupper(substr(slug_tr($this->input->post('ad')), 0, 12)) . '-' . strtoupper(substr(uniqid(), -4));
        }

        $d = array(
            'ad'               => trim((string) $this->input->post('ad')),
            'slug'             => trim((string) $this->input->post('slug')),
            'stok_kodu'        => $stok_kodu,
            'kategori_id'      => $this->input->post('kategori_id') ? (int) $this->input->post('kategori_id') : NULL,
            'marka_id'         => $this->input->post('marka_id') ? (int) $this->input->post('marka_id') : NULL,
            'aciklama'         => $this->input->post('aciklama'),
            'alis_fiyat'       => (float) $this->input->post('alis_fiyat'),
            'fiyat'            => (float) $this->input->post('fiyat'),
            'eski_fiyat'       => (float) $this->input->post('eski_fiyat'),
            'kdv'              => (int) $this->input->post('kdv'),
            'moq'              => max(1, (int) $this->input->post('moq')),
            'birim_adim'       => max(1, (int) $this->input->post('birim_adim')),
            'vitrin'           => $this->input->post('vitrin') ? 1 : 0,
            'cok_satan'        => $this->input->post('cok_satan') ? 1 : 0,
            'durum'            => $this->input->post('durum') ? 1 : 0,
            'meta_title'       => $this->input->post('meta_title'),
            'meta_description' => $this->input->post('meta_description'),
        );

        $guncellendi = $id > 0;
        if ($guncellendi) {
            $this->urun_model->mg_guncelle($id, $d);
        } else {
            $id = $this->urun_model->mg_kaydet($d);
        }

        // Varyantlar (renk/beden/stok) — eskileri sil, yenileri ekle.
        $this->urun_model->mg_varyant_kaydet($id, (array) $this->input->post('varyant'));

        // Fiyat basamağı (ürüne özel adet indirimi) — eskileri sil, yenileri ekle.
        $this->urun_model->mg_basamak_kaydet($id, (array) $this->input->post('basamak'));

        // Görseller (çoklu yükleme, native doğrulama).
        foreach ($this->_gorseller_yukle() as $yol) {
            $this->urun_model->mg_gorsel_ekle($id, $yol);
        }

        // Ana görsel boşsa ilk görseli ata.
        $u = $this->urun_model->mg_admin_getir($id);
        if ($u && empty($u->ana_gorsel)) {
            $gorseller = $this->urun_model->mg_gorseller($id);
            if (! empty($gorseller)) {
                $this->urun_model->mg_gorsel_ana($id, $gorseller[0]->yol);
            }
        }

        $this->auth_admin->audit('urunler', $guncellendi ? 'guncelle' : 'ekle', '#' . $id, $d['ad']);
        $this->session->set_flashdata('bilgi', 'Ürün kaydedildi.');
        redirect('yonetim/urunler');
    }

    /** Durum aç/kapat (aktif↔pasif). */
    public function durum($id = NULL)
    {
        if (! $id) { redirect('yonetim/urunler'); }
        $this->yetki_gerek('urunler', 'duzenle');
        $u = $this->urun_model->mg_admin_getir($id);
        if (! $u) { show_404(); }
        $yeni = $u->durum ? 0 : 1;
        $this->urun_model->mg_durum($id, $yeni);
        $this->auth_admin->audit('urunler', 'durum', '#' . $id, $yeni ? 'aktif' : 'pasif');
        redirect('yonetim/urunler');
    }

    /** Soft-delete (deleted_at + durum=0); veri korunur. */
    public function sil($id = NULL)
    {
        if (! $id) { redirect('yonetim/urunler'); }
        $this->yetki_gerek('urunler', 'sil');
        $this->urun_model->mg_sil($id);
        $this->auth_admin->audit('urunler', 'sil', '#' . $id);
        $this->session->set_flashdata('bilgi', 'Ürün silindi (soft-delete).');
        redirect('yonetim/urunler');
    }

    /** Görsel sil (DB + yerel dosya). */
    public function gorsel_sil($gorsel_id = NULL)
    {
        if (! $gorsel_id) { redirect('yonetim/urunler'); }
        $this->yetki_gerek('urunler', 'sil');
        $yol = $this->urun_model->mg_gorsel_sil($gorsel_id);
        if ($yol && strpos($yol, 'http') !== 0) {
            $tam = FCPATH . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $yol), DIRECTORY_SEPARATOR);
            if (is_file($tam)) { @unlink($tam); }
        }
        redirect($this->input->server('HTTP_REFERER') ?: 'yonetim/urunler');
    }

    /** Görseli ana görsel yap (POST: gorsel_id). */
    public function gorsel_ana($id = NULL)
    {
        if (! $id) { redirect('yonetim/urunler'); }
        $this->yetki_gerek('urunler', 'duzenle');
        $gorsel_id = (int) $this->input->post('gorsel_id');
        $g = $this->db->where('id', $gorsel_id)->limit(1)->get('urun_gorselleri')->row();
        if ($g) {
            $this->urun_model->mg_gorsel_ana($id, $g->yol);
        }
        redirect('yonetim/urunler/duzenle/' . $id);
    }

    /**
     * Çoklu görsel yükleme (native doğrulama: uzantı whitelist + getimagesize +
     * is_uploaded_file + 4MB). CI3 Upload XAMPP'te güvenilmez → elle (workflow §2).
     * Başarılı: 'uploads/urunler/x.ext' yolları listesi.
     */
    private function _gorseller_yukle()
    {
        $yollar = array();
        if (empty($_FILES['gorseller']) || empty($_FILES['gorseller']['name'])) { return $yollar; }

        $izinli = array('jpg', 'jpeg', 'png', 'webp', 'gif');
        $klasor = FCPATH . 'uploads/urunler/';
        if (! is_dir($klasor)) { @mkdir($klasor, 0775, TRUE); }

        $f = $_FILES['gorseller'];
        $adet = is_array($f['name']) ? count($f['name']) : 0;
        for ($i = 0; $i < $adet; $i++) {
            if ($f['error'][$i] !== UPLOAD_ERR_OK) { continue; }
            $tmp = $f['tmp_name'][$i];
            if (! is_uploaded_file($tmp)) { continue; }
            $ext = strtolower(pathinfo($f['name'][$i], PATHINFO_EXTENSION));
            if (! in_array($ext, $izinli, TRUE)) { continue; }
            if (@getimagesize($tmp) === FALSE) { continue; }          // gerçek resim
            if ($f['size'][$i] > 4 * 1024 * 1024) { continue; }       // 4MB
            $ad = 'urun_' . bin2hex(random_bytes(5)) . '.' . $ext;
            if (@move_uploaded_file($tmp, $klasor . $ad)) {
                $yollar[] = 'uploads/urunler/' . $ad;
            }
        }
        return $yollar;
    }
}
