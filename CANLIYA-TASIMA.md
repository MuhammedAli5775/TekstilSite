# CANLIYA-TASIMA.md — TekstilSite Canlıya Taşıma Kontrol Listesi

> Bu oturumda (2026-08-08) yapılan **Faz 5 + polish + sağlamlaştırma** işlerini üretim
> ortamına taşıma rehberi. Tarihçe: `DEGISIKLIK.md`. Mimari/kurallar: `workflow.md`.
>
> Yaklaşım: (1) yedek → (2) DB migration → (3) `application/` senkron → (4) Ayarlar'dan
> modülleri sırayla aktive et → (5) ön-kontrol → (6) canlı sonrası (cron/ops).

---

## 0. Yedek
Production DB + mevcut `application/` klasörünün yedeğini al (geri dönüş güvenliği).

## 1. DB migration (üretim veritabanında, sırayla)

Mevcut prod şemasında `bayiler.son_giris` YOKSA (eskiden kurulmuşsa) önce ALTER:
```sql
ALTER TABLE bayiler ADD COLUMN son_giris DATETIME NULL DEFAULT NULL AFTER olusturma_zaman;
```
Sonra Faz 5 tabloları (idempotent — `IF NOT EXISTS`):
```
mysql ... < sql/migrate_faz5_feed.sql        -- api_anahtarlari
mysql ... < sql/migrate_faz5_fatura.sql      -- faturalar
mysql ... < sql/migrate_faz5_pazaryeri.sql   -- pazaryeri_hesaplari + _urun_eslestirme + _loglari
```
> Taze kurulumda hepsi `sql/schema.sql` + `sql/seed.sql` ile gelir; migration gerekmez.

## 2. Kod senkronu
Tüm **`application/`** klasörünü üretimle senkronla (controllers/models/libraries/views/
config/helpers/core). Çok dosya değişti — tek tek seçmek yerine **bütün klasör** gönder.

Bu oturumda **yeni** eklenenler:
- `application/libraries/`: `Xml_export.php`, `Sms.php`, `Efatura.php`, `Pazaryeri_api.php`
- `application/models/`: `Api_anahtar_model.php`, `Fatura_model.php`, `Pazaryeri_model.php`
- `application/controllers/`: `Seo.php`; `api/Feed.php`; `yonetim/{Feed,Faturalar,Pazaryeri}.php`
- `application/views/yonetim/{feed,faturalar,pazaryeri}/`

**Değişen** (mevcut dosyalar): `controllers/{Odeme,Sepet}.php`,
`controllers/yonetim/{Ayarlar,Siparisler,Urunler,Kategoriler,Bayiler}.php`,
`core/MY_Controller.php`, `models/Urun_model.php`, `config/routes.php`,
`views/yonetim/{ayarlar/index,siparisler/detay}.php`, `controllers/api/Feed.php`.

## 3. Ayarlar (admin → Ayarlar) — modülleri sırayla aktive et
Her modül kimlik girilince kendiliğinden canlıya açılır (graceful — boşsa pasif, akış bozulmaz).

| Modül | Nerden | Ne girilir | Sonuç |
|-------|--------|-----------|-------|
| **E-posta** | Ayarlar › SMTP | sunucu/port/kullanıcı/şifre/gönderen | sipariş + durum maili canlı |
| **SMS** | Ayarlar › SMS | Netgsm kullanıcı/şifre/gönderen + `sms_aktif` | durum SMS canlı |
| **E-Fatura** | Ayarlar › E-Fatura | entegratör + API URL + token + satıcı VKN/ünvan | siparişten fatura → entegratör (asenkron) |
| **Feed (B2B)** | API / Feed › anahtar üret | toptancıya `?key=` ver | XML/JSON katalog erişimi |
| **Pazaryeri** | Pazaryeri › hesap ekle | Trendyol supplierId + API key/secret | stok/fiyat + sipariş senkronu |
| **SEO** | Ayarlar › SEO | `site_adi`, meta, GA/Pixel; `arama_index` | indeksleme + analytics |
| **PayTR (kart)** | Ayarlar › PayTR | merchant_id/key/salt + test modu; `odeme_yontemleri.paytr` durum=1 | kartlı ödeme (iFrame) |
| **Para birimi** | Ayarlar › Para birimleri | kur_try tanımları (USD/EUR/GBP…); bayi profilden seçer | sipariş bayi para biriminde |

