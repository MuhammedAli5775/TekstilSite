<?php
/**
 * tests/regresyon.php — TekstilSite uçtan uca regresyon paketi (C7).
 *
 * Kapsam: yayın sayfaları, hukuki sayfalar, SEO, bayi kayıt→giriş→sepet→sipariş,
 * kullanıcı (B2C) kayıt→giriş→hesabım + oturum-ID dönüşü/misafir-sepet sürekliliği,
 * admin panel smoke + sipariş durum + e-fatura (bekliyor), yetki matrisi (rol-2),
 * feed anahtar doğrulama + IP rate-limit, graceful hata-log denetimi, temizlik.
 *
 * Kullanım:
 *   php tests/regresyon.php                    # http://localhost:8000
 *   php tests/regresyon.php https://alanadi    # canlı (C7 lansman günü)
 *   php tests/regresyon.php https://localhost:8443 --insecure
 *       # yerel prod provası (Apache + öz-imzalı sertifika)
 *   REGRESYON_DB=teksilsite_rehearsal php tests/regresyon.php
 *       # sıfır-DB provası: scratch DB'ye §3 kurulumu + CI_ENV=testing sunucu
 *
 * Kurallar (ci3-http-test-recipe): CSRF cookie'den (regenerate=FALSE), bayi
 * formları 2-segment, admin 3-segment, redirect 303 kabul (30x aralığı),
 * oturumlar ayrı cookie havuzlarında. Test verisi ASCII + benzersiz e-posta;
 * koşu sonunda ürettiği tüm satırları ve stok değişimini geri alır.
 *
 * Çıkış: 0 = tam PASS; 1 = en az bir FAIL (özet + FAIL listesi basılır).
 */
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }

$BASE = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://localhost:8000';
if (strpos($BASE, 'localhost') === FALSE && in_array('--force', $argv, TRUE) === FALSE) {
    exit("GUARD: hedef localhost değil ($BASE). Canlıya karşı koşmak için --force ekle.\n");
}
// --insecure: yalnızca yerel prod provası (Apache+öz-imzalı sertifika); canlı koşuda KULLANMA.
$INSECURE = in_array('--insecure', $argv, TRUE);

/* ---- altyapı ------------------------------------------------------------- */
// REGRESYON_DB: sıfır-DB provasında scratch şemaya koşmak için; varsayılan dev DB.
// Sunucu tarafının database.php'i de AYNI DB'yi göstermeli (CI_ENV=testing override).
$DBNAME = getenv('REGRESYON_DB') ?: 'teksilsite';
$db = new mysqli('127.0.0.1', 'root', 'mysql1234', $DBNAME);
if ($db->connect_errno) { exit("DB bağlanamadı: (canlı koşuda config'i uyarla) " . $db->connect_error . "\n"); }
$db->set_charset('utf8mb4');

$SES = array();          // oturum adı => cookie havuzu (guest/bayi/admin/admin2)
$PASS = 0; $FAIL = 0; $FAILED = array();

function q($sql) { global $db; $r = $db->query($sql); if ($r === FALSE) { die("SQL hatası: $sql\n"); } return $r; }
function q1($sql) { $r = q($sql)->fetch_row(); return $r ? $r[0] : NULL; }
function esc($s) { global $db; return $db->real_escape_string($s); }

function hh($ses, $url, $post = NULL) {
    global $BASE, $SES, $INSECURE;
    if (! isset($SES[$ses])) { $SES[$ses] = array(); }
    $ch = curl_init();
    $opt = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER => TRUE,
        CURLOPT_FOLLOWLOCATION => FALSE,
        CURLOPT_SSL_VERIFYPEER => ! $INSECURE,
        CURLOPT_SSL_VERIFYHOST => $INSECURE ? 0 : 2,
        CURLOPT_TIMEOUT => 20,
    );
    if ($SES[$ses]) {
        $p = array();
        foreach ($SES[$ses] as $k => $v) { $p[] = "$k=$v"; }
        $opt[CURLOPT_COOKIE] = implode('; ', $p);
    }
    if ($post !== NULL) {
        $opt[CURLOPT_POST] = TRUE;
        $opt[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $opt);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($r === FALSE) { die("CURL hatası ($url): $err\n"); }
    if (preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $r, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) { $SES[$ses][trim($c[1])] = $c[2]; }
    }
    return array($code, $r);
}
function get($ses, $path) { global $BASE; return hh($ses, $BASE . $path); }
function post($ses, $path, $data) {
    global $BASE, $SES;
    if (empty($SES[$ses]['teksil_csrf_cookie'])) { get($ses, '/'); }   // csrf cookie garanti
    $data['teksil_csrf'] = $SES[$ses]['teksil_csrf_cookie'];
    return hh($ses, $BASE . $path, $data);
}
function check($ad, $kosul) {
    global $PASS, $FAIL, $FAILED;
    if ($kosul) { $PASS++; echo "PASS $ad\n"; }
    else { $FAIL++; $FAILED[] = $ad; echo "FAIL $ad\n"; }
}
function is_redir($code) { return $code >= 300 && $code < 400; }

