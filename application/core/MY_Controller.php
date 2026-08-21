<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Çekirdek controller tabanları.
 *  - Magaza_Controller : mağaza (storefront) yüzeyi
 *  - Admin_Controller  : yönetim paneli (Faz 4'te auth/yetki dolar)
 *
 * DB, autoload edilmez; burada lazy + db_debug=FALSE ile yüklenir —
 * DB bağlı değilken uygulama çökmez (Faz 0).
 */
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
}

class Magaza_Controller extends MY_Controller
{
    /** @var array view'lara taşınan ortak veri */
    public $v = array();

    /** @var object|false|null giriş yapmış bayi (önbellek) */
    protected $bayi_cache = NULL;

    public function __construct()
    {
        parent::__construct();
        // @ : DB bağlı değilken mysqli uyarısını sustur; durumu db_hazir() ile kontrol ederiz.
        // db_debug=FALSE zaten fatal atmayı engeller; @ yalnızca uyarı seviyesini temizler.
        @$this->load->database();
        $this->load->model('kategori_model');
        $this->_ortak_veri();
    }

    /** DB gerçekten bağlı mı? */
    protected function db_hazir()
    {
        return (isset($this->db) && is_object($this->db) && ! empty($this->db->conn_id));
    }

    /* ---------------- Bayi (B2B) oturum yardımları ---------------- */
    /** Giriş yapmış bayi_id veya null. */
    public function bayi_id()
    {
        $id = $this->session->userdata('bayi_id');
        return $id ? (int) $id : NULL;
    }

    /** Giriş yapmış bayi nesnesi (önbellekli) veya null. */
    public function bayi()
    {
        $id = $this->bayi_id();
        if (! $id) { return NULL; }
        if ($this->bayi_cache === NULL) {
            $this->load->model('bayi_model');
            $this->bayi_cache = $this->bayi_model->get($id) ?: FALSE;
        }
        return $this->bayi_cache ?: NULL;
    }

    public function bayi_giris_yap($bayi)
    {
        // Oturum sabitleme (fixation) koruması: yetki değişince ID döner.
        // Misafir sepeti eski anahtarda — transfer eski ID ile yapılır.
        $eski_sid = $this->session->session_id ?: session_id();
        $this->session->sess_regenerate();
        $this->session->set_userdata('bayi_id', (int) $bayi->id);
        $this->bayi_cache = $bayi;
        $this->load->model('sepet_model');
        $this->sepet_model->transfer_to_bayi($bayi->id, $eski_sid);
    }

    public function bayi_cikis()
    {
        $this->session->unset_userdata('bayi_id');
        $this->session->sess_regenerate();
        $this->bayi_cache = FALSE;
    }

    /* ---------------- Kullanıcı (B2C) oturum yardımları ---------------- */
    /** @var object|false|null giriş yapmış kullanıcı (önbellek) */
    protected $kullanici_cache = NULL;

    /** Giriş yapmış kullanıcı_id veya null. */
    public function kullanici_id()
    {
        $id = $this->session->userdata('kullanici_id');
        return $id ? (int) $id : NULL;
    }

    /** Giriş yapmış kullanıcı nesnesi (önbellekli) veya null. */
    public function kullanici()
    {
        $id = $this->kullanici_id();
        if (! $id) { return NULL; }
        if ($this->kullanici_cache === NULL) {
            $this->load->model('kullanici_model');
            $this->kullanici_cache = $this->kullanici_model->get($id) ?: FALSE;
        }
        return $this->kullanici_cache ?: NULL;
    }

    public function kullanici_giris_yap($kullanici)
    {
        $this->_oturum_dondur();   // fixation koruması + misafir sepetini yeni anahtara taşı
        $this->session->set_userdata('kullanici_id', (int) $kullanici->id);
        $this->kullanici_cache = $kullanici;
    }

    public function kullanici_cikis()
    {
        $this->session->unset_userdata('kullanici_id');
        $this->_oturum_dondur();
        $this->kullanici_cache = FALSE;
    }

    /**
     * Oturum ID'sini döndürür (fixation koruması) ve bayiye ait olmayan (misafir)
     * sepet satırlarını yeni anahtara taşır — B2C sepet anahtarı oturum_id'dir.
     */
    protected function _oturum_dondur()
    {
        $eski = $this->session->session_id ?: session_id();
        $this->session->sess_regenerate();
        $this->load->model('sepet_model');
        $this->sepet_model->oturum_tasi($eski, $this->session->session_id ?: session_id());
    }

