<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * teksil_helper — mağaza/panel ortak yardımcıları.
 * Escape, para biçimi, ayar okuma, layout yardımları.
 */

if ( ! function_exists('e'))
{
    /** Güvenli HTML çıktısı (UTF-8). Güvenilir olmayan her veride kullan. */
    function e($dizi)
    {
        return htmlspecialchars((string) $dizi, ENT_QUOTES, 'UTF-8');
    }
}

if ( ! function_exists('me'))
{
    /** e() için kısa takma ad (Nesem alışkanlığı). */
    function me($dizi)
    {
        return e($dizi);
    }
}

if ( ! function_exists('para_tr'))
{
    /** 1234.5 → "1.234,50 ₺" (TRY). */
    function para_tr($tutar, $birim = '₺')
    {
        $tutar = (float) $tutar;
        return number_format($tutar, 2, ',', '.') . ($birim !== '' ? ' ' . $birim : '');
    }
}

if ( ! function_exists('ayar'))
{
    /** ayarlar tablosundan değer oku; yoksa varsayılan. DB yoksa varsayılan döner. */
    function ayar($anahtar, $varsayilan = null)
    {
        $CI =& get_instance();
        if ( ! isset($CI->db) || ! is_object($CI->db) || empty($CI->db->conn_id))
        {
            return $varsayilan;
        }
        $CI->db->select('deger')->where('anahtar', $anahtar)->limit(1);
        $q = $CI->db->get('ayarlar');
        if ($q && $q->num_rows() === 1)
        {
            $row = $q->row();
            return $row->deger;
        }
        return $varsayilan;
    }
}

if ( ! function_exists('asset'))
{
    /** assets/ altındaki dosya URL'i (cache-bust ile). */
    function asset($yol)
    {
        $CI =& get_instance();
        $tam = FCPATH . 'assets/' . ltrim($yol, '/');
        $v = is_file($tam) ? '?' . filemtime($tam) : '';
        return base_url('assets/' . ltrim($yol, '/')) . $v;
    }
}

if ( ! function_exists('csrf_field'))
{
    /** Form için gizli CSRF alanı (POST formlarda zorunlu). */
    function csrf_field()
    {
        $CI =& get_instance();
        return '<input type="hidden" name="' . config_item('csrf_token_name')
             . '" value="' . $CI->security->get_csrf_hash() . '">';
    }
}

if ( ! function_exists('bayi_indirim'))
{
    /** Giriş yapmış bayinin grup indirimi (%) — 0 ise indirim yok. */
    function bayi_indirim()
    {
        $CI =& get_instance();
        $bid = $CI->session->userdata('bayi_id');
        if (! $bid) { return 0.0; }
        $row = $CI->db->select('g.indirim_yuzde')
                      ->from('bayiler b')
                      ->join('bayi_gruplari g', 'g.id = b.grup_id', 'left')
                      ->where('b.id', (int) $bid)->limit(1)->get()->row();
        return $row ? (float) $row->indirim_yuzde : 0.0;
    }
}

if ( ! function_exists('durum_etiket'))
{
    /** Sipariş durumu → [etiket, rozet-sınıfı]. */
    function durum_etiket($durum)
    {
        $map = array(
            'onay_bekliyor' => array('Onay bekliyor', 'bekle'),
            'onaylandi'     => array('Onaylandı', 'mavi'),
            'hazirlaniyor'  => array('Hazırlanıyor', 'mavi'),
            'kargolandi'    => array('Kargolandı', 'sari'),
            'teslim_edildi' => array('Teslim edildi', 'yesil'),
            'iptal'         => array('İptal', 'kirmizi'),
            'iade_talep'    => array('İade talebi', 'turuncu'),
            'iade_edildi'   => array('İade edildi', 'kirmizi'),
        );
        if (! isset($map[$durum])) { return array($durum, 'gri'); }
        // XXXI: mağaza bağlamında çevirilir; dil yardımcısı yoksa (admin) Türkçe kalır.
        $map[$durum][0] = function_exists('t') ? t('durum_' . $durum, $map[$durum][0]) : $map[$durum][0];
        return $map[$durum];
    }
}

if ( ! function_exists('bas_harfler'))
{
    /** İsimden baş harf avatarı üret. */
    function bas_harfler($ad)
    {
        $parca = array_values(array_filter(explode(' ', trim((string) $ad))));
        if (! $parca) { return '?'; }
        $ilk = mb_substr($parca[0], 0, 1, 'UTF-8');
        $son = isset($parca[1]) ? mb_substr($parca[1], 0, 1, 'UTF-8') : '';
        return mb_strtoupper($ilk . $son, 'UTF-8');
    }
}

if ( ! function_exists('slug_tr'))
{
    /** Türkçe uyumlu slug üret. */
    function slug_tr($metin)
    {
        $ara  = array('ç','Ç','ğ','Ğ','ı','İ','ö','Ö','ş','Ş','ü','Ü');
        $deg  = array('c','c','g','g','i','i','o','o','s','s','u','u');
        $metin = str_replace($ara, $deg, $metin);
        $metin = strtolower($metin);
        $metin = preg_replace('/[^a-z0-9]+/', '-', $metin);
        $metin = trim($metin, '-');
        return $metin;
    }
}

if ( ! function_exists('qs_url'))
{
    /**
     * Mevcut URL'i koruyup sorgu parametrelerini birleştirir.
     * Sıralama değişince sayfa 1'e döner (sayfa korunmaz).
     * @param array $degisiklik  $_GET ile birleştirilecek anahtar=>değer
     * @return string
     */
    function qs_url($degisiklik = array())
    {
        $q = array_merge($_GET, $degisiklik);
        if (isset($degisiklik['sira']) && ! isset($degisiklik['sayfa'])) {
            unset($q['sayfa']);
        }
        $qs = http_build_query($q);
        return current_url() . ($qs !== '' ? '?' . $qs : '');
    }
}

