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
// XLVIII: marka yenilendi — "Nesem Tesettür" görünür, eski ad hiçbir yerde yok
check('anasayfa-marka-nesem', strpos($r, 'Nesem Tesettür') !== FALSE && strpos($r, 'TekstilSite') === FALSE);
// LII: favicon seti + sosyal paylaşım kartı (OG/Twitter) — link önizlemesi görselli çıksın
check('anasayfa-favicon', strpos($r, 'favicon.svg') !== FALSE && strpos($r, 'apple-touch-icon') !== FALSE);
check('anasayfa-og-meta', strpos($r, 'property="og:image"') !== FALSE && strpos($r, 'og-default.png') !== FALSE && strpos($r, 'name="twitter:card"') !== FALSE && strpos($r, 'og:locale" content="tr_TR"') !== FALSE);
// LVI: markalı 404 — durum 404 + marka + TR mesaj (curl Accept-Language göndermez → tr)
list($c, $r) = get('guest', '/boyle-bir-sayfa-yok-' . $T);
check('sayfa-404-markali', $c === 404 && strpos($r, 'Nesem') !== FALSE && strpos($r, 'Anasayfaya') !== FALSE && strpos($r, 'robots') !== FALSE);
// LVII: çerez onay bandı + ayar-gated izleyici kimlikleri (GA/FB yalnız onayla yüklenir)
list($c, $r) = get('guest', '/');
check('cerez-bant-render', $c === 200 && strpos($r, 'cerezBant') !== FALSE && strpos($r, 'cerezRed') !== FALSE && strpos($r, 'sayfa/cerez') !== FALSE);
q("INSERT INTO ayarlar (anahtar, deger) VALUES ('ga_id','G-REGTEST') ON DUPLICATE KEY UPDATE deger=VALUES(deger)");
list($c, $r) = get('guest', '/');
check('izleyici-kimlik-gecisi', strpos($r, 'tkIzleyici') !== FALSE && strpos($r, 'G-REGTEST') !== FALSE);
q("UPDATE ayarlar SET deger='' WHERE anahtar='ga_id'");
list($c, $r) = get('guest', '/');
check('izleyici-bosken-gizli', strpos($r, 'tkIzleyici') === FALSE);
list($c, $r) = get('guest', '/assets/magaza/js/teksil.js');
check('cerez-js-mantik', $c === 200 && strpos($r, 'cerezOnay') !== FALSE && strpos($r, 'googletagmanager') !== FALSE);
list($c, $r) = get('guest', '/katalog');     check('katalog-200', $c === 200);
check('katalog-urun-karti', strpos($r, 'urun/') !== FALSE);
list($c, ) = get('guest', '/katalog?sira=fiyat_asc'); check('katalog-fiyat-siralama-200', $c === 200);
list($c, ) = get('guest', '/katalog?sira=yeni');      check('katalog-yeni-siralama-200', $c === 200);
list($c, ) = get('guest', '/katalog?bedenler[]=S');   check('katalog-beden-filtre-200', $c === 200);
list($c, $r) = get('guest', '/urun/suprem-v-yaka-body');
check('urun-detay-200', $c === 200);
check('urun-detay-pdVeri', strpos($r, 'pdVeri') !== FALSE);
// LII: ürün sayfası sosyal kartı — tip 'product' + og:image DB'deki ana_görsel
check('urun-detay-og-urun', strpos($r, 'og:type" content="product"') !== FALSE && strpos($r, 'og:image" content="' . q1('SELECT ana_gorsel FROM urunler WHERE id=1')) !== FALSE);
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

list($c, $r) = post('bayi', '/sepet/ekle', array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 9)); // moq=6, adım=6 → ızgara FLOOR: 6 (XLVI)
$jr = json_decode(trim(strstr($r, '{"'), "\r\n"), TRUE);   // header'lı gövdeden JSON çekilemezse strstr sonrası satır
if ($jr === NULL && preg_match('/\{.*\}/s', $r, $m)) { $jr = json_decode($m[0], TRUE); }
check('sepet-ekle-json-ok', is_array($jr) && ! empty($jr['ok']));
check('sepet-ekle-izgara-floor', (int) q1("SELECT adet FROM sepet WHERE bayi_id=$bayiId AND urun_id=1 AND varyant_id=1") === 6);

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
// L: periyodik oturum-ID döndürmesi KAPALI kalmalı — açılırsa oturum-anahtarlı
// (bayi_id'siz) misafir/B2C sepetleri 5 dk sonra yetim kalır ("sepet boş").
check('oturum-periyodik-donme-kapali', strpos(file_get_contents('application/config/config.php'), "sess_time_to_update'] = 0") !== FALSE);
// LI: zengin footer — yapı (iletişim bloğu + güven şeridi + rozetler), keşif
// bağlantıları (çerez sayfası / yeni gelenler / blog) ve DB rozetleri (ödeme/kargo).
list($c, $r) = get('guest', '/');
check('footer-zengin-yapi', $c === 200 && strpos($r, 'footer__iletisim') !== FALSE && strpos($r, 'footer__strip') !== FALSE && strpos($r, 'footer__rozet') !== FALSE);
check('footer-kesif-baglantilari', strpos($r, 'sayfa/cerez') !== FALSE && strpos($r, 'katalog/yeni') !== FALSE && strpos($r, '/blog') !== FALSE);
check('footer-guven-seridi', strpos($r, 'Havale') !== FALSE && strpos($r, 'Kargo') !== FALSE);
// LV: e-bülten — footer formu + kayıt akışı (ODKU çift kaydı engeller)
check('ebulten-form-render', strpos($r, 'ebulten/kayit') !== FALSE);
list($c, ) = post('guest', '/ebulten/kayit', array('eposta' => "bulten$T@test.local"));
check('ebulten-kayit-redirect', is_redir($c));
check('ebulten-kayit-db', (int) q1("SELECT COUNT(*) FROM ebulten_aboneler WHERE eposta='" . esc("bulten$T@test.local") . "'") === 1);
list($c, ) = post('guest', '/ebulten/kayit', array('eposta' => "bulten$T@test.local"));
check('ebulten-cift-kayit-engelli', is_redir($c) && (int) q1("SELECT COUNT(*) FROM ebulten_aboneler WHERE eposta='" . esc("bulten$T@test.local") . "'") === 1);
list($c, ) = post('guest', '/ebulten/kayit', array('eposta' => "kotu-eposta$T"));
check('ebulten-gecersiz-reddi', is_redir($c) && (int) q1("SELECT COUNT(*) FROM ebulten_aboneler WHERE eposta='kotu-eposta$T'") === 0);
list($c, $r) = get('bayi', '/sepet'); check('sepet-200-urun', $c === 200 && strpos($r, 'prem') !== FALSE); // "Süprem" — ASCII güvenli parça

