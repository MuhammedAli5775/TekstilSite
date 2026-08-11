# workflow.md — TekstilSite çalışma kuralları & yol haritası

> Bu dosya **nasıl çalışacağımızı** belirler: kilit kararlar, kodlama kuralları,
> tasarım sistemi haritası ve faz yol haritası. CLAUDE.md (Nesem Tesettur referans
> mimarisi) ve DESIGN.md (MongoDB görsel sistemi) bu projeye uyarlanmıştır.
>
> Hedef: **kaktusmoda.com gibi bir TOPTAN (B2B) kadın giyim e-ticaret platformu.**
> Stack: **CodeIgniter 3.1 + MySQL (XAMPP)**. Görsel: **DESIGN.md (MongoDB yeşil/lacivert).**

---

## 1. Kilit kararlar (kilitli)

| Konu | Karar |
|------|-------|
| **Kapsam** | Tam işler mağaza: storefront (mağaza) + yönetim paneli (admin) + MySQL DB |
| **Stack** | PHP 8.1 + CodeIgniter 3.1.13 (MVC) + MySQL/MariaDB (XAMPP) |
| **Tasarım referansı** | DESIGN.md — MongoDB görsel sistemi (yeşil pill buton, lacivert hero bandı, Euclid Circular A + Source Code Pro) |
| **Satış modeli** | **Toptan / B2B** (kaktusmoda gibi): minimum sipariş adedi (MOQ), toptan/adet fiyat basamağı, çoklu para birimi, XML/API katalog dışa aktarım, bayi (toptancı) hesapları |
| **Marka** | Yer tutaku: **TekstilSite** (sonra değiştirilebilir; logo/renk değişince yalnızca token dosyası) |
| **Ortam** | XAMPP — PHP `C:\xampp\php\php.exe`, MariaDB `C:\xampp\mysql\bin\mysql.exe` |

> **3 yönlü referans çelişkisi çözüldü:** CLAUDE.md'nin Next.js/AdminLTE
> önerileri geçersiz (stack = CI 3.1). Nesem'in monokrom/Cormorant estetiği
> **kullanılmaz**; görsel = DESIGN.md. kaktusmoda yalnızca **içerik/kategori yapısı
> ve B2B akış** için referans (renk/font değil).

---

## 2. Çalışma kuralları (her değişiklikte)

