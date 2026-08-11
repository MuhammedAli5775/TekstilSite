<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-08-07 15:28:18 --> Severity: Warning --> mysqli::real_connect(): (HY000/1045): Access denied for user 'root'@'localhost' (using password: NO) C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\system\database\drivers\mysqli\mysqli_driver.php 211
ERROR - 2026-08-07 15:28:18 --> Unable to connect to the database
ERROR - 2026-08-07 15:29:16 --> Unable to connect to the database
ERROR - 2026-08-07 15:30:23 --> Unable to connect to the database
ERROR - 2026-08-07 15:32:53 --> Unable to connect to the database
ERROR - 2026-08-07 15:32:53 --> 404 Page Not Found: Faviconico/index
ERROR - 2026-08-07 15:33:03 --> 404 Page Not Found: Katalog/alt_giyim
ERROR - 2026-08-07 15:33:05 --> Unable to connect to the database
ERROR - 2026-08-07 15:33:05 --> 404 Page Not Found: Katalog/ust_giyim
ERROR - 2026-08-07 15:33:07 --> Unable to connect to the database
ERROR - 2026-08-07 15:33:09 --> 404 Page Not Found: Urun/suprem_v_yaka_body
ERROR - 2026-08-07 15:33:10 --> Unable to connect to the database
ERROR - 2026-08-07 15:33:21 --> 404 Page Not Found: Urun/oversize_gomlek
ERROR - 2026-08-07 15:33:22 --> Unable to connect to the database
ERROR - 2026-08-07 15:33:39 --> 404 Page Not Found: Bayi/kayit
ERROR - 2026-08-07 16:09:31 --> ARAMA_DEBUG q=[tirt] hex=[74697274] toplam=0 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%tirt%' ESCAPE '!'
OR  `stok_kodu` LIKE '%tirt%' ESCAPE '!'
OR  `aciklama` LIKE '%tirt%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:09:31 --> ARAMA_DEBUG q=[ifon] hex=[69666f6e] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%ifon%' ESCAPE '!'
OR  `stok_kodu` LIKE '%ifon%' ESCAPE '!'
OR  `aciklama` LIKE '%ifon%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:14:34 --> ARAMA_RAW qs=[q=ti%fe%f6rt] _get=[74697274] inputget=[74697274]
ERROR - 2026-08-07 16:14:34 --> ARAMA_DEBUG q=[tirt] hex=[74697274] toplam=0 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%tirt%' ESCAPE '!'
OR  `stok_kodu` LIKE '%tirt%' ESCAPE '!'
OR  `aciklama` LIKE '%tirt%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:31 --> ARAMA_RAW qs=[q=%C5%9F] _get=[c59f] inputget=[c59f]
ERROR - 2026-08-07 16:15:31 --> ARAMA_DEBUG q=[ş] hex=[c59f] toplam=8 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%ş%' ESCAPE '!'
OR  `stok_kodu` LIKE '%ş%' ESCAPE '!'
OR  `aciklama` LIKE '%ş%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:31 --> ARAMA_RAW qs=[q=g%C3%B6mlek] _get=[67c3b66d6c656b] inputget=[67c3b66d6c656b]
ERROR - 2026-08-07 16:15:31 --> ARAMA_DEBUG q=[gömlek] hex=[67c3b66d6c656b] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%gömlek%' ESCAPE '!'
OR  `stok_kodu` LIKE '%gömlek%' ESCAPE '!'
OR  `aciklama` LIKE '%gömlek%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:31 --> ARAMA_RAW qs=[q=pantolon] _get=[70616e746f6c6f6e] inputget=[70616e746f6c6f6e]
ERROR - 2026-08-07 16:15:31 --> ARAMA_DEBUG q=[pantolon] hex=[70616e746f6c6f6e] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%pantolon%' ESCAPE '!'
OR  `stok_kodu` LIKE '%pantolon%' ESCAPE '!'
OR  `aciklama` LIKE '%pantolon%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:31 --> ARAMA_RAW qs=[q=ti%C5%9F%C3%B6rt] _get=[7469c59fc3b67274] inputget=[7469c59fc3b67274]
ERROR - 2026-08-07 16:15:31 --> ARAMA_DEBUG q=[tişört] hex=[7469c59fc3b67274] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%tişört%' ESCAPE '!'
OR  `stok_kodu` LIKE '%tişört%' ESCAPE '!'
OR  `aciklama` LIKE '%tişört%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:32 --> ARAMA_RAW qs=[q=%C5%9Fifon] _get=[c59f69666f6e] inputget=[c59f69666f6e]
ERROR - 2026-08-07 16:15:32 --> ARAMA_DEBUG q=[şifon] hex=[c59f69666f6e] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%şifon%' ESCAPE '!'
OR  `stok_kodu` LIKE '%şifon%' ESCAPE '!'
OR  `aciklama` LIKE '%şifon%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:32 --> ARAMA_RAW qs=[q=h%C4%B1rka] _get=[68c4b1726b61] inputget=[68c4b1726b61]
ERROR - 2026-08-07 16:15:32 --> ARAMA_DEBUG q=[hırka] hex=[68c4b1726b61] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%hırka%' ESCAPE '!'
OR  `stok_kodu` LIKE '%hırka%' ESCAPE '!'
OR  `aciklama` LIKE '%hırka%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:15:32 --> ARAMA_RAW qs=[q=s%C3%BCprem] _get=[73c3bc7072656d] inputget=[73c3bc7072656d]
ERROR - 2026-08-07 16:15:32 --> ARAMA_DEBUG q=[süprem] hex=[73c3bc7072656d] toplam=1 SQL=SELECT `id`, `ad`, `slug`, `stok_kodu`, `ana_gorsel`, `fiyat`, `eski_fiyat`, `moq`
FROM `urunler`
WHERE `durum` = 1
AND   (
`ad` LIKE '%süprem%' ESCAPE '!'
OR  `stok_kodu` LIKE '%süprem%' ESCAPE '!'
OR  `aciklama` LIKE '%süprem%' ESCAPE '!'
 )