// XLV+XLVI: sepet adet güncelleme ızgaraya FLOOR ile oturur (yukarı zıplama YOK —
// 9 yazımı adım-6 üründe 12 değil 6 olur; eski round 12'ye zıplatıyordu). MOQ tabanı
// + stok tavanı kalır; ızgara dışı miktar yazılamaz (paket kuralı sunucuda zorunlu).
$gSatir = (int) q1("SELECT id FROM sepet WHERE bayi_id=$bayiId AND urun_id=1 AND varyant_id=1 LIMIT 1");
$gStok  = (int) q1("SELECT stok FROM urun_varyantlari WHERE id=1");
list($c, ) = post('bayi', '/sepet/guncelle/' . $gSatir, array('adet' => 9));
check('sepet-guncelle-izgara-floor', is_redir($c) && (int) q1("SELECT adet FROM sepet WHERE id=$gSatir") === 6);
list($c, ) = post('bayi', '/sepet/guncelle/' . $gSatir, array('adet' => 1));
check('sepet-guncelle-moq-tabani', is_redir($c) && (int) q1("SELECT adet FROM sepet WHERE id=$gSatir") === 6);
list($c, ) = post('bayi', '/sepet/guncelle/' . $gSatir, array('adet' => 999999));
check('sepet-guncelle-stok-tavani', is_redir($c) && (int) q1("SELECT adet FROM sepet WHERE id=$gSatir") === $gStok);
list($c, ) = post('bayi', '/sepet/guncelle/' . $gSatir, array('adet' => 6));
check('sepet-guncelle-geri-6', is_redir($c) && (int) q1("SELECT adet FROM sepet WHERE id=$gSatir") === 6);
// XLVII: sepet adet girişi varyant stoğuyla sınırlı — max/data-stok + satır uyarısı
list($c, $r) = get('bayi', '/sepet');
check('sepet-stok-max-attr', $c === 200 && strpos($r, 'max="' . $gStok . '"') !== FALSE && strpos($r, 'data-stok="' . $gStok . '"') !== FALSE && strpos($r, 'stok-uyari') !== FALSE);
list($c, ) = get('bayi', '/odeme'); check('odeme-form-200', $c === 200);

// Kapalı/bilinmeyen ödeme yöntemi reddedilmeli (POST'a güvenilmez): sipariş oluşmaz.
// XLIX: diğer alanlar GEÇERLİ gönderilir — ret tam olarak yöntem callback'inden gelmeli.
$oncekiS = (int) q1("SELECT COUNT(*) FROM siparisler WHERE email='" . esc($E) . "'");
list($c, ) = post('bayi', '/odeme/tamamla', array(
    'teslimat_ad' => 'Regresyon Test', 'teslimat_adres' => 'Test mahalle cadde no 1',
    'teslimat_il' => 'Istanbul', 'teslimat_ilce' => 'Merkez', 'teslimat_telefon' => '5551112233', 'email' => $E,
    'fatura_ayni' => '1', 'odeme_yontemi' => 'kapali_yontem', 'kargo_firma_id' => 1, 'sozlesme' => '1',
));
check('odeme-kapali-yontem-reddi', is_redir($c) && (int) q1("SELECT COUNT(*) FROM siparisler WHERE email='" . esc($E) . "'") === $oncekiS);

// XLIX: ödeme formu validasyonu — hatalı telefon biçimi / var olmayan il reddedilir
// (PRG redirect + sipariş oluşmaz).
list($c, ) = post('bayi', '/odeme/tamamla', array(
    'teslimat_ad' => 'Regresyon Test', 'teslimat_adres' => 'Test mahalle cadde no 1',
    'teslimat_il' => 'Istanbul', 'teslimat_telefon' => 'abc', 'email' => $E,
    'fatura_ayni' => '1', 'odeme_yontemi' => 'havale', 'kargo_firma_id' => 1, 'sozlesme' => '1',
));
check('odeme-telefon-format-reddi', is_redir($c) && (int) q1("SELECT COUNT(*) FROM siparisler WHERE email='" . esc($E) . "'") === $oncekiS);
list($c, ) = post('bayi', '/odeme/tamamla', array(
    'teslimat_ad' => 'Regresyon Test', 'teslimat_adres' => 'Test mahalle cadde no 1',
    'teslimat_il' => 'Atlantis', 'teslimat_telefon' => '5551112233', 'email' => $E,
    'fatura_ayni' => '1', 'odeme_yontemi' => 'havale', 'kargo_firma_id' => 1, 'sozlesme' => '1',
));
check('odeme-il-gecersiz-reddi', is_redir($c) && (int) q1("SELECT COUNT(*) FROM siparisler WHERE email='" . esc($E) . "'") === $oncekiS);

// KDV ürün bazından gelmeli (hardcoded 20 değil): ürün 1 KDV'sini 10 yap, detayda 10 düşmeli.
q("UPDATE urunler SET kdv=10 WHERE id=1");

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
check('siparis-detay-kdv-urun', (int) q1("SELECT kdv FROM siparis_detaylari WHERE siparis_id=$siparisId AND urun_id=1 LIMIT 1") === 10);
q("UPDATE urunler SET kdv=20 WHERE id=1");   // geri al
check('sepet-bosaldi', (int) q1("SELECT COUNT(*) FROM sepet WHERE bayi_id=$bayiId") === 0);
list($c, ) = get('bayi', '/odeme/basarili'); check('odeme-basarili-200', $c === 200);

// PayTR sonuç sayfaları sahiplik ister (XXVI): sahibi görür, yabancı redirect'e düşer
list($c, ) = get('bayi', "/paytr/basarili/$siparisId");  check('paytr-basarili-sahibine-200', $c === 200);
list($c, ) = get('guest', "/paytr/basarili/$siparisId"); check('paytr-basarili-yabanci-red', is_redir($c));

// Misafir ödeme kapalı (XLIV): ödeme yüzeyi giriş ister; POST'la da sipariş oluşmaz,
// flash mesajı login sayfasında görünür.
list($c, $r) = get('guest', '/odeme');
check('misafir-odeme-giris-yonlendirme', is_redir($c) && strpos($r, 'kullanici/giris') !== FALSE);
$mOnce = (int) q1('SELECT COUNT(*) FROM siparisler');
list($c, $r) = post('guest', '/odeme/tamamla', array(
    'teslimat_ad' => 'Misafir Test', 'teslimat_adres' => 'Test mahalle cadde no 3',
    'teslimat_il' => 'Istanbul', 'teslimat_ilce' => 'Merkez', 'teslimat_telefon' => '5552223344',
    'email' => "misafir$T@test.local", 'fatura_ayni' => '1', 'odeme_yontemi' => 'havale',
    'kargo_firma_id' => 1, 'sozlesme' => '1',
));
check('misafir-odeme-tamamla-yonlendirme', is_redir($c) && strpos($r, 'kullanici/giris') !== FALSE && strpos($r, 'donus=odeme') !== FALSE && strpos($r, '%2Ftamamla') === FALSE);   // L: tamamla → form hedefine iner
check('misafir-siparis-olusmadi', (int) q1('SELECT COUNT(*) FROM siparisler') === $mOnce);
list($c, $r) = get('guest', '/kullanici/giris');
check('misafir-odeme-flash-mesaj', $c === 200 && strpos($r, 'giriş yapmalısınız') !== FALSE);

// LIV: saf misafir sepeti — ekleme + SAYFA render'ı (yalnız DB satırı değil);
// guard önceliği: sepeti olsa bile misafir ödeme formuna giremez.
list($c, $r) = post('guest', '/sepet/ekle', array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 2));
check('misafir-sepet-ekle-ok', $c === 200 && strpos($r, '"ok":true') !== FALSE);
list($c, $r) = get('guest', '/sepet');
check('misafir-sepet-sayfa-render', $c === 200 && strpos($r, 'prem') !== FALSE);   // "Süprem" — ASCII güvenli parça
list($c, ) = get('guest', '/odeme');
check('misafir-sepetli-odeme-guard', is_redir($c));

// PayTR callback provası (XXVII): test anahtarlarıyla — geçerli hash + YANLIŞ tutar
// → 'tutar uyusmazligi' (ödendi işaretlenmez); DOĞRU tutar (kuruş) → 'OK' + odendi.
// XXXVI: INSERT ODKU — taze §3 kurulumunda paytr_* satırları yoktur (seed etmez),
// UPDATE 0 satır etkiler ve hash 'bad hash'e düşerdi (sıfır-DB provasında bulundu).
q("INSERT INTO ayarlar (anahtar, deger) VALUES ('paytr_merchant_key','TKEY123') ON DUPLICATE KEY UPDATE deger=VALUES(deger)");
q("INSERT INTO ayarlar (anahtar, deger) VALUES ('paytr_merchant_salt','TSALT123') ON DUPLICATE KEY UPDATE deger=VALUES(deger)");
q("INSERT INTO ayarlar (anahtar, deger) VALUES ('paytr_merchant_id','TID123') ON DUPLICATE KEY UPDATE deger=VALUES(deger)");
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