### Güvenlik (breaker = bloke eder)
- **Ham SQL YOK.** Her sorgu CI **Query Builder (Active Record)** → otomatik parametre bağlama (SQL injection koruması).
- **Şifre** `password_hash()` / `password_verify()` (bcrypt) — md5/plain YASAK.
- **CSRF** her formda açık; gizli endpoint'ler `csrf_exclude_uris`.
- **Para** her zaman `DECIMAL(10,2)` — asla FLOAT. Para birimi `TRY` (varsayılan).
- **Stok/para** işlemleri **DB transaction** içinde (`trans_begin`…`trans_rollback/commit`).
- **Upload** native doğrulama: uzantı whitelist + `getimagesize` (gerçek-resim) + `is_uploaded_file` + boyut sınırı (CI3 Upload XAMPP'te güvenilmez → kullanma).
- **API anahtarları** DB'de **CI Encryption** ile şifreli (plaintext YASAK).
- **Soft delete:** müşteri/sipariş/ürün asla hard-delete (`deleted_at` / `durum`).
- **Veri izolasyonu:** müşteri yalnız kendi verisi; admin işlemi rol-yetki matrisine tabi + audit log.

### Çıktı & UTF-8 (mojibake YASAK)
- Tüm çıktı **`e()`** (htmlspecialchars, UTF-8) ile escape — güvenilir olmayan her veri.
- Dosyalar **UTF-8 (BOM'suz)**; SQL seed **`SET NAMES utf8mb4;`** ile.
- DB/tablolar: `utf8mb4_unicode_ci`. Tablo/kolon adları **Türkçe, Türkçe karaktersiz**
  (`siparisler`, `urunler`, `musteriler`). `mysql.exe -e "Türkçe..."` konsol codepage'inde
  `?`'e bozar → UTF-8 dosyadan pipe ile çalış.
- CI3'te library yüklerken sınıf adı = dosya adı (küçük harf load): `$this->load->library('odeme_api')`
  → `$this->odeme_api`. Controller ile aynı isimde library AÇMA (çakışma → fatal).

### Mimari sözleşme
- Üçüncü parti servisler ayrı **library** (ödeme/kargo/pazaryeri/e-fatura/reklam);
  anahtar yoksa **graceful** (pasif/stub), uygulama çalışmaya devam eder.
- Ortak tekrar desenleri **helper/library**'de, view'da inline değil.
- View'larda PHP `tw()`/`e()`/`me()` yardımcıları; ham HTML printf'i değil.

### Doğrulama (her PR/değişiklik)
1. `php -l` lint temiz (dokunulan her dosya).
2. İlgili rota HTTP 200 / beklenen yönlendirme; fatal yok (error_log temiz).
3. DB'ye yazan akış: gerçek test → doğrula → test verisini **temizle**.
4. Mojibake kontrolü (UTF-8 byte-grep).
5. Değişen dosyalar + DB değişikliği `DEGISIKLIK.md`'ye (en üste, en yeni ilk) satır olarak.

---

## 3. Tasarım sistemi haritası (DESIGN.md → CSS)

DESIGN.md token'ları birebir `assets/magaza/css/teksil.css` `:root` değişkenlerine
çevrilir; bileşenler语义 sınıflara (`.btn-primary`, `.card-feature`, `.hero-band`) ayrılır.

### Renkler
```
--brand-green:#00ed64;  --primary-pressed:#008c34;  --on-primary:#001e2b;
--teal-deep:#001e2b;    --teal:#003d4f;             --teal-mid:#00684a;
--canvas:#ffffff;       --surface:#f9fbfa;          --surface-soft:#f4f7f6;
--hairline:#e1e5e8;     --hairline-strong:#c1ccd6;
--ink:#001e2b;          --slate:#3d4f5b;            --steel:#5c6c7a;   --muted:#a8b3bc;
--accent-purple:#7b3ff2; --accent-orange:#fa6e39;   /* YALNIZ kategori etiketi */
```
- **Kural:** parlayan yeşil (`--brand-green`) **yalnız CTA buton/rozet** — büyük yüzey/gövde metni DEĞİL.
- Kategori etiketleri dışında doygun renk **yok**.
- Lacivert (`--teal-deep`) = hero bandı, footer, CTA banner, kod mockup zemini.

### Tipografi
- **Euclid Circular A** (MongoDB'nin tescilli/paid fontu) → dosyalar elimizde DEĞİL.
  DESIGN.md fallback zincirini kullanırız, önüne yakın bir **ücretsiz geometrik sans**
  koyarız: `font-family:'Euclid Circular A','Figtree',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;`
  (Euclid lisansı gelirse dosyaları eklenir, gerisi otomatik). **Figtree** (Google Fonts, ücretsiz) Euclid'e en yakın.
- **Kod:** Source Code Pro (Google Fonts, ücretsiz).
- Hiyerarşi (DESIGN.md): hero 72/500, h1 48, h2 36, h3 28, h4 22, h5 18/600, body 16/1.55,
  button 14/600, caption-bold 13/600, micro-uppercase 11/600+1px.
- Negatif letter-spacing display boyutlarda (-1.5px…-0.5px); hero leading 1.10.

### Şekil & boşluk
- Radius: xs4 sm6 md8 **lg12 (kartlar)** xl16 xxl24 **full9999 (tüm butonlar/rozetler)**.
- Butonlar daima **pill** (`full`). Kartlar `lg` (12px).
- Spacing 4→120 (8 ana kadem). Hero bandı `120px`, section `96px`/`64px`.
- Container 1280px max + 32px oluk.

### Bileşenler (DESIGN.md'den)
`.btn-primary` (yeşil pill, 10×22), `.btn-secondary` (outlined pill), `.btn-on-dark`,
`.card-base/.card-feature` (12px + 1px hairline), `.hero-band-dark`, `.cta-banner-dark`,
`.badge-green/.badge-green-soft/.badge-popular`, `.pill-tab`, `.segmented-tab`,
`.search-pill`, `.pricing-card-featured` (mint + yeşil border), `.code-mockup-card`.
> Hover state'leri DESIGN.md politikası gereği **belgelenmez** (default + pressed/active).

---

## 4. Mimari

```
Tarayıcı (toptancı müşteri / admin)
   │ HTTP(S) — Apache (XAMPP)
   ▼
CodeIgniter 3.1 (MVC)
   ├─ Mağaza katmanı   (application/controllers/*.php)         ─► views/magaza/*  (DESIGN.md)
   └─ Yönetim katmanı  (application/controllers/yonetim/*.php) ─► views/yonetim/* (DESIGN.md)
        │
   Controllers ─► Models (Query Builder) ─► MySQL (db: teksilsite)
        │
   Libraries: Odeme_api (PayTR/iyzico), Kargo_api, Pazaryeri_api, Efatura, Xml_export
   Cron: XML senkron, terk sepet, günlük rapor, yedek
```
- **Tek uygulama**, iki yüzey (`/` mağaza, `/yonetim` admin), aynı DB.
- CI **Query Builder**; InnoDB; `utf8mb4_unicode_ci`; session = **DB driver** (`ci_sessions`).

---

## 5. Klasör yapısı

```
TekstilSite/
├── index.php · .htaccess          ← CI giriş + clean URL + dosya koruması
├── system/                        ← CI çekirdek (DOKUNMA)
├── application/
│   ├── config/                    ← config/database/routes/autoload/config
│   ├── core/MY_Controller.php     ← Magaza_Controller + Admin_Controller
│   ├── controllers/
│   │   ├── (mağaza)               ← Anasayfa, Katalog, Urun, Sepet, Odeme, Bayi...
│   │   ├── yonetim/               ← admin modülleri
│   │   └── api/                   ← webhook/callback (ödeme, kargo, XML feed)
│   ├── models/                    ← Urun_model, Siparis_model, ... (modül başına 1)
│   ├── views/{magaza,yonetim}/
│   ├── libraries/                 ← Auth_admin, Odeme_api, Xml_export...
│   └── helpers/                   ← teksil_helper.php (e/para_tr/tw/...)
├── assets/{magaza,yonetim}/       ← CSS/JS (DESIGN.md → teksil.css)
└── uploads/                       ← ürün görseli/banner/logo
└── sql/                           ← schema.sql + seed.sql (ithal edilebilir)
```

---

## 6. Veri modeli özeti (B2B — toptan)

kaktusmoda B2B mantığına göre çekirdek tablo grupları (tam DDL `sql/schema.sql`):

| Grup | Tablolar (örnek) |
|------|------------------|
| Coğrafi | `iller`, `ilceler` |
| Yetki | `roller`, `yetkiler`, `yoneticiler`, `yonetici_loglari` |
| Bayi/Müşteri | `bayi_gruplari`, `bayiler` (firma/VKN/vergi dairesi + bakiye + özel fiyat grubu), `bayi_adresleri`, `sepet`, `favoriler` |
| Katalog | `kategoriler`, `markalar`, `urunler` (MOQ, çoklu fiyat basamağı), `urun_varyantlari` (renk+beden+stok+SKU), `urun_gorselleri`, `ozellikler`, `urun_ozellikleri`, `etiketler` |
| Fiyatlandırma | `fiyat_basamaklari` (adet aralığı → indirim), `bayi_grup_fiyatlari` (grup bazlı ek indirim) |
| Sipariş | `siparisler` (bayi_id, para_birimi, kur, ara_toplam, islem_ucreti, kargo_ucreti, indirim, toplam, durum), `siparis_detaylari` (anlık kopya), `siparis_durum_gecmisi`, `iade_talepleri` |
| Ödeme | `odeme_yontemleri`, `banka_hesaplari`, `odeme_islemleri` |
| Kargo | `kargo_firmalari`, `kargo_ucretleri` (desi/bölge), `ucretsiz_kargo_kurallari` |
| Kampanya | `kuponlar`, `kampanyalar` |
| İçerik | `sayfalar`, `bannerlar`, `sss`, `blog_yazilari` |
| Sistem | `ayarlar`, `eposta_sablonlari`, `ci_sessions`, `ziyaret_loglari`, `sistem_loglari`, `bildirimler` |
| B2B entegrasyon | `xml_kaynaklari` (dış XML içe), `api_anahtarlari` (dış XML/REST dışa — toptancı erişimi) |

**B2B kuralları (CLAUDE.md B2C'den farklı):**
- Sepete ekleme **MOQ** ve **katı adet (kutu/duzine)** kontrollü.
- Fiyat = ürün baz fiyat × adet basamağı indirimi × bayi grubu indirimi (server-side).
- Sipariş tutarı **çoklu para birimi** (bayi bölgesi) + anlık kur ile kaydedilir (anlık kopya).
- Bayiye özel XML/API feed (stok+fiyat) — toptancı onayı sonrası açılır.
- Misafir alışveriş **yok**; kayıt + admin onayı (bayi statüsü) gerekir.

---

## 7. Faz yol haritası

> Her faz kod-tam + doğrulanmış olarak biter. Büyük fazlar paralel ajanlara bölünebilir.

- **Faz 0 — Çekirdek (BU TUR):** CI kurulumu, config (base_url/DB/session/CSRF), `MY_Controller`
  (Magaza_Controller/Admin_Controller), `teksil_helper` (e/para_tr/tw...), DESIGN.md →
  `teksil.css` (token+temel bileşenler), mağaza layout (head/header/footer MongoDB tasarımı),
  `sql/schema.sql` + `sql/seed.sql`, anasayfa iskeleti (çalışır + veri bağlanınca dolar).
- **Faz 1 — Katalog:** kategori/liste (sol filtre, mega menü), ürün detay (galeri+varyant+MOQ),
  ürün arama. Demo katalog seed.
- **Faz 2 — Sepet & Checkout:** sepet (MOQ/adet basamağı), ödeme (havale/kart), çoklu para
  birimi, sipariş transaction + onay e-postası.
- **Faz 3 — Bayi (B2B):** kayıt/onay, giriş, hesabım (siparişler/adresler/faturalar/bakiye),
  bayiye özel fiyatlandırma.
- **Faz 4 — Yönetim paneli:** dashboard, sipariş/ürün/stok/bayi/katalog/CMS/kampanya/kargo/
  ödeme/rapor/ayar/yetki modülleri (DESIGN.md yatay menü).
- **Faz 5 — B2B entegrasyon:** XML içe/dışa aktarım, API feed (toptancı), pazaryeri senkron,
  e-fatura, SMS/e-posta, cron işleri.

---

## 8. Ortam & çalıştırma

```
Kod kökü:   ...\TekstilSite  (CI app root)
PHP:        C:\xampp\php\php.exe  (8.1.25)
MySQL:      C:\xampp\mysql\bin\mysql.exe  (MariaDB 10.4.32)
DB:         teksilsite  (utf8mb4)   — root şifreli [! bekliyor]
Çalıştırma (dev önizleme):
   C:\xampp\php\php.exe -S localhost:8000 -t .
   → http://localhost:8000   (mağaza)
Üretim-benzeri: kodu C:\xampp\htdocs\teksilsite altına kopyala → http://localhost/teksilsite
phpMyAdmin: http://localhost/phpmyadmin
```
> **[!] MySQL root şifreli** — DB oluşturma/seed kullanıcı girdisi bekler (bkz. Faz 0 notu).
> OneDrive altında session/upload kilidi riski → CI session save_path sistem tmp'sine yönlendirilecek.

---

## 9. İsimlendirme & sözleşmeler

- Controller dosya/sınıf: `Urun.php` / `class Urun extends Magaza_Controller`.
- Model: `Urun_model.php` / `class Urun_model extends CI_Model`, metotlar `mg_*` (mağaza) / admin native.
- Helper: `teksil_helper.php` → `e()`, `para_tr()`, `ayar()`, `tw()`, `moq_kontrol()`...
- View klasörü: `views/magaza/{anasayfa,katalog,urun,sepet,odeme,bayi,layout}/*`.
- Asset: `assets/magaza/css/teksil.css`, `assets/magaza/js/teksil.js` (`?v=filemtime` cache-bust).
- Yönetici süper admin seed: `admin@teksilsite.test` / `Tekstil2026!` (Faz 4'te).
