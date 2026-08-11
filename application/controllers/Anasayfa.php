<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Anasayfa extends Magaza_Controller
{
    public function index()
    {
        $this->v['meta_title'] = ayar('meta_title', 'TekstilSite — Toptan Kadın Giyim');
        $this->v['meta_desc']  = ayar('meta_description', 'Toptan kadın giyimde üretici fiyatı, kaliteli kumaş ve hızlı kargo. Bayi hesabıyla MOQ ve toptan fiyatlandırma.');

        $vitrin = array();
        if ($this->db_hazir())
        {
            $this->load->model('urun_model');
            $vitrin = $this->urun_model->mg_vitrin(8);
        }
        if (empty($vitrin)) {
            $vitrin = $this->_demo_vitrin();   // DB/şema yokken tasarım önizlemesi
        }

        $this->render('magaza/anasayfa', array('vitrin' => $vitrin));
    }

    /** DB/şema yokken tasarım önizlemesi için örnek ürün kartları (urun_karti yapısı). */
    private function _demo_vitrin()
    {
        $demo = array(
            array('ad' => 'Basic Triko Bluz',     'slug' => 'demo-1', 'sku' => 'TS-BLZ-001', 'fiyat' => 189.90, 'eski' => 249.90, 'moq' => 6,  'seed' => 'bluz1'),
            array('ad' => 'Viskon Elbise',        'slug' => 'demo-2', 'sku' => 'TS-ELB-014', 'fiyat' => 429.00, 'eski' => 0,      'moq' => 4,  'seed' => 'elbise2'),
            array('ad' => 'Pamuklu Tişört',       'slug' => 'demo-3', 'sku' => 'TS-TST-007', 'fiyat' => 99.90,  'eski' => 129.90, 'moq' => 12, 'seed' => 'tisort3'),
            array('ad' => 'Kruvaze Yelek',        'slug' => 'demo-4', 'sku' => 'TS-YLK-021', 'fiyat' => 359.50, 'eski' => 0,      'moq' => 4,  'seed' => 'yelek4'),
        );
        $out = array();
        foreach ($demo as $d) {
            $out[] = array(
                'id'         => 0,
                'ad'         => $d['ad'],
                'slug'       => $d['slug'],
                'url'        => site_url('urun/' . $d['slug']),
                'gorsel'     => 'https://picsum.photos/seed/' . $d['seed'] . '/600/800',
                'fiyat'      => $d['fiyat'],
                'eski_fiyat' => $d['eski'],
                'stok_kodu'  => $d['sku'],
                'moq'        => $d['moq'],
                'etiket'     => null,
            );
        }
        return $out;
    }
}