// LVI: kupon atıflaması (XXXVIII entegrasyonu) — kuponlu sipariş kupon_kod'u kalıcı taşır,
// indirim > 0 yazılır, sipariş sonrası kupon oturumu temizlenir (sonraki sipariş etkilenmez).
q("INSERT INTO kuponlar (kod, tip, deger, min_sepet_tutar, max_indirim, kullanim_limiti, baslangic_zaman, bitis_zaman, aciklama, durum) VALUES ('REGKUP2$T', 'yuzde', 10, 0, 0, 0, NULL, NULL, 'regresyon', 1)");
list($c, $r) = post('bayi', '/sepet/ekle', array('urun_id' => 1, 'varyant_id' => 1, 'adet' => 6));
check('kuponlu-siparis-sepet-ekle', $c === 200 && strpos($r, '"ok":true') !== FALSE);
list($c, $r) = post('bayi', '/odeme/kupon_uygula', array('kod' => "REGKUP2$T"));
check('kupon-uygulama-redirect', is_redir($c));
list($c, $r) = post('bayi', '/odeme/tamamla', array(
    'teslimat_ad' => 'Regresyon Kupon', 'teslimat_adres' => 'Test mahalle cadde no 4',
    'teslimat_il' => 'Istanbul', 'teslimat_ilce' => 'Merkez', 'teslimat_telefon' => '5551112299',
    'email' => $E, 'fatura_ayni' => '1', 'odeme_yontemi' => 'havale', 'kargo_firma_id' => 1,
    'sozlesme' => '1',
));
check('kuponlu-tamamla-redirect', is_redir($c));
$kupSiparisId = (int) q1("SELECT id FROM siparisler WHERE email='" . esc($E) . "' ORDER BY id DESC LIMIT 1");
check('kupon-atiflama-db', $kupSiparisId > $siparisId && q1("SELECT kupon_kod FROM siparisler WHERE id=$kupSiparisId") === "REGKUP2$T");
check('kuponlu-indirim-db', (float) q1("SELECT indirim FROM siparisler WHERE id=$kupSiparisId") > 0);
check('kupon-oturum-temizlendi', (int) q1("SELECT COUNT(*) FROM sepet WHERE bayi_id=$bayiId") === 0);

/* ---- C) admin smoke + sipariş/fatura -------------------------------------- */
$sessOnce = $SES['admin']['teksil_sess'] ?? '';
list($c, ) = post('admin', '/yonetim/giris/giris_yap', array('email' => 'admin@teksilsite.test', 'sifre' => 'Tekstil2026!'));
check('admin-giris-redirect', is_redir($c));
check('admin-giris-oturum-doner', ($SES['admin']['teksil_sess'] ?? '') !== $sessOnce);
foreach (array('dashboard','urunler','kategoriler','markalar','siparisler','bayiler','kullanicilar','stok',
               'kuponlar','bannerlar','sayfalar','faturalar','raporlar','ebulten','feed','ayarlar','yetkiler','pazaryeri') as $m) {
    list($c, ) = get('admin', "/yonetim/$m");
    check("admin-$m-200", $c === 200);
}
// LV: e-bülten yönetim — abone listede + CSV (başlık ve içerik)
list($c, $r) = get('admin', '/yonetim/ebulten');
check('admin-ebulten-liste', $c === 200 && strpos($r, "bulten$T@test.local") !== FALSE);
list($c, $r) = get('admin', '/yonetim/ebulten/csv');
check('admin-ebulten-csv', $c === 200 && strpos($r, 'text/csv') !== FALSE && strpos($r, "bulten$T@test.local") !== FALSE);
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
// XLVI: ürün adı satıştaki ürünün detayına linkli (ürün 1 → slug'ı)
check('bayi-siparis-detay-urun-link', strpos($r, 'urun/suprem-v-yaka-body') !== FALSE);

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
// LIV: devri SAYFA düzeyinde de doğrula — giriş sonrası /sepet ürünü göstersin
list($c, $r) = get('kullanici', '/sepet');
check('kullanici-sepet-sayfa-render', $c === 200 && strpos($r, 'prem') !== FALSE);

// Kullanıcı siparişi hesabına işlenmeli (XXV): formda yanlış e-posta olsa bile
// sipariş hesabın e-postasıyla kaydedilir (hesabım eşleşmesi bu alandan).
list($c, ) = get('kullanici', '/odeme');
check('kullanici-odeme-form-200', $c === 200);
// Sipariş para birimi seçili teslimat ülkesinden gelmeli (XXXIV tutarlılık):
// sepet görüntüsü (EUR) ↔ kaydedilen sipariş snapshot'u (EUR) birebir.
$SES['kullanici']['teksil_ulke'] = 'de';
list($c, ) = post('kullanici', '/odeme/tamamla', array(
    'teslimat_ad' => 'Reg Kullanici', 'teslimat_adres' => 'Test mahalle cadde no 2',
    'teslimat_il' => 'Istanbul', 'teslimat_ilce' => 'Merkez', 'teslimat_telefon' => '5551112244',
    'email' => "yanlis$T@test.local", 'fatura_ayni' => '1', 'odeme_yontemi' => 'havale', 'kargo_firma_id' => 1,
    'sozlesme' => '1',
));
check('kullanici-siparis-redirect', is_redir($c));
$kSiparisId = (int) q1("SELECT id FROM siparisler WHERE email='" . esc($EK) . "' ORDER BY id DESC LIMIT 1");
check('kullanici-siparis-hesap-eposta', $kSiparisId > 0 && (int) q1("SELECT COUNT(*) FROM siparisler WHERE email='" . esc("yanlis$T@test.local") . "'") === 0);
check('kullanici-siparis-para-birimi-ulke', q1("SELECT para_birimi FROM siparisler WHERE id=$kSiparisId") === 'EUR');
unset($SES['kullanici']['teksil_ulke']);
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
// XLVI: sidebar renk etiketleri aktif dilde — EN'de "Black" görünür, "Siyah" etiketi gitmiş olmalı
check('dil-en-renk-etiket', strpos($r, '>Black') !== FALSE && strpos($r, 'Siyah <small>') === FALSE);
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
// XLVII: Arapça'da RTL yer değiştirme YOK — tüm diller LTR düzen (yalnız metin Arapça)
list($c, ) = get('dil', '/dil/cevir/ar');
list($c, $r) = get('dil', '/');
check('dil-ar-ltr-duzen', $c === 200 && strpos($r, 'dir="ltr"') !== FALSE && strpos($r, 'dir="rtl"') === FALSE);
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

/* ---- XXXII: CI3 validation mesajları aktif dilde ---- */
list($c, $r) = post('dil', '/bayi/kayit_kaydet', array());
check('validation-en-mesaj', $c === 200 && strpos($r, 'The Full Name field is required.') !== FALSE && strpos($r, 'alanı zorunludur') === FALSE);
list($c, $r) = post('guest', '/bayi/kayit_kaydet', array());
check('validation-tr-mesaj', $c === 200 && strpos($r, 'Ad Soyad alanı zorunludur.') !== FALSE && strpos($r, 'field is required') === FALSE);

