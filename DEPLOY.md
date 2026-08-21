# DEPLOY.md — TekstilSite canlı kurulum runbook'u (Faz B)

> Hedef okur: siteyi hosting'e kuracak kişi (Ops). Bu belge TAMAMLAMA_PLANI.md
> Faz B'nin (B1–B8) somut komutlara çevrilmiş halidir. Sırayla uygula;
> her adımın sonunda doğrulama satırı var. Sonda "İlk kurulum doğrulama"
> kontrol listesi ve rollback notu bulunur.
>
> Hazırlanma: 2026-08-14. Ortam hedefi: Apache 2.4 + PHP 7.4–8.2 + MySQL 5.7+/8.0
> (`mysqli` eklentisi şart), Linux hosting (cPanel/DirectAdmin/Plesk veya VPS).

---

## 0. Gerekenler (elden olmazsa başlama)

- [ ] Alan adı + hosting (Apache, `.htaccess` AllowOverride açık — çoğu paylaşımlı hosting'te açık)
- [ ] **SSL sertifikası** (Let's Encrypt ücretsiz; ödeme formu var → HTTPS zorunlu)
- [ ] MySQL veritabanı + phpMyAdmin ya da SSH erişimi
- [ ] SSH erişimi OLMAK ZORUNDA DEĞİL — FTP ile de kurulur; cron+yedek (adım 6–7) için
      kontrol panelinde cron görevi ve yedek özelliği yeterli.

## 0b. Yerel prod provası (lansmandan ÖNCE — hosting alınmadan kanıtlanabilir)

> 2026-08-15'te bu reçeteyle **74/74 PASS** kanıtlandı (XAMPP Apache + öz-imzalı SSL).
> Amaç: canlıya para harcanmadan üretim davranışının (CI_ENV=production + HTTPS +
> `.htaccess` + SetEnv mekanizması + production config seti) tamamının yerelde koşulması.

1. Repoyu Türkçe karakter/boşluk içermeyen bir yola kopyala (Apache + OneDrive yolu
   junction'u güvenilir değil — gerçek kopya kullan): `robocopy <kaynak> C:\teksilprova /E /XD .git`
2. Kopyanın `application/config/production/` altında GEÇİCİ olarak: `base_url =
   'https://localhost:8443/'` + database.php'ye yerel DB kimlikleri.
3. XAMPP `httpd-vhosts.conf`'a geçici vhost: `Listen 8443` + `<VirtualHost *:8443>`
   (SSLEngine on, default self-signed cert, `SetEnv CI_ENV production`,
   `AllowOverride All`, `Require local`) → `httpd -t` → Apache başlat.
4. Doğrula: `curl -sk -I https://localhost:8443/yonetim/giris` → `teksil_csrf_cookie`
   VE `teksil_sess` çerezleri `secure` bayraklı DÜŞMELİ; `curl -sk -o /dev/null -w
   "%{http_code}" https://localhost:8443/sql/schema.sql` → **403** (.htaccess guard'ı
   ilk kez gerçek Apache'de test edilmiş olur).
5. Paket: kopya dizininden `php <repo>/tests/regresyon.php https://localhost:8443 --insecure`
   (CWD kopya olmalı — log denetimi sunulan uygulamanın loguna bakar; `--insecure`
   yalnızca öz-imzalı sertifika içindir, canlıda KULLANMA).
6. Teardown: Apache durdur, vhost bloğunu geri al, kopyayı ve geçici config dolgusunu
   sil (`git checkout -- application/config/production/`).

Provanın 15-08'de yakaladığı gerçek tuzak: **HTTPS'siz prod modunda tüm formlar 403**
— bkz. bölüm 5'teki Cloudflare/Flexible SSL uyarısı.

## 1. Kodu sunucuya taşı

SSH varsa (tercih):

```sh
cd ~/public_html            # ya da sitenin document root'u
git clone https://github.com/MuhammedAli5775/TekstilSite.git .
```

FTP ile: reponun tamamını (system/, application/, assets/, index.php, .htaccess,
uploads/ içindeki .htaccess dahil) document root'a yükle.

**ÖNEMLİ — production config'i doldur:**

1. `application/config/production/config.php`:
   - `base_url` → `https://www.alanadin.com/` (sondaki `/` şart; www'lu ya da www'suz —
     hangisini kullanacaksan onu yaz, karışmasın)
2. `application/config/production/database.php`: **eksiksiz dosyadır** (CI3 bu dosya varken
   temel database.php'yi hiç okumaz) — hostname/username/password/database doldur.
   `password`'ü `SERT_PAROLA_BURAYA` yer tutucusundan değiştirmeyi unutma.

Bu dosyalar repoya işlenmiş ŞABLONLARDIR; sunucuda doldurulurlar. Şifreyi tekrar
commitlemeye/FTP dışına taşımaya çalışma.

## 2. Veritabanı kullanıcısı (en-az-ayrıcalık, B3)

phpMyAdmin > SQL ya da SSH mysql:

```sql
CREATE DATABASE IF NOT EXISTS teksilsite
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'teksil_app'@'localhost' IDENTIFIED BY '<SERT_PAROLA>';
GRANT ALL PRIVILEGES ON teksilsite.* TO 'teksil_app'@'localhost';
FLUSH PRIVILEGES;
```

(root kullanma. Tek DB'e kısıtlı ALL; global ayrıcalık yok. Kurulum sonrası
istenen sertlikte `SELECT,INSERT,UPDATE,DELETE,LOCK TABLES`'a düşürülebilir —
migration günlerinde ALL gerekir.)

## 3. Veritabanı şeması — SIRASI ŞART (B3)

```sh
mysql -u teksil_app -p teksilsite < sql/schema.sql
mysql -u teksil_app -p teksilsite < sql/migrate_faz2.sql
mysql -u teksil_app -p teksilsite < sql/migrate_faz4.sql
mysql -u teksil_app -p teksilsite < sql/migrate_faz5_fatura.sql
mysql -u teksil_app -p teksilsite < sql/migrate_faz5_feed.sql
mysql -u teksil_app -p teksilsite < sql/migrate_faz5_pazaryeri.sql
mysql -u teksil_app -p teksilsite < sql/migrate_faz5_xml_ice.sql     # XML içe aktarım (tedarikçi feed → katalog; yetki satırı migrate_yetkiler'de)
mysql -u teksil_app -p teksilsite < sql/migrate_kuponlar.sql
mysql -u teksil_app -p teksilsite < sql/migrate_para_birimi.sql
mysql -u teksil_app -p teksilsite < sql/migrate_2026_08_09.sql
mysql -u teksil_app -p teksilsite < sql/migrate_feed_rate_limit.sql
mysql -u teksil_app -p teksilsite < sql/migrate_perf_index.sql
mysql -u teksil_app -p teksilsite < sql/migrate_kullanicilar.sql     # kullanıcı (B2C) hesap tablosu
mysql -u teksil_app -p teksilsite < sql/migrate_banner_dil.sql      # bannerlar.dil (çoklu dil slider filtresi)
mysql -u teksil_app -p teksilsite < sql/migrate_ulke_para.sql       # para_birimleri +GBP/RUB/AED (teslimat ülkesi → para birimi, XXXIV)
mysql -u teksil_app -p teksilsite < sql/migrate_yazilar.sql         # yazilar tablosu + yetki + demo yazılar (blog, XXXV)
mysql -u teksil_app -p teksilsite < sql/seed.sql
mysql -u teksil_app -p teksilsite < sql/migrate_yetkiler.sql       # seed'den SONRA (FK roller'a bağlı)
mysql -u teksil_app -p teksilsite < sql/migrate_kategori_dil.sql    # kategoriler.ad_{en,ru,ar} — seed'den SONRA: UPDATE'ler seed'in kategorilerine işler (XXXVI prova bulgusu)
mysql -u teksil_app -p teksilsite < sql/seed_hukuki_sayfalar.sql   # hukuki taslaklar (yer tutucu doldur, adım 8)
mysql -u teksil_app -p teksilsite < sql/seed_sayfalar_footer.sql   # iletisim/toptan-sartlari/xml-feed — YOKSA footer 404
mysql -u teksil_app -p teksilsite < sql/seed_slider.sql            # demo anasayfa slider'ı (admin Bannerlar'dan değiştir)
```

**seed.sql SONRA, yetki/footer/slider seed'leri EN SONDA** (seed.sql truncate içerir;
sıra karışırsa veri ezilir). `migrate_yetkiler` seed'den SONRAYLA: yetkiler FK'ı
`roller(id)`'ye bağlı ve roller'i dolduran seed'dir — önce koşulursa INSERT IGNORE
boş roller'e sessizce 0 satır ekler (15-08 provasında bulundu; rol-2 o durumda her
modüle 403 alır). `bayiler.son_giris` ve `siparisler.email` kolonları `schema.sql`'e
işlendi; eski bir kurulum üzerine upgrade ediyorsan elle
`ALTER TABLE bayiler ADD COLUMN son_giris DATETIME NULL DEFAULT NULL` /
`ALTER TABLE siparisler ADD COLUMN email VARCHAR(150) NULL` gerekebilir.

## 4. Dizin izinleri (B6)

Yazılabilir olması gerekenler (web sunucusu kullanıcısı — Apache'de genelde
`www-data` ya da cPanel'te kullanıcı hesabın):

```sh
mkdir -p application/sessions application/logs uploads/bannerlar
chmod 750 application/sessions application/logs
chmod 755 uploads uploads/bannerlar
# Dosya sahipliği panel kullanıcısındaysa chmod yeter; SSH'ta:
# chown -R www-data:www-data application/sessions application/logs uploads
```

- `application/sessions/` — oturum dosyaları (production config bu yolu kullanır;
  `.htaccess` application/ dizinine web erişimini zaten kapatıyor)
- `uploads/.htaccess` repodan gelir (PHP çalıştırma yasağı) — silme.

## 5. ENVIRONMENT=production (B4)

cPanel/DirectAdmin Apache'de `.htaccess`'e ekle (mod_env):

```apache
SetEnv CI_ENV production
```

Vhost erişimin varsa `<VirtualHost>` içine `SetEnv CI_ENV production` (daha temiz).
Plesk: "Apache & nginx Settings" > ek direktifler.

**Doğrulama (mekanizma 2026-08-14'te empirik test edildi):**
- Ana sayfayı aç → sayfa kaynağında form `action="https://alanadin.com/..."` olmalı
  (base_url production'dan geliyor).
- `curl -I https://alanadin.com/bayi/giris` → `Set-Cookie: teksil_sess=...` satırında
  `secure; HttpOnly` bayrakları olmalı.
- İkisinden biri tutmuyorsa CI_ENV set edilmemiştir → ENVIRONMENT hâlâ development'dir.

Not: PHP hata görüntüleme production'da CI3 tarafından otomatik kapatılır
(index.php ENVIRONMENT switch'i). Ekstra `php_flag display_errors off` istenirse
eklenebilir ama şart değil.

**[!] Cloudflare / TLS-sonlandıran proxy uyarısı (15-08 provasında bulundu):**
CI3 `Security.php` `csrf_set_cookie()`, `cookie_secure=TRUE` **ve istek HTTPS
değilse** CSRF çerezini HİÇ yazmaz → PHP'nin HTTPS görmediği her kurulumda
sitedeki TÜM form POST'ları 403 düşer (giriş, kayıt, sepet, ödeme dahil).
Cloudflare kullanılırsa **Flexible SSL KULLANMA** — origin'e HTTPS taşıyan
**Full (Strict)** mod şart. Proxy arkasında `proxy_ips` doldurulmalı
(`application/config/production/config.php`); X-Forwarded-Proto'suz esnek
SSL ile PHP hâlâ http görür ve formlar yine 403 verir.

## 6. Cron (B7)

Terk sepet + pazaryeri senkronu + e-fatura durum sorgusu + XML içe aktarım (hepsi tek komutta):

```cron
*/15 * * * * CI_ENV=production /usr/bin/php /home/KULLANICI/public_html/index.php cron calis >> /home/KULLANICI/teksil-cron.log 2>&1
```

- `CI_ENV=production` öneki ŞART (yoksa dev config'le koşar; PHP CLI env'leri okur).
- PHP yolu panelden farklıysa (`/usr/local/bin/php` gibi) panele bak.
- Web'den `cron/calis` çağrısı `is_cli()` guard'ı ile 403 verir — tasarım gereği.
- Tek tek de koşulabilir: `cron terk_sepet 7`, `cron pazaryeri_senkron`,
  `cron efatura_durum`, `cron xml_ice_aktar` (ayrıntı: `application/controllers/Cron.php`).

Elle ilk koşu + doğrulama: `CI_ENV=production php index.php cron calis` →
stdout'a iş özetleri basılır; `teksil-cron.log`'a bak.

## 7. Yedekleme (B8)

`scripts/yedek.sh` repoda gelir. Sunucuda bir kez düzenle (üstteki 4 değişken),
ardından gece cron'u:

```cron
17 3 * * * /home/KULLANICI/scripts/yedek.sh >> /home/KULLANICI/teksil-yedek.log 2>&1
```

DB dökümü (`mysqldump --single-transaction`) + `uploads/` arşivi → `yedekler/`
altına gün damgalı; 14 günden eski otomatik silinir. Uzak kopya istenirse
`RSCMD` satırını doldur (rsync örnek olarak yorumda).

CI3 kendi log rotasyonunu yapmaz; log temizliği için (isteğe bağlı):

```cron
25 4 * * * find /home/KULLANICI/public_html/application/logs -name 'log-*.php' -mtime +30 -delete
```

### 7b. Hata log izleme (E3)

`scripts/log_kontrol.sh` repodan gelir — günlük ERROR özeti (mesaj bazlı gruplu):

```cron
35 7 * * * /home/KULLANICI/scripts/log_kontrol.sh >> /home/KULLANICI/teksil-cron.log 2>&1
```

**Uptime izleme dışarıdan** (ücretsiz bir servis yeterli — örn. UptimeRobot/Better Stack):
canlı domain açılıp ayarlandıktan sonra 2 monitör ekle: `https://alanadin.com/` (HTTP 200,
5 dk aralık) ve `https://alanadin.com/feed/urunler` (401 beklenir — 200/500 değil; API'nin
yaşadığını gösterir). Bildirim e-postası: işletme adresi.

## 8. İlk kurulum doğrulama kontrol listesi

- [ ] `https://alanadin.com/` 200; HTTP → HTTPS yönlendirmesi çalışıyor
      (hosting panelinden "Force HTTPS" ya da .htaccess'e rewrite kuralı)
- [ ] Sayfa kaynağında tüm asset URL'leri `https://alanadin.com/...` (karışık içerik yok)
- [ ] `https://alanadin.com/robots.txt` → `Sitemap: https://alanadin.com/sitemap.xml`
- [ ] Yönetim → Ayarlar → **"Arama motoru indekslemesi açık (yayında)"** işaretli
      (`arama_index=1` — işaretlenmedikçe robots.txt tüm siteyi `Disallow: /` ile
      engellemeye devam eder; LVII'de checklist'e eklendi)
- [ ] `https://alanadin.com/sitemap.xml` XML listesi geliyor
- [ ] `https://alanadin.com/yonetim` → yönetim girişi (admin giriş sonrası dashboard)
- [ ] **Admin şifresi İLK GİRİŞTE DEĞİŞTİRİLSİN** — GitHub tarihçesinde eski dev
      şifresi bulunuyor (bkz. DEGISIKLIK.md 2026-08-14 repo hijyeni notu)
- [ ] Bayi kaydı → admin onayı → sepet → (PayTR canlı anahtar girildiyse) küçük tutarlı
      gerçek ödeme + e-posta düşmesi
- [ ] `application/logs/` içinde `log-YYYY-AA-GG.php` birikmiyor (hata yok)
- [ ] Cron logu işliyor; `yedekler/` altında ilk döküm var
- [ ] Uptime monitörleri (anasayfa + feed) kurulmuş, bildirim e-postası test edilmiş
- [ ] Hukuki sayfalar (Mesafeli Satış / İade / KVKK / Çerez) gözden geçirilmiş:
      `sql/seed_hukuki_sayfalar.sql` TASLAKTIR — [YER TUTUCU] kalmadığından ve
      [POLİTİKA:...] maddelerinin işletme kararıyla doldurulduğundan emin ol
- [ ] `https://alanadin.com/system/...` ve `/application/...` → 403
- [ ] `https://alanadin.com/uploads/` → dizin listesi kapalı (403/boş)

## 9. Rollback

Kod tarafı git ise: sunucuda `git log` → `git checkout <önceki-commit>` (config
dosyaları untracked değişiklik kalır — dokunulmaz). DB tarafı: `gunzip < yedekler/db-YYYYAA-GG-HHMMSS.sql.gz | mysql -u teksil_app -p teksilsite`.
Migration'lar geri alınmaz — geriye dönüş = döküm restore.

## 10. Lansman sonrası (ayrı hat — burada değil)

Faz A kimlikleri (SMTP/PayTR canlı/SMS/e-fatura entegratör/Trendyol) Ayarlar
panelinden (D0 sonrası panelden girilebilir) ya da elle `ayarlar` tablosuna
girilir; C7 tam regresyon + C6 Trendyol canlı testi yapılır.
