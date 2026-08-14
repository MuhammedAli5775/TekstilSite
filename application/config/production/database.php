<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------|
| PRODUCTION veritabanı ayarları (EKSİKSİZ dosya — parçalı DEĞİL)          |
|--------------------------------------------------------------------------|
| DİKKAT: environment database.php mevcutsa CI3 temel                      |
| application/config/database.php'yi HİÇ yüklemez (system/database/DB.php: |
| 58 — env dosyası varsa yalnızca o include edilir). Bu dosya bu yüzden    |
| tam $db tanımı içerir.                                                   |
|                                                                          |
| Kurulum öncesi doldur: hostname / username / password / database.        |
| Ayrıcalıklı kullanıcı oluştur (root KULLANMA — B3, en-az-ayrıcalık):     |
|                                                                          |
|   CREATE USER 'teksil_app'@'localhost' IDENTIFIED BY '<SERT_PAROLA>';   |
|   GRANT ALL PRIVILEGES ON teksilsite.* TO 'teksil_app'@'localhost';     |
|   FLUSH PRIVILEGES;                                                      |
|                                                                          |
| (Tek veritabanına kısıtlı ALL; global/SUPER/FILE ayrıcalığı YOK.        |
| Migration'lar kurulum sırasında ALTER/CREATE/DROP da kullanacağı için   |
| kurulumdan sonra istenirse INSERT/SELECT/UPDATE/DELETE/LOCK TABLES'a     |
| düşürülebilir.)                                                          |
|--------------------------------------------------------------------------|
*/

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => '127.0.0.1',
	'username' => 'teksil_app',
	'password' => 'SERT_PAROLA_BURAYA',
	'database' => 'teksilsite',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	// db_debug=FALSE (dev ile aynı): DB hatası fatal yerine graceful düşer.
	'db_debug' => FALSE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_unicode_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	// MySQL 5.7+/8 varsayılanı zaten STRICT; açıkça sabitle.
	'stricton' => TRUE,
	'failover' => array(),
	'save_queries' => (ENVIRONMENT !== 'production')
);
