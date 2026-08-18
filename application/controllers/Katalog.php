<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Katalog extends Magaza_Controller
{
    /** Tüm ürünler. */
    public function index()
    {
        $this->_liste(null, t('kat_tum_urunler', 'Tüm Ürünler'), 'katalog');
    }

    /** Yeni gelenler (en yeni eklenenler). */
    public function yeni()
    {
        $this->_liste(null, t('kat_sira_yeni', 'Yeni Gelenler'), 'katalog/yeni', array('sira' => 'yeni'));
    }

    /** Kategori (üst veya alt). URL: katalog/{slug} veya katalog/{ust}/{alt}. */
    public function kategori($slug1, $slug2 = null)
    {
        $slug = $slug2 ?: $slug1;
        $kat = $this->kategori_model->mg_by_slug($slug);
        if (! $kat) { show_404(); }

        $idler = array((int) $kat->id);
        if (empty($kat->ust_id)) {
            // üst kategori → alt kategorilerini de kapsa
            $idler = array_merge($idler, $this->kategori_model->mg_alt_idler($kat->id));
        }

        $this->_liste($kat, kategori_ad($kat), 'katalog/' . $kat->slug, array('kategori_idler' => $idler));
    }

    /** Ortak liste render. */
    private function _liste($kategori, $baslik, $liste_url, $ekstra = array())
    {
        $this->load->model('urun_model');

        $filtre = array(
            'kategori_idler' => $ekstra['kategori_idler'] ?? null,
            'bedenler' => $this->_dizi('beden'),
            'renkler'  => $this->_dizi('renk'),
            'min'      => $this->input->get('min'),
            'max'      => $this->input->get('max'),
            'sira'     => $this->input->get('sira') ?: ($ekstra['sira'] ?? 'yeni'),
        );

        $limit = 12;
        $sayfa = max(1, (int) $this->input->get('sayfa'));
        $offset = ($sayfa - 1) * $limit;

        $toplam  = $this->urun_model->mg_liste_say($filtre);
        $urunler = $this->urun_model->mg_liste($filtre, $limit, $offset);

        $data = array(
            'baslik'          => $baslik,
            'liste_url'       => $liste_url,
            'kategori'        => $kategori,
            'ust_yol'         => $kategori ? $this->kategori_model->mg_ust_yol($kategori) : array(),
            'alt_kategoriler' => $kategori ? $this->kategori_model->mg_alt_kategoriler($kategori->id) : array(),
            'urunler'         => $urunler,
            'toplam'          => $toplam,
            'filtre'          => $filtre,
            'secili_beden'    => $filtre['bedenler'],
            'secili_renk'     => $filtre['renkler'],
            'facet_beden'     => $this->urun_model->mg_facet_beden($filtre),
            'facet_renk'      => $this->urun_model->mg_facet_renk($filtre),
            'sayfa'           => $sayfa,
            'limit'           => $limit,
            'sayfa_sayisi'    => max(1, (int) ceil($toplam / $limit)),
        );

        $this->v['meta_title'] = $baslik . ' — ' . ayar('site_adi', 'TekstilSite');
        $this->v['meta_desc']  = t('kat_meta_desc', '%s — toptan kadın giyim, üretici fiyatı, gerçek stok.', $baslik);

        $this->render('magaza/katalog/index', $data);
    }

    /** GET'ten dizi oku (beden[] veya virgül-dizi). */
    private function _dizi($anahtar)
    {
        $v = $this->input->get($anahtar);
        if (is_array($v)) {
            return array_values(array_filter(array_map('trim', $v)));
        }
        if ($v === null || $v === '') { return array(); }
        return array_values(array_filter(array_map('trim', explode(',', (string) $v))));
    }
}