    /** Mağaza çapında ortak veriyi hazırlar (menü, başlık, sepet sayısı, bayi, durum). */
    protected function _ortak_veri()
    {
        $this->load->model('kategori_model');
        $this->load->model('sepet_model');
        $this->v['site_adi']    = ayar('site_adi', 'Nesem Tesettür');
        $this->v['meta_title']  = '';
        $this->v['meta_desc']   = '';
        $this->v['menu']        = $this->db_hazir() ? $this->kategori_model->mg_menu() : array();
        // LI: footer güven şeridi — aktif ödeme yöntemleri + kargo firmaları DB'den;
        // yöntem/firma yönetimden açılınca rozet kendiliğinden belirir.
        $this->v['ftr_odemeler'] = $this->db_hazir() ? $this->db->where('durum', 1)->order_by('sira', 'ASC')->get('odeme_yontemleri')->result_array() : array();
        $this->v['ftr_kargolar'] = $this->db_hazir() ? $this->db->where('durum', 1)->order_by('ad', 'ASC')->get('kargo_firmalari')->result_array() : array();
        $this->v['sepet_adet']  = $this->db_hazir() ? $this->sepet_model->sayi() : 0;
        $this->v['bayi']        = $this->db_hazir() ? $this->bayi() : NULL;
        $this->v['bayi_indirim']= $this->v['bayi'] ? bayi_indirim() : 0.0;
        $this->v['kullanici']   = $this->db_hazir() ? $this->kullanici() : NULL;
        $this->v['db_hazir']    = $this->db_hazir();
        $this->v['v_be_layout'] = TRUE;
        // Çoklu dil (XXIX): TR varsayılan; seçici mağaza header'ındaki utility bar'da.
        $this->load->helper('dil');
        $this->v['dil']     = aktif_dil();
        $this->v['dil_adi'] = dil_adi($this->v['dil']);
        // XXXII: CI3 çekirdek mesajları (form_validation) aktif dile yüklensin;
        // yönetim paneli config varsayılanıyla (turkish) kalır.
        $this->config->set_item('language', dil_klasor($this->v['dil']));
    }

    /** Tam sayfa render (head + header + view + footer). */
    protected function render($view, $ek = array())
    {
        $data = array_merge($this->v, $ek);
        $this->load->view('magaza/layout/head', $data);
        $this->load->view('magaza/layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('magaza/layout/footer', $data);
    }
}

class Admin_Controller extends MY_Controller
{
    /** @var object giriş yapmış yönetici */
    public $admin = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(array('form_validation', 'auth_admin'));
        $this->load->model('yonetici_model');

        // Auth guard — Giris controller hariç her yerde giriş zorunlu
        if (strtolower(get_class($this)) !== 'giris') {
            if (! $this->auth_admin->logged_in()) {
                redirect('yonetim/giris?donus=' . urlencode(ltrim($this->uri->uri_string(), '/')));
            } else {
                $this->admin = $this->auth_admin->yonetici();
            }
        }
    }

    /** Yetki kontrolü — yoksa 403. */
    protected function yetki_gerek($modul, $islem = 'goruntule')
    {
        if (! $this->auth_admin->yetki($modul, $islem)) {
            show_error('Bu işlem için yetkiniz yok.', 403);
        }
    }