**Ödeme:** havale/EFT + kapıda + **PayTR (kartlı)** aktif edilince. PayTR iFrame API
entegre (get-token + hash/callback testli); merchant anahtarları (PayTR paneli →
Integration Information) + Bildirim URL (`<site>/paytr/bildirim`) + `odeme_yontemleri.
paytr` durum=1 girilince canlı.

**Para birimi:** `para_birimleri` tablosu + kur_try (1 birim = N TRY). Bayi Hesabım ›
Bilgiler'den para birimi seçer; sipariş anlık kur ile o para biriminde snapshot. Katalog
TRY bazlı; sepet/ödeme/sipariş kayıtları bayi para biriminde.

**Yer tutak:** `site_adi` şu an "TekstilSite" — gerçek marka adıyla değiştir.

## 4. Üretim ortam farkları (local dev ≠ prod)
- `application/config/database.php` → üretim DB host/kullanıcı/şifre/veritabanı.
- `application/config/config.php` → `base_url` (`https://...`). **`encryption_key`
  AYNI kalmalı** — pazaryeri hesap kimlikleri CI Encryption ile buna bağlı; değişirse
  mevcut şifreli kimlikler çözülemez!
- Session `files` driver, `sess_save_path` sistem tmp'sinde — prod'de tmp yazılabilir olsun.
- `router.php` yalnızca `php -S` içindir; prod'de Apache `.htaccess` veya nginx rewrite.
- GD extension (WebP görsel dönüşümü) — `php.ini extension=gd` + web server restart.
- HTTPS/SSL zorunlu (KVKK/ödeme); OneDrive yolu DEĞİL, gerçek document root.

## 5. Ön-kontrol (canlıda)
1. `php -l` temiz (bu oturumda tüm dokunan dosyalar doğrulandı).
2. Smoke test — hepsi 200/beklenen: `/`, `/katalog`, kategori, ürün, `/sepet`, `/odeme`,
   `/bayi/giris`, `/yonetim/*`, `/sitemap.xml`, `/robots.txt`, `/feed/urunler` (anahtarsız 401).
3. Error log temiz (fatal yok).
4. Mojibake yok (UTF-8) — `application/` FFFD byte denetimi.
5. `arama_index`: site tam hazır olana dek **kapalı** (noindex); yayında Açık yap.

## 6. Canlı sonrası (ops)
- **Cron (otomasyon):** `php index.php cron calis` periyodik çalıştır (Linux crontab /
  Windows Task Scheduler). İşler: terk-sepet temizliği, pazaryeri stok/fiyat senkronu
  (aktif hesaplar), e-fatura durum sorgusu (işlenen faturalar). Web erişimi engelli
  (sadece CLI). Örn saatlik: `0 * * * * cd /site && php index.php cron calis`.
- **E-Fatura:** "Durum Yenile" ile asenkron process_id takibi (cron da sorgular).
- **DB + `uploads/` yedek** + log izleme.

---

## Bu oturumda (2026-08-08) yapılanlar özeti
- Faz 5: B2B XML/REST feed · SMS (Netgsm) · E-Fatura/E-Arşiv · Pazaryeri (Trendyol).
- Polish: sitemap+robots (yeni Seo) · api/Feed mojibake temizliği · sepet sağlamlaştırma.
- Sağlamlaştırma: admin argüman-zorunlu metodlar (9 metod) · `Urunler::duzenle`,
  `Siparisler::detay` guard · `bayiler.son_giris` kolonu.
- Tümü `php -l` temiz, UTF-8/mojibake-temiz, smoke-test onaylı. Detay `DEGISIKLIK.md`.
