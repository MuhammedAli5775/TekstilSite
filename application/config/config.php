<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------|
| Base Site URL                                                            |
|--------------------------------------------------------------------------|
| Boş bırakılırsa CI3 auto-detect yapıyor ve PHP yerleşik sunucuda PORT    |
| (8000) düşüyor → tarayıcı CSS/form isteklerini :80'a atıyor. Dev için    |
| açıkça http://localhost:8000/. Canlıda gerçek prod URL'i yazılır.        |
|--------------------------------------------------------------------------|
*/
$config['base_url'] = 'http://localhost:8000/';

/*
|--------------------------------------------------------------------------|
| Index File                                                               |
|--------------------------------------------------------------------------|
*/
$config['index_page'] = '';

/*
|--------------------------------------------------------------------------|
| URI PROTOCOL                                                             |
|--------------------------------------------------------------------------|
*/
$config['uri_protocol']	= 'REQUEST_URI';

/*
|--------------------------------------------------------------------------|
| URL suffix                                                               |
|--------------------------------------------------------------------------|
*/
$config['url_suffix'] = '';

/*
|--------------------------------------------------------------------------|
| Default Language and Character Set                                       |
|--------------------------------------------------------------------------|
*/
$config['language']	= 'turkish';
$config['charset'] = 'UTF-8';

/*
|--------------------------------------------------------------------------|
| General Variables                                                        |
|--------------------------------------------------------------------------|
*/
$config['composer_autoload'] = FALSE;
$config['allow_get_array'] = TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

/*
|--------------------------------------------------------------------------|
| Extension Hook & Class Prefixes                                          |
|--------------------------------------------------------------------------|
| subclass_prefix: çekirdek sınıf genişletmelerinin öneki (MY_Controller).  |
|--------------------------------------------------------------------------|
*/
$config['subclass_prefix'] = 'MY_';

/*
|--------------------------------------------------------------------------|
| Error Logging Threshold                                                  |
|--------------------------------------------------------------------------|
*/
$config['log_threshold'] = 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------|
| Error Views Directory Path                                               |
|--------------------------------------------------------------------------|
*/
$config['error_views_path'] = '';

/*
|--------------------------------------------------------------------------|
| Cache Directory Path                                                     |
|--------------------------------------------------------------------------|
*/
$config['cache_path'] = '';

/*
|--------------------------------------------------------------------------|
| Encryption Key                                                           |
| CI Encryption (pazaryeri API anahtarları) için. Üretimde değiştirme.     |
|--------------------------------------------------------------------------|
*/
$config['encryption_key'] = '412378c18e674f4a3871afceb9a53785f4b575eb7af561578f6e6055e6f63080';

/*
|--------------------------------------------------------------------------|
| Session Variables                                                        |
| Driver: files (OneDrive kilit riskine karşın DB değil; save_path tmp).   |
|--------------------------------------------------------------------------|
*/
$config['sess_driver'] = 'files';
$config['sess_cookie_name'] = 'teksil_sess';
$config['sess_expiration'] = 7200;
$config['sess_save_path'] = 'C:/xampp/tmp';
$config['sess_match_ip'] = FALSE;
/* L: periyodik oturum-ID döndürmesi KAPALI. CI3 5 dakikada bir session_id
   yeniler (veri korunur, çerez değişir) — oturum-anahtarlı (bayi_id'siz) misafir
   ve B2C sepetleri eski anahtarda yetim kalıp "sepet boş" görünüyordu (ödeme
   formunu 5+ dk doldurup gönderince). Fixation savunması etkilenmiyor: giriş/
   yetki değişiminde sess_regenerate AÇIK çağrılıyor (sepet devriyle birlikte). */
$config['sess_time_to_update'] = 0;
$config['sess_regenerate_destroy'] = FALSE;

/*
|--------------------------------------------------------------------------|
| Cookie Related Variables                                                 |
|--------------------------------------------------------------------------|
*/
$config['cookie_prefix']	= '';
$config['cookie_domain']	= '';
$config['cookie_path']		= '/';
$config['cookie_secure']	= FALSE;
$config['cookie_httponly'] 	= FALSE;

/*
|--------------------------------------------------------------------------|
| Standardize newlines                                                     |
|--------------------------------------------------------------------------|
*/
$config['standardize_newlines'] = FALSE;

/*
|--------------------------------------------------------------------------|
| Global XSS Filtering                                                    |
|--------------------------------------------------------------------------|
| View'larda e() (htmlspecialchars) ile escape edildiği için FALSE.        |
*/
$config['global_xss_filtering'] = FALSE;

/*
|--------------------------------------------------------------------------|
| Cross Site Request Forgery                                               |
|--------------------------------------------------------------------------|
| Her formda CSRF açık. paytr/bildirim callback'u muaf (hash doğrular).   |
*/
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'teksil_csrf';
$config['csrf_cookie_name'] = 'teksil_csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = FALSE;
$config['csrf_exclude_uris'] = array(
	'paytr/bildirim',
);

/*
|--------------------------------------------------------------------------|
| Output Compression                                                       |
|--------------------------------------------------------------------------|
*/
$config['compress_output'] = FALSE;

/*
|--------------------------------------------------------------------------|
| Master Time Reference                                                    |
|--------------------------------------------------------------------------|
*/
$config['time_reference'] = 'local';

/*
|--------------------------------------------------------------------------|
| Rewrite PHP Short Tags                                                   |
|--------------------------------------------------------------------------|
*/
$config['rewrite_short_tags'] = FALSE;

/*
|--------------------------------------------------------------------------|
| Reverse Proxy IPs                                                        |
|--------------------------------------------------------------------------|
*/
$config['proxy_ips'] = '';
