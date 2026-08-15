<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hesap — "Hesabım" alanı (giriş zorunlu, sahiplik izole).
 * İki mod: bayi (B2B — firma/indirim/fatura) VEYA kullanıcı (B2C — e-posta
 * eşleşmeli sipariş listesi). bilgiler/sifre yalnız bayi modunda.
 */
class Hesap extends Magaza_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->_giris_zorunlu();
        if ($this->bayi()) { $this->load->model('bayi_model'); }
    }

    private function _giris_zorunlu()
    {
        if (! $this->bayi() && ! $this->kullanici()) {
            $this->session->set_flashdata('hata', 'Bu sayfa için giriş yapmalısınız.');
            redirect('kullanici/giris?donus=' . urlencode(ltrim($this->uri->uri_string(), '/')));
        }
    }

    /** Bayi modu mı? (bilgiler/sifre bayiye özel) */
    private function _bayi_modu()
    {
        return (bool) $this->bayi();
    }

    private function _bayi_ozel()
    {
        if (! $this->_bayi_modu()) {
            $this->session->set_flashdata('hata', 'Bu bölüm yalnızca bayi hesapları içindir.');
            redirect('hesabim');
        }
    }

    public function index() { $this->dashboard(); }

    public function dashboard()
    {
        if ($this->_bayi_modu()) {
            $b = $this->bayi();
            $siparisler = $this->bayi_model->mg_siparisler($b->id);
        } else {
            $b = $this->_kullanici_kart();               // view'ların $b alanlarına uyumlu sade kart
            $this->load->model('kullanici_model');
            $siparisler = $this->kullanici_model->mg_siparisler($this->kullanici()->email);
        }
        $data = array(
            'b'             => $b,
            'indirim'       => $this->_bayi_modu() ? ($this->v['bayi_indirim'] ?? 0.0) : 0.0,
            'siparis_sayi'  => count($siparisler),
            'son_siparisler'=> array_slice($siparisler, 0, 5),
            'aktif_sayi'    => $this->_aktif_sayi($siparisler),
            'menu_aktif'    => 'dashboard',
        );
        $this->v['meta_title']     = 'Hesabım — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/dashboard', $data);
    }

    public function siparisler()
    {
        if ($this->_bayi_modu()) {
            $b          = $this->bayi();
            $siparisler = $this->bayi_model->mg_siparisler($b->id);
        } else {
            $b = $this->_kullanici_kart();
            $this->load->model('kullanici_model');
            $siparisler = $this->kullanici_model->mg_siparisler($this->kullanici()->email);
        }
        $data = array(
            'b'          => $b,
            'siparisler' => $siparisler,
            'menu_aktif' => 'siparisler',
        );
        $this->v['meta_title']     = 'Siparişlerim — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/siparisler', $data);
    }

    public function siparis_detay($id)
    {
        if ($this->_bayi_modu()) {
            $s = $this->bayi_model->mg_siparis_getir($this->bayi()->id, $id);
        } else {
            $this->load->model('kullanici_model');
            $s = $this->kullanici_model->mg_siparis_getir($this->kullanici()->email, $id);
        }
        if (! $s) { show_404(); }
        $b = $this->_bayi_modu() ? $this->bayi() : $this->_kullanici_kart();
        $data = array('b' => $b, 's' => $s, 'menu_aktif' => 'siparisler');
        $this->v['meta_title']     = 'Sipariş #' . $s->siparis_no . ' — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/siparis_detay', $data);
    }

    public function bilgiler()
    {
        $this->_bayi_ozel();
        $data = array('b' => $this->bayi(), 'menu_aktif' => 'bilgiler');
        $this->v['meta_title']     = 'Bilgilerim — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/bilgiler', $data);
    }

    public function bilgiler_kaydet()
    {
        $this->_bayi_ozel();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('yetkili_ad_soyad', 'Ad Soyad', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('telefon', 'Telefon', 'trim|required|max_length[30]');
        if ($this->form_validation->run() === FALSE) { $this->bilgiler(); return; }

        $b = $this->bayi();
        $this->bayi_model->bilgiler_guncelle($b->id, array(
            'yetkili_ad_soyad' => $this->input->post('yetkili_ad_soyad'),
            'telefon'          => $this->input->post('telefon'),
            'firma_adi'        => $this->input->post('firma_adi'),
            'vergi_no'         => $this->input->post('vergi_no'),
            'vergi_dairesi'    => $this->input->post('vergi_dairesi'),
        ));
        $this->bayi_cache = NULL; // önbelleği yenile
        $this->session->set_flashdata('bilgi', 'Bilgileriniz güncellendi.');
        redirect('hesabim/bilgiler');
    }

    public function sifre()
    {
        $this->_bayi_ozel();
        $data = array('b' => $this->bayi(), 'menu_aktif' => 'sifre');
        $this->v['meta_title']     = 'Şifre Değiştir — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/sifre', $data);
    }

    public function sifre_kaydet()
    {
        $this->_bayi_ozel();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('eski', 'Eski şifre', 'trim|required');
        $this->form_validation->set_rules('yeni', 'Yeni şifre', 'trim|required|min_length[6]|max_length[60]');
        $this->form_validation->set_rules('yeni2', 'Yeni şifre tekrar', 'trim|required|matches[yeni]');

        if ($this->form_validation->run() === FALSE) { $this->sifre(); return; }

        $b = $this->bayi();
        $taze = $this->bayi_model->get($b->id);
        if (! password_verify($this->input->post('eski'), $taze->sifre)) {
            $this->session->set_flashdata('hata', 'Eski şifre yanlış.');
            redirect('hesabim/sifre');
        }
        $this->bayi_model->sifre_guncelle($b->id, $this->input->post('yeni'));
        $this->session->set_flashdata('bilgi', 'Şifreniz güncellendi.');
        redirect('hesabim');
    }

    /** Kullanıcı modunda view'ların $b alanlarına uyumlu sade kart. */
    private function _kullanici_kart()
    {
        $k = $this->kullanici();
        return (object) array(
            'id'               => $k->id,
            'yetkili_ad_soyad' => $k->ad_soyad,
            'firma_adi'        => NULL,
            'email'            => $k->email,
            'telefon'          => $k->telefon,
        );
    }

    private function _aktif_sayi($siparisler)
    {
        $biten = array('teslim_edildi', 'iptal', 'iade_edildi');
        $n = 0;
        foreach ($siparisler as $s) { if (! in_array($s->durum, $biten, TRUE)) { $n++; } }
        return $n;
    }
}
