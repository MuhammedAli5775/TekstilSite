<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Anasayfa extends Magaza_Controller
{
    public function index()
    {
        $this->v['meta_title'] = ayar('meta_title', 'TekstilSite — Toptan Kadın Giyim');
        $this->v['meta_desc']  = ayar('meta_description', 'Toptan kadın giyimde üretici fiyatı, kaliteli kumaş ve hızlı kargo. Bayi hesabıyla MOQ ve toptan fiyatlandırma.');

        // Anasayfa artık ürün VİTRİNİ göstermez — kategoriler, değer önerileri,
        // istatistik ve bayi yorumları site-tanıtım odaklıdır. Ürünler /katalog altında.
        $this->render('magaza/anasayfa');
    }
}