/* ---- hazırlık: benzersiz veri + önceki durum kayıtları -------------------- */
$T   = date('YmdHis');
$E   = "regresyon$T@test.local";
$EK  = "kul$T@test.local";       // kullanıcı (B2C) akışı için ayrı e-posta
$logFile = 'application/logs/log-' . date('Y-m-d') . '.php';
$logOnce = is_file($logFile) ? (int) substr_count(file_get_contents($logFile), "\n") : 0;
$vStokOnce = (int) q1("SELECT stok FROM urun_varyantlari WHERE id=1");
q("DELETE FROM feed_denemeler");
echo "hedef: $BASE | test e-posta: $E\n---\n";

/* ---- A) yayın sayfaları (guest) ------------------------------------------- */
list($c, $r) = get('guest', '/');            check('anasayfa-200', $c === 200);
check('anasayfa-css', strpos($r, 'teksil.css') !== FALSE);
list($c, $r) = get('guest', '/katalog');     check('katalog-200', $c === 200);
check('katalog-urun-karti', strpos($r, 'urun/') !== FALSE);
list($c, ) = get('guest', '/katalog?sira=fiyat_asc'); check('katalog-fiyat-siralama-200', $c === 200);
list($c, ) = get('guest', '/katalog?sira=yeni');      check('katalog-yeni-siralama-200', $c === 200);
list($c, ) = get('guest', '/katalog?bedenler[]=S');   check('katalog-beden-filtre-200', $c === 200);
list($c, $r) = get('guest', '/urun/suprem-v-yaka-body');
check('urun-detay-200', $c === 200);
check('urun-detay-pdVeri', strpos($r, 'pdVeri') !== FALSE);
list($c, $r) = get('guest', '/arama?q=suprem'); check('arama-200', $c === 200);

foreach (array('mesafeli-satis','iade-degisim','gizlilik','cerez','hakkimizda','iletisim','toptan-sartlari') as $slug) {
    list($c, ) = get('guest', "/sayfa/$slug");
    check("sayfa-$slug-200", $c === 200);
}
list($c, $r) = get('guest', '/sayfa/mesafeli-satis');
check('mesafeli-taslak-icerik', strpos($r, 'Taraflar') !== FALSE);   // E4 seed'i işlendi
list($c, ) = get('guest', '/sayfa/yok-boyle-sayfa-xyz'); check('cms-404', $c === 404);
list($c, ) = get('guest', '/yardim');        check('yardim-200', $c === 200);
list($c, ) = get('guest', '/siparis-takip'); check('siparis-takip-200', $c === 200);
list($c, ) = get('guest', '/favorilerim');   check('favorilerim-200', $c === 200);
list($c, $r) = get('guest', '/robots.txt');  check('robots-200', $c === 200);
check('robots-sitemap', strpos($r, 'Sitemap') !== FALSE);
list($c, $r) = get('guest', '/sitemap.xml'); check('sitemap-200', $c === 200);
check('sitemap-urlset', strpos($r, 'urlset') !== FALSE);
list($c, ) = get('guest', '/feed/urunler');  check('feed-anahtarsiz-401', $c === 401);
list($c, $r) = get('guest', '/yonetim');      check('anon-yonetim-login-formu', $c === 200 && strpos($r, 'yonetim/giris') !== FALSE);

/* ---- B) bayi akışı -------------------------------------------------------- */
list($c, ) = post('bayi', '/bayi/kayit_kaydet', array(
    'yetkili_ad_soyad' => 'Regresyon Test', 'firma_adi' => 'Regresyon Test Ltd',
    'email' => $E, 'telefon' => '5551112233', 'vergi_no' => '1234567890',
    'vergi_dairesi' => 'Test', 'sifre' => 'Reg2026x', 'sifre2' => 'Reg2026x', 'sozlesme' => '1',
));
check('bayi-kayit-redirect', is_redir($c));
$bayiId = (int) q1("SELECT id FROM bayiler WHERE email='" . esc($E) . "'");
// Kayit akisi: durum=0 (onay bekler) - admin onayi sonrasi giris acilir.
check('bayi-kayit-db-onay-bekliyor', $bayiId > 0 && (int) q1("SELECT durum FROM bayiler WHERE id=$bayiId") === 0);

// Onaysiz giris denemesi: redirect + oturum acilmaz (hesabim'a erisilemez).
list($c, ) = post('bayi', '/bayi/giris_yap', array('email' => $E, 'sifre' => 'Reg2026x'));
check('bayi-onaysiz-giris-red', is_redir($c));
list($c, ) = get('bayi', '/hesabim');
check('bayi-onaysiz-hesabim-kapali', is_redir($c));

// Admin onayi (Bayiler paneli durum toggle'inin DB etkisi; panel HTTP akisi
// onceki oturumlarda testli).
q("UPDATE bayiler SET durum=1 WHERE id=$bayiId");
// Misafir sepeti: giriş öncesi oturum-anahtarlı satır (rotasyon + transfer provası)
list($c, $r) = post('bayi', '/sepet/ekle', array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 6));
check('bayi-misafir-sepet-ekle', $c === 200 && strpos($r, '"ok":true') !== FALSE);
$sessOnce = $SES['bayi']['teksil_sess'] ?? '';
list($c, ) = post('bayi', '/bayi/giris_yap', array('email' => $E, 'sifre' => 'Reg2026x'));
check('bayi-giris-redirect', is_redir($c));
check('bayi-giris-oturum-doner', ($SES['bayi']['teksil_sess'] ?? '') !== $sessOnce);
check('bayi-giris-sepet-transferi', (int) q1("SELECT COUNT(*) FROM sepet WHERE bayi_id=$bayiId AND urun_id=1 AND varyant_id=1 AND oturum_id IS NULL") === 1);
q("DELETE FROM sepet WHERE bayi_id=$bayiId AND urun_id=1 AND varyant_id=1"); // ana akış taze başlasın