/* ---- XXXIII: dil-bazlı slider setleri + footer çevirisi ---- */
list($c, $r) = get('dil', '/');
check('slider-en-kendi-seti', $c === 200 && strpos($r, 'Factory prices in wholesale womens clothing') !== FALSE && strpos($r, 'Toptan kadın giyimde üretici fiyatı') === FALSE);
check('ftr-en-cevrildi', strpos($r, 'Wholesale Terms (MOQ)') !== FALSE && strpos($r, 'Toptan Şartlar') === FALSE);
list($c, $r) = get('guest', '/');
check('slider-tr-kendi-seti', $c === 200 && strpos($r, 'Toptan kadın giyimde üretici fiyatı') !== FALSE && strpos($r, 'Factory prices in wholesale womens clothing') === FALSE);

/* ---- XXXIV: teslimat ülkesi → para birimi ---- */
/* Not: header ülke dropdown'unda ₺/$ sembolleri boşluksuz gelir (<small>₺</small>);
 * para biçimi ise boşlukludur ('1.234,50 ₺' / '1.234,50 $'). İddialar bu ayrımla kuruldu. */
list($c, ) = get('dil', '/ulke/sec/us');
check('ulke-sec-redirect', is_redir($c));
list($c, $r) = get('dil', '/katalog');
check('ulke-katalog-usd', $c === 200 && strpos($r, ' $') !== FALSE && strpos($r, ' ₺') === FALSE);
list($c, $r) = get('dil', '/urun/' . $urunSlug);
check('ulke-detay-usd', $c === 200 && strpos($r, ' $') !== FALSE && strpos($r, ' ₺') === FALSE);
list($c, ) = get('dil', '/ulke/sec/xyz');
list($c, $r) = get('dil', '/katalog');
check('ulke-gecersiz-tr-geri', $c === 200 && strpos($r, ' ₺') !== FALSE);
get('dil', '/ulke/sec/tr');   // sonraki bölümler için ülkeyi sıfırla

/* ---- XXXV: blog (D3) ---- */
list($c, $r) = get('guest', '/blog');
check('blog-liste-dolu', $c === 200 && strpos($r, 'Devamını Oku') !== FALSE && strpos($r, 'yeni-sezon-2026-trendleri') !== FALSE);
$blogSlug = (string) q1("SELECT slug FROM yazilar WHERE durum = 1 ORDER BY yayin_tarihi DESC, id DESC LIMIT 1");
list($c, $r) = get('guest', '/blog/' . $blogSlug);
check('blog-detay-200', $c === 200 && strpos($r, 'class="prose"') !== FALSE);
list($c, ) = get('guest', '/blog/olmayan-yazi-xyz');
check('blog-detay-404', $c === 404);
$regYazi = 'REGXXXV' . $T . ' Yazısı';
list($c) = post('admin', '/yonetim/yazilar/kaydet', array('baslik' => $regYazi, 'icerik' => '<p>Test icerik REGXXXV</p>', 'durum' => '1'));
check('yazi-admin-kaydet-redirect', is_redir($c));
$regYaziId = (int) q1("SELECT id FROM yazilar WHERE baslik = '" . esc($regYazi) . "'");
$regYaziSlug = (string) q1("SELECT slug FROM yazilar WHERE id = $regYaziId");
list($c, $r) = get('guest', '/blog/' . $regYaziSlug);
check('yazi-admin-vitrinde', $c === 200 && strpos($r, 'Test icerik REGXXXV') !== FALSE);
list($c) = post('admin', '/yonetim/yazilar/kaydet', array('id' => (string) $regYaziId, 'baslik' => $regYazi, 'icerik' => '<p>x</p>'));
list($c, ) = get('guest', '/blog/' . $regYaziSlug);
check('yazi-pasif-404', $c === 404);
q("DELETE FROM yazilar WHERE id = $regYaziId");

/* ---- XXXVII: SEO cilası (sitemap blog+CMS + canonical + JSON-LD) ---- */
list($c, $r) = get('guest', '/sitemap.xml');
check('seo-sitemap-200', $c === 200 && strpos($r, '<urlset') !== FALSE);
check('seo-sitemap-blog', strpos($r, '/blog</loc>') !== FALSE && strpos($r, '/blog/yeni-sezon-2026-trendleri') !== FALSE);
check('seo-sitemap-cms', strpos($r, '/iletisim</loc>') !== FALSE && strpos($r, '/sayfa/hakkimizda') !== FALSE);
$regSeoSlug = 'seo-pasif-' . $T;
q("INSERT INTO urunler (ad, slug, stok_kodu, kategori_id, fiyat, durum) VALUES ('SEO pasif', '" . esc($regSeoSlug) . "', 'SEO-" . $T . "', 1, 100, 0)");
list($c, $r) = get('guest', '/sitemap.xml');
check('seo-sitemap-pasif-urun-yok', strpos($r, $regSeoSlug) === FALSE);
q("DELETE FROM urunler WHERE slug = '" . esc($regSeoSlug) . "'");
list($c, $r) = get('guest', '/urun/' . $urunSlug);
check('seo-kanonik-detay', $c === 200 && strpos($r, '<link rel="canonical" href="' . $BASE . '/urun/' . $urunSlug . '"') !== FALSE);
check('seo-detay-jsonld', strpos($r, '"@type":"Product"') !== FALSE && strpos($r, '"priceCurrency":"TRY"') !== FALSE);
list($c, $r) = get('guest', '/katalog?sira=artan&sayfa=2');
check('seo-kanonik-filtre-atilir', strpos($r, 'rel="canonical" href="' . $BASE . '/katalog?sayfa=2"') !== FALSE);

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

/* ---- E2) XML içe aktarım (Faz 5) -------------------------------------------- */
// Not: sunucu-içi URL çekmesi Windows php -S'de (tek iş parçacığı) KENDİNE
// curl kilitlenmesi yaratır — URL yolu yalnız hızlı-hata durumunda sınanır;
// Xml_export ↔ içe aktarım simetrisi feed gövdesi xml_metin ile verilerek test edilir.
$gKod       = q1("SELECT stok_kodu FROM urunler WHERE id=1");
$gFiyatOnce = (float) q1("SELECT fiyat FROM urunler WHERE id=1");

// Kaynak: URL hata yolu (bağlanılamaz adres → hızlı ret)
list($c, ) = post('admin', '/yonetim/xml_ice/kaydet', array(
    'ad' => 'Regresyon XML', 'url' => 'http://127.0.0.1:1/hata.xml',
    'varsayilan_kategori_id' => '0', 'fiyat_carpani' => '1', 'yeni_urun_olustur' => '1',
));
check('xml-kaynak-ekle-redirect', is_redir($c));
$XK = (int) q1("SELECT id FROM xml_kaynaklari ORDER BY id DESC LIMIT 1");
check('xml-kaynak-db', $XK > 0);
list($c, $r) = get('admin', '/yonetim/xml_ice');
check('xml-ice-200', $c === 200 && strpos($r, 'yonetim/xml_ice') !== FALSE);
list($c, $r) = get('admin', "/yonetim/xml_ice/onizleme/$XK");
check('xml-cek-hata-yolu', $c === 200 && strpos($r, 'adm-uyari--hata') !== FALSE);

// Kendi feed'imiz geri okunabilmeli (Xml_export ↔ içe aktarım simetrisi).
// hh() başlıklarla döndüğü için gövde \r\n\r\n sonrasıdır — XML'e yalnız o kısım girer.
list($fc, $feedTam) = get('guest', "/feed/urunler?key=" . urlencode($anahtar));
$feedGovde = substr($feedTam, strpos($feedTam, "\r\n\r\n") + 4);
list($c, ) = post('admin', '/yonetim/xml_ice/kaydet', array(
    'id' => (string) $XK, 'ad' => 'Regresyon XML', 'url' => "$BASE/feed/urunler?key=" . urlencode($anahtar),
    'varsayilan_kategori_id' => '0', 'fiyat_carpani' => '1', 'yeni_urun_olustur' => '1',
));
list($c, $r) = post('admin', "/yonetim/xml_ice/onizleme/$XK", array('xml_metin' => $feedGovde));
check('xml-kendi-feed-geri-okundu', $fc === 200 && $c === 200 && strpos($r, 'Kuru ko') !== FALSE);
check('xml-kendi-feed-urun-sayisi',
      (int) q1("SELECT urun_sayisi FROM xml_loglari WHERE kaynak_id=$XK ORDER BY id DESC LIMIT 1")
      === (int) substr_count($feedGovde, '<urun id='));

