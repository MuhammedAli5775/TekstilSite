<?php defined('BASEPATH') OR exit('No direct script access allowed');
$_dil   = $dil ?? 'tr';
$_title = !empty($meta_title) ? $meta_title : ($site_adi ?? 'TekstilSite');
$_desc  = !empty($meta_desc)  ? $meta_desc  : t('meta_desc_default', 'Toptan kadın giyim — üretici fiyatı, kaliteli kumaş, hızlı kargo.');
$_index = isset($indexlenebilir) ? $indexlenebilir : TRUE;
?>
<!DOCTYPE html>
<html lang="<?= e($_dil) ?>" dir="<?= $_dil === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($_title) ?></title>
<meta name="description" content="<?= e($_desc) ?>">
<?php if (! $_index): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
<?php
// Kanonik URL (XXXVII): filtre/sıralama parametreleri atılır, sayfalama (sayfa)
// korunur — filtreli görünümler temiz kategori URL'sine, sayfa 2 kendine işaret eder.
$_kanonik_qs = $_GET;
unset($_kanonik_qs['beden'], $_kanonik_qs['renk'], $_kanonik_qs['min'], $_kanonik_qs['max'], $_kanonik_qs['sira']);
$_kanonik = site_url(uri_string() === '' ? '' : uri_string()) . ($_kanonik_qs ? '?' . http_build_query($_kanonik_qs) : '');
?>
<link rel="canonical" href="<?= e($_kanonik) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('magaza/css/teksil.css') ?>">
</head>
<body>
