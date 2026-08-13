<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Urun_model extends CI_Model
{
    /** Vitrin ürünleri (anasayfa). */
    public function mg_vitrin($limit = 8)
    {
        if (! $this->db->table_exists('urunler')) { return array(); }
        $rows = $this->db->select('id, ad, slug, stok_kodu, ana_gorsel, fiyat, eski_fiyat, moq')
                         ->where('durum', 1)->where('vitrin', 1)
                         ->order_by('sira', 'ASC')->limit((int) $limit)
                         ->get('urunler')->result();
        return $this->_normalize($rows);
    }

    /** En çok satanlar (anasayfa). */
    public function mg_cok_satan($limit = 8)
    {
        if (! $this->db->table_exists('urunler')) { return array(); }
        $rows = $this->db->select('id, ad, slug, stok_kodu, ana_gorsel, fiyat, eski_fiyat, moq, satis_adet')
                         ->where('durum', 1)
                         ->order_by('satis_adet', 'DESC')->limit((int) $limit)
                         ->get('urunler')->result();
        return $this->_normalize($rows);
    }

    // ------------------------------------------------------------------
    // Katalog: liste + sayı + facet'ler
    // $f: kategori_idler[], bedenler[], renkler[], min, max, sira
    // ------------------------------------------------------------------
    public function mg_liste($f, $limit, $offset)
    {
        $this->_cekirdek($f);
        $this->db->select('urunler.id, urunler.ad, urunler.slug, urunler.stok_kodu, urunler.ana_gorsel, urunler.fiyat, urunler.eski_fiyat, urunler.moq, urunler.olusturma_zaman, urunler.sira, urunler.satis_adet');

        if (! empty($f['bedenler']) || ! empty($f['renkler'])) {
            $this->db->join('urun_varyantlari v', 'v.urun_id = urunler.id AND v.durum = 1', 'inner');
            if (! empty($f['bedenler'])) { $this->db->where_in('v.beden', $f['bedenler']); }
            if (! empty($f['renkler']))  { $this->db->where_in('v.renk', $f['renkler']); }
            $this->db->distinct();
        }

        $sira = $this->_sira_kolonu($f['sira'] ?? 'yeni');
        $rows = $this->db->order_by($sira[0], $sira[1])
                         ->limit((int) $limit, (int) $offset)
                         ->get()->result();
        $rows = $this->seri_ekle($rows);
        return $this->_normalize($rows);
    }

    public function mg_liste_say($f)
    {
        $this->_cekirdek($f);
        if (! empty($f['bedenler']) || ! empty($f['renkler'])) {
            $this->db->join('urun_varyantlari v', 'v.urun_id = urunler.id AND v.durum = 1', 'inner');
            if (! empty($f['bedenler'])) { $this->db->where_in('v.beden', $f['bedenler']); }
            if (! empty($f['renkler']))  { $this->db->where_in('v.renk', $f['renkler']); }
        }
        $this->db->select('COUNT(DISTINCT urunler.id) AS c', FALSE);
        $row = $this->db->get()->row();
        return $row ? (int) $row->c : 0;
    }

    /** Beden facet'leri (mevcut seçenekler + ürün adedi; beden/renk filtresi hariç). */
    public function mg_facet_beden($f)
    {
        $this->_cekirdek($f);
        $this->db->select('v.beden, COUNT(DISTINCT urunler.id) AS adet', FALSE)
                 ->join('urun_varyantlari v', 'v.urun_id = urunler.id AND v.durum = 1', 'inner')
                 ->where('v.beden IS NOT NULL', null, FALSE)
                 ->group_by('v.beden');
        $rows = $this->db->get()->result();
        return $this->_sirala_beden($rows);
    }

    /** Renk facet'leri. */
    public function mg_facet_renk($f)
    {
        $this->_cekirdek($f);
        $this->db->select('v.renk, COUNT(DISTINCT urunler.id) AS adet', FALSE)
                 ->join('urun_varyantlari v', 'v.urun_id = urunler.id AND v.durum = 1', 'inner')
                 ->where('v.renk IS NOT NULL', null, FALSE)
                 ->group_by('v.renk')
                 ->order_by('v.renk', 'ASC');
        return $this->db->get()->result();
    }

    // ------------------------------------------------------------------
    // Ürün detay
    // ------------------------------------------------------------------
    public function mg_detay($slug)
    {
        if (! $this->db->table_exists('urunler')) { return null; }
        return $this->db->select('u.*, k.ad AS kategori_adi, k.slug AS kategori_slug')
                        ->from('urunler u')
                        ->join('kategoriler k', 'k.id = u.kategori_id', 'left')
                        ->where('u.slug', $slug)
                        ->where('u.durum', 1)
                        ->limit(1)
                        ->get()->row();
    }

    public function mg_varyantlar($urun_id)
    {
        $rows = $this->db->select('id, renk, beden, stok, sku')
                         ->where('urun_id', (int) $urun_id)
                         ->where('durum', 1)
                         ->order_by('renk', 'ASC')
                         ->get('urun_varyantlari')->result();
        return $this->_sirala_varyant_beden($rows);
    }

    public function mg_benzer($urun_id, $limit = 4)
    {
        $u = $this->db->select('kategori_id')->where('id', (int) $urun_id)->limit(1)->get('urunler')->row();
        if (! $u || ! $u->kategori_id) { return array(); }
        $rows = $this->db->select('id, ad, slug, stok_kodu, ana_gorsel, fiyat, eski_fiyat, moq')
                         ->where('kategori_id', $u->kategori_id)
                         ->where('durum', 1)->where('id !=', (int) $urun_id)
                         ->order_by('sira', 'ASC')->limit((int) $limit)
                         ->get('urunler')->result();
        return $this->_normalize($rows);
    }

    /** Fiyat basamakları: ürün-özel + global, min adete göre artan. */
    public function mg_basamaklar($urun_id)
    {
        return $this->db->where('(urun_id = ' . (int) $urun_id . ' OR urun_id IS NULL)', null, FALSE)
                        ->order_by('urun_id', 'DESC')   // ürün-özel önce
                        ->order_by('min_adet', 'ASC')
                        ->get('fiyat_basamaklari')->result();
    }

    /**
     * Liste/arama kartları için en iyi seri (miktar indirimi) fiyatını hazırla.
     * Her ürün için ürün-özel VEYA global basamaktan en yüksek indirim oranını bulur,
     * seri_yuzde + seri_adet (o oranın min_adet'i) ekler. _normalize seri_fiyat üretir.
     */
    public function seri_ekle(array $rows)
    {
        if (! $rows) { return $rows; }
        $idler = array();
        foreach ($rows as $r) { if (! empty($r->id)) { $idler[(int) $r->id] = (int) $r->id; } }
        if (! $idler) { return $rows; }
        $tiers = $this->db->where('(urun_id IN (' . implode(',', $idler) . ') OR urun_id IS NULL)', NULL, FALSE)
                          ->order_by('indirim_yuzde', 'DESC')->get('fiyat_basamaklari')->result();
        $best_own = array(); $best_global = NULL;
        foreach ($tiers as $t) {
            if ($t->urun_id === NULL) { if ($best_global === NULL) { $best_global = $t; } }
            else { $pid = (int) $t->urun_id; if (! isset($best_own[$pid])) { $best_own[$pid] = $t; } }
        }
        foreach ($rows as $r) {
            $pid  = (int) $r->id;
            $own  = $best_own[$pid] ?? NULL;
            $pick = ($own && $best_global) ? (($own->indirim_yuzde >= $best_global->indirim_yuzde) ? $own : $best_global)
                   : ($own ?: $best_global);
            if ($pick) { $r->seri_yuzde = (float) $pick->indirim_yuzde; $r->seri_adet = (int) $pick->min_adet; }
        }
        return $rows;
    }

    // ------------------------------------------------------------------
    // Arama
    // ------------------------------------------------------------------
    public function mg_arama($q, $limit, $offset)
    {
        $rows = $this->db->select('id, ad, slug, stok_kodu, ana_gorsel, fiyat, eski_fiyat, moq')
                         ->from('urunler')->where('durum', 1)
                         ->group_start()
                            ->like('ad', $q)->or_like('stok_kodu', $q)->or_like('aciklama', $q)
                         ->group_end()
                         ->order_by('olusturma_zaman', 'DESC')
                         ->limit((int) $limit, (int) $offset)
                         ->get()->result();
        $rows = $this->seri_ekle($rows);
        return $this->_normalize($rows);
    }

    public function mg_arama_say($q)
    {
        $this->db->from('urunler')->where('durum', 1)
                 ->group_start()->like('ad', $q)->or_like('stok_kodu', $q)->or_like('aciklama', $q)->group_end();
        return $this->db->count_all_results();
    }

    // ------------------------------------------------------------------
    // Yardımcılar
    // ------------------------------------------------------------------
    /** Ortak where çekirdeği: durum + kategori + fiyat (beden/renk hariç). */
    private function _cekirdek($f)
    {
        $this->db->from('urunler');
        $this->db->where('urunler.durum', 1);
        if (! empty($f['kategori_idler'])) {
            $this->db->where_in('urunler.kategori_id', array_map('intval', $f['kategori_idler']));
        }
        $min = $this->_sayi($f['min'] ?? null);
        $max = $this->_sayi($f['max'] ?? null);
        if ($min !== null) { $this->db->where('urunler.fiyat >=', $min); }
        if ($max !== null) { $this->db->where('urunler.fiyat <=', $max); }
    }

    private function _sira_kolonu($sira)
    {
        switch ($sira) {
            case 'fiyat_asc':  return array('urunler.fiyat', 'ASC');
            case 'fiyat_desc': return array('urunler.fiyat', 'DESC');
            case 'ad':         return array('urunler.ad', 'ASC');
            case 'cok_satan':  return array('urunler.satis_adet', 'DESC');
            case 'yeni':
            default:           return array('urunler.olusturma_zaman', 'DESC');
        }
    }

    private function _sayi($v)
    {
        if ($v === null || $v === '') { return null; }
        if (is_string($v)) { $v = str_replace(array(',', ' '), array('.', ''), $v); }
        return is_numeric($v) ? (float) $v : null;
    }

    /** Kart formatına normalize (liste/vitrin/arama/benzer için ortak). */
    private function _normalize($rows)
    {
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'id'         => (int) $r->id,
                'ad'         => $r->ad,
                'slug'       => $r->slug,
                'url'        => site_url('urun/' . $r->slug),
                'gorsel'     => ! empty($r->ana_gorsel) ? $r->ana_gorsel : '',
                'fiyat'      => (float) $r->fiyat,
                'eski_fiyat' => (float) (isset($r->eski_fiyat) ? $r->eski_fiyat : 0),
                'stok_kodu'  => $r->stok_kodu,
                'moq'        => (int) (isset($r->moq) ? $r->moq : 1),
                'seri_fiyat' => (isset($r->seri_yuzde) && $r->seri_yuzde > 0) ? round((float) $r->fiyat * (1 - (float) $r->seri_yuzde / 100), 2) : 0.0,
                'seri_adet'  => isset($r->seri_adet) ? (int) $r->seri_adet : 0,
                'etiket'     => null,
            );
        }
        return $out;
    }

    /** Beden facet'lerini standart sıraya koyar. */
    private function _sirala_beden($rows)
    {
        $oncelik = array('XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, 'STD' => 7);
        usort($rows, function ($a, $b) use ($oncelik) {
            $ka = $oncelik[$a->beden] ?? 99;
            $kb = $oncelik[$b->beden] ?? 99;
            return $ka <=> $kb;
        });
        return $rows;
    }

    /** Varyant satırlarını renk → standart beden sırasıyla döndürür. */
    private function _sirala_varyant_beden($rows)
    {
        $oncelik = array('XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, 'STD' => 7);
        usort($rows, function ($a, $b) use ($oncelik) {
            $c = strcasecmp($a->renk, $b->renk);
            if ($c !== 0) { return $c; }
            $ka = $oncelik[$a->beden] ?? 99;
            $kb = $oncelik[$b->beden] ?? 99;
            return $ka <=> $kb;
        });
        return $rows;
    }

    // ------------------------------------------------------------------
    // YÖNETİM (admin) metotları
    // ------------------------------------------------------------------
    public function mg_admin_liste($f, $limit, $offset)
    {
        $this->db->select('urunler.id, urunler.ad, urunler.stok_kodu, urunler.slug, urunler.fiyat, urunler.moq, urunler.durum, urunler.vitrin, urunler.ana_gorsel, k.ad AS kategori')
                 ->from('urunler')->join('kategoriler k', 'k.id = urunler.kategori_id', 'left');
        $this->_admin_filtre($f);
        return $this->db->order_by('urunler.id', 'DESC')->limit((int) $limit, (int) $offset)->get()->result();
    }

    public function mg_admin_liste_say($f)
    {
        $this->db->from('urunler')->join('kategoriler k', 'k.id = urunler.kategori_id', 'left');
        $this->_admin_filtre($f);
        return $this->db->count_all_results();
    }

    private function _admin_filtre($f)
    {
        // Soft-delete ürünleri admin listesinden gizle.
        $this->db->where('urunler.deleted_at IS NULL', NULL, FALSE);
        if (! empty($f['q'])) {
            $this->db->group_start()->like('urunler.ad', $f['q'])->or_like('urunler.stok_kodu', $f['q'])->group_end();
        }
        if (! empty($f['kategori_id'])) { $this->db->where('urunler.kategori_id', (int) $f['kategori_id']); }
        if (isset($f['durum']) && $f['durum'] !== '') { $this->db->where('urunler.durum', (int) $f['durum']); }
    }

    /** Form için: ürün + varyantlar + görseller. */
    public function mg_admin_getir($id)
    {
        $u = $this->db->where('id', (int) $id)->limit(1)->get('urunler')->row();
        if (! $u) { return NULL; }
        $u->varyantlar = $this->db->where('urun_id', (int) $id)->order_by('renk, beden', 'ASC')->get('urun_varyantlari')->result();
        $u->gorseller  = $this->db->where('urun_id', (int) $id)->order_by('sira', 'ASC')->get('urun_gorselleri')->result();
        $u->basamaklar = $this->db->where('urun_id', (int) $id)->order_by('min_adet', 'ASC')->get('fiyat_basamaklari')->result();
        return $u;
    }

    public function mg_kaydet($d)
    {
        $slug = trim((string) ($d['slug'] ?? ''));
        if ($slug === '') { $slug = slug_tr($d['ad'] ?? ''); }
        $d['slug'] = $this->_slug_benzersiz($slug, 0);
        $this->db->insert('urunler', $d);
        return $this->db->insert_id();
    }

    public function mg_guncelle($id, $d)
    {
        if (array_key_exists('slug', $d)) {
            $slug = trim((string) $d['slug']);
            if ($slug === '') { $slug = slug_tr($d['ad'] ?? ''); }
            $d['slug'] = $this->_slug_benzersiz($slug, (int) $id);
        }
        $this->db->where('id', (int) $id)->update('urunler', $d);
    }

    public function mg_durum($id, $durum)
    {
        $this->db->where('id', (int) $id)->update('urunler', array('durum' => (int) $durum));
    }

    public function mg_sil($id)
    {
        // Soft delete (workflow §2): deleted_at + durum=0. Varyant/görsel/stok korunur,
        // hard-delete yerine. Mağaza/admin sorguları deleted_at IS NULL ile filtreler.
        $this->db->where('id', (int) $id)->update('urunler', array(
            'deleted_at' => date('Y-m-d H:i:s'),
            'durum'      => 0,
        ));
    }

    /** Varyantları değiştir (eski sil + yenileri ekle). */
    public function mg_varyant_kaydet($urun_id, $rows)
    {
        // (renk,beden)'e gore birlestir — degistir-atil DEGIL. Eslesen mevcut varyant
        // GUNCELLENIR (ID + buna bagli siparis/stok-hareket referanslari korunur),
        // yeniler eklenir, cikarilanlar silinir. Boylece urun duzenleme (orn. fiyat
        // degisikligi) siparisi olan varyantlari silip iade stok-geri-yuklemesini
        // (UPDATE ... WHERE id=varyant_id -> 0 satir -> stok sizintisi) bozmaz.
        $urun_id = (int) $urun_id;
        if (! is_array($rows)) { $rows = array(); }

        $mevcut = $this->db->where('urun_id', $urun_id)->get('urun_varyantlari')->result();
        $idx = array();
        foreach ($mevcut as $m) { $idx[$this->_vkey($m->renk, $m->beden)] = (int) $m->id; }

        $goruldu = array();
        foreach ($rows as $r) {
            $renk  = trim((string) ($r['renk'] ?? ''));
            $beden = trim((string) ($r['beden'] ?? ''));
            if ($renk === '' && $beden === '') { continue; }
            $key = $this->_vkey($renk, $beden);
            $goruldu[$key] = TRUE;
            $veri = array(
                'renk'  => $renk ?: NULL,
                'beden' => $beden ?: NULL,
                'stok'  => (int) ($r['stok'] ?? 0),
                'sku'   => trim((string) ($r['sku'] ?? '')) ?: NULL,
                'durum' => 1,
            );
            if (isset($idx[$key])) {
                $this->db->where('id', $idx[$key])->update('urun_varyantlari', $veri);
            } else {
                $veri['urun_id'] = $urun_id;
                $this->db->insert('urun_varyantlari', $veri);
                $idx[$key] = (int) $this->db->insert_id();
            }
        }

        foreach ($idx as $key => $id) {
            if (! isset($goruldu[$key])) { $this->db->where('id', $id)->delete('urun_varyantlari'); }
        }
    }

    /** Varyant eslesme anahtari (renk \x1F beden). */
    private function _vkey($renk, $beden) { return (string) $renk . "\x1F" . (string) $beden; }

    /** Fiyat basamaklarını değiştir (ürüne özel: eskileri sil + yenileri ekle). */
    public function mg_basamak_kaydet($urun_id, $rows)
    {
        $this->db->where('urun_id', (int) $urun_id)->delete('fiyat_basamaklari');
        if (! is_array($rows)) { return; }
        foreach ($rows as $r) {
            $min   = (int) ($r['min_adet'] ?? 0);
            $yuzde = (float) ($r['indirim_yuzde'] ?? 0);
            if ($min < 1 || $yuzde <= 0) { continue; } // boş/geçersiz satır atla
            $this->db->insert('fiyat_basamaklari', array(
                'urun_id'       => (int) $urun_id,
                'min_adet'      => $min,
                'indirim_yuzde' => $yuzde,
            ));
        }
    }

    public function mg_gorsel_ekle($urun_id, $yol)
    {
        $sira = (int) $this->db->select('COALESCE(MAX(sira),0)+1 AS s', FALSE)->where('urun_id', (int) $urun_id)->get('urun_gorselleri')->row()->s;
        $this->db->insert('urun_gorselleri', array('urun_id' => (int) $urun_id, 'yol' => $yol, 'sira' => $sira));
        return $yol;
    }

    public function mg_gorseller($urun_id)
    {
        return $this->db->select('id, yol, sira')->where('urun_id', (int) $urun_id)->order_by('sira', 'ASC')->get('urun_gorselleri')->result();
    }

    public function mg_gorsel_sil($gorsel_id)
    {
        $g = $this->db->where('id', (int) $gorsel_id)->limit(1)->get('urun_gorselleri')->row();
        if (! $g) { return NULL; }
        $this->db->where('id', (int) $gorsel_id)->delete('urun_gorselleri');
        return $g->yol;
    }

    public function mg_gorsel_ana($urun_id, $yol)
    {
        $this->db->where('id', (int) $urun_id)->update('urunler', array('ana_gorsel' => $yol));
    }

    /**
     * B2B feed için aktif ürünler + kategori + varyantlar.
     * "Toplu varyant çekme": varyantlar tek sorguda çekilip urun_id'ye göre gruplanır
     * (N+1 sorgu önlenir). Çıktı Xml_export sözleşmesine uyar.
     */
    public function feed_liste()
    {
        if (! $this->db->table_exists('urunler')) { return array(); }
        $urunler = $this->db->select('urunler.id, urunler.stok_kodu, urunler.ad, urunler.slug,
                                      urunler.fiyat, urunler.eski_fiyat, urunler.moq, urunler.birim_adim,
                                      urunler.ana_gorsel, k.ad AS kategori')
                            ->from('urunler')
                            ->join('kategoriler k', 'k.id = urunler.kategori_id', 'left')
                            ->where('urunler.durum', 1)
                            ->where('urunler.deleted_at IS NULL', NULL, FALSE)
                            ->order_by('urunler.id', 'ASC')
                            ->get()->result();
        if (! $urunler) { return array(); }

        $idler = array();
        foreach ($urunler as $u) { $idler[] = (int) $u->id; }

        $varyant_harita = array();
        $vs = $this->db->where_in('urun_id', $idler)->where('durum', 1)
                       ->order_by('urun_id', 'ASC')->get('urun_varyantlari')->result();
        foreach ($vs as $v) {
            $varyant_harita[(int) $v->urun_id][] = array(
                'renk'  => ($v->renk  !== NULL) ? (string) $v->renk  : '',
                'beden' => ($v->beden !== NULL) ? (string) $v->beden : '',
                'sku'   => ($v->sku   !== NULL) ? (string) $v->sku   : '',
                'stok'  => (int) $v->stok,
            );
        }

        $out = array();
        foreach ($urunler as $u) {
            $out[] = array(
                'id'         => (int) $u->id,
                'stok_kodu'  => ($u->stok_kodu !== NULL) ? (string) $u->stok_kodu : '',
                'ad'         => (string) $u->ad,
                'slug'       => (string) $u->slug,
                'url'        => site_url('urun/' . $u->slug),
                'kategori'   => ($u->kategori !== NULL) ? (string) $u->kategori : '',
                'fiyat'      => (float) $u->fiyat,
                'eski_fiyat' => (float) $u->eski_fiyat,
                'moq'        => (int) $u->moq,
                'birim_adim' => (int) $u->birim_adim,
                'gorsel'     => ! empty($u->ana_gorsel) ? gorsel_url($u->ana_gorsel) : '',
                'varyantlar' => $varyant_harita[(int) $u->id] ?? array(),
            );
        }
        return $out;
    }

    private function _slug_benzersiz($slug, $id)
    {
        $slug = slug_tr($slug);
        if ($slug === '') { $slug = 'urun'; }
        $aday = $slug; $i = 1;
        while (TRUE) {
            $this->db->where('slug', $aday);
            if ($id) { $this->db->where('id !=', $id); }
            if ($this->db->count_all_results('urunler') == 0) { break; }
            $aday = $slug . '-' . (++$i);
        }
        return $aday;
    }
}