// Fixtür: yeni ürün (TR biçimli fiyat, moq, 2 varyant) + mevcut ürün güncellemesi + kodsuz satır
$fixture = '<?xml version="1.0" encoding="UTF-8"?><katalog><urun><stokKodu>REG-XML-YENI</stokKodu>'
    . '<ad>Regresyon XML Urunu</ad><fiyat>12,34</fiyat><moq>2</moq>'
    . '<varyantlar><varyant><renk>Siyah</renk><beden>M</beden><sku>REG-XML-V1</sku><stok>7</stok></varyant>'
    . '<varyant><renk>Beyaz</renk><beden>L</beden><stok>3</stok></varyant></varyantlar></urun>'
    . "<urun><stokKodu>$gKod</stokKodu><ad>Guncellendi</ad><fiyat>98,76</fiyat></urun>"
    . '<urun><stokKodu></stokKodu><ad>Kodsuz</ad><fiyat>1</fiyat></urun></katalog>';

// Önizleme: kuru koşu — DB'de iz bırakmamalı
list($c, $r) = post('admin', "/yonetim/xml_ice/onizleme/$XK", array('xml_metin' => $fixture));
check('xml-onizleme-kuru', $c === 200 && strpos($r, 'Kuru ko') !== FALSE);
check('xml-onizleme-iz-yok', (int) q1("SELECT COUNT(*) FROM urunler WHERE stok_kodu='REG-XML-YENI'") === 0);

// GET ile gerçek aktarim reddi (yalnız POST)
list($c, ) = get('admin', "/yonetim/xml_ice/calistir/$XK");
check('xml-calistir-get-reddi', is_redir($c));

// Gerçek aktarım: yeni ürün + varyant + güncelleme + atlanan
list($c, ) = post('admin', "/yonetim/xml_ice/calistir/$XK", array('xml_metin' => $fixture));
check('xml-calistir-redirect', is_redir($c));
$yId = (int) q1("SELECT id FROM urunler WHERE stok_kodu='REG-XML-YENI'");
check('xml-yeni-urun-db', $yId > 0);
check('xml-yeni-fiyat-tr', abs((float) q1("SELECT fiyat FROM urunler WHERE id=$yId") - 12.34) < 0.001);
check('xml-yeni-moq', (int) q1("SELECT moq FROM urunler WHERE id=$yId") === 2);
check('xml-yeni-varyant', (int) q1("SELECT COUNT(*) FROM urun_varyantlari WHERE urun_id=$yId") === 2);
check('xml-guncelleme', abs((float) q1("SELECT fiyat FROM urunler WHERE id=1") - 98.76) < 0.001);
check('xml-log-atlanan', (int) q1("SELECT atlanan FROM xml_loglari WHERE kaynak_id=$XK AND kip='gercek' ORDER BY id DESC LIMIT 1") === 1);

// Tekrar koş → idempotent: yeni=0, guncellenen=2, varyant güncelleme=2
post('admin', "/yonetim/xml_ice/calistir/$XK", array('xml_metin' => $fixture));
$satir = q("SELECT yeni, guncellenen, varyant_guncellenen FROM xml_loglari WHERE kaynak_id=$XK AND kip='gercek' ORDER BY id DESC LIMIT 1")->fetch_assoc();
check('xml-tekrar-idempotent', (int) $satir['yeni'] === 0 && (int) $satir['guncellenen'] === 2 && (int) $satir['varyant_guncellenen'] === 2);

// Çarpan: 1.25 → 98.76 × 1.25 = 123.45
post('admin', '/yonetim/xml_ice/kaydet', array(
    'id' => (string) $XK, 'ad' => 'Regresyon XML', 'url' => 'http://127.0.0.1:1/hata.xml',
    'varsayilan_kategori_id' => '0', 'fiyat_carpani' => '1.25', 'yeni_urun_olustur' => '1',
));
post('admin', "/yonetim/xml_ice/calistir/$XK", array('xml_metin' => $fixture));
check('xml-fiyat-carpani', abs((float) q1("SELECT fiyat FROM urunler WHERE id=1") - 123.45) < 0.01);

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

/* ---- G) admin özellik taraması (her modülün GET eylemi + güvenli yazı akışları) */
$_gid = function($t){ return (int) q1("SELECT id FROM $t ORDER BY id ASC LIMIT 1"); };
$_aGet = function($ad, $url){
    list($c, $r) = get('admin', $url);
    check("admin-get-$ad", $c === 200 && ! preg_match('/(Fatal error|A PHP Error|Severity: error|Parse error|Call to a member function on)/i', $r));
};
// GET taraması — her admin rotası yüklenmeli, fatal/PHP hatası olmamalı.
$_aGet('dashboard', '/yonetim/dashboard');
$_aGet('siparisler', '/yonetim/siparisler'); if (($_i=$_gid('siparisler'))) $_aGet('siparisler-detay', "/yonetim/siparisler/detay/$_i");
$_aGet('urunler', '/yonetim/urunler'); $_aGet('urunler-ekle', '/yonetim/urunler/ekle'); if (($_i=$_gid('urunler'))) $_aGet('urunler-duzenle', "/yonetim/urunler/duzenle/$_i");
$_aGet('kategoriler', '/yonetim/kategoriler'); if (($_i=$_gid('kategoriler'))) $_aGet('kategoriler-duzenle', "/yonetim/kategoriler?duzenle=$_i");
$_aGet('markalar', '/yonetim/markalar'); if (($_i=$_gid('markalar'))) $_aGet('markalar-duzenle', "/yonetim/markalar?duzenle=$_i");
$_aGet('stok', '/yonetim/stok'); $_aGet('stok-hareketler', '/yonetim/stok/hareketler');
$_aGet('bayiler', '/yonetim/bayiler'); if (($_i=$_gid('bayiler'))) $_aGet('bayiler-detay', "/yonetim/bayiler/detay/$_i");
$_aGet('faturalar', '/yonetim/faturalar'); if (($_i=$_gid('faturalar'))) $_aGet('faturalar-detay', "/yonetim/faturalar/detay/$_i");
$_aGet('pazaryeri', '/yonetim/pazaryeri'); if (($_i=$_gid('pazaryeri_hesaplari'))) $_aGet('pazaryeri-detay', "/yonetim/pazaryeri/detay/$_i");
$_aGet('feed', '/yonetim/feed');
$_aGet('xml-ice', '/yonetim/xml_ice'); if (($_i=$_gid('xml_kaynaklari'))) $_aGet('xml-ice-log', "/yonetim/xml_ice/log/$_i");
$_aGet('raporlar', '/yonetim/raporlar'); $_aGet('raporlar-disa', '/yonetim/raporlar/disa_aktar');
$_aGet('bannerlar', '/yonetim/bannerlar'); if (($_i=$_gid('bannerlar'))) $_aGet('bannerlar-duzenle', "/yonetim/bannerlar?duzenle=$_i");
$_aGet('sayfalar', '/yonetim/sayfalar'); $_aGet('sayfalar-ekle', '/yonetim/sayfalar/ekle'); if (($_i=$_gid('sayfalar'))) $_aGet('sayfalar-duzenle', "/yonetim/sayfalar?duzenle=$_i");
$_aGet('kuponlar', '/yonetim/kuponlar'); $_aGet('kuponlar-ekle', '/yonetim/kuponlar/ekle'); if (($_i=$_gid('kuponlar'))) $_aGet('kuponlar-duzenle', "/yonetim/kuponlar?duzenle=$_i");
$_aGet('yazilar', '/yonetim/yazilar'); if (($_i=$_gid('yazilar'))) $_aGet('yazilar-duzenle', "/yonetim/yazilar?duzenle=$_i");
$_aGet('para-birimi', '/yonetim/para_birimi');
$_aGet('ayarlar', '/yonetim/ayarlar');
$_aGet('yetkiler', '/yonetim/yetkiler');

