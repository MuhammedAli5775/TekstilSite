<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Paytr — mağaza kartlı ödeme akışı (PayTR iFrame).
 *   ode/{id}       → token al + iframe sayfası
 *   basarili/{id}  → tarayıcı başarılı yönlendirmesi (gerçek onay callback'te)
 *   basarisiz/{id} → tarayıcı başarısız yönlendirmesi
 */
class Paytr extends Magaza_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('siparis_model');
        $this->load->library('paytr_api');
    }

    public function ode($id = NULL)
    {
        $id = (int) $id;
        $s = $this->siparis_model->mg_getir($id);
        if (! $s || ! $this->_sahip($s)) { show_404(); }

        $data = array('s' => $s, 'token' => NULL, 'hata' => NULL);
        if (! $this->paytr_api->hazir()) {
            $data['hata'] = 'Kartlı ödeme henüz yapılandırılmamış. Lütfen havale/EFT ile ödeyin veya bizimle iletişime geçin.';
        } else {
            $res = $this->paytr_api->get_token($s);
            if ($res['ok']) { $data['token'] = $res['token']; }
            else            { $data['hata'] = $res['mesaj']; }
        }

        $this->v['meta_title']     = 'Kartla Ödeme — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/odeme/paytr', $data);
    }

    public function basarili($id = NULL)
    {
        $id = (int) $id;
        $s = $this->siparis_model->mg_getir($id);
        // Sahiplik ŞART (XXVI): IDsiz sahiplik atlanırsa sıralı id ile herkes
        // sipariş görür + kendi son_siparis_id'sini atayıp ödeme sayfasına erişir.
        if (! $s || ! $this->_sahip($s)) { redirect(''); }

        $this->session->set_userdata('son_siparis_id', $id);
        $this->v['meta_title']     = 'Ödeme Alındı — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/odeme/paytr', array('s' => $s, 'token' => NULL, 'hata' => NULL, 'bekle' => TRUE));
    }

    public function basarisiz($id = NULL)
    {
        $s = $this->siparis_model->mg_getir((int) $id);
        if (! $s || ! $this->_sahip($s)) { redirect(''); }   // sahiplik ŞART (XXVI)
        $this->v['meta_title']     = 'Ödeme Başarısız — ' . ayar('site_adi', 'Nesem Tesettür');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/odeme/paytr_basarisiz', array('s' => $s));
    }

    /** Sahiplik: bayi kendi siparişi VEYA misafir (session son_siparis_id). */
    private function _sahip($s)
    {
        $bid = $this->bayi_id();
        if ($bid && (int) $s->bayi_id === (int) $bid) { return TRUE; }
        return (int) $this->session->userdata('son_siparis_id') === (int) $s->id;
    }
}
