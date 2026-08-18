<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Arama extends Magaza_Controller
{
    public function index()
    {
        $this->load->model('urun_model');
        $q = trim((string) $this->input->get('q'));
        $limit = 12;
        $sayfa = max(1, (int) $this->input->get('sayfa'));
        $offset = ($sayfa - 1) * $limit;

        $sonuc = array();
        $toplam = 0;
        if ($q !== '') {
            $toplam = $this->urun_model->mg_arama_say($q);
            $sonuc  = $this->urun_model->mg_arama($q, $limit, $offset);
        }

        $this->v['meta_title']     = $q !== '' ? (t('arama_title_q', 'Arama: %s', $q) . ' — ' . ayar('site_adi', 'TekstilSite')) : (t('arama_baslik', 'Arama') . ' — ' . ayar('site_adi', 'TekstilSite'));
        $this->v['indexlenebilir'] = FALSE; // arama sayfası noindex

        $this->render('magaza/arama/index', array(
            'q'            => $q,
            'sonuc'        => $sonuc,
            'toplam'       => $toplam,
            'sayfa'        => $sayfa,
            'sayfa_sayisi' => max(1, (int) ceil($toplam / $limit)),
        ));
    }
}
