<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * LVI: markalı 404 sayfası — CI çekirdek hata görünümü yerine.
 * Bağımsız çalışır: DB/oturum/harici CSS YOK (hata anında erişilemez olabilir);
 * dil tarayıcının Accept-Language başlığından (tr/en/ru/ar), alt dizin kurulumunu
 * desteklemek için dönüş kökü SCRIPT_NAME'den hesaplanır (footer tkBase deseni).
 * Marka adı bilinçli hardcode — ayarlar DB'ye bağlı (favicon.svg ile aynı durum).
 */
$_eb  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$_kes = (int) strrpos($_eb, '/');
$_kok = ($_kes > 0 ? rtrim(substr($_eb, 0, $_kes), '/') : '') . '/';
$_dil = strtolower(substr((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'tr'), 0, 2));
$_M = array(
    'tr' => array('Sayfa Bulunamadı', 'Aradığınız sayfa taşınmış ya da hiç var olmamış olabilir.', 'Anasayfaya Dön'),
    'en' => array('Page Not Found', 'The page you are looking for may have been moved or never existed.', 'Back to Home'),
    'ru' => array('Страница не найдена', 'Возможно, страница была перемещена или не существовала.', 'На главную'),
    'ar' => array('الصفحة غير موجودة', 'ربما تم نقل الصفحة أو أنها لم تكن موجودة.', 'العودة إلى الرئيسية'),
);
if (! isset($_M[$_dil])) { $_dil = 'tr'; }
?>
<!DOCTYPE html>
<html lang="<?= $_dil ?>" dir="<?= $_dil === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>404 — <?= htmlspecialchars($_M[$_dil][0]) ?></title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#001e2b;color:#e8f0f4;font-family:Georgia,'Times New Roman',serif;text-align:center;padding:24px;box-sizing:border-box;}
  .k{max-width:440px;}
  .marka{font-size:26px;letter-spacing:.02em;margin-bottom:26px;}
  .marka b{color:#fff;font-weight:700;}
  .marka i{color:#b98d5f;font-style:normal;}
  .kod{font-size:84px;line-height:1;color:#b98d5f;font-weight:700;margin-bottom:10px;}
  h1{font-size:20px;margin:0 0 10px;color:#fff;font-weight:600;}
  p{margin:0 0 28px;color:#9db4c0;font-size:14.5px;font-family:Helvetica,Arial,sans-serif;}
  a.dugme{display:inline-block;background:#b98d5f;color:#001e2b;text-decoration:none;font-weight:700;font-size:14px;padding:11px 22px;border-radius:8px;font-family:Helvetica,Arial,sans-serif;}
  a.dugme:hover{filter:brightness(1.1);}
</style>
</head>
<body>
<div class="k">
    <div class="marka"><b>Nesem</b> <i>Tesettür</i></div>
    <div class="kod">404</div>
    <h1><?= htmlspecialchars($_M[$_dil][0]) ?></h1>
    <p><?= htmlspecialchars($_M[$_dil][1]) ?></p>
    <a class="dugme" href="<?= htmlspecialchars($_kok) ?>"><?= htmlspecialchars($_M[$_dil][2]) ?></a>
</div>
</body>
</html>