if ( ! function_exists('gorsel_url'))
{
    /** Görsel yolunu mutlak URL yap (uzak http korunur; yerel uploads/ → base_url). */
    function gorsel_url($yol)
    {
        if (! $yol) { return ''; }
        if (preg_match('#^https?://#i', $yol) || strpos($yol, '//') === 0) { return $yol; }
        return base_url(ltrim($yol, '/'));
    }
}

if ( ! function_exists('renk_hex'))
{
    /** Renk adına yaklaşık hex döndürür (swatch için). */
    function renk_hex($renk)
    {
        $eslesme = array(
            'siyah' => '#1a1a1a', 'beyaz' => '#ffffff', 'taş' => '#b9b2a6',
            'lacivert' => '#1f2a44', 'mavi' => '#2d5fa6', 'kırmızı' => '#c0392b',
            'yeşil' => '#2e7d4f', 'sarı' => '#e3b341', 'pembe' => '#e58fb0',
            'mor' => '#7b3ff2', 'bej' => '#d8cbb6', 'gri' => '#8a8a8a',
            'ekru' => '#eae3d2', 'bordo' => '#6e1b1b', 'kiremit' => '#a14b3c',
            'camel' => '#b08d57', 'haki' => '#6b6a45',
        );
        $k = mb_strtolower(trim((string) $renk), 'UTF-8');
        foreach ($eslesme as $anahtar => $hex) {
            if (strpos($k, $anahtar) !== FALSE) { return $hex; }
        }
        return '#cccccc';
    }
}

/* -----------------------------------------------------------------
 * Para birimi (coklu kur) yardimcilari.
 * para_birimleri tablosu: kod/ad/sembol/kur_try (1 birim = N TRY).
 * Katalog TRY bazli; siparis anlik kopyasi bayi para biriminde snapshot.
 * ----------------------------------------------------------------- */
if ( ! function_exists('_para_birim_harita'))
{
    /** kod => {ad, sembol, kur_try} haritasi (istek icinde onbellekli). */
    function _para_birim_harita()
    {
        static $map = NULL;
        if ($map !== NULL) { return $map; }
        $map = array();
        $CI =& get_instance();
        if (isset($CI->db) && is_object($CI->db) && ! empty($CI->db->conn_id)
            && $CI->db->table_exists('para_birimleri'))
        {
            foreach ($CI->db->get('para_birimleri')->result() as $b) {
                $map[strtoupper($b->kod)] = array(
                    'ad'     => $b->ad,
                    'sembol' => $b->sembol,
                    'kur_try'=> (float) $b->kur_try,
                );
            }
        }
        if ( ! isset($map['TRY'])) {
            $map['TRY'] = array('ad' => 'Turk Lirasi', 'sembol' => ayar('para_sembol_try', 'TL'), 'kur_try' => 1.0);
        }
        return $map;
    }
}

if ( ! function_exists('para_birimleri_liste'))
{
    /** Aktif para birimleri (admin formu / secici icin). */
    function para_birimleri_liste()
    {
        $CI =& get_instance();
        if ( ! isset($CI->db) || ! is_object($CI->db) || ! $CI->db->table_exists('para_birimleri')) {
            return array();
        }
        return $CI->db->where('durum', 1)->order_by('sira', 'ASC')->get('para_birimleri')->result();
    }
}

if ( ! function_exists('kur_getir'))
{
    /** Bir kod icin kur_try (1 birim = N TRY). TRY/bilinmeyen = 1.0. */
    function kur_getir($kod)
    {
        $kod = strtoupper(trim((string) $kod));
        if ($kod === '' || $kod === 'TRY') { return 1.0; }
        $map = _para_birim_harita();
        return isset($map[$kod]) ? (float) $map[$kod]['kur_try'] : 1.0;
    }
}

if ( ! function_exists('aktif_para_birimi'))
{
    /** Giris yapmis bayinin para birimi; yoksa TRY. */
    function aktif_para_birimi()
    {
        $CI =& get_instance();
        $bid = $CI->session->userdata('bayi_id');
        if (! $bid) { return 'TRY'; }
        $row = $CI->db->select('para_birimi')->where('id', (int) $bid)->limit(1)->get('bayiler')->row();
        $kod = $row ? strtoupper(trim((string) $row->para_birimi)) : 'TRY';
        return $kod !== '' ? $kod : 'TRY';
    }
}

if ( ! function_exists('para_cevir'))
{
    /** TRY tutari hedef para birimine cevir (kur_try ile bol). */
    function para_cevir($tutar_try, $hedef_kod)
    {
        $kur = kur_getir($hedef_kod);
        if ($kur <= 0) { $kur = 1.0; }
        return (float) $tutar_try / $kur;
    }
}

if ( ! function_exists('para_formatla'))
{
    /** Tutari verilen para biriminin semboluyle bicimle: "1.234,50 $". */
    function para_formatla($tutar, $kod)
    {
        $kod = strtoupper(trim((string) $kod));
        if ($kod === '') { $kod = 'TRY'; }
        $map = _para_birim_harita();
        $sembol = isset($map[$kod]) ? $map[$kod]['sembol'] : $kod;
        return number_format((float) $tutar, 2, ',', '.') . ' ' . $sembol;
    }
}

if ( ! function_exists('para_goster'))
{
    /** TRY bazli tutari bayinin (ya da belirtilen) para biriminde goster. */
    function para_goster($tutar_try, $kod = NULL)
    {
        $kod = $kod ? strtoupper(trim((string) $kod)) : aktif_para_birimi();
        return para_formatla(para_cevir($tutar_try, $kod), $kod);
    }
}