// Sabitleme (fixation) özelliği: ESKİ çerezle gelen artık giriş yapmamıştır —
// yetki verisi yalnız yeni oturum ID'sine yazıldı; eski dosya misafir kalır.
$ch = curl_init($BASE . '/hesabim');
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HEADER         => TRUE,
    CURLOPT_FOLLOWLOCATION => FALSE,
    CURLOPT_SSL_VERIFYPEER => ! $INSECURE,
    CURLOPT_SSL_VERIFYHOST => $INSECURE ? 0 : 2,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_COOKIE         => 'teksil_sess=' . $sessOnce,
));
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
check('bayi-eski-oturum-giris-degil', is_redir($code));
list($c, $r) = get('bayi', '/hesabim'); check('hesabim-200', $c === 200);
// Bayi bilgilerim (çift-modlu düzenlemeden sonra bayi yolu sabit kalmalı)
list($c, $r) = get('bayi', '/hesabim/bilgiler');
check('bayi-bilgiler-200', $c === 200 && strpos($r, 'yetkili_ad_soyad') !== FALSE);

list($c, $r) = post('bayi', '/sepet/ekle', array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 6)); // moq=6
$jr = json_decode(trim(strstr($r, '{"'), "\r\n"), TRUE);   // header'lı gövdeden JSON çekilemezse strstr sonrası satır
if ($jr === NULL && preg_match('/\{.*\}/s', $r, $m)) { $jr = json_decode($m[0], TRUE); }
check('sepet-ekle-json-ok', is_array($jr) && ! empty($jr['ok']));

// CSRF sözleşmesi (XXII): geçerli çerez + bayat hash → 403 + text/html.
// teksil.js bu sözleşmeye dayanır ('csrf' dalı → yenileme kurtarması);
// ileride yanıt şekil değiştirirse bu test düşer, JS ile birlikte güncellenir.
$ch = curl_init($BASE . '/sepet/ekle');
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HEADER         => TRUE,
    CURLOPT_FOLLOWLOCATION => FALSE,
    CURLOPT_SSL_VERIFYPEER => ! $INSECURE,
    CURLOPT_SSL_VERIFYHOST => $INSECURE ? 0 : 2,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_POST           => TRUE,
    CURLOPT_POSTFIELDS     => http_build_query(array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 6, 'teksil_csrf' => str_repeat('0', 28) . 'dead')),
    CURLOPT_COOKIE         => 'teksil_sess=' . ($SES['bayi']['teksil_sess'] ?? '')
                             . '; teksil_csrf_cookie=' . ($SES['bayi']['teksil_csrf_cookie'] ?? ''),
));
curl_exec($ch);
$c403 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$t403 = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);
check('sepet-ekle-csrf-403-html', $c403 === 403 && strpos($t403, 'text/html') !== FALSE);

// CSRF çerez politikası (XXIII): stok CI3 Strict basıyordu; oturum çereziyle
// hizalı Lax olmalı (Strict bazı tarayıcı akışlarında hash/çerez senkronunu bozuyordu).
list($c, $r) = get('guest', '/');
preg_match('/^Set-Cookie:\s*teksil_csrf_cookie=[^\r\n]*/mi', $r, $cm);
check('csrf-cerez-samesite-lax', ! empty($cm) && stripos($cm[0], 'SameSite=Lax') !== FALSE && stripos($cm[0], 'SameSite=Strict') === FALSE);
list($c, $r) = get('bayi', '/sepet'); check('sepet-200-urun', $c === 200 && strpos($r, 'prem') !== FALSE); // "Süprem" — ASCII güvenli parça
list($c, ) = get('bayi', '/odeme'); check('odeme-form-200', $c === 200);

list($c, ) = post('bayi', '/odeme/tamamla', array(
    'teslimat_ad' => 'Regresyon Test', 'teslimat_adres' => 'Test mahalle cadde no 1',
    'teslimat_il' => 'Istanbul', 'teslimat_ilce' => 'Merkez', 'teslimat_telefon' => '5551112233',
    'email' => $E, 'fatura_ayni' => '1', 'odeme_yontemi' => 'havale', 'kargo_firma_id' => 1,
    'sozlesme' => '1',
));
check('odeme-tamamla-redirect', is_redir($c));
$siparisId = (int) q1("SELECT id FROM siparisler WHERE email='" . esc($E) . "' ORDER BY id DESC LIMIT 1");
check('siparis-db-olustu', $siparisId > 0);
$siparisNo = q1("SELECT siparis_no FROM siparisler WHERE id=$siparisId");
check('sepet-bosaldi', (int) q1("SELECT COUNT(*) FROM sepet WHERE bayi_id=$bayiId") === 0);
list($c, ) = get('bayi', '/odeme/basarili'); check('odeme-basarili-200', $c === 200);

// PayTR sonuç sayfaları sahiplik ister (XXVI): sahibi görür, yabancı redirect'e düşer
list($c, ) = get('bayi', "/paytr/basarili/$siparisId");  check('paytr-basarili-sahibine-200', $c === 200);
list($c, ) = get('guest', "/paytr/basarili/$siparisId"); check('paytr-basarili-yabanci-red', is_redir($c));

