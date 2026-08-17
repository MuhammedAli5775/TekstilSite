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

    /** Adres defteri kullanıcı (B2C) hesabına özel. */
    private function _kullanici_ozel()
    {
        if (! $this->kullanici()) {
            $this->session->set_flashdata('hata', 'Bu bölüm yalnızca kullanıcı hesapları içindir.');
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
        // $s sahiplik kontrolünden geçti — bu siparişin faturaları da oturum sahibinin.
        $this->load->model('fatura_model');
        $b = $this->_bayi_modu() ? $this->bayi() : $this->_kullanici_kart();
        $data = array(
            'b'         => $b,
            's'         => $s,
            'faturalar' => $this->fatura_model->siparis_faturalari($s->id),
            'menu_aktif'=> 'siparisler',
        );
        $this->v['meta_title']     = 'Sipariş #' . $s->siparis_no . ' — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/siparis_detay', $data);
    }

    /** Faturalarım (çift modlu — bayi: sipariş sahipliği, kullanıcı: sipariş e-postası). */
    public function faturalar()
    {
        $this->load->model('fatura_model');
        if ($this->_bayi_modu()) {
            $b         = $this->bayi();
            $faturalar = $this->fatura_model->mg_bayi_liste($b->id);
        } else {
            $b         = $this->_kullanici_kart();
            $faturalar = $this->fatura_model->mg_kullanici_liste($this->kullanici()->email);
        }
        $data = array(
            'b'          => $b,
            'faturalar'  => $faturalar,
            'menu_aktif' => 'faturalar',
        );
        $this->v['meta_title']     = 'Faturalarım — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/faturalar', $data);
    }

    public function bilgiler()
    {
        if (! $this->_bayi_modu()) { $this->_kullanici_bilgiler(); return; }
        $data = array('b' => $this->bayi(), 'menu_aktif' => 'bilgiler');
        $this->v['meta_title']     = 'Bilgilerim — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/bilgiler', $data);
    }

    /** Kullanıcı modu bilgilerim — bayi formundan ayrı view (firma/vergi alanı yok). */
    private function _kullanici_bilgiler($menu_aktif = 'bilgiler')
    {
        $data = array('b' => $this->_kullanici_kart(), 'menu_aktif' => $menu_aktif);
        $this->v['meta_title']     = 'Bilgilerim — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/kullanici_bilgiler', $data);
    }

    public function bilgiler_kaydet()
    {
        $this->load->library('form_validation');
        if ($this->_bayi_modu()) {
            $this->form_validation->set_rules('yetkili_ad_soyad', 'Ad Soyad', 'trim|required|max_length[120]');
        } else {
            $this->form_validation->set_rules('ad_soyad', 'Ad Soyad', 'trim|required|max_length[120]');
            $this->form_validation->set_rules('kullanici_adi', 'Kullanıcı Adı', 'trim|required|alpha_dash|min_length[3]|max_length[30]', array('alpha_dash' => 'Kullanıcı adı yalnızca harf, rakam, tire (-) ve alt çizgi (_) içerebilir.'));
        }
        $this->form_validation->set_rules('telefon', 'Telefon', 'trim|required|max_length[30]');
        if ($this->form_validation->run() === FALSE) { $this->bilgiler(); return; }

        if ($this->_bayi_modu()) {
            $b = $this->bayi();
            $this->bayi_model->bilgiler_guncelle($b->id, array(
                'yetkili_ad_soyad' => $this->input->post('yetkili_ad_soyad'),
                'telefon'          => $this->input->post('telefon'),
                'firma_adi'        => $this->input->post('firma_adi'),
                'vergi_no'         => $this->input->post('vergi_no'),
                'vergi_dairesi'    => $this->input->post('vergi_dairesi'),
            ));
            $this->bayi_cache = NULL; // önbelleği yenile
        } else {
            $k = $this->kullanici();
            $this->load->model('kullanici_model');
            $kadi = $this->input->post('kullanici_adi');
            if ($kadi !== NULL && ! $this->kullanici_model->kullanici_adi_musait($kadi, $k->id)) {
                $this->session->set_flashdata('hata', 'Bu kullanıcı adı alınmış. Farklı bir ad deneyin.');
                redirect('hesabim/bilgiler');
            }
            $veri = array(
                'ad_soyad' => $this->input->post('ad_soyad'),
                'telefon'  => $this->input->post('telefon'),
            );
            if ($kadi !== NULL) { $veri['kullanici_adi'] = $kadi; }
            $this->kullanici_model->bilgiler_guncelle($k->id, $veri);
            $this->kullanici_cache = NULL;
        }
        $this->session->set_flashdata('bilgi', 'Bilgileriniz güncellendi.');
        redirect('hesabim/bilgiler');
    }

    public function sifre()
    {
        $b = $this->_bayi_modu() ? $this->bayi() : $this->_kullanici_kart();
        $data = array('b' => $b, 'menu_aktif' => 'sifre');
        $this->v['meta_title']     = 'Şifre Değiştir — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/sifre', $data);
    }

    public function sifre_kaydet()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('eski', 'Eski şifre', 'trim|required');
        $this->form_validation->set_rules('yeni', 'Yeni şifre', 'trim|required|min_length[6]|max_length[60]');
        $this->form_validation->set_rules('yeni2', 'Yeni şifre tekrar', 'trim|required|matches[yeni]');

        if ($this->form_validation->run() === FALSE) { $this->sifre(); return; }

        if ($this->_bayi_modu()) {
            $b = $this->bayi();
            $taze = $this->bayi_model->get($b->id);
            if (! password_verify($this->input->post('eski'), $taze->sifre)) {
                $this->session->set_flashdata('hata', 'Eski şifre yanlış.');
                redirect('hesabim/sifre');
            }
            $this->bayi_model->sifre_guncelle($b->id, $this->input->post('yeni'));
        } else {
            $k = $this->kullanici();
            $this->load->model('kullanici_model');
            $taze = $this->kullanici_model->get($k->id);
            if (! password_verify($this->input->post('eski'), $taze->sifre)) {
                $this->session->set_flashdata('hata', 'Eski şifre yanlış.');
                redirect('hesabim/sifre');
            }
            $this->kullanici_model->sifre_guncelle($k->id, $this->input->post('yeni'));
        }
        $this->_oturum_dondur(); // şifre değişti — oturum ID'si de dönsün
        $this->session->set_flashdata('bilgi', 'Şifreniz güncellendi.');
        redirect('hesabim');
    }

    /* ---------------- Adres defteri (kullanıcı) ---------------- */
    public function adresler()
    {
        $this->_kullanici_ozel();
        $this->load->model('kullanici_model');
        $k = $this->kullanici();
        $duzenle = (int) $this->input->get('duzenle');
        $data = array(
            'b'          => $this->_kullanici_kart(),
            'adresler'   => $this->kullanici_model->adresler($k->id),
            'duzenlenen' => $duzenle ? $this->kullanici_model->adres_getir($k->id, $duzenle) : NULL,
            'menu_aktif' => 'adresler',
        );
        $this->v['meta_title']     = 'Adreslerim — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/hesabim/adresler', $data);
    }

    public function adres_kaydet()
    {
        $this->_kullanici_ozel();
        $this->load->library('form_validation');
        $this->form_validation->set_rules('ad_soyad', 'Ad Soyad', 'trim|required|max_length[120]');
        $this->form_validation->set_rules('adres', 'Adres', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('il', 'İl', 'trim|required|max_length[60]');
        $this->form_validation->set_rules('ilce', 'İlçe', 'trim|required|max_length[90]');
        $this->form_validation->set_rules('telefon', 'Telefon', 'trim|max_length[30]');
        $id = (int) $this->input->post('id');

        if ($this->form_validation->run() === FALSE) { redirect('hesabim/adresler' . ($id ? "?duzenle=$id" : '')); return; }

        $this->load->model('kullanici_model');
        // Düzenleme ID'si formdan gelir — sahiplik model katmanında da doğrulanır.
        $this->kullanici_model->adres_kaydet($this->kullanici()->id, array(
            'ad_soyad'   => $this->input->post('ad_soyad'),
            'adres'      => $this->input->post('adres'),
            'il'         => $this->input->post('il'),
            'ilce'       => $this->input->post('ilce'),
            'telefon'    => $this->input->post('telefon'),
            'tip'        => $this->input->post('tip'),
            'varsayilan' => $this->input->post('varsayilan'),
        ), $id ?: NULL);

        $this->session->set_flashdata('bilgi', $id ? 'Adres güncellendi.' : 'Adres eklendi.');
        redirect('hesabim/adresler');
    }

    public function adres_sil($id)
    {
        $this->_kullanici_ozel();
        $this->load->model('kullanici_model');
        $this->kullanici_model->adres_sil($this->kullanici()->id, (int) $id);
        $this->session->set_flashdata('bilgi', 'Adres silindi.');
        redirect('hesabim/adresler');
    }

    /** Kullanıcı modunda view'ların $b alanlarına uyumlu sade kart. */
    private function _kullanici_kart()
    {
        $k = $this->kullanici();
        return (object) array(
            'id'               => $k->id,
            'yetkili_ad_soyad' => $k->ad_soyad,
            'kullanici_adi'    => $k->kullanici_adi ?? NULL,
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