ORDER BY `olusturma_zaman` DESC
 LIMIT 12
ERROR - 2026-08-07 16:36:42 --> Severity: error --> Exception: Call to undefined function validation_errors() C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\magaza\odeme\index.php 5
ERROR - 2026-08-07 16:38:16 --> Query error: Unknown column 'ic_not' in 'field list' - Invalid query: INSERT INTO `siparisler` (`siparis_no`, `bayi_id`, `para_birimi`, `kur`, `ara_toplam`, `indirim`, `islem_ucreti`, `kargo_ucreti`, `toplam`, `odeme_yontemi`, `odeme_durumu`, `durum`, `teslimat_ad`, `teslimat_adres`, `teslimat_il`, `teslimat_ilce`, `teslimat_telefon`, `email`, `fatura_ad`, `fatura_adres`, `firma_adi`, `vergi_no`, `kargo_firma_id`, `ic_not`) VALUES ('TS26080723E551', NULL, 'TRY', 1, 659.7, 0, 0, 79.9, 739.6, 'Havale / EFT', 'bekliyor', 'onay_bekliyor', 'Test Toptanci', 'Test Mah. Test Cad. No:1', 'İstanbul', 'Merter', '05551112233', 'test@ornek.test', 'Test Toptanci', 'Test Mah. Test Cad. No:1', 'Test Tekstil Ltd', '1234567890', 1, NULL)
ERROR - 2026-08-07 16:38:16 --> Query error: Cannot add or update a child row: a foreign key constraint fails (`teksilsite`.`siparis_detaylari`, CONSTRAINT `fk_detay_siparis` FOREIGN KEY (`siparis_id`) REFERENCES `siparisler` (`id`) ON DELETE CASCADE) - Invalid query: INSERT INTO `siparis_detaylari` (`urun_id`, `urun_adi`, `stok_kodu`, `varyant_bilgi`, `birim_fiyat`, `adet`, `kdv`, `ara_toplam`, `siparis_id`) VALUES (5, 'Şifon Elbise', 'TKS-3001', 'Siyah / S', 219.9, 3, 20, 659.7, 0)
ERROR - 2026-08-07 16:38:16 --> Query error: Cannot add or update a child row: a foreign key constraint fails (`teksilsite`.`siparis_durum_gecmisi`, CONSTRAINT `fk_gecmis_siparis` FOREIGN KEY (`siparis_id`) REFERENCES `siparisler` (`id`) ON DELETE CASCADE) - Invalid query: INSERT INTO `siparis_durum_gecmisi` (`siparis_id`, `durum`, `taraf`, `notu`) VALUES (0, 'onay_bekliyor', 'sistem', 'Sipariş alındı')
ERROR - 2026-08-07 16:39:22 --> Query error: Unknown column 'ic_not' in 'field list' - Invalid query: INSERT INTO `siparisler` (`siparis_no`, `bayi_id`, `para_birimi`, `kur`, `ara_toplam`, `indirim`, `islem_ucreti`, `kargo_ucreti`, `toplam`, `odeme_yontemi`, `odeme_durumu`, `durum`, `teslimat_ad`, `teslimat_adres`, `teslimat_il`, `teslimat_ilce`, `teslimat_telefon`, `email`, `fatura_ad`, `fatura_adres`, `firma_adi`, `vergi_no`, `kargo_firma_id`, `ic_not`) VALUES ('TS260807850D0C', NULL, 'TRY', 1, 659.7, 0, 0, 79.9, 739.6, 'Havale / EFT', 'bekliyor', 'onay_bekliyor', 'Test Toptanci', 'Test Mah. Test Cad. No:1', 'İstanbul', 'Merter', '05551112233', 'test@ornek.test', 'Test Toptanci', 'Test Mah. Test Cad. No:1', 'Test Tekstil Ltd', '1234567890', 1, NULL)
ERROR - 2026-08-07 16:39:22 --> Query error: Cannot add or update a child row: a foreign key constraint fails (`teksilsite`.`siparis_detaylari`, CONSTRAINT `fk_detay_siparis` FOREIGN KEY (`siparis_id`) REFERENCES `siparisler` (`id`) ON DELETE CASCADE) - Invalid query: INSERT INTO `siparis_detaylari` (`urun_id`, `urun_adi`, `stok_kodu`, `varyant_bilgi`, `birim_fiyat`, `adet`, `kdv`, `ara_toplam`, `siparis_id`) VALUES (5, 'Şifon Elbise', 'TKS-3001', 'Siyah / S', 219.9, 3, 20, 659.7, 0)
ERROR - 2026-08-07 16:39:22 --> Query error: Cannot add or update a child row: a foreign key constraint fails (`teksilsite`.`siparis_durum_gecmisi`, CONSTRAINT `fk_gecmis_siparis` FOREIGN KEY (`siparis_id`) REFERENCES `siparisler` (`id`) ON DELETE CASCADE) - Invalid query: INSERT INTO `siparis_durum_gecmisi` (`siparis_id`, `durum`, `taraf`, `notu`) VALUES (0, 'onay_bekliyor', 'sistem', 'Sipariş alındı')
ERROR - 2026-08-07 16:40:34 --> Eposta: SMTP yapılandırılmamış — sipariş TS260807159F55 e-postası atlandı (graceful).
ERROR - 2026-08-07 16:41:27 --> Eposta: SMTP yapılandırılmamış — sipariş TS26080707ED5D e-postası atlandı (graceful).
ERROR - 2026-08-07 17:22:06 --> Query error: Unknown column 'son_giris' in 'field list' - Invalid query: UPDATE `bayiler` SET `son_giris` = '2026-08-07 17:22:06'
WHERE `id` = 2
ERROR - 2026-08-07 17:22:07 --> Eposta: SMTP yapılandırılmamış — sipariş TS260807063270 e-postası atlandı (graceful).
ERROR - 2026-08-07 17:22:49 --> 404 Page Not Found: 
ERROR - 2026-08-07 17:22:49 --> Query error: Unknown column 'son_giris' in 'field list' - Invalid query: UPDATE `bayiler` SET `son_giris` = '2026-08-07 17:22:49'
WHERE `id` = 1
ERROR - 2026-08-07 17:22:49 --> 404 Page Not Found: 
ERROR - 2026-08-07 17:23:53 --> 404 Page Not Found: 
ERROR - 2026-08-07 17:47:54 --> Eposta: SMTP yok — durum bildirimi atlandı (graceful).
ERROR - 2026-08-07 17:48:27 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:48:28 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:48:28 --> 404 Page Not Found: Ayarlar/index
ERROR - 2026-08-07 17:50:52 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:50:52 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:50:53 --> Eposta: SMTP yok — durum bildirimi atlandı (graceful).
ERROR - 2026-08-07 17:52:55 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:53:24 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:53:53 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 17:53:53 --> Severity: Warning --> Undefined property: stdClass::$son_giris C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\views\yonetim\bayiler\detay.php 65
ERROR - 2026-08-07 18:32:32 --> Severity: error --> Exception: Too few arguments to function Urunler::duzenle(), 0 passed in C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\system\core\CodeIgniter.php on line 533 and exactly 1 expected C:\Users\fbber\OneDrive\Masaüstü\Yazılım Projeleri\TekstilSite\application\controllers\yonetim\Urunler.php 38
ERROR - 2026-08-07 18:33:07 --> 404 Page Not Found: 
ERROR - 2026-08-07 18:34:26 --> 404 Page Not Found: Kategoriler/kaydet