// PayTR callback provası (XXVII): test anahtarlarıyla — geçerli hash + YANLIŞ tutar
// → 'tutar uyusmazligi' (ödendi işaretlenmez); DOĞRU tutar (kuruş) → 'OK' + odendi.
q("UPDATE ayarlar SET deger='TKEY123' WHERE anahtar='paytr_merchant_key'");
q("UPDATE ayarlar SET deger='TSALT123' WHERE anahtar='paytr_merchant_salt'");
q("UPDATE ayarlar SET deger='TID123' WHERE anahtar='paytr_merchant_id'");
$no = q1("SELECT siparis_no FROM siparisler WHERE id=$siparisId");
list($ttop, $tkur) = array_map('floatval', explode('|', q1("SELECT CONCAT(toplam,'|',kur) FROM siparisler WHERE id=$siparisId")));
$kurus = (string) (int) round($ttop * $tkur * 100);
function paytr_cb($url, $oid, $stat, $total, $key, $salt) {
    $h = base64_encode(hash_hmac('sha256', $oid . $salt . $stat . $total, $key, TRUE));
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_HEADER => TRUE, CURLOPT_FOLLOWLOCATION => FALSE,
        CURLOPT_SSL_VERIFYPEER => ! $GLOBALS['INSECURE'], CURLOPT_SSL_VERIFYHOST => $GLOBALS['INSECURE'] ? 0 : 2,
        CURLOPT_TIMEOUT => 20, CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => http_build_query(array('merchant_oid' => $oid, 'status' => $stat, 'total_amount' => $total, 'hash' => $h)),
    ));
    $r = curl_exec($ch);
    curl_close($ch);
    return preg_match('/\r?\n\r?\n(.*)$/s', (string) $r, $m) ? trim($m[1]) : '';
}
$r1 = paytr_cb($BASE . '/paytr/bildirim', $no, 'success', '1', 'TKEY123', 'TSALT123');
check('paytr-callback-tutar-red', $r1 === 'tutar uyusmazligi' && q1("SELECT odeme_durumu FROM siparisler WHERE id=$siparisId") === 'bekliyor');
$r2 = paytr_cb($BASE . '/paytr/bildirim', $no, 'success', $kurus, 'TKEY123', 'TSALT123');
check('paytr-callback-gecerli-ok', $r2 === 'OK');
check('paytr-callback-odendi-db', q1("SELECT odeme_durumu FROM siparisler WHERE id=$siparisId") === 'odendi');

/* ---- C) admin smoke + sipariş/fatura -------------------------------------- */
$sessOnce = $SES['admin']['teksil_sess'] ?? '';
list($c, ) = post('admin', '/yonetim/giris/giris_yap', array('email' => 'admin@teksilsite.test', 'sifre' => 'Tekstil2026!'));
check('admin-giris-redirect', is_redir($c));
check('admin-giris-oturum-doner', ($SES['admin']['teksil_sess'] ?? '') !== $sessOnce);
foreach (array('dashboard','urunler','kategoriler','markalar','siparisler','bayiler','stok',
               'kuponlar','bannerlar','sayfalar','faturalar','raporlar','feed','ayarlar','yetkiler','pazaryeri') as $m) {
    list($c, ) = get('admin', "/yonetim/$m");
    check("admin-$m-200", $c === 200);
}
list($c, $r) = get('admin', "/yonetim/siparisler/detay/$siparisId");
check('admin-siparis-detay-200', $c === 200 && strpos($r, $siparisNo) !== FALSE);

list($c, ) = post('admin', "/yonetim/siparisler/durum_guncelle/$siparisId", array('durum' => 'hazirlaniyor', 'notu' => 'regresyon'));
check('admin-durum-guncelle-redirect', is_redir($c));
check('siparis-durum-db', q1("SELECT durum FROM siparisler WHERE id=$siparisId") === 'hazirlaniyor');

list($c, ) = post('admin', "/yonetim/faturalar/olustur/$siparisId", array('tip' => 'earsiv'));
check('admin-fatura-olustur-redirect', is_redir($c));
$faturaId = (int) q1("SELECT id FROM faturalar WHERE siparis_id=$siparisId ORDER BY id DESC LIMIT 1");
check('fatura-db-bekliyor', $faturaId > 0 && q1("SELECT durum FROM faturalar WHERE id=$faturaId") === 'bekliyor');

// Bayi faturalarım: sahiplik (siparisler.bayi_id) üzerinden listelenmeli
list($c, $r) = get('bayi', '/hesabim/faturalar');
check('bayi-faturalar-200', $c === 200);
check('bayi-faturalar-liste', strpos($r, $siparisNo) !== FALSE && strpos($r, 'Bekliyor') !== FALSE);

// Bayi sipariş detayı: fatura bölümü de görünmeli (sayfanın kalıcı testi ilk kez)
list($c, $r) = get('bayi', "/hesabim/siparis/$siparisId");
check('bayi-siparis-detay-200', $c === 200 && strpos($r, $siparisNo) !== FALSE);
check('bayi-siparis-detay-fatura', strpos($r, 'Bekliyor') !== FALSE);

/* ---- D) yetki matrisi (rol-2) ---------------------------------------------- */
q("INSERT INTO yoneticiler (rol_id, ad_soyad, email, sifre, durum) VALUES (2, 'Reg Rol2', 'reg2$T@test.local', '"
  . esc(password_hash('Reg2026x', PASSWORD_BCRYPT)) . "', 1)");
