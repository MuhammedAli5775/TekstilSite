<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bannerlar — anasayfa hero slider yönetimi (bannerlar tablosu).
 *   index    — liste (sol) + ekle/düzenle formu (sağ)
 *   kaydet   — ekle/güncelle (görsel: dosya yükleme VEYA URL)
 *   sil      — banner + yerel görseli temizle
 *
 * Görsel alanı yerel upload (uploads/bannerlar/) veya uzak URL olabilir;
 * storefront gorsel_url() ile ikisini de çözer. CI3 Upload XAMPP'te
 * güvenilmez olduğu için native doğrulama kullanılır (workflow §2).
 */
class Bannerlar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('bannerlar', 'goruntule');
    }

    public function index()
    {
        $duzenle_id = (int) $this->input->get('duzenle');
        $bannerlar = $this->db->where('yer', 'anasayfa_slider')
            ->order_by('sira', 'ASC')->order_by('id', 'ASC')
            ->get('bannerlar')->result();
        $data = array(
            'sayfa_basligi' => 'Bannerlar',
            'menu_aktif'    => 'bannerlar',
            'bannerlar'     => $bannerlar,
            'duzenle'       => $duzenle_id ? $this->db->where('id', $duzenle_id)->get('bannerlar')->row() : NULL,
        );
        $this->render('yonetim/bannerlar/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('bannerlar', 'duzenle');
        $id = (int) $this->input->post('id');

        // Görsel: yükleme > URL > mevcut (düzenleme).
        $gorsel = trim((string) $this->input->post('gorsel_url'));
        if ($gorsel !== '' && ! preg_match('#^https://#i', $gorsel)) { $gorsel = ''; } // dış URL yalnız https — '../' veya şemasız yol DB'ye girmez (XXVIII)
        $yuklenen = $this->_gorsel_yukle();
        if ($yuklenen !== NULL) {
            $gorsel = $yuklenen;
        } elseif ($gorsel === '' && $id) {
            $mevcut = $this->db->where('id', $id)->get('bannerlar')->row();
            $gorsel = $mevcut ? $mevcut->gorsel : '';
        }

        $konum = $this->input->post('yazi_konum');
        $d = array(
            'yer'        => 'anasayfa_slider',
            'baslik'     => trim((string) $this->input->post('baslik')),
            'alt_baslik' => trim((string) $this->input->post('alt_baslik')),
            'gorsel'     => $gorsel,
            'link'       => trim((string) $this->input->post('link')),
            'buton_yazi' => trim((string) $this->input->post('buton_yazi')),
            'yazi_konum' => in_array($konum, array('sol', 'orta', 'sag'), TRUE) ? $konum : 'sol',
            'sira'       => (int) $this->input->post('sira'),
            'durum'      => $this->input->post('durum') ? 1 : 0,
        );

        if ($d['gorsel'] === '') {
            $this->session->set_flashdata('hata', 'Banner görseli zorunlu — dosya yükleyin veya görsel URL girin.');
            redirect('yonetim/bannerlar' . ($id ? '?duzenle=' . $id : ''));
        }

        if ($id) {
            // Görsel değiştiyse eski yerel dosyayı temizle.
            $this->_eski_gorseli_temizle($id, $gorsel);
            $this->db->where('id', $id)->update('bannerlar', $d);
            $this->auth_admin->audit('bannerlar', 'guncelle', '#' . $id, $d['baslik']);
        } else {
            $this->db->insert('bannerlar', $d);
            $id = $this->db->insert_id();
            $this->auth_admin->audit('bannerlar', 'ekle', '#' . $id, $d['baslik']);
        }
        $this->session->set_flashdata('bilgi', 'Banner kaydedildi.');
        redirect('yonetim/bannerlar');
    }

    public function sil($id = NULL)
    {
        if (! $id) { redirect('yonetim/bannerlar'); }
        $this->yetki_gerek('bannerlar', 'sil');
        $id = (int) $id;
        $b = $this->db->where('id', $id)->get('bannerlar')->row();
        if ($b) {
            $this->_yerel_gorsel_sil($b->gorsel);
            $this->db->where('id', $id)->delete('bannerlar');
            $this->auth_admin->audit('bannerlar', 'sil', '#' . $id, $b->baslik);
        }
        $this->session->set_flashdata('bilgi', 'Banner silindi.');
        redirect('yonetim/bannerlar');
    }

    /** Native tekil görsel yükleme. Başarılı: 'uploads/bannerlar/x.ext'; yoksa NULL. */
    private function _gorsel_yukle()
    {
        if (empty($_FILES['gorsel_dosya']) || $_FILES['gorsel_dosya']['error'] !== UPLOAD_ERR_OK) { return NULL; }
        $izinli = array('jpg', 'jpeg', 'png', 'webp', 'gif');
        $tmp = $_FILES['gorsel_dosya']['tmp_name'];
        if (! is_uploaded_file($tmp)) { return NULL; }
        $ext = strtolower(pathinfo($_FILES['gorsel_dosya']['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, $izinli, TRUE)) { return NULL; }
        if (@getimagesize($tmp) === FALSE) { return NULL; }            // gerçek resim
        if ($_FILES['gorsel_dosya']['size'] > 4 * 1024 * 1024) { return NULL; } // 4MB
        $klasor = FCPATH . 'uploads/bannerlar/';
        if (! is_dir($klasor)) { @mkdir($klasor, 0775, TRUE); }
        $ad = 'banner_' . bin2hex(random_bytes(5)) . '.' . $ext;
        if (! @move_uploaded_file($tmp, $klasor . $ad)) { return NULL; }
        return 'uploads/bannerlar/' . $ad;
    }

    /** Düzenlemede görsel değiştiyse eski YEREL dosyayı sil (URL'ye dokunma). */
    private function _eski_gorseli_temizle($id, $yeni_gorsel)
    {
        $mevcut = $this->db->where('id', $id)->get('bannerlar')->row();
        if (! $mevcut || ! $mevcut->gorsel || $mevcut->gorsel === $yeni_gorsel) { return; }
        $this->_yerel_gorsel_sil($mevcut->gorsel);
    }

    /** Yerel (http olmayan) görsel dosyasını diskten siler — yalnız uploads/ altına iner. */
    private function _yerel_gorsel_sil($gorsel)
    {
        if (! $gorsel || strpos($gorsel, 'http') === 0) { return; }
        $yol = FCPATH . ltrim($gorsel, '/');
        $gercek = realpath($yol);
        // Kapatma (XXVIII): DB'den gelen yola girilebilecek '../' ile docroot
        // dışına çıkıp rastgele dosya silme (path traversal) engellenir.
        if ($gercek === FALSE || strpos($gercek, realpath(FCPATH . 'uploads') . DIRECTORY_SEPARATOR) !== 0) { return; }
        if (is_file($gercek)) { @unlink($gercek); }
    }
}