// Yazı akışları: oluştur → DB'de doğrula → sil → silindi doğrula (her CRUD modülü).
// Markalar
list($c, ) = post('admin', '/yonetim/markalar/kaydet', array('ad' => "REG-MARKA-$T", 'slug' => '', 'logo' => '', 'durum' => '1'));
check('admin-marka-ekle', is_redir($c) && (int) q1("SELECT COUNT(*) FROM markalar WHERE ad='REG-MARKA-$T'") === 1);
$_mi = (int) q1("SELECT id FROM markalar WHERE ad='REG-MARKA-$T' LIMIT 1");
list($c, ) = get('admin', "/yonetim/markalar/sil/$_mi");
check('admin-marka-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM markalar WHERE id=$_mi") === 0);
// Sayfalar
list($c, ) = post('admin', '/yonetim/sayfalar/kaydet', array('baslik' => "REG Sayfa $T", 'slug' => "reg-sayfa-$T", 'icerik' => 'test', 'seo_title' => '', 'seo_description' => '', 'durum' => '1'));
check('admin-sayfa-ekle', is_redir($c) && (int) q1("SELECT COUNT(*) FROM sayfalar WHERE slug='reg-sayfa-$T'") === 1);
$_si = (int) q1("SELECT id FROM sayfalar WHERE slug='reg-sayfa-$T' LIMIT 1");
list($c, ) = get('admin', "/yonetim/sayfalar/sil/$_si");
check('admin-sayfa-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM sayfalar WHERE id=$_si") === 0);
// Kuponlar
list($c, ) = post('admin', '/yonetim/kuponlar/kaydet', array('kod' => "REGKUP$T", 'tip' => 'yuzde', 'deger' => '10', 'min_sepet_tutar' => '0', 'max_indirim' => '0', 'kullanim_limiti' => '0', 'baslangic_zaman' => '', 'bitis_zaman' => '', 'aciklama' => 'reg', 'durum' => '1'));
check('admin-kupon-ekle', is_redir($c) && (int) q1("SELECT COUNT(*) FROM kuponlar WHERE kod='REGKUP$T'") === 1);
$_ki = (int) q1("SELECT id FROM kuponlar WHERE kod='REGKUP$T' LIMIT 1");
list($c, ) = get('admin', "/yonetim/kuponlar/sil/$_ki");
check('admin-kupon-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM kuponlar WHERE id=$_ki") === 0);
// LVI: Günlük Trend + Kupon Kullanımı raporları (XXXVIII entegrasyonu) — kupon raporu
// REGKUP2 siparişini göstermeli (atıflama zinciri uçtan uca).
list($c, $r) = get('admin', '/yonetim/raporlar/index/gunluk');
check('admin-rapor-gunluk-200', $c === 200);
list($c, $r) = get('admin', '/yonetim/raporlar/index/kupon');
check('admin-rapor-kupon-kod', $c === 200 && strpos($r, "REGKUP2$T") !== FALSE);
// LVII: Kullanıcılar (B2C) yönetimi — listede test kullanıcısı; durum + şifre sıfırlama
list($c, $r) = get('admin', '/yonetim/kullanicilar');
check('admin-kullanicilar-liste', $c === 200 && strpos($r, $EK) !== FALSE);
$_kid = (int) q1("SELECT id FROM kullanicilar WHERE email='" . esc($EK) . "'");
list($c, ) = post('admin', "/yonetim/kullanicilar/durum_guncelle/$_kid", array('durum' => '0'));
check('admin-kullanicilar-pasif', is_redir($c) && (int) q1("SELECT durum FROM kullanicilar WHERE id=$_kid") === 0);
list($c, ) = post('admin', "/yonetim/kullanicilar/sifre_sifirla/$_kid", array());
check('admin-kullanicilar-sifre-sifirla', is_redir($c));
// Bayi şifre sıfırlama — hash değişmeli (yeni şifre flash'ta bir kez gösterilir)
$_bayiHashOnce = (string) q1("SELECT sifre FROM bayiler WHERE id=$bayiId");
list($c, ) = post('admin', "/yonetim/bayiler/sifre_sifirla/$bayiId", array());
check('admin-bayi-sifre-sifirla', is_redir($c) && q1("SELECT sifre FROM bayiler WHERE id=$bayiId") !== $_bayiHashOnce);
// Bannerlar (gorsel_url ile; dil zorunlu)
list($c, ) = post('admin', '/yonetim/bannerlar/kaydet', array('baslik' => "REG Banner $T", 'alt_baslik' => '', 'buton_yazi' => '', 'link' => '', 'gorsel_url' => 'https://example.com/x.jpg', 'yazi_konum' => 'sol', 'dil' => 'tr', 'sira' => '999', 'durum' => '1'));
check('admin-banner-ekle', is_redir($c) && (int) q1("SELECT COUNT(*) FROM bannerlar WHERE baslik='REG Banner $T'") === 1);
$_bi = (int) q1("SELECT id FROM bannerlar WHERE baslik='REG Banner $T' LIMIT 1");
list($c, ) = get('admin', "/yonetim/bannerlar/sil/$_bi");
check('admin-banner-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM bannerlar WHERE id=$_bi") === 0);
// Yazilar
list($c, ) = post('admin', '/yonetim/yazilar/kaydet', array('baslik' => "REG Yazi $T", 'slug' => "reg-yazi-$T", 'ozet' => 'x', 'icerik' => 'x', 'gorsel' => '', 'yayin_tarihi' => date('Y-m-d H:i:s'), 'durum' => '1'));
check('admin-yazi-ekle', is_redir($c) && (int) q1("SELECT COUNT(*) FROM yazilar WHERE slug='reg-yazi-$T'") === 1);
$_yi = (int) q1("SELECT id FROM yazilar WHERE slug='reg-yazi-$T' LIMIT 1");
list($c, ) = get('admin', "/yonetim/yazilar/sil/$_yi");
check('admin-yazi-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM yazilar WHERE id=$_yi") === 0);
// Para_birimi: kaydet DİZİ alır (tüm birimleri tek formdan). USD kur'unu güncelle → geri al.
$_usdKurOnce = (string) q1("SELECT kur_try FROM para_birimleri WHERE kod='USD'");
list($c, ) = post('admin', '/yonetim/para_birimi/kaydet', array(
    'kod' => array('USD'), 'ad' => array('Dolar'), 'sembol' => array('$'),
    'kur_try' => array('99.99'), 'durum' => array('1'), 'sira' => array('2'),
));
check('admin-pb-kur-guncelle', is_redir($c) && abs((float) q1("SELECT kur_try FROM para_birimleri WHERE kod='USD'") - 99.99) < 0.001);
q("UPDATE para_birimleri SET kur_try=$_usdKurOnce WHERE kod='USD'");
// Para_birimi: yeni birim ekle (kaydet dizisine yeni kod) → sil(kod). kod CHAR(3) → 'TST'.
q("DELETE FROM para_birimleri WHERE kod='TST'");   // önceki koşu artığı varsa
list($c, ) = post('admin', '/yonetim/para_birimi/kaydet', array(
    'kod' => array('TST'), 'ad' => array('Test PB'), 'sembol' => array('T'),
    'kur_try' => array('2'), 'durum' => array('1'), 'sira' => array('99'),
));
check('admin-pb-ekle', is_redir($c) && (int) q1("SELECT COUNT(*) FROM para_birimleri WHERE kod='TST'") === 1);
list($c, ) = get('admin', '/yonetim/para_birimi/sil/TST');
check('admin-pb-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM para_birimleri WHERE kod='TST'") === 0);
// Geçersiz uzun kod reddedilmeli (>3 harf → eklenmez, DB sessiz truncate etmez)
list($c, ) = post('admin', '/yonetim/para_birimi/kaydet', array(
    'kod' => array('UZUNKOD'), 'ad' => array('X'), 'sembol' => array('X'),
    'kur_try' => array('1'), 'durum' => array('1'), 'sira' => array('1'),
));
check('admin-pb-uzun-kod-reddi', (int) q1("SELECT COUNT(*) FROM para_birimleri WHERE kod='UZU' OR kod='UZUNKOD'") === 0);
// Feed: api anahtarı oluştur → sil
list($c, ) = post('admin', '/yonetim/feed/olustur', array('ad' => 'REG Feed', 'bayi_id' => ''));
check('admin-feed-olustur', is_redir($c) && (int) q1("SELECT COUNT(*) FROM api_anahtarlari WHERE ad='REG Feed'") === 1);
$_fi = (int) q1("SELECT id FROM api_anahtarlari WHERE ad='REG Feed' LIMIT 1");
list($c, ) = get('admin', "/yonetim/feed/sil/$_fi");
check('admin-feed-sil', is_redir($c) && (int) q1("SELECT COUNT(*) FROM api_anahtarlari WHERE id=$_fi") === 0);
// Stok düzeltme (no-op: mevcut stoğu yeniden yaz → hareket oluşur, stok değişmez)
$_sv = (int) q1("SELECT id FROM urun_varyantlari WHERE id != 1 ORDER BY id ASC LIMIT 1");
if ($_sv) {
    $_svStok = (int) q1("SELECT stok FROM urun_varyantlari WHERE id=$_sv");
    list($c, ) = post('admin', "/yonetim/stok/duzeltle/$_sv", array('yeni_stok' => (string) $_svStok, 'sebep' => 'reg-noop'));
    check('admin-stok-duzeltle', is_redir($c) && (int) q1("SELECT stok FROM urun_varyantlari WHERE id=$_sv") === $_svStok
        && (int) q1("SELECT COUNT(*) FROM stok_hareketleri WHERE varyant_id=$_sv AND tip='duzeltme' ORDER BY id DESC LIMIT 1") >= 1);
    q("DELETE FROM stok_hareketleri WHERE varyant_id=$_sv AND tip='duzeltme' AND aciklama LIKE '%reg-noop%'");
}
// Siparisler durum güncelleme: onaylandi (stok etkilemeyen geçiş) → doğrula → geri al.
$_sdOnce = (string) q1("SELECT durum FROM siparisler WHERE id=$siparisId");
list($c, ) = post('admin', "/yonetim/siparisler/durum_guncelle/$siparisId", array('durum' => 'onaylandi', 'notu' => 'reg'));
check('admin-siparis-durum-guncelle', is_redir($c) && q1("SELECT durum FROM siparisler WHERE id=$siparisId") === 'onaylandi');
q("UPDATE siparisler SET durum='$_sdOnce' WHERE id=$siparisId");   // geri al (gecmis satırı temizlikten düşer)
// Kargolandı durumunda takip no zorunlu — eksikse reddedilmeli (durum değişmemeli)
list($c, ) = post('admin', "/yonetim/siparisler/durum_guncelle/$siparisId", array('durum' => 'kargolandi', 'notu' => ''));
check('admin-siparis-kargo-takip-zorunlu', is_redir($c) && q1("SELECT durum FROM siparisler WHERE id=$siparisId") === $_sdOnce);
unset($_sdOnce);
// Ayarlar kaydet (toggle yazı yolu): 4 toggle'ın TAMAMI post edilmeli (kısmi post
// diğer toggle'ları sıfırlar — checkbox semantiği). arama_index çevir → doğrula → geri al.
$_ayToggles = array('arama_index','sms_aktif','paytr_test','efatura_test');
$_ayOnce = array(); foreach ($_ayToggles as $tg) $_ayOnce[$tg] = (string) q1("SELECT deger FROM ayarlar WHERE anahtar='$tg'");
$_ayPost = array(); foreach ($_ayToggles as $tg) $_ayPost[$tg] = $_ayOnce[$tg] === '1' ? '1' : '0';
$_ayPost['arama_index'] = $_ayOnce['arama_index'] === '1' ? '0' : '1';   // çevir
list($c, ) = post('admin', '/yonetim/ayarlar/kaydet', $_ayPost);
check('admin-ayarlar-kaydet', is_redir($c) && q1("SELECT deger FROM ayarlar WHERE anahtar='arama_index'") === $_ayPost['arama_index']);
foreach ($_ayToggles as $tg) q("UPDATE ayarlar SET deger='" . esc($_ayOnce[$tg]) . "' WHERE anahtar='$tg'");   // geri al
// Yetkiler matris kaydet (yazı yolu, DAVRANIŞSAL): rol-2 'stok' görüntülemeyi kapat →
// admin2 /yonetim/stok 403 → geri aç → 200. (kaydet tüm matrisi yazar → tam grid post.)
$_ykGrid = array(); $_ykR = q("SELECT modul, goruntule, duzenle, sil FROM yetkiler WHERE rol_id=2");
while ($_row = $_ykR->fetch_assoc()) { $_m = $_row['modul']; $_ykGrid[$_m] = array(); if ((int)$_row['goruntule']) $_ykGrid[$_m]['goruntule']=1; if ((int)$_row['duzenle']) $_ykGrid[$_m]['duzenle']=1; if ((int)$_row['sil']) $_ykGrid[$_m]['sil']=1; }
unset($_ykGrid['stok']['goruntule']);
list($c, ) = post('admin', '/yonetim/yetkiler/kaydet', array('rol' => '2', 'grid' => $_ykGrid));
list($c2, ) = get('admin2', '/yonetim/stok');
check('admin-yetkiler-kaydet-403', is_redir($c) && $c2 === 403);
$_ykGrid['stok']['goruntule'] = 1; post('admin', '/yonetim/yetkiler/kaydet', array('rol' => '2', 'grid' => $_ykGrid));
list($c2, ) = get('admin2', '/yonetim/stok'); check('admin-yetkiler-geri-ac-200', $c2 === 200);
// Bayiler durum + grup güncelleme (test bayi $bayiId — B bölümü bitti, cleanup siler).
$_bdOnce = (string) q1("SELECT durum FROM bayiler WHERE id=$bayiId");
list($c, ) = post('admin', "/yonetim/bayiler/durum_guncelle/$bayiId", array('durum' => '2'));
check('admin-bayi-durum-guncelle', is_redir($c) && (string) q1("SELECT durum FROM bayiler WHERE id=$bayiId") === '2');
q("UPDATE bayiler SET durum=$_bdOnce WHERE id=$bayiId");
$_bgOnce = (string) q1("SELECT grup_id FROM bayiler WHERE id=$bayiId");
$_bgYeni = (int) q1("SELECT id FROM bayi_gruplari WHERE id != $_bgOnce ORDER BY id ASC LIMIT 1");
if ($_bgYeni) {
    list($c, ) = post('admin', "/yonetim/bayiler/grup_guncelle/$bayiId", array('grup_id' => (string) $_bgYeni));
    check('admin-bayi-grup-guncelle', is_redir($c) && (string) q1("SELECT grup_id FROM bayiler WHERE id=$bayiId") === (string) $_bgYeni);
    q("UPDATE bayiler SET grup_id=$_bgOnce WHERE id=$bayiId");
}
// Manuel ödeme işaretleme (havale/kapıda): odeme_durumu='odendi' + geçmiş. İdempotent.
$_odOnce = (string) q1("SELECT odeme_durumu FROM siparisler WHERE id=$siparisId");
list($c, ) = post('admin', "/yonetim/siparisler/odeme_isaretle/$siparisId", array('notu' => 'reg'));
check('admin-siparis-odeme-isaretle', is_redir($c) && q1("SELECT odeme_durumu FROM siparisler WHERE id=$siparisId") === 'odendi');
$_gecOnce = (int) q1("SELECT COUNT(*) FROM siparis_durum_gecmisi WHERE siparis_id=$siparisId");
post('admin', "/yonetim/siparisler/odeme_isaretle/$siparisId", array('notu' => ''));
check('admin-siparis-odeme-idempotent', q1("SELECT odeme_durumu FROM siparisler WHERE id=$siparisId") === 'odendi' && (int) q1("SELECT COUNT(*) FROM siparis_durum_gecmisi WHERE siparis_id=$siparisId") === $_gecOnce);
q("UPDATE siparisler SET odeme_durumu='$_odOnce' WHERE id=$siparisId");   // geri al (geçmiş 'reg' safety-netten)
// D2 rapor cilası: Satış Özeti'ne iade/iptal oranı + para birimi dağılımı + hızlı tarih seçiciler.
list($c, $r) = get('admin', '/yonetim/raporlar/index/satis');
check('admin-rapor-satis-yeni-metrikler', $c === 200 && strpos($r, 'İade/İptal Oranı') !== FALSE && strpos($r, 'Para Birimi Dağılımı') !== FALSE && strpos($r, 'Son 7 gün') !== FALSE);
list($c, $r) = get('admin', '/yonetim/raporlar/disa_aktar/satis/csv');
check('admin-rapor-csv-yeni-metrikler', $c === 200 && strpos($r, 'İade/İptal Oranı') !== FALSE && strpos($r, 'Para Birimi') !== FALSE);
unset($_ayToggles, $_ayOnce, $_ayPost, $_ykGrid, $_ykR, $_row, $_m, $_bdOnce, $_bgOnce, $_bgYeni, $_odOnce, $_gecOnce, $c2);
// Güvenlik ağı: yazı akışları yarım kalırsa test artığı kalmasın.
q("DELETE FROM markalar WHERE ad LIKE 'REG-MARKA-%'");
q("DELETE FROM sayfalar WHERE slug LIKE 'reg-sayfa-%'");
q("DELETE FROM kuponlar WHERE kod LIKE 'REGKUP%'");
q("DELETE FROM bannerlar WHERE baslik LIKE 'REG Banner%'");
q("DELETE FROM yazilar WHERE slug LIKE 'reg-yazi-%'");
q("DELETE FROM api_anahtarlari WHERE ad='REG Feed'");
q("DELETE FROM para_birimleri WHERE kod IN ('TST','UZU','UZUNKOD')");
q("DELETE FROM siparis_durum_gecmisi WHERE notu='reg'");   // admin durum_guncelle test satırları
q("UPDATE yetkiler SET goruntule=1 WHERE rol_id=2 AND modul='stok'");   // Yetkiler test çökerse rol-2 stok geri açılsın (seed=1)
unset($_gid, $_aGet, $_i, $_mi, $_si, $_ki, $_bi, $_yi, $_usdKurOnce, $_fi, $_sv, $_svStok);

/* ---- temizlik ---------------------------------------------------------------- */
q("DELETE FROM faturalar WHERE siparis_id=$siparisId");
q("DELETE FROM siparis_detaylari WHERE siparis_id=$siparisId");
q("DELETE FROM siparisler WHERE id=$siparisId");
q("DELETE FROM faturalar WHERE siparis_id=" . (int) ($kSiparisId ?? 0));
q("DELETE FROM siparis_detaylari WHERE siparis_id=" . (int) ($kSiparisId ?? 0));
q("DELETE FROM siparisler WHERE id=" . (int) ($kSiparisId ?? 0));
q("DELETE FROM faturalar WHERE siparis_id=" . (int) ($kupSiparisId ?? 0));   // LVI: kuponlu sipariş
q("DELETE FROM siparis_detaylari WHERE siparis_id=" . (int) ($kupSiparisId ?? 0));
q("DELETE FROM siparisler WHERE id=" . (int) ($kupSiparisId ?? 0));
q("DELETE FROM sepet WHERE bayi_id=$bayiId");
q("DELETE FROM sepet WHERE oturum_id='" . esc($SES['kullanici']['teksil_sess'] ?? '') . "'");
q("UPDATE ayarlar SET deger='' WHERE anahtar IN ('paytr_merchant_key','paytr_merchant_salt','paytr_merchant_id')"); // callback provası anahtarlarını geri al
q("DELETE FROM bayiler WHERE id=$bayiId");
q("DELETE FROM yoneticiler WHERE email='reg2$T@test.local'");
q("DELETE FROM kullanicilar WHERE email='" . esc($EK) . "'");
q("DELETE FROM api_anahtarlari WHERE id=$anahtarId");
q("DELETE FROM feed_denemeler");
q("UPDATE urun_varyantlari SET stok=$vStokOnce WHERE id=1");
q("DELETE FROM urun_varyantlari WHERE urun_id=" . (int) ($yId ?? 0));
q("DELETE FROM urunler WHERE stok_kodu LIKE 'REG-XML-%'");
q("UPDATE urunler SET fiyat=$gFiyatOnce WHERE id=1");
q("DELETE FROM xml_kaynaklari WHERE ad='Regresyon XML'");   // xml_loglari CASCADE (ad'yla — crash artığına dayanıklı, $kalan da ad'la sayar)
q("DELETE FROM ebulten_aboneler WHERE eposta LIKE 'bulten%@test.local'");   // LV: e-bülten test aboneleri (geçersiz e-posta hiç yazılmaz)
if ($db->query("SHOW TABLES LIKE 'stok_hareketleri'")->num_rows
    && $db->query("SHOW COLUMNS FROM stok_hareketleri LIKE 'siparis_id'")->num_rows) {
    q("DELETE FROM stok_hareketleri WHERE siparis_id IN ($siparisId, " . (int) ($kSiparisId ?? 0) . ", " . (int) ($kupSiparisId ?? 0) . ")");
}
$kalan = (int) q1("SELECT (SELECT COUNT(*) FROM siparisler WHERE email='" . esc($E) . "')
                 + (SELECT COUNT(*) FROM bayiler WHERE email='" . esc($E) . "')
                 + (SELECT COUNT(*) FROM yoneticiler WHERE email='reg2$T@test.local')
                 + (SELECT COUNT(*) FROM kullanicilar WHERE email='" . esc($EK) . "')
                 + (SELECT COUNT(*) FROM api_anahtarlari WHERE id=$anahtarId)
                 + (SELECT COUNT(*) FROM feed_denemeler)
                 + (SELECT COUNT(*) FROM xml_kaynaklari WHERE ad='Regresyon XML')
                 + (SELECT COUNT(*) FROM urunler WHERE stok_kodu LIKE 'REG-XML-%')");
check('temizlik-tamam', $kalan === 0);
check('stok-geri-yuklendi', (int) q1("SELECT stok FROM urun_varyantlari WHERE id=1") === $vStokOnce);

/* ---- özet -------------------------------------------------------------------- */
echo "---\n$PASS PASS / $FAIL FAIL\n";
if ($FAILED) { echo "FAIL listesi:\n - " . implode("\n - ", $FAILED) . "\n"; }
exit($FAIL ? 1 : 0);
