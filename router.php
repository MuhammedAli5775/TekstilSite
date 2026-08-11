<?php
/**
 * PHP yerleşik sunucu (php -S) yönlendiricisi — Faz 0 dev önizlemesi için.
 * Apache kullanacaksan bunu görmezden gel (.htaccess işi görür).
 *
 * Çalıştırma:
 *   C:\xampp\php\php.exe -S localhost:8000 router.php
 *   → http://localhost:8000
 *
 * Statik dosyaları (assets/, uploads/) doğrudan servis eder; geri kalanı CI'ya.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// Kök değilse ve gerçek bir statik dosyaysa → sunucu servis etsin
if ($uri !== '/' && $uri !== '') {
    $aday = __DIR__ . $uri;
    if (file_exists($aday) && is_file($aday) && strtolower(pathinfo($aday, PATHINFO_EXTENSION)) !== 'php') {
        return false; // yerleşik sunucu dosyayı servis eder
    }
}

// Geri kalan her şey CodeIgniter front controller'a gider
$_SERVER['PATH_INFO']       = $uri;
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
