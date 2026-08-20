<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xml_export — B2B katalog feed'i üretici.
 *
 * 3. parti bağımlılık YOK: veri Urun_model::feed_liste() tarafından yapılır,
 * bu sınıf yalnızca XML (DOMDocument) veya JSON biçimine çevirir.
 * Tüm metinler XML/JSON codec mekanizmalarıyla escape edilir (XSS/injection yok).
 */
class Xml_export
{
    /** JSON feed (UTF-8, okunabilir girintili). */
    public function json(array $urunler)
    {
        return json_encode(array(
            'site'        => ayar('site_adi', 'Nesem Tesettür'),
            'olusturma'   => date('c'),
            'paraBirimi'  => 'TRY',
            'urunSayisi'  => count($urunler),
            'urunler'     => $urunler,
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /** XML feed (DOMDocument, UTF-8). */
    public function xml(array $urunler)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = TRUE;

        $root = $dom->createElement('katalog');
        $root->setAttribute('site', ayar('site_adi', 'Nesem Tesettür'));
        $root->setAttribute('paraBirimi', 'TRY');
        $root->setAttribute('olusturma', date('c'));
        $root->setAttribute('urunSayisi', (string) count($urunler));
        $dom->appendChild($root);

        foreach ($urunler as $u) {
            $un = $dom->createElement('urun');
            $un->setAttribute('id', (string) $u['id']);
            $this->_text($dom, $un, 'stokKodu',  $u['stok_kodu']);
            $this->_text($dom, $un, 'ad',        $u['ad']);
            $this->_text($dom, $un, 'slug',      $u['slug']);
            $this->_text($dom, $un, 'url',       $u['url']);
            $this->_text($dom, $un, 'kategori',  $u['kategori']);
            $this->_text($dom, $un, 'fiyat',     $this->_fiyat($u['fiyat']));
            $this->_text($dom, $un, 'eskiFiyat', $this->_fiyat($u['eski_fiyat']));
            $this->_text($dom, $un, 'moq',       (string) $u['moq']);
            $this->_text($dom, $un, 'birimAdim', (string) $u['birim_adim']);
            if ($u['gorsel'] !== '') { $this->_text($dom, $un, 'gorsel', $u['gorsel']); }

            $vs = $dom->createElement('varyantlar');
            foreach ($u['varyantlar'] as $v) {
                $vn = $dom->createElement('varyant');
                if ($v['renk']  !== '') { $this->_text($dom, $vn, 'renk',  $v['renk']); }
                if ($v['beden'] !== '') { $this->_text($dom, $vn, 'beden', $v['beden']); }
                if ($v['sku']   !== '') { $this->_text($dom, $vn, 'sku',   $v['sku']); }
                $this->_text($dom, $vn, 'stok', (string) $v['stok']);
                $vs->appendChild($vn);
            }
            $un->appendChild($vs);
            $root->appendChild($un);
        }
        return $dom->saveXML();
    }

    /** Güvenli metin elemanı (createTextNode escape eder). */
    private function _text($dom, $parent, $ad, $deger)
    {
        $el = $dom->createElement($ad);
        $el->appendChild($dom->createTextNode((string) $deger));
        $parent->appendChild($el);
    }

    private function _fiyat($v)
    {
        return number_format((float) $v, 2, '.', '');
    }
}
