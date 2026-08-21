<?php defined('BASEPATH') OR exit('No direct script access allowed');
$_dil   = $dil ?? 'tr';
$_title = !empty($meta_title) ? $meta_title : ($site_adi ?? 'Nesem Tesettür');
$_desc  = !empty($meta_desc)  ? $meta_desc  : t('meta_desc_default', 'Toptan kadın giyim — üretici fiyatı, kaliteli kumaş, hızlı kargo.');
$_index = isset($indexlenebilir) ? $indexlenebilir : TRUE;
?>
<!DOCTYPE html>
<!-- XLVII: Arapça'da sayfa yer değiştirme (RTL flip) sahibin isteğiyle kaldırıldı —
     AR dahil tüm diller LTR düzen; yalnızca metinler Arapça. -->
<html lang="<?= e($_dil) ?>" dir="ltr">
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
<?php
// LII: favicon seti + sosyal paylaşım kartı (OG/Twitter) — WhatsApp vb. link
// önizlemesi görselsiz çıkmasın. Ürün sayfası $og_gorsel/$og_tip ile ürünün
// ana görselini ve 'product' tipini override eder; varsayılanlar marka kartı.
$_og_tip      = $og_tip ?? 'website';
$_og_baslik   = $og_baslik ?? $_title;
$_og_aciklama = ! empty($og_aciklama) ? $og_aciklama : $_desc;
$_og_gorsel   = $og_gorsel ?? asset('magaza/img/og-default.png');
$_og_lcl      = array('tr' => 'tr_TR', 'en' => 'en_US', 'ru' => 'ru_RU', 'ar' => 'ar_AR');
$_og_lcl      = $_og_lcl[$_dil] ?? 'tr_TR';
?>
<link rel="icon" href="<?= asset('magaza/img/favicon.svg') ?>" type="image/svg+xml">
<link rel="icon" href="<?= asset('magaza/img/favicon-32.png') ?>" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="<?= asset('magaza/img/apple-touch-icon.png') ?>">
<meta property="og:type" content="<?= e($_og_tip) ?>">
<meta property="og:site_name" content="<?= e($site_adi ?? 'Nesem Tesettür') ?>">
<meta property="og:locale" content="<?= e($_og_lcl) ?>">
<meta property="og:title" content="<?= e($_og_baslik) ?>">
<meta property="og:description" content="<?= e($_og_aciklama) ?>">
<meta property="og:url" content="<?= e($_kanonik) ?>">
<meta property="og:image" content="<?= e($_og_gorsel) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($_og_baslik) ?>">
<meta name="twitter:description" content="<?= e($_og_aciklama) ?>">
<meta name="twitter:image" content="<?= e($_og_gorsel) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('magaza/css/teksil.css') ?>">
</head>
<body>
