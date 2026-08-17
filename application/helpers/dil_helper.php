<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * dil_helper — mağaza çoklu-dil altyapısı (DEGISIKLIK XXIX).
 * TR varsayılan; EN/RU/AR oturum + 1 yıllık çerezle seçilir (Dil::cevir).
 * Önce Türkçe dosya yüklenir, aktif dil üstüne yazar → çevrilmemiş anahtar
 * Türkçe kalır; görünüm katmanı artımlı çevrilir, sayfa çevirisiz kalmaz.
 */

if (! function_exists('aktif_dil'))
{
    /** Oturum → çerez → 'tr' (kod beyaz listeli). */
    function aktif_dil()
    {
        static $dil = NULL;
        if ($dil !== NULL) { return $dil; }
        $CI =& get_instance();
        $CI->load->library('session');
        $kod = $CI->session->userdata('dil');
        if (! $kod) { $kod = (string) $CI->input->cookie('teksil_dil'); }
        $dil = in_array($kod, array('tr', 'en', 'ru', 'ar'), TRUE) ? $kod : 'tr';
        return $dil;
    }
}

if (! function_exists('dil_klasor'))
{
    /** Dil kodu → language/ klasörü. */
    function dil_klasor($kod)
    {
        $map = array('tr' => 'turkish', 'en' => 'english', 'ru' => 'russian', 'ar' => 'arabic');
        return isset($map[$kod]) ? $map[$kod] : 'turkish';
    }
}

if (! function_exists('dil_adi'))
{
    /** Dil kodu → kendi dilindeki adı (seçici etiketi). */
    function dil_adi($kod)
    {
        $adlar = array('tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский', 'ar' => 'العربية');
        return isset($adlar[$kod]) ? $adlar[$kod] : 'Türkçe';
    }
}

if (! function_exists('dil_satirlari'))
{
    /** Önce TR, sonra aktif dil include edilip birleşir (istek başına önbellek). */
    function dil_satirlari()
    {
        static $L = NULL;
        if ($L !== NULL) { return $L; }
        $L = array();
        $kod = aktif_dil();
        $klasorler = $kod === 'tr' ? array('turkish') : array('turkish', dil_klasor($kod));
        foreach ($klasorler as $klasor) {
            $dosya = APPPATH . 'language/' . $klasor . '/teksil_lang.php';
            if (is_file($dosya)) {
                $lang = array();
                include $dosya;   // dosya $lang dizisi tanımlar
                if (is_array($lang)) { $L = array_merge($L, $lang); }
            }
        }
        return $L;
    }
}

if (! function_exists('t'))
{
    /** Anahtarın aktif dildeki karşılığı; yoksa Türkçe karşılık, o da yoksa
     *  $varsayilan. Ek argümanlar vsprintf'e gider: t('ftr_telif', '© %s …', $yil). */
    function t($anahtar, $varsayilan = '')
    {
        $L = dil_satirlari();
        $satir = (isset($L[$anahtar]) && $L[$anahtar] !== '') ? $L[$anahtar] : $varsayilan;
        $args = func_get_args();
        array_splice($args, 0, 2);
        return $args ? vsprintf($satir, $args) : $satir;
    }
}