list($c, ) = post('admin2', '/yonetim/giris/giris_yap', array('email' => "reg2$T@test.local", 'sifre' => 'Reg2026x'));
check('rol2-giris-redirect', is_redir($c));
list($c, ) = get('admin2', '/yonetim/siparisler'); check('rol2-siparisler-200', $c === 200);   // seed: tam erişim
list($c, ) = get('admin2', '/yonetim/yetkiler');   check('rol2-yetkiler-403', $c === 403);      // süper-only

/* ---- D2) kullanıcı (B2C) akışı — bayi girişinin yanı sıra kullanıcı girişi ---- */
list($c, ) = get('guest', '/kullanici/kayit');    check('kullanici-kayit-200', $c === 200);
list($c, ) = post('guest', '/kullanici/kayit_kaydet', array(
    'ad_soyad' => 'Reg Kullanici', 'kullanici_adi' => "regkul$T", 'email' => $EK, 'telefon' => '5551112233',
    'sifre' => 'Kul2026x', 'sifre2' => 'Kul2026x', 'sozlesme' => '1',
));
check('kullanici-kayit-redirect', is_redir($c));
check('kullanici-db-durum1', (int) q1("SELECT durum FROM kullanicilar WHERE email='" . esc($EK) . "'") === 1);
check('kullanici-adi-db', q1("SELECT kullanici_adi FROM kullanicilar WHERE email='" . esc($EK) . "'") === "regkul$T");
// Aynı kullanıcı adı ikinci kayıtta alınmış olmalı (form yeniden render, DB'de yine tek satır)
list($c, ) = post('guest', '/kullanici/kayit_kaydet', array(
    'ad_soyad' => 'Reg Kullanici 2', 'kullanici_adi' => "regkul$T", 'email' => "baska$T@test.local",
    'telefon' => '5551112233', 'sifre' => 'Kul2026x', 'sifre2' => 'Kul2026x', 'sozlesme' => '1',
));
check('kullanici-adi-alinmis', $c === 200 && (int) q1("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_adi='" . esc("regkul$T") . "'") === 1);
list($c, ) = post('kullanici', '/kullanici/giris_yap', array('email' => $EK, 'sifre' => 'yanlis'));
check('kullanici-yanlis-sifre-reddi', is_redir($c));
// Misafir sepeti (oturum-anahtarlı) — giriş rotasyonundan sonra yeni anahtara taşınmalı
list($c, $r) = post('kullanici', '/sepet/ekle', array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 6));
check('kullanici-misafir-sepet-ekle', $c === 200 && strpos($r, '"ok":true') !== FALSE);
$sessOnce = $SES['kullanici']['teksil_sess'] ?? '';
list($c, ) = post('kullanici', '/kullanici/giris_yap', array('email' => $EK, 'sifre' => 'Kul2026x'));
check('kullanici-giris-redirect', is_redir($c));
check('kullanici-giris-oturum-doner', ($SES['kullanici']['teksil_sess'] ?? '') !== $sessOnce);
check('kullanici-giris-sepet-devredi', (int) q1("SELECT COUNT(*) FROM sepet WHERE oturum_id='" . esc($SES['kullanici']['teksil_sess'] ?? '') . "' AND bayi_id IS NULL AND urun_id=1 AND varyant_id=1") === 1);

// Kullanıcı siparişi hesabına işlenmeli (XXV): formda yanlış e-posta olsa bile
// sipariş hesabın e-postasıyla kaydedilir (hesabım eşleşmesi bu alandan).
list($c, ) = get('kullanici', '/odeme');
check('kullanici-odeme-form-200', $c === 200);
list($c, ) = post('kullanici', '/odeme/tamamla', array(
    'teslimat_ad' => 'Reg Kullanici', 'teslimat_adres' => 'Test mahalle cadde no 2',
    'teslimat_il' => 'Istanbul', 'teslimat_ilce' => 'Merkez', 'teslimat_telefon' => '5551112244',
    'email' => "yanlis$T@test.local", 'fatura_ayni' => '1', 'odeme_yontemi' => 'havale', 'kargo_firma_id' => 1,
    'sozlesme' => '1',
));
check('kullanici-siparis-redirect', is_redir($c));
$kSiparisId = (int) q1("SELECT id FROM siparisler WHERE email='" . esc($EK) . "' ORDER BY id DESC LIMIT 1");
check('kullanici-siparis-hesap-eposta', $kSiparisId > 0 && (int) q1("SELECT COUNT(*) FROM siparisler WHERE email='" . esc("yanlis$T@test.local") . "'") === 0);
$kSiparisNo = (string) q1("SELECT siparis_no FROM siparisler WHERE id=$kSiparisId");
list($c, $r) = get('kullanici', '/hesabim/siparisler');
check('kullanici-siparisler-gorunur', $c === 200 && $kSiparisId > 0 && strpos($r, $kSiparisNo) !== FALSE);
list($c, $r) = get('kullanici', '/hesabim');
check('kullanici-hesabim-200', $c === 200 && strpos($r, 'Reg Kullanici') !== FALSE);
check('kullanici-navbar-cikis', strpos($r, 'kullanici/cikis') !== FALSE);
check('kullanici-cikis-confirm', strpos($r, 'emin misiniz') !== FALSE);   // çıkış onay diyalogu

