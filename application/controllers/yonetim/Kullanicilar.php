<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kullanicilar — B2C kullanıcı yönetimi (LVII): liste + arama + durum + şifre sıfırlama.
 * Erişim/menü: bayiler iznine eşlendi (müşteri hesapları yönetimi — para_birimi/ebulten
 * eşleme örneği izlendi; ayrı yetki modülü seed'i gerekmez).
 */
class Kullanicilar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('bayiler', 'goruntule');
    }

    public function index()
    {
        $q = trim((string) $this->input->get('q'));
        $this->db->order_by('id', 'DESC')->limit(200);
        if ($q !== '') {
            $this->db->group_start()->like('email', $q)->or_like('ad_soyad', $q)->or_like('kullanici_adi', $q)->group_end();
        }
        $data = array(
            'sayfa_basligi' => 'Kullanıcılar (B2C)',
            'menu_aktif'    => 'kullanicilar',
            'kullanicilar'  => $this->db->get('kullanicilar')->result(),
            'q'             => $q,
            'toplam'        => (int) $this->db->count_all('kullanicilar'),
        );
        $this->render('yonetim/kullanicilar/index', $data);
    }

    public function durum_guncelle($id)
    {
        $this->yetki_gerek('bayiler', 'duzenle');
        $d = $this->input->post('durum');
        if (! in_array($d, array('0', '1'), TRUE)) { show_404(); }
        $this->db->where('id', (int) $id)->update('kullanicilar', array('durum' => (int) $d));
        $this->auth_admin->audit('kullanicilar', 'durum', '#' . $id, 'durum=' . $d);
        $this->session->set_flashdata('bilgi', 'Kullanıcı durumu güncellendi: ' . ((int) $d === 1 ? 'Aktif' : 'Pasif') . '.');
        redirect('yonetim/kullanicilar');
    }

    /** LVII: kullanıcı şifresini rastgele sıfırla — flash'ta BİR KEZ gösterilir. */
    public function sifre_sifirla($id)
    {
        $this->yetki_gerek('bayiler', 'duzenle');
        $k = $this->db->where('id', (int) $id)->limit(1)->get('kullanicilar')->row();
        if (! $k) { show_404(); }
        $yeni = 'Nesem-' . bin2hex(random_bytes(4));
        $this->load->model('kullanici_model');
        $this->kullanici_model->sifre_guncelle($id, $yeni);
        $this->auth_admin->audit('kullanicilar', 'sifre', '#' . $id, 'sifirlandi');
        $this->session->set_flashdata('bilgi', 'Yeni şifre (' . $k->email . '): <b>' . $yeni . '</b> — kullanıcıya iletilmek üzere bir kez gösterilir.');
        redirect('yonetim/kullanicilar');
    }
}
