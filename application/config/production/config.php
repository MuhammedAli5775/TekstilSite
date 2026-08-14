<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------|
| PRODUCTION config override'ları                                          |
|--------------------------------------------------------------------------|
| CI3 önce application/config/config.php'i yükler, SONRA bu dosyayı        |
| üstüne bindirir (get_config(), system/core/common.php:253). Bu yüzden    |
| burada YALNIZCA production'da değişen anahtarlar vardır.                 |
|                                                                          |
| Aktivasyon: ENVIRONMENT='production' — Apache: `SetEnv CI_ENV production`|
| (.htaccess ya da vhost), CLI/cron: `CI_ENV=production php index.php ...` |
| (index.php:56 $_SERVER['CI_ENV'] okur).                                  |
|--------------------------------------------------------------------------|
*/

/*
| Base URL — gerçek alan adınla doldur (sondaki / şart).
| Boş bırakma: CI3 auto-detect'i Host-header spoof'una açık.
*/
$config['base_url'] = 'https://ALAN_ADIN_BURAYA/';

/*
| Oturum dosyaları — application/sessions/ (Linux yolu; dev'deki C:/xampp/tmp
| değil). .htaccess application/ dizinini zaten web'e kapalı tutuyor.
| DEPLOY.md kurulum adımı bu dizini oluşturur; .gitignore'dadır.
*/
$config['sess_save_path'] = APPPATH.'sessions';

/*
| HTTPS zorunlu: tüm çerezler (oturum + CSRF dahil) yalnız secure bağlamda.
| JS CSRF'i document.cookie'den DEĞİL window.tkCsrf + gizli input'tan alır
| (assets/magaza/js/teksil.js:27) → httponly açmak güvenli.
*/
$config['cookie_secure']   = TRUE;
$config['cookie_httponly'] = TRUE;

/*
| gzip'i Apache mod_deflate yapıyor (.htaccess) → PHP katmanında çifte
| sıkıştırma istemiyoruz.
*/
$config['compress_output'] = FALSE;

/*
| Ters proxy (Cloudflare vb.) arkasına geçilirse doldur, yoksa boş bırak.
| Boşken X-Forwarded-For'e güvenilmez → input->ip_address() gerçek peer döner.
*/
$config['proxy_ips'] = '';
