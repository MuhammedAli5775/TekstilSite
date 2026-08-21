<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Odeme extends Magaza_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->_giris_zorunlu();
        $this->load->model('sepet_model');
        $this->load->model('siparis_model');
    }

    /**
     * Misafir siparişi kapalı (XLIV): ödeme adımı bayi VEYA kullanıcı girişi
     * ister. Sepet serbest — misafir sepeti giriş anında hesaba devrolur
     * (MY_Controller::_oturum_dondur / transfer_to_bayi), donus=odeme ile akış
     * kaldığı yerden sürer.
     */
    private function _giris_zorunlu()
    {
        if (! $this->bayi() && ! $this->kullanici()) {
            // L: POST-only eyleme (tamamla) denk gelen misafir, giriş sonrası
            // boş-POST tamamla'ya değil doğrudan ödeme formuna düşsün.
            $uri  = ltrim($this->uri->uri_string(), '/');
            $uri  = ($uri === 'odeme/tamamla') ? 'odeme' : $uri;
            $this->session->set_flashdata('hata', t('flash_odeme_giris_gerekli', 'Sipariş vermek için giriş yapmalısınız.'));
            redirect('kullanici/giris?donus=' . urlencode($uri));
        }
    }

    /** Checkout formu. */
    public function index()
    {
        $liste = $this->sepet_model->liste();
        if (empty($liste['satirlar'])) {
            $this->session->set_flashdata('bilgi', t('flash_odeme_sepet_bos_a', 'Ödemeye geçmek için önce sepete ürün ekleyin.'));
            redirect('sepet');
        }

        $data = $liste;
        $data['esik']           = (float) ayar('ucretsiz_kargo_esik', 2000);
        $data['bayi']           = $this->bayi();
        $data['iller']          = $this->db->order_by('ad', 'ASC')->get('iller')->result();
        $data['odeme_yontemleri'] = $this->db->where('durum', 1)->order_by('sira', 'ASC')->get('odeme_yontemleri')->result();
        $data['kargo_firmalari']  = $this->db->where('durum', 1)->order_by('ad', 'ASC')->get('kargo_firmalari')->result();
        $data['banka_hesaplari']  = $this->db->where('durum', 1)->order_by('banka_adi', 'ASC')->get('banka_hesaplari')->result();

        // Uygulanan kupon (session) + TRY indirimi (özet gösterimi için).
        $kupon_kod = (string) $this->session->userdata('kupon');
        $data['kupon_kod']     = $kupon_kod;
        $data['kupon_indirim'] = 0.0;
        $data['kupon_mesaj']   = '';
        if ($kupon_kod !== '') {
            $this->load->model('kupon_model');
            $kr = $this->kupon_model->dogrula($kupon_kod, (float) $liste['ara_toplam']);
            $data['kupon_indirim'] = $kr['ok'] ? (float) $kr['indirim'] : 0.0;
            if (! $kr['ok']) { $data['kupon_mesaj'] = $kr['mesaj']; }
        }

        $this->v['meta_title']     = t('odeme_baslik', 'Ödeme') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;

        $this->render('magaza/odeme/index', $data);
    }

    /** Siparişi oluştur. */
    public function tamamla()
    {
        $liste = $this->sepet_model->liste();
        if (empty($liste['satirlar'])) {
            $this->session->set_flashdata('bilgi', t('flash_odeme_sepet_bos_b', 'Sepetiniz boş.'));
            redirect('sepet');
        }

        $this->load->library('form_validation');
        // XLIX: alan biçimi + DB tutarlılığı (il / kargo / ödeme yöntemi) kuralları.
        // Regex/callback mesajları 4 dilde; hatalı POST inline render yerine PRG
        // ile odeme'ye döner (POST URL'de kalmasın — tarayıcı yenilemesi tekrar
        // sipariş denemesin).
        $this->form_validation->set_message('_il_gecerli', t('val_il_gecersiz', 'Geçersiz il seçimi.'));
        $this->form_validation->set_message('_kargo_gecerli', t('val_kargo_gecersiz', 'Geçersiz kargo firması.'));
        $this->form_validation->set_message('_odeme_yontemi_gecerli', t('val_odeme_yontem_gecersiz', 'Geçersiz ödeme yöntemi.'));
        $this->form_validation->set_rules('teslimat_ad', t('odeme_ad_soyad', 'Ad Soyad'), 'trim|required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('teslimat_adres', t('odeme_adres', 'Adres'), 'trim|required|min_length[10]|max_length[500]');
        $this->form_validation->set_rules('teslimat_il', t('odeme_il', 'İl'), 'trim|required|callback__il_gecerli');
        $this->form_validation->set_rules('teslimat_ilce', t('odeme_ilce', 'İlçe'), 'trim|max_length[60]');
        $this->form_validation->set_rules('teslimat_telefon', t('odeme_telefon', 'Telefon'), 'trim|required|max_length[20]|regex_match[/^\+?[0-9 ()-]{10,19}$/]', array('regex_match' => t('val_telefon_gecersiz', 'Telefon biçimi geçersiz (örn. 5xx xxx xx xx).')));
        // E-posta kuralı yalnız bayi için — misafir ödeme kapalı (XLIV), giriş
        // yapmış KULLANICININ siparişi hesabının e-postasına işlenir (form değeri
        // eşleşmeyi koparamaz, XXV).
        if (! $this->kullanici()) {
            $this->form_validation->set_rules('email', t('odeme_eposta', 'E-posta'), 'trim|required|valid_email|max_length[150]');
        }
        $this->form_validation->set_rules('fatura_ad', t('odeme_fatura_ad', 'Fatura Ad / Ünvan'), 'trim|max_length[150]');
        $this->form_validation->set_rules('fatura_adres', t('odeme_fatura_adres', 'Fatura Adresi'), 'trim|max_length[500]');
        $this->form_validation->set_rules('firma_adi', t('odeme_firma_unvan', 'Firma Ünvanı'), 'trim|max_length[150]');
        $this->form_validation->set_rules('vergi_no', t('odeme_vergi_no', 'Vergi / TC No'), 'trim|max_length[13]');
        $this->form_validation->set_rules('odeme_yontemi', t('odeme_yontem', 'Ödeme Yöntemi'), 'trim|required|callback__odeme_yontemi_gecerli');
        $this->form_validation->set_rules('kargo_firma_id', t('odeme_kargo_firma', 'Kargo Firması'), 'trim|required|integer|callback__kargo_gecerli');
        $this->form_validation->set_rules('sozlesme', 'Sözleşme onayı', 'trim|required', array('required' => t('val_sozlesme_odeme', 'Mesafeli satış sözleşmesini onaylamanız gerekir.')));

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('bilgi', t('flash_zorunlu_alan', 'Lütfen zorunlu alanları doğru biçimde doldurun.'));
            redirect('odeme');
        }

        $fatura_ayni = (bool) $this->input->post('fatura_ayni');
        $g = array(
            'bayi_id'          => $this->bayi_id(), // giriş yapmış bayi veya NULL
            'teslimat_ad'      => $this->input->post('teslimat_ad'),
            'teslimat_adres'   => $this->input->post('teslimat_adres'),
            'teslimat_il'      => $this->input->post('teslimat_il'),
            'teslimat_ilce'    => $this->input->post('teslimat_ilce'),
            'teslimat_telefon' => $this->input->post('teslimat_telefon'),
            'email'            => $this->kullanici() ? $this->kullanici()->email : $this->input->post('email'), // kullanıcı: hesap e-postası otoriter
            'fatura_ad'        => $fatura_ayni ? $this->input->post('teslimat_ad') : $this->input->post('fatura_ad'),
            'fatura_adres'     => $fatura_ayni ? $this->input->post('teslimat_adres') : $this->input->post('fatura_adres'),
            'firma_adi'        => $this->input->post('firma_adi'),
            'vergi_no'         => $this->input->post('vergi_no'),
            'odeme_yontemi'    => $this->input->post('odeme_yontemi'),
            'kargo_firma_id'   => (int) $this->input->post('kargo_firma_id'),
        );

        $res = $this->siparis_model->mg_olustur($g);
        if (! $res['ok']) {
            $this->session->set_flashdata('bilgi', $res['mesaj']);
            redirect('odeme');
        }

        // Onay e-postası (graceful — başarısızsa siparişi bozmaz)
        $this->load->library('eposta');
        $this->eposta->siparis_onay($res['siparis_id']);

        // SMS bildirimi (graceful — pasif/hata siparişi bozmaz)
        $this->load->library('sms');
        @$this->sms->siparis_onay($res['siparis_id']);

        $this->session->set_userdata('son_siparis_id', $res['siparis_id']);

        // PayTR (kartlı): iframe ödeme sayfasına yönlen; gerçek onay callback'te.
        if ($this->input->post('odeme_yontemi') === 'paytr') {
            $this->load->library('paytr_api');
            if ($this->paytr_api->hazir()) {
                redirect('paytr/ode/' . $res['siparis_id']);
            }
        }

        redirect('odeme/basarili');
    }

    /** Başarı sayfası (tek seferlik — session). */
    /** Kupon uygula (POST kodu → doğrula → session). PRG. */
    public function kupon_uygula()
    {
        $this->load->model('kupon_model');
        $kod   = (string) $this->input->post('kod');
        $liste = $this->sepet_model->liste();
        $r = $this->kupon_model->dogrula($kod, (float) ($liste['ara_toplam'] ?? 0));
        if ($r['ok']) {
            $this->session->set_userdata('kupon', $r['kupon']->kod);
            $this->session->set_flashdata('bilgi', t('flash_kupon_uygulandi', 'Kupon uygulandı: -%s (%s).', para_goster($r['indirim']), e($r['kupon']->kod)));
        } else {
            $this->session->set_flashdata('hata', $r['mesaj']);
        }
        redirect('odeme');
    }

    /** Uygulanan kuponu kaldır. */
    public function kupon_kaldir()
    {
        $this->session->unset_userdata('kupon');
        $this->session->set_flashdata('bilgi', t('flash_kupon_kaldirildi', 'Kupon kaldırıldı.'));
        redirect('odeme');
    }

    public function basarili()
    {
        $sid = (int) $this->session->userdata('son_siparis_id');
        if (! $sid) { redirect(''); }
        $sip = $this->siparis_model->mg_getir($sid);
        if (! $sip) { redirect(''); }
        $this->session->unset_userdata('son_siparis_id');

        $this->v['meta_title']     = t('sonuc_title', 'Sipariş Alındı') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;

        $this->render('magaza/odeme/basarili', array('sip' => $sip));
    }

    /* ---------- XLIX validasyon callback'leri ---------- */

    /** İl, iller tablosunda var mı — Türkçe/asgi normalizasyonla (İstanbul=Istanbul). */
    public function _il_gecerli($girilen)
    {
        if ($girilen === '') { return TRUE; }   // required kendi hata mesajını basar
        $harf = array('İ' => 'i', 'I' => 'i', 'Ş' => 's', 'Ğ' => 'g', 'Ü' => 'u', 'Ö' => 'o', 'Ç' => 'c',
                      'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c');
        $norm = function ($s) use ($harf) {
            return preg_replace('/[^a-z0-9]/', '', mb_strtolower(strtr((string) $s, $harf), 'UTF-8'));
        };
        $girdi = $norm($girilen);
        if ($girdi === '') { return FALSE; }
        foreach ($this->db->select('ad')->get('iller')->result() as $il) {
            if ($norm($il->ad) === $girdi) { return TRUE; }
        }
        return FALSE;
    }

    /** Kargo firması AKTİF kayıt mı (durum=1)? */
    public function _kargo_gecerli($id)
    {
        if ((int) $id <= 0) { return FALSE; }
        return (bool) $this->db->where('id', (int) $id)->where('durum', 1)->count_all_results('kargo_firmalari');
    }

    /** Ödeme yöntemi AKTİF kayıt mı (durum=1)? */
    public function _odeme_yontemi_gecerli($kod)
    {
        $kod = trim((string) $kod);
        if ($kod === '') { return FALSE; }
        return (bool) $this->db->where('kod', $kod)->where('durum', 1)->count_all_results('odeme_yontemleri');
    }
}
