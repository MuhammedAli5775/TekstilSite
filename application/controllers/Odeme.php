<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Odeme extends Magaza_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sepet_model');
        $this->load->model('siparis_model');
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

        $this->v['meta_title']     = t('odeme_baslik', 'Ödeme') . ' — ' . ayar('site_adi', 'TekstilSite');
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
        $this->form_validation->set_rules('teslimat_ad', t('odeme_ad_soyad', 'Ad Soyad'), 'trim|required|max_length[150]');
        $this->form_validation->set_rules('teslimat_adres', t('odeme_adres', 'Adres'), 'trim|required|max_length[500]');
        $this->form_validation->set_rules('teslimat_il', t('odeme_il', 'İl'), 'trim|required');
        $this->form_validation->set_rules('teslimat_telefon', t('odeme_telefon', 'Telefon'), 'trim|required|max_length[30]');
        // E-posta kuralı yalnız misafir/bayi için — giriş yapmış KULLANICININ siparişi
        // hesabının e-postasına işlenir (form değeri eşleşmeyi koparamaz, XXV).
        if (! $this->kullanici()) {
            $this->form_validation->set_rules('email', t('odeme_eposta', 'E-posta'), 'trim|required|valid_email|max_length[150]');
        }
        $this->form_validation->set_rules('odeme_yontemi', t('odeme_yontem', 'Ödeme Yöntemi'), 'trim|required');
        $this->form_validation->set_rules('kargo_firma_id', t('odeme_kargo_firma', 'Kargo Firması'), 'trim|required|integer');
        $this->form_validation->set_rules('sozlesme', 'Sözleşme onayı', 'trim|required', array('required' => t('val_sozlesme_odeme', 'Mesafeli satış sözleşmesini onaylamanız gerekir.')));

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('bilgi', t('flash_zorunlu_alan', 'Lütfen zorunlu alanları doldurun.'));
            $this->index();
            return;
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
            $this->session->set_flashdata('bilgi', t('flash_kupon_uygulandi', 'Kupon uygulandı: -%s (%s).', para_tr($r['indirim']), e($r['kupon']->kod)));
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

        $this->v['meta_title']     = t('sonuc_title', 'Sipariş Alındı') . ' — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;

        $this->render('magaza/odeme/basarili', array('sip' => $sip));
    }
}
