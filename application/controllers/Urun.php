<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Urun extends Magaza_Controller
{
    public function detay($slug)
    {
        $this->load->model('urun_model');
        $u = $this->urun_model->mg_detay($slug);
        if (! $u) { show_404(); }

        // XXXI: kategori adı aktif dilde gösterilir (çeviri yoksa modelin TR join'i kalır).
        if (! empty($u->kategori_slug)) {
            $kat = $this->kategori_model->mg_by_slug($u->kategori_slug);
            if ($kat) { $u->kategori_adi = kategori_ad($kat); }
        }

        $varyantlar = $this->urun_model->mg_varyantlar($u->id);

        // renk → beden → stok haritası (JS varyant seçimi için)
        $vmap = array();
        foreach ($varyantlar as $v) {
            $vmap[$v->renk . '|' . $v->beden] = array('id' => (int) $v->id, 'stok' => (int) $v->stok);
        }

        $data = array(
            'u'            => $u,
            'gorseller'    => $this->_galeri($u, $this->urun_model->mg_gorseller($u->id)),
            'renkler'      => $this->_unique_renkler($varyantlar),
            'bedenler'     => $this->_unique_bedenler($varyantlar),
            'varyant_map'  => $vmap,
            'basamaklar'   => $this->urun_model->mg_basamaklar($u->id),
            'benzer'       => $this->urun_model->mg_benzer($u->id, 4),
            // Favori butonu durumlu render edilsin (session wishlist)
            'favorilerde'  => in_array((int) $u->id, array_map('intval', (array) $this->session->userdata('favoriler')), TRUE),
        );

        $this->v['meta_title'] = ! empty($u->meta_title) ? $u->meta_title : ($u->ad . ' — ' . ayar('site_adi', 'TekstilSite'));
        $this->v['meta_desc']  = ! empty($u->meta_description) ? $u->meta_description : character_limiter(strip_tags((string) $u->aciklama), 150);

        $this->render('magaza/urun/detay', $data);
    }

    /** Galeri: ana görsel + ek görseller (benzersiz). */
    private function _galeri($u, $ekstra)
    {
        $g = array();
        if (! empty($u->ana_gorsel)) { $g[] = $u->ana_gorsel; }
        foreach ($ekstra as $e) { if (! empty($e->yol)) { $g[] = $e->yol; } }
        $g = array_values(array_unique($g));
        return $g;
    }

    private function _unique_renkler($varyantlar)
    {
        $out = array();
        foreach ($varyantlar as $v) { if (! in_array($v->renk, $out, TRUE)) { $out[] = $v->renk; } }
        return $out;
    }

    private function _unique_bedenler($varyantlar)
    {
        $oncelik = array('XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, 'STD' => 7);
        $set = array();
        foreach ($varyantlar as $v) { if (! in_array($v->beden, $set, TRUE)) { $set[] = $v->beden; } }
        usort($set, function ($a, $b) use ($oncelik) { return ($oncelik[$a] ?? 99) <=> ($oncelik[$b] ?? 99); });
        return $set;
    }
}
