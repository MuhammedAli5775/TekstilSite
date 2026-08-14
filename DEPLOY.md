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
mysql -u teksil_app -p teksilsite < sql/migrate_kuponlar.sql
mysql -u teksil_app -p teksilsite < sql/migrate_para_birimi.sql
mysql -u teksil_app -p teksilsite < sql/migrate_2026_08_09.sql
mysql -u teksil_app -p teksilsite < sql/migrate_yetkiler.sql
mysql -u teksil_app -p teksilsite < sql/migrate_feed_rate_limit.sql
mysql -u teksil_app -p teksilsite < sql/migrate_perf_index.sql
mysql -u teksil_app -p teksilsite < sql/seed.sql
```

**seed.sql EN SONDA** (truncate içerir; sıra karışırsa veri ezilir). `bayiler.son_giris`
kolonu `schema.sql`'e işlendi; eski bir kurulum üzerine upgrade ediyorsan elle
`ALTER TABLE bayiler ADD COLUMN son_giris DATETIME NULL DEFAULT NULL` gerekebilir.

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

## 6. Cron (B7)

Terk sepet + pazaryeri senkronu + e-fatura durum sorgusu (hepsi tek komutta):

```cron
*/15 * * * * CI_ENV=production /usr/bin/php /home/KULLANICI/public_html/index.php cron calis >> /home/KULLANICI/teksil-cron.log 2>&1
```

- `CI_ENV=production` öneki ŞART (yoksa dev config'le koşar; PHP CLI env'leri okur).
- PHP yolu panelden farklıysa (`/usr/local/bin/php` gibi) panele bak.
- Web'den `cron/calis` çağrısı `is_cli()` guard'ı ile 403 verir — tasarım gereği.
- Tek tek de koşulabilir: `cron terk_sepet 7`, `cron pazaryeri_senkron`,
  `cron efatura_durum` (ayrıntı: `application/controllers/Cron.php`).

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

## 8. İlk kurulum doğrulama kontrol listesi

- [ ] `https://alanadin.com/` 200; HTTP → HTTPS yönlendirmesi çalışıyor
      (hosting panelinden "Force HTTPS" ya da .htaccess'e rewrite kuralı)
- [ ] Sayfa kaynağında tüm asset URL'leri `https://alanadin.com/...` (karışık içerik yok)
- [ ] `https://alanadin.com/robots.txt` → `Sitemap: https://alanadin.com/sitemap.xml`
- [ ] `https://alanadin.com/sitemap.xml` XML listesi geliyor
- [ ] `https://alanadin.com/yonetim` → yönetim girişi (admin giriş sonrası dashboard)
- [ ] **Admin şifresi İLK GİRİŞTE DEĞİŞTİRİLSİN** — GitHub tarihçesinde eski dev
      şifresi bulunuyor (bkz. DEGISIKLIK.md 2026-08-14 repo hijyeni notu)
- [ ] Bayi kaydı → admin onayı → sepet → (PayTR canlı anahtar girildiyse) küçük tutarlı
      gerçek ödeme + e-posta düşmesi
- [ ] `application/logs/` içinde `log-YYYY-AA-GG.php` birikmiyor (hata yok)
- [ ] Cron logu işliyor; `yedekler/` altında ilk döküm var
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