    /** Admin layout render (sidebar + topbar + içerik). */
    protected function render($view, $data = array())
    {
        $data['admin']      = $this->admin;
        $data['menu_aktif'] = $data['menu_aktif'] ?? '';
        $data['site_adi']   = ayar('site_adi', 'Nesem Tesettür');
        $data['menu']       = array(
            array('key' => 'dashboard',   'baslik' => 'Dashboard',   'url' => site_url('yonetim/dashboard'),   'ikon' => '▦'),
            array('key' => 'siparisler',  'baslik' => 'Siparişler',  'url' => site_url('yonetim/siparisler'),  'ikon' => '▥'),
            array('key' => 'urunler',     'baslik' => 'Ürünler',     'url' => site_url('yonetim/urunler'),     'ikon' => '▦'),
            array('key' => 'kategoriler', 'baslik' => 'Kategoriler', 'url' => site_url('yonetim/kategoriler'), 'ikon' => '≣'),
            array('key' => 'markalar',    'baslik' => 'Markalar',    'url' => site_url('yonetim/markalar'),    'ikon' => '◇'),
            array('key' => 'stok',        'baslik' => 'Stok',        'url' => site_url('yonetim/stok'),        'ikon' => '◫'),
            array('key' => 'bayiler',     'baslik' => 'Bayiler',     'url' => site_url('yonetim/bayiler'),     'ikon' => '◐'),
            array('key' => 'faturalar',   'baslik' => 'Faturalar',   'url' => site_url('yonetim/faturalar'),   'ikon' => '▤'),
            array('key' => 'pazaryeri',   'baslik' => 'Pazaryeri',   'url' => site_url('yonetim/pazaryeri'),   'ikon' => '⇄'),
            array('key' => 'feed',        'baslik' => 'API / Feed',  'url' => site_url('yonetim/feed'),        'ikon' => '⌁'),
            array('key' => 'xml_ice',     'baslik' => 'XML İçe Aktar', 'url' => site_url('yonetim/xml_ice'),  'ikon' => '⇩'),
            array('key' => 'raporlar',    'baslik' => 'Raporlar',    'url' => site_url('yonetim/raporlar'),    'ikon' => '◉'),
            array('key' => 'ebulten',     'baslik' => 'E-Bülten',    'url' => site_url('yonetim/ebulten'),     'ikon' => '✉'),
            array('key' => 'bannerlar',   'baslik' => 'Bannerlar',   'url' => site_url('yonetim/bannerlar'),   'ikon' => '▦'),
            array('key' => 'yazilar',     'baslik' => 'Blog Yazıları', 'url' => site_url('yonetim/yazilar'),  'ikon' => '✎'),
            array('key' => 'sayfalar',    'baslik' => 'Sayfalar',    'url' => site_url('yonetim/sayfalar'),    'ikon' => '☰'),
            array('key' => 'kuponlar',    'baslik' => 'Kuponlar',    'url' => site_url('yonetim/kuponlar'),    'ikon' => '✦'),
            array('key' => 'para_birimi', 'baslik' => 'Para Birimi', 'url' => site_url('yonetim/para_birimi'), 'ikon' => '¤'),
            array('key' => 'ayarlar',     'baslik' => 'Ayarlar',     'url' => site_url('yonetim/ayarlar'),     'ikon' => '⚙'),
            array('key' => 'yetkiler',    'baslik' => 'Yetki Matrisi', 'url' => site_url('yonetim/yetkiler'),  'ikon' => '⊕'),
        );
        // Rol bazlı menü filtreleme: süper (rol 1) tüm menüyü görür; dashboard her zaman
        // görünür; 'yetkiler' yalnız süperde; diğerleri yetki(modul,'goruntule') ile.
        if (isset($this->auth_admin) && $this->auth_admin->logged_in()) {
            $super = ($this->auth_admin->rol_id() === 1);
            $filtre = array();
            foreach ($data['menu'] as $m) {
                $key = $m['key'];
                if ($key === 'yetkiler')  { if ($super) { $filtre[] = $m; } continue; }   // süper only
                if ($key === 'dashboard') { $filtre[] = $m; continue; }                   // her zaman görünür
                if ($super)               { $filtre[] = $m; continue; }
                $modul = ($key === 'para_birimi') ? 'ayarlar' : $key;                     // para_birimi -> ayarlar izni
                $modul = ($key === 'ebulten') ? 'raporlar' : $modul;                     // LV: e-bülten -> raporlar izni
                if ($this->auth_admin->yetki($modul, 'goruntule')) { $filtre[] = $m; }
            }
            $data['menu'] = $filtre;
        }
        $this->load->view('yonetim/layout/head', $data);
        $this->load->view('yonetim/layout/sidebar', $data);
        $this->load->view('yonetim/layout/header', $data);
        $this->load->view($view, $data);
        $this->load->view('yonetim/layout/footer', $data);
    }

    /** Login sayfası (sidebarsız) için minimal render — login view'i kendi kapatır. */
    protected function render_bare($view, $data = array())
    {
        $data['site_adi'] = ayar('site_adi', 'Nesem Tesettür');
        $this->load->view('yonetim/layout/head', $data);
        $this->load->view($view, $data);
    }
}