// kullanıcı faturalarım (siparişi yok → boş liste + menü linki render)
list($c, $r) = get('kullanici', '/hesabim/faturalar');
check('kullanici-faturalar-200', $c === 200 && strpos($r, 'hesabim/faturalar') !== FALSE);

// kullanıcı bilgilerim
list($c, $r) = get('kullanici', '/hesabim/bilgiler');
check('kullanici-bilgiler-200', $c === 200 && strpos($r, 'name="ad_soyad"') !== FALSE);
list($c, ) = post('kullanici', '/hesabim/bilgiler/kaydet', array('ad_soyad' => 'Reg Ad Yeni', 'kullanici_adi' => "regkul2$T", 'telefon' => '5559998877'));
check('kullanici-bilgiler-kaydet-redirect', is_redir($c));
check('kullanici-bilgiler-db', q1("SELECT ad_soyad FROM kullanicilar WHERE email='" . esc($EK) . "'") === 'Reg Ad Yeni');
check('kullanici-adi-guncellendi', q1("SELECT kullanici_adi FROM kullanicilar WHERE email='" . esc($EK) . "'") === "regkul2$T");

// kullanıcı adres defteri
$kulId = (int) q1("SELECT id FROM kullanicilar WHERE email='" . esc($EK) . "'");
list($c, ) = get('kullanici', '/hesabim/adresler');
check('kullanici-adresler-200', $c === 200);
list($c, ) = post('kullanici', '/hesabim/adresler/kaydet', array(
    'id' => 0, 'ad_soyad' => 'Reg Ad Yeni', 'adres' => 'Test mah cadde no 1',
    'il' => 'Istanbul', 'ilce' => 'Kagithane', 'telefon' => '5559998877',
    'tip' => 'teslimat', 'varsayilan' => '1',
));
check('kullanici-adres-ekle-redirect', is_redir($c));
$adresId = (int) q1("SELECT id FROM kullanicilar_adresleri WHERE kullanici_id=$kulId");
check('kullanici-adres-db', $adresId > 0 && (int) q1("SELECT varsayilan FROM kullanicilar_adresleri WHERE id=$adresId") === 1);
list($c, ) = post('kullanici', '/hesabim/adresler/sil/' . $adresId, array());
check('kullanici-adres-sil-redirect', is_redir($c));
check('kullanici-adres-sil-db', (int) q1("SELECT COUNT(*) FROM kullanicilar_adresleri WHERE kullanici_id=$kulId") === 0);

// kullanıcı şifre güncelleme → yeni şifreyle giriş
list($c, ) = post('kullanici', '/hesabim/sifre/kaydet', array('eski' => 'Kul2026x', 'yeni' => 'Kul2026y', 'yeni2' => 'Kul2026y'));
check('kullanici-sifre-redirect', is_redir($c));
list($c, ) = post('kullanici2', '/kullanici/giris_yap', array('email' => $EK, 'sifre' => 'Kul2026y'));
check('kullanici-yeni-sifre-giris', is_redir($c));
list($c, ) = get('kullanici', '/kullanici/cikis');
check('kullanici-cikis-redirect', is_redir($c));
list($c, ) = get('kullanici', '/hesabim');
check('kullanici-sonrasi-hesabim-kapali', is_redir($c));
// navbar çıkışta kullanıcı girişine döner (bayi değil)
list($c, $r) = get('guest', '/');
check('navbar-kullanici-girisi', $c === 200 && strpos($r, 'kullanici/giris') !== FALSE);

/* ---- dil seçici (XXIX): TR varsayılan; EN/RU/AR geçiş; geçersiz kod → TR ---- */
list($c, $r) = get('dil', '/');
check('dil-varsayilan-tr', $c === 200 && strpos($r, 'Sipariş Takibi') !== FALSE);
check('dil-secici-menusu', strpos($r, 'dil/cevir/en') !== FALSE && strpos($r, 'dil/cevir/ru') !== FALSE && strpos($r, 'dil/cevir/ar') !== FALSE);
list($c, ) = get('dil', '/dil/cevir/en');
check('dil-gecis-redirect', is_redir($c));
list($c, $r) = get('dil', '/');
check('dil-en-etkin', $c === 200 && strpos($r, 'Order Tracking') !== FALSE && strpos($r, 'Sipariş Takibi') === FALSE);
list($c, ) = get('dil', '/dil/cevir/xyz');
list($c, $r) = get('dil', '/');
check('dil-gecersiz-tr-doner', $c === 200 && strpos($r, 'Sipariş Takibi') !== FALSE);

/* ---- XXX: artımlı çeviri (anasayfa/katalog/sepet/odeme) + banner dil filtresi ---- */
list($c, ) = get('dil', '/dil/cevir/en');
list($c, $r) = get('dil', '/');
check('dil-en-anasayfa', $c === 200 && strpos($r, 'Tops') !== FALSE && strpos($r, 'Kategorilere göz atın') === FALSE);
list($c, $r) = get('dil', '/katalog');
check('dil-en-katalog', $c === 200 && strpos($r, 'New Arrivals') !== FALSE && strpos($r, 'Fiyat (Artan)') === FALSE);
list($c, $r) = get('dil', '/sepet');
check('dil-en-sepet', $c === 200 && strpos($r, 'Your cart is empty.') !== FALSE);

