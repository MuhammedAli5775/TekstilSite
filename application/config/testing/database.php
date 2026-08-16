<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS — TESTING (sıfır-DB kurulum provası)
| -------------------------------------------------------------------
| CI_ENV=testing ile yüklenir (ör. CI_ENV=testing npm run dev).
| Base database.php'in kopyası — yalnız 'database' scratch prova
| şemasına işaret eder (DEPLOY.md §3 sırasıyla kurulur; koşu sonrası
| DROP edilir). Dev DB'sine (teksilsite) dokunulmaz.
*/
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => '127.0.0.1',
	'username' => 'root',
	'password' => 'mysql1234',
	'database' => 'teksilsite_rehearsal',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	// db_debug=FALSE: DB bağlı değilken fatal atmasın (base config ile aynı).
	'db_debug' => FALSE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8mb4',
	'dbcollat' => 'utf8mb4_unicode_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => (ENVIRONMENT !== 'production')
);
