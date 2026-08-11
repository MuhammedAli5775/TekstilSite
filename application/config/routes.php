<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'anasayfa';
$route['404_override'] = '';
// FALSE: dashed slug'ları (ust-giyim vb.) olduğu gibi korumak için.
// (controller/method adlarımızda tire yok; slug'larda var.)
$route['translate_uri_dashes'] = FALSE;

/*
| -------------------------------------------------------------------------
| TekstilSite rotaları (mağaza)
| -------------------------------------------------------------------------
*/
$route['sitemap\.xml'] = 'seo/sitemap';

// Katalog (sıra önemli: özele -> genele)
$route['katalog']                    = 'katalog/index';
$route['katalog/yeni']               = 'katalog/yeni';
$route['katalog/(:any)/(:any)']      = 'katalog/kategori/$1/$2';
$route['katalog/(:any)']             = 'katalog/kategori/$1';

$route['urun/(:any)']                = 'urun/detay/$1';
$route['arama']                      = 'arama/index';

// Bayi (B2B) & Hesabım
$route['hesabim']                    = 'hesap/index';
$route['hesabim/siparisler']         = 'hesap/siparisler';
$route['hesabim/siparis/(:num)']     = 'hesap/siparis_detay/$1';
$route['hesabim/bilgiler']           = 'hesap/bilgiler';
$route['hesabim/bilgiler/kaydet']    = 'hesap/bilgiler_kaydet';
$route['hesabim/sifre']              = 'hesap/sifre';
$route['hesabim/sifre/kaydet']       = 'hesap/sifre_kaydet';

// Yönetim paneli (/yonetim → giriş)
$route['yonetim']                    = 'yonetim/giris';

/*
| -------------------------------------------------------------------------
| TekstilSite rotaları (mağaza — explicit mapping)
| -------------------------------------------------------------------------
*/

// CMS / misafir sayfaları (Sayfa controller)
$route['yardim']                     = 'sayfa/yardim';
$route['blog']                       = 'sayfa/blog';
$route['favorilerim']                = 'sayfa/favorilerim';
$route['siparis-takip']              = 'sayfa/siparis_takip';
$route['favoriler/ekle/(:num)']      = 'sayfa/favoriler_ekle/$1';
$route['favoriler/sil/(:num)']       = 'sayfa/favoriler_sil/$1';

// B2B katalog feed (XML/JSON, ?key= ile kimlik)
$route['feed/urunler']               = 'api/feed/urunler';

// PayTR ödeme bildirimi (CSRF muaf — config csrf_exclude_uris)
$route['paytr/bildirim']             = 'paytr_bildirim/index';

// SEO
$route['robots\.txt']                = 'seo/robots';

// CMS sayfalari (footer / kurumsal — sayfalar tablosundan slug ile)
$route['sayfa/(:any)']               = 'sayfa/goster/$1';
$route['iletisim']                   = 'sayfa/goster/iletisim';
$route['toptan-sartlari']            = 'sayfa/goster/toptan-sartlari';
$route['xml-feed']                   = 'sayfa/goster/xml-feed';

/*
| Diğer rotalar (bayi/giris, bayi/kayit, sepet/*, odeme/*, kategori/urun/yönetim
| CRUD) CI3 varsayılan yönlendirmesiyle (controller/method/arg) çalışır.
*/