// Banner dil filtresi: RU'ya özel banner RU vitrininde görünür, EN'de gizli.
q("INSERT INTO bannerlar (yer, baslik, gorsel, link, yazi_konum, dil, sira, durum) VALUES ('anasayfa_slider', 'REGXXX RU BANNER', 'https://picsum.photos/seed/regxxxru/1600/700', 'katalog', 'sol', 'ru', 99, 1)");
$regBannerId = (int) $db->insert_id;
list($c, $r) = get('dil', '/');
check('banner-dil-en-gizli', $c === 200 && strpos($r, 'REGXXX RU BANNER') === FALSE);
list($c, ) = get('dil', '/dil/cevir/ru');
list($c, $r) = get('dil', '/');
check('banner-dil-ru-gorunur', $c === 200 && strpos($r, 'REGXXX RU BANNER') !== FALSE);
q("DELETE FROM bannerlar WHERE id = $regBannerId");

// Admin Bannerlar dil alanı (E2E): geçerli kod kaydolur; geçersiz kod NULL'a düşer.
list($c) = post('admin', '/yonetim/bannerlar/kaydet', array('baslik' => 'REGXXX DILBANNER', 'gorsel_url' => 'https://picsum.photos/seed/regxxxdil/1600/700', 'yazi_konum' => 'sol', 'dil' => 'en', 'sira' => 98, 'durum' => 1));
check('banner-admin-kaydet-redirect', is_redir($c));
$regB = (int) q1("SELECT id FROM bannerlar WHERE baslik = 'REGXXX DILBANNER'");
check('banner-admin-dil-kayitli', $regB > 0 && q1("SELECT dil FROM bannerlar WHERE id = $regB") === 'en');
list($c) = post('admin', '/yonetim/bannerlar/kaydet', array('id' => $regB, 'baslik' => 'REGXXX DILBANNER', 'gorsel_url' => 'https://picsum.photos/seed/regxxxdil/1600/700', 'yazi_konum' => 'sol', 'dil' => 'zz', 'sira' => 98, 'durum' => 1));
check('banner-admin-gecersiz-dil-null', is_redir($c) && q1("SELECT dil FROM bannerlar WHERE id = $regB") === NULL);
q("DELETE FROM bannerlar WHERE id = $regB");

/* ---- XXXI: kalan yüzeyler + kategori adları çoklu dil ---- */
$urunSlug = (string) q1("SELECT slug FROM urunler WHERE deleted_at IS NULL AND durum = 1 ORDER BY id ASC LIMIT 1");
list($c, ) = get('dil', '/dil/cevir/en');   // dil havuzu RU'dan geliyordu
list($c, $r) = get('dil', '/katalog/ust-giyim');
check('dil-en-kategori-baslik', $c === 200 && strpos($r, '<h1 class="kat-baslik">Tops</h1>') !== FALSE);
list($c, $r) = get('dil', '/urun/' . $urunSlug);
check('dil-en-urun-detay', $c === 200 && strpos($r, 'Add to Cart') !== FALSE && strpos($r, 'Sepete Ekle') === FALSE);
list($c, $r) = get('dil', '/arama?q=ti');
check('dil-en-arama', $c === 200 && (strpos($r, 'results found') !== FALSE || strpos($r, 'No results found') !== FALSE));
list($c, $r) = get('dil', '/bayi/giris');
check('dil-en-bayi-giris', $c === 200 && strpos($r, 'Dealer Login') !== FALSE && strpos($r, 'Bayi Girişi') === FALSE);
list($c, $r) = get('dil', '/');
check('dil-en-header-menu', $c === 200 && strpos($r, '>Tops<') !== FALSE);

// Kategori admin E2E: ad_en kaydolur → EN vitrinde EN ad, TR vitrinde TR ad (fallback).
$regKatAd = 'REGXXXI' . $T;
list($c) = post('admin', '/yonetim/kategoriler/kaydet', array('ad' => $regKatAd . ' TR', 'ad_en' => $regKatAd . ' EN', 'ust_id' => '', 'durum' => 1, 'sira' => 99));
check('kategori-admin-kaydet-redirect', is_redir($c));
$regKatId = (int) q1("SELECT id FROM kategoriler WHERE ad = '" . esc($regKatAd . ' TR') . "'");
$regKatSlug = (string) q1("SELECT slug FROM kategoriler WHERE id = $regKatId");
check('kategori-admin-dil-kayitli', $regKatId > 0 && q1("SELECT ad_en FROM kategoriler WHERE id = $regKatId") === $regKatAd . ' EN');
list($c, $r) = get('dil', '/katalog/' . $regKatSlug);
check('kategori-vitrin-en-baslik', $c === 200 && strpos($r, $regKatAd . ' EN') !== FALSE);
list($c, $r) = get('guest', '/katalog/' . $regKatSlug);
check('kategori-vitrin-tr-baslik', $c === 200 && strpos($r, $regKatAd . ' TR') !== FALSE && strpos($r, $regKatAd . ' EN') === FALSE);
q("DELETE FROM kategoriler WHERE id = $regKatId");

/* ---- E) feed tam yol + rate-limit ------------------------------------------ */
$anahtar = 'regtest_' . bin2hex(random_bytes(16));
q("INSERT INTO api_anahtarlari (bayi_id, ad, onek, anahtar_hash, durum) VALUES (NULL, 'regresyon', 'reg', '"
  . esc(hash('sha256', $anahtar)) . "', 1)");
