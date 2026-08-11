<?php defined('BASEPATH') OR exit('No direct script access allowed');
$_title = !empty($meta_title) ? $meta_title : ($site_adi ?? 'TekstilSite');
$_desc  = !empty($meta_desc)  ? $meta_desc  : 'Toptan kadın giyim — üretici fiyatı, kaliteli kumaş, hızlı kargo.';
$_index = isset($indexlenebilir) ? $indexlenebilir : TRUE;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($_title) ?></title>
<meta name="description" content="<?= e($_desc) ?>">
<?php if (! $_index): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Source+Code+Pro:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('magaza/css/teksil.css') ?>">
</head>
<body>
