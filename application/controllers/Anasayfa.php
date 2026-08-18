<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Anasayfa extends Magaza_Controller
{
    public function index()
    {
        // Meta: admin DB override'u boşsa DİLE GÖRE çevrilmiş varsayılana düş
        // (XXXVI prova bulgusu — ayar satırı yokken/boşken her dil TR meta alıyordu).
        $mt = trim((string) ayar('meta_title'));
        $md = trim((string) ayar('meta_description'));
        $this->v['meta_title'] = $mt !== '' ? $mt : t('meta_title_default', 'TekstilSite — Toptan Kadın Giyim');
        $this->v['meta_desc']  = $md !== '' ? $md : t('meta_desc_default', 'Toptan kadın giyim — üretici fiyatı, kaliteli kumaş, hızlı kargo.');

        // Anasayfa artık ürün VİTRİNİ göstermez — kategoriler, değer önerileri,
        // istatistik ve bayi yorumları site-tanıtım odaklıdır. Ürünler /katalog altında.
        $this->render('magaza/anasayfa');
    }
}