$anahtarId = (int) $db->insert_id;
list($c, $r) = get('guest', "/feed/urunler?key=" . urlencode($anahtar));
check('feed-gecerli-200', $c === 200 && strpos($r, 'katalog') !== FALSE);   // XML root = <katalog>
check('feed-kullanim-sayaci', (int) q1("SELECT kullanim_sayisi FROM api_anahtarlari WHERE id=$anahtarId") === 1);

$kodlar = array();
for ($i = 1; $i <= 21; $i++) {
    list($c, ) = get('guest', '/feed/urunler?key=yanlis_' . $i);
    $kodlar[] = $c;
}
check('feed-ratelimit-20x403', substr_count(implode(',', $kodlar), '403') === 20);
check('feed-ratelimit-429', end($kodlar) === 429);
q("DELETE FROM feed_denemeler");
list($c, ) = get('guest', "/feed/urunler?key=" . urlencode($anahtar));
check('feed-blok-sonrasi-temiz-200', $c === 200);

/* ---- F) graceful log denetimi ------------------------------------------------ */
$yeni = array();
if (is_file($logFile)) {
    $satirlar = explode("\n", file_get_contents($logFile));
    for ($i = $logOnce; $i < count($satirlar); $i++) {
        $l = trim($satirlar[$i]);
        if ($l === '' || strpos($l, 'ERROR -') === FALSE) { continue; }
        // Beklenen/önbilinen: kimliksiz ortamda graceful atlamalar + testin kendi 404'ü.
        if (strpos($l, 'Eposta:') !== FALSE || strpos($l, 'Sms') !== FALSE
            || strpos($l, 'Efatura:') !== FALSE || strpos($l, '404 Page Not Found') !== FALSE) { continue; }
        // XXIII: csrf-403 sözleşme testinin kendi ret satırı — yalnız o URI'de
        // atlanır; başka uçta gerçek CSRF kırılırsa denetim yine düşer.
        if (strpos($l, 'CSRF reddi') !== FALSE && strpos($l, 'uri=/sepet/ekle') !== FALSE) { continue; }
        // XXVII: callback provasının kendi satırları (tutar uyuşmazlığı + ödendi
        // işaretlendi) — PayTR bildirimleri bilgilendirici ERROR seviyesinde loglanır.
        if (strpos($l, 'PayTR bildirim') !== FALSE) { continue; }
        $yeni[] = $l;
    }
}
check('log-beklenmeyen-hata-yok', empty($yeni));
foreach ($yeni as $l) { echo "  LOG: $l\n"; }

/* ---- temizlik ---------------------------------------------------------------- */
q("DELETE FROM faturalar WHERE siparis_id=$siparisId");
q("DELETE FROM siparis_detaylari WHERE siparis_id=$siparisId");
q("DELETE FROM siparisler WHERE id=$siparisId");
q("DELETE FROM faturalar WHERE siparis_id=" . (int) ($kSiparisId ?? 0));
q("DELETE FROM siparis_detaylari WHERE siparis_id=" . (int) ($kSiparisId ?? 0));
q("DELETE FROM siparisler WHERE id=" . (int) ($kSiparisId ?? 0));
q("DELETE FROM sepet WHERE bayi_id=$bayiId");
q("DELETE FROM sepet WHERE oturum_id='" . esc($SES['kullanici']['teksil_sess'] ?? '') . "'");
q("UPDATE ayarlar SET deger='' WHERE anahtar IN ('paytr_merchant_key','paytr_merchant_salt','paytr_merchant_id')"); // callback provası anahtarlarını geri al
q("DELETE FROM bayiler WHERE id=$bayiId");
q("DELETE FROM yoneticiler WHERE email='reg2$T@test.local'");
q("DELETE FROM kullanicilar WHERE email='" . esc($EK) . "'");
q("DELETE FROM api_anahtarlari WHERE id=$anahtarId");
q("DELETE FROM feed_denemeler");
q("UPDATE urun_varyantlari SET stok=$vStokOnce WHERE id=1");
if ($db->query("SHOW TABLES LIKE 'stok_hareketleri'")->num_rows
    && $db->query("SHOW COLUMNS FROM stok_hareketleri LIKE 'siparis_id'")->num_rows) {
    q("DELETE FROM stok_hareketleri WHERE siparis_id IN ($siparisId, " . (int) ($kSiparisId ?? 0) . ")");
}
$kalan = (int) q1("SELECT (SELECT COUNT(*) FROM siparisler WHERE email='" . esc($E) . "')
                 + (SELECT COUNT(*) FROM bayiler WHERE email='" . esc($E) . "')
                 + (SELECT COUNT(*) FROM yoneticiler WHERE email='reg2$T@test.local')
                 + (SELECT COUNT(*) FROM kullanicilar WHERE email='" . esc($EK) . "')
                 + (SELECT COUNT(*) FROM api_anahtarlari WHERE id=$anahtarId)
                 + (SELECT COUNT(*) FROM feed_denemeler)");
check('temizlik-tamam', $kalan === 0);
check('stok-geri-yuklendi', (int) q1("SELECT stok FROM urun_varyantlari WHERE id=1") === $vStokOnce);

/* ---- özet -------------------------------------------------------------------- */
echo "---\n$PASS PASS / $FAIL FAIL\n";
if ($FAILED) { echo "FAIL listesi:\n - " . implode("\n - ", $FAILED) . "\n"; }
exit($FAIL ? 1 : 0);
