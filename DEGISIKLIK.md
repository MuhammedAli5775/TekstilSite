# DEGISIKLIK.md — TekstilSite Değişiklik Günlüğü

> Bu dosya TekstilSite'de yapılan her değişikliği (**dosya + DB + doğrulama**)
> tarihsel sırayla kaydeder. Yeni kayıt **en üste** eklenir (en yeni ilk).
>
> Kayıt kuralı: `workflow.md` §2 (Doğrulama) madde 5 — her değişiklik "hangi dosya
> değişti + hangi DB değişikliği yapıldı + nasıl doğrulandı" biçiminde bir satır/
> blok olarak yazılır. Yeni kayıt eklemeden önce:
>   1. `php -l` ile dokunulan dosya lint temiz,
>   2. ilgili rota 200 / beklenen yönlendirme, fatal yok,
>   3. DB'ye yazan akış gerçek test edildi + test verisi temizlendi,
>   4. mojibake (UTF-8 byte-grep) kontrolü.
>
> Bu günlük **2026-08-08'de açıldı**. Öncesindeki Faz 0–4 kurulum işleri
> `workflow.md` yol haritasında ve kod tabanında kendini gösterir; burada ayrıca
> kayıtlı değildir. CLAUDE.md bu projeye ait değildir (eski Nesem referansıdır) —
> gerçek rehber `workflow.md` + bu dosyadır.

---

## 2026-08-12 — Sipariş yaşam döngüsü E2E doğrulama (admin geçişler + iade stok iadesi)

B2B sipariş akışının doğrulanmamış yarısı kapatıldı: sipariş **oluşturma** 53/53
doğrulanmıştı; bu turda **yaşam döngüsü** (sonrası) doğrulandı — admin durum
geçişleri, iade'de stok geri ekleme ve çift-iade koruması. **25/25 PASS.** Kod
değişikliği yok (yalnızca test); riskli para/stok mantığının doğru çalıştığı teyit.

**Doğrulanan akış (tek test siparişi, varyant #1 qty 6, stok 248→242→…→248):**
- **Oluşturma** (real flow: kayıt→sepet→odeme): sipariş oluştu, stok 248→242, ilk
  durum geçmişi (sistem/onay_bekliyor).
- **İleri geçişler** (admin `yonetim/siparisler/durum_guncelle/{id}`):
  onaylandi → hazirlaniyor → kargolandi → teslim_edildi. Her biri: durum güncellendi,
  `siparis_durum_gecmisi`'ne bir satır eklendi, **stok değişmedi** (iade dışı).
  `kargolandi`'da `kargo_takip_no` + `kargo_firma_id` yazıldı (takip no zorunlu kuralı).
- **iade_edildi**: stok **geri eklendi** 242→248 (+6); `stok_hareketleri` tip=iade
  adet=+6 onceki_stok=242 loglandı.
- **Çift-iade koruması** (`_stok_iade_et`): iade_edildi → iptal geçişinde eski durum
  zaten iade'de olduğu için stok **TEKRAR eklenmedi** (248 sabit), yalnızca 1 iade
  hareketi. Bu, `_stok_iade_et`'in `in_array(yeni) && !in_array(eski)` koşulunun doğru
  çalıştığını teyit eder (stok iki kez geri eklenmesi = envanter sızıntısı engellendi).
- Her geçiş `yonetici_loglari`'na audit yazıldı; e-posta/SMS bildirimi graceful
  (SMTP yoksa atlar, akış bozulmadı).

**Doğrulama:** test betiği 25/25 PASS; kendi test verisini (bayi/sipariş/detay/
hareket/geçmiş/audit) temizledi + varyant #1 stoğunu 248'e geri yükledi. Bağımsız
baseline: bayiler 2, varyant #1 stok 248, test artığı satır 0. Sunucu logu tüm test
boyunca temiz. (Test betiği `AppData/Local/Temp`'te geçici — silindi.)

**[!] Canlıya taşı:** kod değişikliği yok.

---

## 2026-08-12 — Dashboard dönem filtresi (bugün/hafta/ay/yıl/tümü)

`yonetim/dashboard` üstüne dönem seçici eklendi. Sipariş-tabanlı tüm veriler (KPI
sipariş/bekleyen/ciro, durum dağılımı, trend grafiği, son siparişler) seçili döneme
göre filtrelenir; "Aktif Bayi/Ürün" anlık durum olarak kalır (etiketi zaten "Aktif").

**Dosyalar:**
- `controllers/yonetim/Dashboard.php`: `?donem=bugun|hafta|ay|yil|tumu` GET param +
  `_donem_aralik()` — her dönem için `olusturma_zaman` [basla,bitir] penceresi +
  trend granüleri (bugün→saatlik, hafta/ay→günlük, yıl/tümü→aylik). Default: ay.
- `models/Dashboard_model.php`: `ozet/son_siparisler/durum_dagilim` opsiyonel
  `$basla,$bitir` alır; `_aralik($basla,$bitir,$alan)` yardımcısı where uygular.
  `siparis_trendi($basla,$bitir,$granul)` yeniden yazıldı — hour/day/month
  gruplama + hazır `etiket` (grafikte kullanılır).
- `views/yonetim/dashboard/index.php`: `.adm-donem` seçici (segmented pill links),
  KPI/trend başlıkları seçili dönemi gösterir, trend `etiket` kullanır, boş-dönemde
  "Bu dönemde sipariş yok".
- `assets/yonetim/css/admin.css`: `.adm-donem*` stilleri (ink aktif pill).

**Bug (test sırasında):** `son_siparisler` join'inde (siparisler `s` + bayiler `b`,
ikisinde de `olusturma_zaman`) tarih filtresi niteliksizdi → "Column
'olusturma_zaman' is ambiguous" → 500. `_aralik` kolon parametresi alacak şekilde
genelleştirildi; join'li sorguda `s.olusturma_zaman` verilir.

**Doğrulama:** admin login ile her dönem 200; dashboard sipariş sayısı DB ile birebir
(bugün 0, ay 14, yıl 18, tümü 18); seçili dönem butonu vurgulu; trend etiketleri doğru
(Bugün/Bu Ay/Bu Yıl/Tümü), `ay` trendinde günlük `01.08…10.08` verisi. 4 PHP/CSS dosyası
lint temiz + FFFD=0; render FFFD=0; CI log + sunucu logu temiz (ambiguous giderildi).

**[!] Canlıya taşı:** `controllers/yonetim/Dashboard.php` +
`models/Dashboard_model.php` + `views/yonetim/dashboard/index.php` +
`assets/yonetim/css/admin.css` FTP.

---

## 2026-08-12 — Favoriler header'a taşındı + katalog filtre checkbox hizalama bug'ı

İki UI isteği: (1) `utility-bar`'daki Favoriler linki `header-actions`'a taşındı
("Bayi Ol" yerine); (2) katalog filtresinde checkbox'lar ismin altında duruyordu —
düzeltildi.

**(1) Favoriler taşınması — `views/magaza/layout/header.php`:**
- `utility-bar__right`: `♡ Favoriler` linki kaldırıldı.
- `header-actions`: guest "Bayi Ol" → `♡ Favoriler` ile değiştirildi. Favoriler
  linki if/else dışına alındı (guest + giriş yapmış bayi her ikisinde de görünür);
  böylece utility-bar'dan kalkınca favoriler kimse için erişilmez kalmıyor.
  Guest: Favoriler · Giriş · Sepet. Bayi: Favoriler · Hesabım · Çıkış · Sepet.

**(2) Checkbox hizalama bug'ı — `assets/magaza/css/teksil.css`:**
- Kök neden: katalog filtresi `<label class="filtre-check">` kullanıyor ama CSS'te
  `.filtre-check` YOKtu; `.filtre-etiket` kuralı yazılmıştı (label için tasarlanmış)
  ama view `.filtre-etiket`'i iç `<span>`'a koymuştu. Sonuç: `.filtre-etiket` span'i
  `display:flex` (block-level) → kendi satırına zorlanıyor, checkbox üstte / isim
  altta kalıyordu (16 filtre öğesi).
- Düzeltme: `.filtre-check` flex satır olarak tanımlandı (`display:flex;
  align-items:center;gap:9px`) → checkbox + isim yan yana. İç `.filtre-etiket` span'i
  `display:inline` (block-flex'ten kurtarıldı), `<small>` adet sayacı muted, `.swatch`
  renk noktası 14×14 boyutlandırıldı (önce stilisizdi, renk filtresi görünmüyordu).
- Ek: `.odeme-check` (kayıt sözleşmesi onay kutusu) de stilisizdi → flex satır
  (`align-items:flex-start`, çok satırlı sözleşme metnine hizalı) olarak eklendi.

**Doğrulama:** `/` 200; header-actions'ta Favoriler VAR, utility-bar'da YOK; catbar
site-header kardeş (div dengesi sağlam). Servis edilen `teksil.css`'te `.filtre-
check{display:flex`, `.odeme-check{display:flex`, `.filtre-check .swatch` VAR.
`/katalog` filtresinde 16 `.filtre-check` label'ı (checkbox + isim aynı label).
`header.php` lint temiz + FFFD=0; sunucu logu temiz.

**[!] Canlıya taşı:** `views/magaza/layout/header.php` +
`assets/magaza/css/teksil.css` FTP.

---

## 2026-08-12 — Anasayfa yeniden tasarım (ürün yok) + sticky header + catbar hizalama

Anasayfa artık ürün VİTRİNİ göstermiyor; site-tanıtım odaklı akış: slider → değer
önerileri → kategoriler → istatistik → bayi yorumları → CTA. Ayrıca üst bar + ana
header artık sticky; catbar öğeleri eşit aralıklı + ortalanmış; dropdown ▾ ikonu
kaldırıldı.

**Dosyalar:**
- `views/magaza/anasayfa.php`: "Öne çıkan parçalar" ürün bölümü (`.prodgrid` +
  `urun_karti` partial) kaldırıldı. Yeni bölümler: `.stats` (4 güven ölçütü, yeşil
  mint zemin) ve `.reviews` (3 bayi yorumu — SVG yıldız + lacivert baş harf avatar).
  Yorumlar statik dizi (DB'de yorum tablosu yok, Faz 5+).
- `controllers/Anasayfa.php`: `mg_vitrin` çağrısı + `_demo_vitrin()` kaldırıldı
  (anasayfada artık ürün yok). `index()` yalnız meta + render.
- `views/magaza/layout/header.php`: catbar `<a>` içindeki `▾` dropdown ikonu koşulu
  (`!empty($m['altlar'])`) kaldırıldı; hover alt menü `.mega__sub` korundu.
- `assets/magaza/css/teksil.css`:
  - `header{position:sticky;top:0;z-index:50}` — `utility-bar` + `header-main`
    birlikte sabit (pinned yükseklik ~158px = 38+72+48). `.header-main`'in kendi
    `position:sticky`'si kaldırıldı (iç-içe sticky önlemi).
  - `.mega` → `justify-content:space-evenly;width:100%` (öğeler eşit aralıklı +
    ortalanmış; eski `gap:6px` kalktı).
  - Yeni: `.stats`/`.stat__*` (4 kolon), `.reviews__grid`/`.review-card*` (3 kolon,
    turuncu SVG yıldız, lacivert avatar) + responsive (1100px→2 kol, 480px→1 kol).
  - Sticky ofset güncellemesi (pinned header ~120→158px çıkınca): `.hesabim-aside`,
    `.pd-gallery` `top:120→158px`; `.auth-sarma` `min-height:calc(100vh-110→158px)`.

**Doğrulama:** `/` 200; vitrin/ürün kartı render YOK; `.stats__grid` + `.review-card`
+ "Bayilerimiz ne diyor?" + "Kategorilere göz atın" VAR; catbar `▾` sayısı 0; servis
edinin `teksil.css`'inde `header{position:sticky}`, `justify-content:space-evenly`,
`.reviews__grid`, `.stats__grid`, `top:158px`, `calc(100vh - 158px)` hepsi VAR.
Render + 4 dokunulan dosya FFFD=0; PHP lint temiz (3 dosya); sunucu logu temiz.
(Not: `Urun_model::mg_vitrin` model metodu duruyor, yalnızca anasayfada çağrılmıyor;
admin ürün "vitrin" onay kutusu ayrı bir DB kolonudur, alakasız.)

**[!] Canlıya taşı:** `views/magaza/anasayfa.php` + `controllers/Anasayfa.php` +
`views/magaza/layout/header.php` + `assets/magaza/css/teksil.css` FTP.

---

## 2026-08-12 — B2B akışı uçtan uca doğrulama + kargo eşiği typo düzeltmesi

B2B toptan akışının tamamı (bayi kayıt → admin onay → sepet → sipariş → fatura)
gerçek HTTP + DB-yazan testle doğrulandı. Tek PHP test betiği (geçici, proje ağacı
dışında `AppData/Local/Temp/e2e_b2b.php`) curl ile gerçek rotaları sürdü (CSRF
cookie-jar'dan okundu, çift oturum: bayi + admin), her adımda mysqli ile DB assert
etti. **53/53 assertion PASS.**

**Bug düzeltmesi:** `application/models/Siparis_model.php:50` `ucretsiz_kargo_esig`
yazılıydı (typo) — DB'de ve diğer tüm dosyalarda (Sepet, Odeme, Ayarlar controller/
view, urun/detay) anahtar `ucretsiz_kargo_esik`. Sonuç: sipariş modeli yapılandırılan
ücretsiz-kargo eşiğini yok sayıp her zaman default (2000) kullanıyordu. Eşik 2000
olduğu için maskelendi; ama admin panelde eşiği değiştiren (ör. 5000) biri checkout'ta
gösterilen kargo ücretiyle siparişe yazılan kargo ücretinin ayrışmasını yaşardı
(gösterim 5000 eşiği, siparis 2000 default ile). `esig` → `esik` düzeltildi.

**Doğrulanan akış (hepsi PASS):**
- **Kayıt:** `bayiler` satırı durum=1 (demo auto-onay), grup_id=1, para_birimi=TRY,
  şifre bcrypt (`$2y$…60`) + password_verify; kayıt sonrası otomatik giriş.
- **Onay kapısı:** durum=0 girşi VE `/hesabim` erişimini engelliyor; admin UI onayı
  (`yonetim/bayiler/durum_guncelle/{id}` durum=1) + audit log; onay sonrası giriş açık.
- **Grup:** admin `grup_guncelle` ile VIP (id=2, %5).
- **Sepet:** MOQ bump (adet 1 → 6), fiyat basamağı (global ≥50 → %5) + VIP grup
  (%5) = %10 → birim 79,90×0,90 = 71,91 / ara 3.883,14 render doğru.
- **Sipariş (transaction):** `SP-YYYY-NNNNNN` no; ara 3883,14 / işlem 50 (kapıda) /
  kargo 0 (>2000 ücretsiz) / toplam 3933,14; para_birimi TRY kur 1, durum
  onay_bekliyor; detay birim 71,91 adet 54 varyant "Siyah / S" kdv 20, urun_adi
  UTF-8 bozulmamış ("Süprem V Yaka Body"); varyant stok 248 → 194; stok_hareketleri
  tip=satis adet=-54 onceki=248; durum geçmişi taraf=sistem; sepet boşaltıldı.
- **Fatura:** `Efatura::olustur` entegratörsüz graceful (durum=bekliyor), fatura_no
  `FT-SP-…`, matrah 3235,95 / kdv 647,19 / toplam 3933,14, uuid 36-karakter; aynı
  siparişe 2. fatura mükerrer korumasıyla engellendi; detay render 200.

**Ek notlar (koddan değil, test sırasında tespit):**
- `bayi_grup_fiyatlari` tablosu YOK (workflow.md §6'da anılsa da) — grup indirimi
  yalnızca `bayi_gruplari.indirim_yuzde`. Test buna göre koşuldu; akış çalışıyor.
- `varsayilan_kargo_ucreti` ayarlarda yok → eşik altında kargo 0 (default). Demo
  için sorun değil; istenirse Ayarlar'a alan eklenip seed'lenmeli.
- `yetkiler` tablosu yok; `Auth_admin::yetki()` şu an tüm giriş yapmış adminlere
  TRUE dönüyor (rol 1 = süper; Faz 5'te yetki matrisi dolacak).

**Doğrulama:** test betiği 53/53 PASS; betik kendi test verisini (bayi/sepet/sipariş/
detay/hareket/geçmiş/fatura/audit) temizledi + varyant #1 stoğunu 248'e geri yükledi.
Bağımsız baseline: `bayiler` yalnız id 1-2, varyant #1 stok 248, test artığı satır 0.
`Siparis_model.php` lint temiz, FFFD=0; sunucu logu tüm test boyunca temiz.

**[!] Canlıya taşı:** `application/models/Siparis_model.php` (kargo eşiği typo
düzeltmesi) FTP. (B2B akış doğrulaması kod değişikliği değil — yalnızca test.)

---

## 2026-08-11 (ek) — teksil.css + DESIGN.md kurtarma (OneDrive hasarı devamı)

08-11 kurtarmasının ardından `assets/` de denetlendi: **`assets/magaza/css/teksil.css`**
538 bayta truncate (yalnızca `:root` token başı kalmış, tüm bileşen stilleri gitmiş →
storefront stylesiz "garip" görünüm). `teksil.js` (11650b) ve `admin.css` (9806b) TAM.
`DESIGN.md` da kayıp. teksil.css **workflow.md §3 tasarım sisteminden + storefront view
sınıflarından/yapısından yeniden kuruldu** (~440 satır: 3-tier header + mega menü, slider,
catgrid, prodcard, katalog filtre, sepet, odeme, hesabim, auth, footer, responsive;
DESIGN.md MongoDB paleti birebir).

**Not:** bu bir REKONSTRÜKSİYON (kayıp orijinalin birebir kopyası değil). Orijinali geri
isteyen için OneDrive "Sürüm geçmişi"nden eski teksil.css geri yüklenebilir.

**Doğrulama:** teksil.css 24KB, HTTP 200 / 0.002s, FFFD=0; storefront + katalog render
(CSS link + layout sınıfları dolu). admin.css zaten tam → admin panel style'ı etkilenmedi.

**[!] Canlıya taşı:** `assets/magaza/css/teksil.css` FTP. (Birebirlik için orijinali
OneDrive sürüm geçmişinden alıp onu koymak tercih edilir.)

---

## 2026-08-11 — Footer linkleri çalışır hale getirildi (CMS sayfa route + goster)

Footer'daki 7 link 404 veriyordu: `/xml-feed`, `/toptan-sartlari`, `/iletisim`,
`/sayfa/hakkimizda|mesafeli-satis|iade-degisim|gizlilik`. Kök neden: `sayfalar`
tablosu bu sayfaların içeriğini (baslik + icerik) içeriyordu ama **slug ile CMS sayfası
servis eden controller metodu/route yoktu** (Sayfa controller'da yalnızca yardim/blog/
favorilerim/siparis_takip vardı).

**Çözüm:**
- `controllers/Sayfa.php` + `goster($slug)`: `sayfalar` tablosundan slug+durum=1 ile sayfayı
  yükler, yoksa `show_404()`; `magaza/sayfa/sayfa.php` view'ına render (baslik + icerik +
  seo_title/seo_description meta).
- `config/routes.php` + 4 route: `sayfa/(:any) → sayfa/goster/$1`, `iletisim`,
  `toptan-sartlari`, `xml-feed` (hepsi ilgili slug ile sayfa/goster'a map).

**Doğrulama:** footer'daki 15 site_url linkinin tamamı 200 (kategoriler 5 + bayi
kayıt/giriş + sipariş-takip zaten çalışıyordu; 7 CMS linki düzeltildi). CMS sayfa içerik
render ediyor (ör. hakkimizda → başlık + icerik); olmayan slug → 404. Lint temiz, FFFD=0.

**[!] Canlıya taşı:** `controllers/Sayfa.php` + `config/routes.php` FTP. (Veri tarafı
`seed_sayfalar_footer.sql` zaten uygulanmıştı — canlı DB'de sayfalar tablosu dolu olmalı.)

---

## 2026-08-11 — Slider 800px + 20 yeni ürün + admin dashboard chart'ları

Üç istek (aynı turda):

**(1) Slider 800px:** `teksil.css` `.slide` `min-height:440px` → **800px** (masaüstü hero
bandı; mobil responsive korundu).

**(2) 20 yeni ürün (+240 varyant):** UTF-8 güvenli seed (kaynak tamamen ASCII; Türkçe
byte-escape — `ş=\xC5\x9F`, `ı=\xC4\xB1`, `ö=\xC3\xB6` vb.; renkler ASCII). 8 → **28 ürün**
(Tişört/Gömlek/Bluz/Sweatshirt/Hırka/Etek/Pantolon/Eşofman/Elbise/Mont/Trençkot), her
ürüne 3 renk × 4 beden varyant (stoklu, sku'lu); 19 vitrin + 16 çok-satan (satis_adet'li).
Türkçe UTF-8 DB'de doğru (FFFD yok — "Tişört", "Gömlek", "Volanlı", "Kapşönlü"). Storefront
vitrin + katalog yeni ürünleri gösteriyor.

**(3) Admin dashboard chart'ları:** `Dashboard_model`'e 4 metot (`siparis_trendi(14)`,
`durum_dagilim`, `cok_satanlar(6)`, `kategori_dagilim`); `Dashboard` controller + view'a
**Chart.js 4.4 (CDN)** ile 4 grafik: Sipariş Trendi (bar+line çift eksen, tam genişlik),
Sipariş Durumu (doughnut, durum_etiket ile), En Çok Satanlar (yatay bar), Kategori Dağılımı
(bar). KPI kartları (4) + son siparişler/bayiler/kritik stok tabloları korundu. `admin.css`'e
chart grid stilleri (`.adm-charts` 3 kol → responsive).

**Doğrulama:** slider `.slide{min-height:800px}` servis; storefront + dashboard 200 / 0 hata /
0 warning; 4 chart canvas + JSON veri dashboard'da (çok-satan 520/340/260…, kategori
6/5/3/3…). Lint temiz (3 PHP), FFFD=0. DB: 28 urun / 368 varyant / 19 vitrin.

**[!] Canlıya taşı:** `teksil.css` (slider) + `admin.css` (chart grid) FTP; DB seed (20 urun
+ varyant) — UTF-8 SQL/PHP ile prod DB'ye; `Dashboard_model.php` + `Dashboard.php` +
`views/yonetim/dashboard/index.php` FTP. Chart.js CDN bağımlılığı (offline admin'de grafik
boş — KPI'lar/tablonun çalışmasına etki yok).

---

## 2026-08-11 — Admin sidebar tamamlama + Stok view'ları (next step)

Admin sidebar menüde yalnızca **6 modül** (Dashboard/Siparişler/Ürünler/Kategoriler/Bayiler/
Ayarlar) vardı ama **13 admin controller** mevcuttu — 7 modül (Stok, Faturalar, Pazaryeri,
Feed, Raporlar, Bannerlar, Para Birimi) URL'yle ulaşılamıyordu. Admin panelini tam gezilebilir
yaptım.

**Yapılanlar:**
- `core/MY_Controller.php` menüsü 6 → **13 öğe**: +Stok, +Faturalar, +Pazaryeri, +API/Feed,
  +Raporlar, +Bannerlar, +Para Birimi (her biri ikonlu, mantıklı sırada: operasyon→katalog→
  entegrasyon→rapor→ayar).
- **Stok modülünün view'ları kayıp** idi (`views/yonetim/stok/` yok — errors/ gibi OneDrive
  hasarı) → `duzeltle` 500 veriyordu. View'ları yeniden kurdum:
  - `views/yonetim/stok/index.php`: varyant stok listesi (arama + kritik/sıfır filtresi + sayfalama)
    + satır içi manuel stok düzeltme formu (yeni_stok + sebep → stok/duzeltle/{id}, CSRF'li).
  - `views/yonetim/stok/hareketler.php`: stok hareket geçmişi (tip filtresi + arama + sayfalama).
- `controllers/yonetim/Para_birimi.php` `menu_aktif` 'ayarlar' → **'para_birimi'** (kendi menü
  öğesinde highlight).

**Doğrulama:** 13 admin modülün tamamı 200 / 0 hata (stok + stok/hareketler dahil); sidebar 13
öğe. **Stok manuel düzeltme uçtan uca**: varyant #1 248→253 (POST 303), `stok_hareketleri`
tip=duzeltme adet=5 onceki=248 loglandı; temizlik (248 geri, test harekets silindi). Lint
temiz, FFFD=0.

**[!] Canlıya taşı:** `core/MY_Controller.php` + `controllers/yonetim/Para_birimi.php` +
`views/yonetim/stok/{index,hareketler}.php` FTP.

---
## 2026-08-11 — Sayfalar admin CRUD (next step: CMS içerik yönetimi)

Footer/kurumsal sayfalar (hakkımızda, mesafeli satış, iade, gizlilik, iletişim, toptan
şartlar, xml-feed, çerez) DB'de duruyordu ama **admin düzenleme UI'si yoktu** — yalnızca
SQL ile değiştirilebiliyordu. Tam CMS CRUD modülü kuruldu.

**Yeni:**
- `models/Sayfa_model.php` — `liste(q)` / `getir(id)` / `kaydet(d)` / `guncelle(id,d)` /
  `durum(id,d)` / `sil(id)` + slug benzersizlık (`_slug_benzersiz`).
- `controllers/yonetim/Sayfalar.php` — `index` (liste+arama), `ekle`/`duzenle` (form),
  `kaydet` (insert/update), `sil(id=NULL)`; `yetki_gerek('sayfalar',...)` + `auth_admin->audit`
  + CSRF + flash + PRG redirect. `duzenle` ve `sil` bare-URL guard'lı.
- `views/yonetim/sayfalar/index.php` — liste tablosu (başlık / slug / durum-yayında↗ / içerik
  krk sayısı / düzenle-sil) + arama.
- `views/yonetim/sayfalar/form.php` — form (başlık, slug, **HTML içerik textarea**, SEO
  başlık/açıklama, durum yayında/taslak).

**Değişen:** `core/MY_Controller.php` menüye **"Sayfalar"** eklendi (Bannerlar'dan sonra;
menü 13 → 14 öğe).

**Doğrulama:** liste 200 (8 mevcut sayfa), ekle formu 200. **Tam CRUD uçtan uca**: test sayfası
oluşturuldu (POST 303 → id=9), **storefront'ta render** (`/sayfa/test-sayfa-cms` → başlık +
HTML `<p>` içerik doğru, `Sayfa::goster` üzerinden), silindi (307 → 0 kaldı). Sidebar 14 öğe.
Lint temiz, FFFD=0. Test verisi temizlendi.

**İçerik notu:** `icerik` admin HTML (güvenilir kaynak) — `magaza/sayfa/sayfa.php` escape'siz
render eder (bilinçli). Form'da monospace HTML textarea; WYSIWYG (TinyMCE/CDN) ileride.

**[!] Canlıya taşı:** `models/Sayfa_model.php` + `controllers/yonetim/Sayfalar.php` +
`views/yonetim/sayfalar/{index,form}.php` + `core/MY_Controller.php` FTP.

---
## 2026-08-11 — Storefront akış view'ları kurtarma (sepet/odeme/hesabim boştu) + urun detay CSS

Storefront fonksiyonel akışları HTTP üzerinden test edilince **5 kritik view'ın boş/truncate**
olduğu ortaya çıktı (OneDrive hasarı — stok/errors gibi; yalnızca `defined()` satırı kalmış,
gövde yok): **`sepet/index.php`**, **`odeme/index.php`**, **`hesabim/{dashboard,siparisler,
siparis_detay}.php`**. Sepet içeriği / checkout formu / bayi hesap sayfaları render olmuyordu.
Hepsi yeniden kuruldu.

**Yeniden kurulan view'lar:**
- `sepet/index.php` — sepet tablosu (görsel+ürün+varyant, adet güncelle formu, birim/ara tutar,
  sil), özet kartı (ara toplam, ücretsiz kargo eşiği uyarısı, ödemeye geç).
- `odeme/index.php` — checkout formu (teslimat/fatura/ödeme/kargo alanları, fatura-ayni toggle,
  ödeme yöntemi radyo + banka hesapları, sözleşme) → `odeme/tamamla`; sipariş özeti yan panel.
- `hesabim/dashboard.php` — hesap özeti (toplam/aktif sipariş + grup indirim KPI), son siparişler.
- `hesabim/siparisler.php` — bayi sipariş listesi (para_birimi+durum rozeti, detay linki).
- `hesabim/siparis_detay.php` — sipariş detayı (kalemler, özet para_formatla+birimi+kur, teslimat).
- (`hesabim/_menu.php` ve `bilgiler.php`/`sifre.php` sağlam; ona göre `_menu` + `hesabim-grid`
  deseniyle uyumlu kuruldu.)

**Ek CSS** (`teksil.css`): urun/detay view'ı `pd-*` sınıfları kullanıyordu ama reconstrükte CSS
`urun-*` varsaymıştı → ürün detay stylesız. 45 `pd-*` tanımı eklendi (pd-grid/gallery/info/
fiyat/opt/stepper/basamak/toplam/sepet/deger/tabs + renk-sw, prose, .b link, hesabim-menu
`.aktif`). Responsive (≤900px tek kolon).

**Doğrulama:** sepet artık içerik render (tablo-sepet/sepet-ozet + ürün satırı); odeme formu
(alanlar dolu); **tam uçtan-uca bayi akışı**: test bayisi (USD) login → sepet → checkout
(POST 303 → basarili) → **sipariş oluştu (SP-..0041, para_birimi=USD kur=32.5)** → hesabim
dashboard/siparisler/siparis_detay render. Misafir akışı (sepet/ekle JSON ok, arama, favori,
bayi kayıt auto-login) de çalıştı. Tüm kritik sayfalar 200 / 0 hata. Boş view kalmadı. Lint
temiz, FFFD=0. Test verisi temizlendi (sipariş+detay+geçmiş+hareket+test bayi, stok geri).

**[!] Canlıya taşı:** `views/magaza/{sepet/index,odeme/index,hesabim/{dashboard,siparisler,
siparis_detay}}.php` + `assets/magaza/css/teksil.css` (pd-*) FTP.

---
## 2026-08-11 — Ürüne özel fiyat basamağı (adet indirimi) yönetimi + bütünlük taraması

**(0) Bütünlük taraması (JS/SQL/router):** OneDrive hasarı PHP+CSS+config'e odaklanmıştım; kalan
dosyaları taradım — **teksil.js** (94/94 denge, temiz IIFE), **tüm sql/\*.sql** (tam), **index.php**
(10255b)/**router.php**/**.htaccess** sağlam, 0-byte dosya yok. Ek corruption yok — kurtarma fazı
bitti, storefront JS↔ürün detay uyumlu (ikisi de orijinal).

**(1) Fiyat basamağı yönetimi (B2B core gap):** `fiyat_basamaklari` tablosu + ürün detayda gösterimi
(`mg_basamaklar`) + sepet fiyatlaması (`Sepet_model::_birim_fiyat`) vardı ama **ürüne özel basamak
yazan kod yoktu** — yalnızca 2 global kural (50+ %5, 100+ %10) seed edilmişti. Admin ürüne özel
adet indirimi giremiyordu.

**Eklendi:**
- `Urun_model::mg_basamak_kaydet($urun_id, $rows)` — ürün-özel basamakları sil + yeniden ekle
  (min<1 veya %≤0 satırlar atlanır). `mg_admin_getir` artık `$u->basamaklar` (ürün-özel) ekler.
- `Urunler::kaydet` → `mg_basamak_kaydet($id, POST['basamak'])`.
- `views/yonetim/urunler/form.php` — "Fiyat Basamağı (adet indirimi)" kartı (dinamik min_adet +
  indirim_yuzde satırları, +/✕ JS; varyant deseniyle aynı).

**Doğrulama:** form basamak bölümü render (basamakListe/Ekle/[]). **Model testi (CLI)**: 4 girişte
2 geçerli kaydedildi, 2 geçersiz atlandı, **varyantlara dokunulmadı** (16→16), `mg_basamaklar` 4
(2 ürün + 2 global) → GECTI. **Storefront**: ürün sayfası basamakları `pd-basamak-item` ile
gösteriyor. Sepet fiyatlaması basamakları zaten uyguluyordu (şimdi admin girebiliyor). Lint temiz,
FFFD=0 (form.php yorumlarındaki 2 mojibake bayt-düzeltildi). Test verisi + temp controller temizlendi.

**[!] Canlıya taşı:** `models/Urun_model.php` + `controllers/yonetim/Urunler.php` +
`views/yonetim/urunler/form.php` FTP.

---
## 2026-08-11 — Marka (brand) admin CRUD (next step: katalog varlığı yönetimi)

`markalar` tablosu (id/ad/slug/logo/durum) + ürün formu marka select'i vardı ama **Marka yönetim
modülü yoktu** — yalnızca 1 marka (TekstilSite) seed edilmişti, admin marka ekleyemiyordu. Tam CRUD
modülü kuruldu (Sayfalar/Kategoriler deseninin aynısı).

**Yeni:**
- `models/Marka_model.php` — `liste(q)` / `getir(id)` / `kaydet(d)` / `guncelle(id,d)` /
  `sil_kontrol(id)` (ürünü varsa FALSE — orphan önlemi) / `sil(id)` + slug benzersizlık.
- `controllers/yonetim/Markalar.php` — `index` (liste+arama), `ekle`/`duzenle` (form), `kaydet`
  (insert/update), `sil(id)` (sil_kontrol ile); `yetki_gerek` + `audit` + CSRF + flash + PRG.
- `views/yonetim/markalar/index.php` — liste (ad / slug / logo thumb / durum / düzenle-sil) + arama.
- `views/yonetim/markalar/form.php` — form (ad, slug, **logo URL/ uploads yolu** + canlı önizleme,
  durum aktif/pasif).

**Değişen:** `core/MY_Controller.php` menüye **"Markalar"** eklendi (Kategoriler'den sonra, katalog
varlıkları grubu; menü 14 → 15 öğe).

**Doğrulama:** liste 200 (marka satırı), ekle formu 200. **Tam CRUD**: test markası oluşturuldu
(POST 303 → id), **ürün formu marka select'i yeni markayı gösteriyor** (TekstilSite + Deneme Marka
göründü — entegrasyon sağlam; Urunler $markalar `durum=1` yükler), `sil_kontrol` ürünü olmayanı
sildi (307 → 0 kaldı). Sidebar 15 öğe. Lint temiz, FFFD=0. Test verisi temizlendi.

**[!] Canlıya taşı:** `models/Marka_model.php` + `controllers/yonetim/Markalar.php` +
`views/yonetim/markalar/{index,form}.php` + `core/MY_Controller.php` FTP.

---
## 2026-08-11 — Kupon / kampanya kodları (checkout indirimi — B2B core feature)

`mg_olustur` "kupon ileride" notuyla boş bırakılmış `$indirim_try` + `siparisler.indirim`
kolonu zaten hazırdı → kupon sistemi temiz entegre edildi. Yeni B2B özelliği: yüzde/sabit
indirimli kampanya kodları, min. sepet/limit/geçerlilik/kullanım limiti ile.

**Yeni:**
- `sql/migrate_kuponlar.sql` + `sql/schema.sql` — `kuponlar` tablosu (kod UNI / aciklama /
  tip:yuzde|sabit / deger / min_sepet_tutar / max_indirim / baslangic+bitis_zaman /
  kullanim_limiti / kullanim_sayisi / durum). (Dev DB + her iki SQL dosyasına uygulandı.)
- `models/Kupon_model.php` — `liste/getir/getir_kod` + **`dogrula($kod,$ara_toplam_try)`**
  (geçerlilik: zaman/limit/min-sepet; indirim: yüzde/sabit + max cap) + `kaydet/guncelle/sil` +
  `kullan_artir` + kod normalizasyon (BÜYÜK + [A-Z0-9_-]).
- `controllers/yonetim/Kuponlar.php` + `views/yonetim/kuponlar/{index,form}.php` — admin CRUD
  (kod/tip/değer/min/max/zaman aralığı/limit/durum; liste: geçerlilik + kullanım rozetleri).

**Değişen:**
- `models/Siparis_model.php` `mg_olustur`: satır 62 `$indirim_try = 0.0` → `_kupon_indirim()`
  (session kupon → TRY indirimi); commit sonrası `kullan_artir` (indirim>0 ise) + session
  temizliği. Yeni `_kupon_indirim()` metodu.
- `controllers/Odeme.php`: `kupon_uygula()` (POST kod → dogrula → session, PRG) +
  `kupon_kaldir()`; `index()` uygulanan kupon + TRY indirimini view'a aktarır.
- `views/magaza/odeme/index.php`: özet panelinde kupon UI (kod girişi + Uygula, veya uygulanan
  kupon + indirim satırı + Kaldır).
- `core/MY_Controller.php` menüye **"Kuponlar"** (Sayfalar'dan sonra; menü 15 → 16).

**Doğrulama (kritik fiyatlandırma):** admin kupon oluşturdu (YAZTEST2026 %20); test bayisi
(TRY) sepete ekledi (ara 479.40) → kupon uyguladı → ödeme özetinde `-95,88 ₺` gösterildi →
checkout tamamla → **sipariş SP-..0042: ara=479.40, indirim=95.88 (tam %20), toplam=383.52**
(ara−indirim ✓); **kupon kullanım sayacı 1'e çıktı**, session temizlendi. Admin liste/ekle 200.
Lint temiz, FFFD=0. Test verisi (sipariş+detay+geçmiş+hareket, stok geri, test bayi, kupon)
temizlendi.

**[!] Canlıya taşı:** `sql/migrate_kuponlar.sql` (mevcut DB) + `sql/schema.sql` (taze) +
`models/{Kupon,Siparis}_model.php` + `controllers/{Odeme,yonetim/Kuponlar}.php` +
`views/{yonetim/kuponlar,magaza/odeme}/*` + `core/MY_Controller.php` FTP.

---
## 2026-08-11 — Büyük veri kurtarma: 2026-08-10 OneDrive sync hasarı (application/ + system/ + config)

2026-08-10'da OneDrive sync bir dizi dosyayı sessizce bozup truncate etti / yanlış
ic-ice yerlestirdi. Uygulama 08-10'dan beri tamamen calismaz halde idi (config +
system cekirdegi gidince MY_Controller yuklenemiyor, her rota fatal). Hasarın tum
kapsamı tespit edildi ve geri yuklendi; tum rotalar + checkout + feed uc-tan-uca
dogrulandi. (Hafiza'ya islendi: [[onedrive-sync-genis-dosya-hasari]].)

**Geri yuklenen hasar (13 nokta):**
1. **system/ (CI3 3.1.13 cekirdek)** — 206 dosya yanlıs yere `system/system/` icine
   nested olmus, ust `system/{core,database,...}` bos. Duzlestirildi (cp -r merge);
   CodeIgniter/Model/Controller vb. yerinde (CI_VERSION 3.1.13).
2. **config/config.php** — 79 satıra truncate (yalnızca base_url/index_page kalmıs).
   encryption_key, csrf_* (teksil_csrf / teksil_csrf_cookie / regenerate=FALSE /
   exclude paytr/bildirim), sess_* (files + C:/xampp/tmp), log_*, subclass_prefix='MY'
   geri yuklendi. (subclass_prefix eksikligi MY_Controller'i yukleyemiyor -> fatal.)
3. **config/autoload.php** — helper='' + libraries='' reset; `array('url','form','text',
   'teksil')` + `array('session')` geri yuklendi (session autoload zorunlu).
4. **config/routes.php** — 85. satirda truncate; Sayfa (yardim/blog/favorilerim/
   siparis-takip/favoriler_*), feed/urunler, paytr/bildirim, robots.txt rotalari eklendi.
5. **views/errors/{html}/** — bos; stock CI3 hata sablonları (error_php/general/404/
   db/exception) geri yuklendi.
6. **models/Siparis_model.php** — 17 satira truncate (mg_olustur govdesiz). Tum model
   (mg_olustur + mg_getir + mg_admin_getir + mg_admin_liste[_say] + mg_kargo_guncelle
   + mg_durum_guncelle + _stok_iade_et) baglamdan yeniden kuruldu.
7. **models/Kategori_model.php** — mg_menu sonrası kesik; 10 metod kayip. Yeniden
   kuruldu.
8. **controllers/Anasayfa.php** — index() govdesiz + _demo_vitrin kayip; geri yuklendi.
9. **controllers/yonetim/Urunler.php** — kaydet govdesi + durum/sil/gorsel_sil/
   gorsel_ana + coklu gorsel yukleme yardimcisi kayip; yeniden kuruldu.
10. **controllers/Katalog.php + Urun.php** — render yollari 'magaza/' onekini
    kaybetmisti ('katalog/index', 'urun/detay'); duzeltildi.
11. **models/Urun_model.php** — feed_liste() kayip; mg_sil hard-delete'e donmus
    (soft-delete olmali); _admin_filtre deleted_at filtresi kayip. Uc nokta geri yuklendi.
12. **helpers/teksil_helper.php** — para birimi helper seti (para_birimleri_liste /
    kur_getir / aktif_para_birimi / para_cevir / para_goster / para_formatla) tamamen
    kayip; yeniden eklendi.
13. **views/yonetim/para_birimi/index.php** — satır 22 `e($b->kod` eksik parantez -> fatal; duzeltildi.

**Dogrulama:** tum storefront + admin rota 200 / 0 fatal / 0 warning. **Checkout
uc-tan-uca** (CLI harness, USD test bayisi): siparis olustu, para_birimi=USD kur=32.5
ara/toplam=29.52 (958.80 TRY ÷ 32.5), varyant_id snapshot, stok 248->236, satis hareketi,
durum gecmisi, sepet bosalma; **iptal -> stok iade** 236->248 + iade hareketi +
**cift-iade korumasi**. **Feed** XML+JSON 8 urun / 136 varyant (Turkce UTF-8 bozulmamis),
kullanim sayaci. Lint temiz (application/ + config), FFFD=0. Test verisi temizlendi.

**[!] Canlıya taşı:** local calısma kopyası (su an dogrulanmıs) FTP ile canlıya
konulmalı — canlı sunucuda OneDrive yok, bu hasar local-dev'e ozgu. encryption_key
yenı uretıldı (prod'da mevcut sifreli blob yoktu: 0 kayit).

---

## 2026-08-09 — Admin denetimi: iptal/iadede stok geri-ekleme + ürün soft-delete

(Ek not 08-11: bu iş 08-09'da yapıldı ama 08-10 OneDrive hasarında Siparis_model +
Urun_model ile birlikte kayboldu; 08-11 kurtarmasında yeniden kurulup doğrulandı.
`sql/migrate_2026_08_09.sql` sağ kalan artifakt.)

**Iki denetim bulgusu (admin audit):**
1. **İptal/iade'de stok geri-ekleme:** sipariş detay snapshot'ına varyant_id
   eklenmemişti → iptal/iadede varyant stoğu geri eklenemiyordu. Düzeltme:
   `siparis_detaylari.varyant_id` kolonu (migrate_2026_08_09.sql); `Siparis_model::
   mg_olustur` varyant_id kaydeder, `mg_durum_guncelle` iptal/iade'de `_stok_iade_et`
   çağırır (çift-iade korumalı: yalnızca ilk geçişte ekler).
2. **Ürün soft-delete (workflow §2):** `mg_sil` hard-delete yapıyordu (varyant/görsel/
   stok_hareketleri CASCADE siliniyordu). Düzeltme: `urunler.deleted_at` kolonu;
   `mg_sil` artık `deleted_at=NOW() + durum=0`; admin/storefront sorguları
   `deleted_at IS NULL` ile filtreler.

**Doğrulama (08-11):** USD test bayisi ile sipariş oluşturuldu (stok düştü), iptal
edildi (stok tam geri yüklendi, çift-iptal stok tekrar eklemedi); admin ürün listesi
deleted_at filtreli (silinen ürün görünmüyor). Test verisi temizlendi.

**[!] Canlıya taşı:** `migrate_2026_08_09.sql` prod DB (local'e zaten uygulandı) +
08-11 kurtarma dosyaları FTP.

---

## 2026-08-08 — Storefront: catbar cilası + arama ortalandı + hero → DB slider (bannerlar)

**Catbar:** gereksiz yatay kaydırıcı (`overflow-x:auto`) kaldırıldı; `justify-content:
space-between` (kategori öğeleri çubuğa yayıldı) + dikey ortalanmış metin. **Arama**
navbar'da daha ortada (`flex:0 1 560px; margin:0 auto`; markanın hemen sağı yerine).
**Hero → büyük slider:** statik `.hero` bandı kaldırıldı; `bannerlar` tablosundan DB tabanlı
slider (3 demo slayt — `sql/seed_slider.sql`): otomatik döndürme (5 sn) + oklar + noktalar +
metin konumu (sol/orta/sağ) + responsive. **Değişen:** `anasayfa.php` (hero → slider + inline
JS), `teksil.css` (`.slider/.slide/.slider__dot/arrow` + catbar + `header-search`),
`sql/seed_slider.sql` (yeni).

**Doğrulama:** anasayfa render 200; 3 slayt + nokta/ok + JS track; eski hero kaldırıldı;
banner başlık doğru UTF-8 ("kadın" → ı=C4B1); mojibake 0; lint temiz. Catbar overflow kalktı +
space-between; header-search margin:auto.

**[!] Canlıya taşı:** `anasayfa.php` + `teksil.css` FTP; prod DB'de `seed_slider.sql` (demo
bannerlar — gerçek görseller SQL/admin ile). Banner admin CRUD ileride.

---

## 2026-08-08 — Storefront: utility-bar 404 fix (Sayfa) + header 3-tier (utility / navbar / kategori)

**(1) Utility-bar linkleri 404 veriyordu — yeni `Sayfa` controller + sayfalar:** `yardim`
(SSS + iletişim), `siparis_takip` (misafir — sipariş no + e-posta ile bulma), `favorilerim`
(session tabanlı wishlist — ekle/sil/liste), `blog` (stub). Routes: `yardim` / `blog` /
`favorilerim` / `siparis-takip` + `favoriler/(ekle|sil)/(:num)`. Ürün detayına "♡ Favorilere
Ekle" butonu. Footer'daki `siparis-takip` linki de artık çalışıyor.

**(2) Header 3-tier:** `utility-bar` (EN ÜST) → `site-header` (navbar — logo, arama, hesap,
sepet) → `catbar` (kategori menüsü, navbarın altında). Arama navbar'da flex (560px).
`header-main` (navbar + catbar) sticky; utility-bar kayıp gider.

**Doğrulama:** 4 sayfa 200 (404 yok); favorilerim ekle→1 kart, sil→boş + "Henüz favori yok";
ürün detayında favori butonu; sipariş takibi doğru (no+email)→bulundu + tablo, yanlış email→
"bulunamadı" (gizlilik); header sırası utility-bar < site-header < catbar. Lint+UTF-8 temiz.
Test verisi temizlendi.

**[!] Canlıya taşı:** `controllers/Sayfa.php` + `views/magaza/sayfa/*` + `layout/header.php` +
`teksil.css` + `routes.php` + `urun/detay.php` FTP.

---

## 2026-08-08 — Storefront UI: daha geniş container + top-bar (navbar altında)

**Container:** `--container: 1280px` → **1600px** (daha geniş görsel alan); arama kutusu
240→320px. **Header yeniden düzenlendi:** `.site-header` (navbar — logo, kategori menüsü,
arama, hesap, sepet) ilk/üstte (sticky); `.utility-bar` (telefon, Toptan/B2B, **Favoriler**,
Sipariş Takibi, Yardım, Blog) artık navbarın ALTINDA — Favoriler navbar'dan top-bar'a taşındı,
tekrar "Bayi Ol/Kayıt" kaldırıldı (navbar'daki hesap linkiyle çakışma). **Değişen:**
`assets/magaza/css/teksil.css` (container + search width), `views/magaza/layout/header.php`
(DOM sırası + Favoriler taşıma).

**Doğrulama:** render 200, fatal yok; site-header utility-bar'dan önce, Favoriler top-bar'da
("♡ Favoriler"), navbar'da sepet + hesap kaldı; FFFD=0, lint temiz.

**[!] Canlıya taşı:** `teksil.css` + `header.php` FTP.

---

## 2026-08-08 — Çoklu para birimi (sipariş anlık kopyası + kur + bayi bölgesi)

Bayi kendi para birimini seçer (Hesabım › Bilgilerim); sipariş o para birimi + anlık kur
ile snapshot olarak kaydedilir (workflow §6 gereği). Katalog TRY bazlı kalır; sepet/
ödeme/sipariş kayıtları bayinin para biriminde gösterilir.

**Yeni:** `sql/migrate_para_birimi.sql` + `sql/schema.sql` (`para_birimleri` tablosu:
kod/ad/sembol/kur_try(1 birim=N TRY)/durum/sira), `controllers/yonetim/Para_birimi.php`
(kur CRUD), `views/yonetim/para_birimi/index.php`. **Helper** (`teksil_helper`):
`para_birimleri_liste/kur_getir/aktif_para_birimi/para_cevir/para_goster/para_formatla`.
**Değişen:** `Siparis_model::mg_olustur` (bayi para_birimi+kur → tutar+detaylar çevrilir,
snapshot), `Bayi_model::bilgiler_guncelle` (para_birimi whitelist), `Hesap::bilgiler` +
view (para birimi seçici), mağaza view'ları (`sepet/index`, `odeme/index` TRY→`para_goster`;
`odeme/basarili`, `hesabim/{siparis_detay,siparisler,dashboard}` stored→`para_formatla(X,
$s->para_birimi)`), `ayarlar/index`'e para-birimi bağlantısı.

**Bug yakalandı (test sırasında):** `para_birimi/index.php` view'da `e($b->kod)` eksik
parantez (PHP fatal) — düzeltildi.

**Doğrulama:** USD test bayisi → sipariş: **para_birimi=USD, kur=32.5, ara 14.75 / kargo
2.46 / toplam 17.21** (TRY 479.40/79.90/559.30 ÷32.5), detay satırı $2.46×6=$14.75; sipariş
detay sayfası **$14.75 / $17.21 / $2.46** gösterdi, ₺/TL yanlış-etiketleme **0**. Admin
para_birimi render 200. Test verisi temizlendi. Lint+UTF-8 temiz.

**v1 sınırı:** katalog (ürün kartı/detay, JS tier-hesap) TRY bazlı kalır — tek tutarlılık
kırılması sepette (TRY→bayi para birimi). Tam katalog çevrimi + detay-JS ileri safha.

**[!] Canlıya taşı:** `migrate_para_birimi.sql` prod DB + `Para_birimi` controller/view +
helper + Siparis_model + Bayi_model + Hesap + mağaza view'ları FTP.

---

## 2026-08-08 — PayTR kartlı ödeme entegrasyonu (iFrame API, yeni modül)

PayTR iFrame API: get-token (imzalı) → iframe → sunucudan-sunucuya bildirim (hash
doğrulama). **Yeni:** `libraries/Paytr_api.php` (`hazir/get_token/callback_dogrula` —
PayTR hash formülleri birebir; `hash_equals` timing-safe; `SSL_VERIFYPEER`),
`controllers/Paytr.php` (ode/basarili/basarisiz iframe akışı + sahiplik), `controllers/
Paytr_bildirim.php` (callback — CI_Controller, session/CSRF muaf; hash doğrula → sipariş
odendi+onaylandi+geçmiş+bildirim, idempotent, "OK"), `views/magaza/odeme/{paytr,
paytr_basarisiz}.php`. **Değişen:** `Odeme::tamamla` (paytr seçilince paytr/ode yönlendirme),
Ayarlar WHITELIST+toggles (`paytr_*`), Ayarlar view'ına PayTR kartı, `config/routes.php`
(`paytr/bildirim`), `config/config.php` (`csrf_exclude_uris` + `paytr/bildirim`).

**Bug bulundu+düzeltildi (test sırasında):** `callback_dogrula`'da `$beklenen`/`$bekenen`
typo (tanımsız değişken → fatal); ASCII `$expected`'e çevrildi.

**Doğrulama:** get_token hash deterministik; callback **doğru hash → kabul**, **yanlış
hash → "bad hash"** (timing-safe); CSRF muaf (token'sız POST 200); checkout paytr'la →
**303 paytr/ode/{id}**, ode sayfası render (fake anahtarla "token alınamadı" graceful).
Gerçek token/ödeme merchant anahtarlarıyla. Test verisi temizlendi. 8 dosya lint temiz.

**[!] Canlıya taşı:** PayTR library + 2 controller + 2 view + Odeme/Ayarlar/config/routes
FTP. Canlı: Ayarlar → PayTR (merchant_id/key/salt + Bildirim URL = `<site>/paytr/bildirim`)
+ `odeme_yontemleri.paytr` durum=1 (checkout'ta görünür).

---

## 2026-08-08 — Cron otomasyonu (yeni CLI controller)

Periyodik bakım/senkron işleri — CI3 CLI (`php index.php cron <metot>`), web erişimi
`is_cli()` guard'ı ile engelli. **Yeni:** `controllers/Cron.php` — `calis()` (hepsi) +
`terk_sepet($gun)` (eski misafir sepetleri), `pazaryeri_senkron()` (aktif hesaplara stok/
fiyat — Faz 5'i otomatikleştirir), `efatura_durum()` (işlenen faturaların asenkron durum
sorgusu). Hepsi graceful (tablo/veri/kimlik yoksa atlar).

**Doğrulama:** CLI `php index.php cron calis` → 3 iş çalıştı (terk_sepet 0, pazaryeri
"aktif hesap yok", efatura "sorgulanacak fatura yok" — graceful); tek-job parametreli
(`terk_sepet 30`); **web erişim engelli** (`/cron/calis` → "yalnızca CLI", job çalışmaz,
sepet etkilenmedi). Lint temiz, ASCII (mojibake yok).

**[!] Canlıya taşı:** `controllers/Cron.php` FTP. Cron schedule (ops): periyodik
`php index.php cron calis` (Linux crontab / Windows Task Scheduler). Örn saatlik:
`0 * * * * cd /site && php index.php cron calis`.

---

## 2026-08-08 — Admin argüman-zorunlu metodlar batch sağlamlaştırma (9 metod)

Bare URL (ID'siz) isteklerde "Too few arguments" fatal'i yerine sessiz yönlendirme
(duzenle/detay/sepet sağlamlaştırmalarının devamı). 4 dosyada 9 metod:
`yonetim/Bayiler` (detay, durum_guncelle, grup_guncelle), `yonetim/Kategoriler` (sil),
`yonetim/Siparisler` (durum_guncelle), `yonetim/Urunler` (durum, sil, gorsel_sil,
gorsel_ana). Her birine `$id = NULL` + `if (! $id) { redirect('yonetim/...'); }` guard.

**Doğrulama:** 9 bare admin URL'nin hepsi **307** (önceden fatal); geçerli id'ler bölünmedi
(bayiler/detay/1 → 200). 4 dosya lint temiz, guard'lar ASCII (mojibake yok). Kalan 2
argüman-zorunlu metod (`Urun::detay`, `Hesap::siparis_detay`) route-korumalı
(`urun/(:any)`, `hesabim/siparis/(:num)`) → bare URL 404 verir, fatal değil.

**[!] Canlıya taşı:** 4 dosya FTP (Bayiler, Kategoriler, Siparisler, Urunler).

---

## 2026-08-08 — Polish/test turu: sitemap+robots (yeni Seo) + mojibake temizliği + sepet sağlamlaştırma

Genel smoke test (storefront + admin tüm route'lar) + güvenlik/mojibake denetimi.

**(1) sitemap.xml + robots.txt 404 veriyordu — EKSİK SEO (gerçek bug):** routes.php'de
`sitemap\.xml → seo/sitemap` route'u vardı ama `Seo` controller hiç yoktu (Nesem'den kalma,
port edilmemiş). **Yeni:** `controllers/Seo.php` — dinamik `sitemap()` (anasayfa + katalog +
kategori + ürün URL'leri; `application/xml`; 22 URL) + `robots()` (`arama_index` ayarına
duyarlı: kapalı→`Disallow: /`, açık→`/yonetim,/hesabim,/odeme,/sepet,/bayi,/api` engelli +
Sitemap satırı). **Değişen:** `config/routes.php` (`robots\.txt → seo/robots` eklendi).

**(2) api/Feed.php mojibake:** dosyayı iki kez yeniden yazarken line 24 yorumunda "Ürün" →
`Ür` + U+FFFD×2 + `n` bozulmuştu (UTF-8 geçerli ama replacement char; önceki mb_check
yakalamamıştı). Byte-düzeyinde düzeltildi. **Tüm application/ (107 php) mojibake denetimi:
FFFD = 0.**

**(3) Sepet sağlamlaştırma:** `Sepet::sil($sepet_id=NULL)` + `guncelle($sepet_id=NULL)`
guard — bare `/sepet/sil` (bot/probe) fatal yerine 307 yönlendirme. (Admin argüman-zorunlu
metodlar — `Bayiler::detay`, `Kategoriler::sil`, `Urunler::durum/sil/gorsel_*` vb. — login
+ UI-id arkasında, düşük risk; istenirse batch sağlamlaştırma yapılabilir.)

**Denetim sonuçları (temiz):** raw SQL yok (hepsi Query Builder) ✅; dangling route yok
(tüm controller'lar mevcut; kullanılmayan `Welcome.php` hariç) ✅; tüm storefront + admin
route'lar 200/expected, 0 fatal ✅.

**[!] Canlıya taşı:** `controllers/Seo.php` (yeni) + `config/routes.php` +
`controllers/api/Feed.php` + `controllers/Sepet.php` FTP.

---

## 2026-08-08 — Faz 5 (devam): Pazaryeri entegrasyonu (Trendyol, yeni modül)

Pazaryerlerine (Trendyol/Hepsiburada/N11) ürün-stok-fiyat senkronu + sipariş çekme.
Sağlayıcı-bağımsız yapı + Trendyol Partner API reference adapter (sapigw, Basic Auth,
supplierId). Diğer platformlar "bekliyor" atlar; akış bozulmaz.

**Yeni:** `sql/migrate_faz5_pazaryeri.sql` + `sql/schema.sql` (3 tablo:
`pazaryeri_hesaplari` platform/ad/supplier_id + `api_key`/`api_secret` CI Encryption
şifreli + durum/son_sin; `pazaryeri_urun_eslestirme` hesap↔ürün+barkod UNIQUE;
`pazaryeri_loglari` islem/durum/ozet/hata), `models/Pazaryeri_model.php` (encrypt/decrypt
round-trip; hesap/eşleştirme/log CRUD), `libraries/Pazaryeri_api.php`
(`hazir/stok_fiyat_gonder/siparis_cek`; Trendyol PUT products/price-and-inventory +
GET suppliers/{id}/orders; Basic Auth + User-Agent; varyant stok toplu çekme),
`controllers/yonetim/Pazaryeri.php` (index/detay/hesap_kaydet/eslesme/stok_fiyat/
siparis_cek/durum/sil), `views/yonetim/pazaryeri/{index,detay}.php`. **Değişen:**
`core/MY_Controller.php` (menüye "Pazaryeri").

**Güvenlik:** hesap kimlikleri DB'de **CI Encryption ile şifreli** (plaintext YASAK),
yalnızca API çağrısında çözülür; `SSL_VERIFYPEER`; Trendyol `User-Agent` zorunlu.
Graceful: kimlik/eşleştirme yok veya HTTP hatası → log + redirect.

**Doğrulama:** 2 test hesabı — (A) kimliksiz → stok/fiyat `bekliyor "Kimlik/supplier
eksik"`; (B) sahte kimlik + eşleştirme → api_key DB'de **şifreli blob** (plaintext DEĞİL),
sync **`hata "Platform HTTP 403"`** (gerçek Trendyol çağrısı denendi → encrypt→decrypt→
hazir→payload→curl→graceful zinciri kanıtlandı). `son_sin` yalnızca başarılı senkron
işaretler. Eşleştirme + log CRUD çalıştı. Render 200, 6 dosya lint temiz, UTF-8/Türkçe
doğrulandı. Test verisi temizlendi.

**[!] Canlıya taşı:** `migrate_faz5_pazaryeri.sql` prod DB + 6 kod dosyası FTP. Canlı:
admin → hesap (platform=Trendyol, supplierId + API key/secret Trendyol Satıcı Paneli →
Integration Information), ürün eşleştir, "Stok/Fiyat Gönder". Cron (ops): periyodik stok/
fiyat + sipariş çekme zamanlanmalı. Hepsiburada/N11 adapter'ları ileride.

---

## 2026-08-08 — Faz 5 (devam): E-Fatura/E-Arşiv kayıt + entegrasyon katmanı + sağlamlaştırma

**E-Fatura modülü (yeni, sağlayıcı-bağımsız + graceful):** Siparişten e-fatura/e-arşiv
kaydı + asenkron entegratör gönderimi. GİB uyumlu UBL-TR imzasını ENTEGRATÖR üretir;
biz fatura VERİSİNİ (satıcı/alıcı/kalemler/matrah/KDV) göndeririz. Entegratör
yapılandırılmamışsa fatura "bekliyor" kaydedilir; akış bozulmaz.

**Yeni:** `sql/migrate_faz5_fatura.sql` + `sql/schema.sql` (`faturalar` tablosu:
etn/uuid/process_id/durum/entegrator/matrah/kdv/toplam/pdf_url/hata;
`siparis_id` BIGINT UNSIGNED FK CASCADE — `siparisler.id` BIGINT ile uyumlu),
`models/Fatura_model.php`, `libraries/Efatura.php` (`hazir/payload/olustur/durum_sorgula`
— %20 KDV ayrışımıyla matrah/KDV; genel JSON sözleşmeli entegratör POST + bearer token,
asenkron process_id + durum takibi; mükerrer koruma), `controllers/yonetim/Faturalar.php`
(liste/olustur/detay/yenile/sil), `views/yonetim/faturalar/{index,detay}.php`.
**Değişen:** Ayarlar WHITELIST+toggles (`efatura_*`), Ayarlar view'ına E-Fatura kartı,
admin menüsüne "Faturalar", `Siparisler::detay` artık fatura listesi + "Fatura Kes"
formu basıyor; `Siparisler::detay($id=NULL)` guard (bare URL → fatal yerine yönlendirme;
`Urunler::duzenle` ile aynı desen).

**Doğrulama:** test siparişi (ara 479.40 / toplam 559.30) → fatura: **matrah 399.50,
KDV 79.90, toplam 559.30** (%20 ayrışımı), alıcı ünvan+VKN siparişten, UUID üretildi,
durum **bekliyor** (entegratör yok → `Efatura: … bekliyor (graceful)` log); **mükerrer**
2. deneme reddedildi (fatura sayısı 1); admin menüsü + Ayarlar E-Fatura kartı + faturalar
liste/detay 200; bare `detay/` artık 307. 10 dosya `php -l` temiz, UTF-8/Türkçe
doğrulandı. Test verisi temizlendi.

**[!] Canlıya taşı:** `migrate_faz5_fatura.sql` prod DB + 11 kod dosyası FTP. E-Fatura
canlı: Ayarlar → entegratör (Paraşüt/Uyumsoft/Foriba) API URL + token + satıcı VKN
girilince siparişten kesilen faturalar otomatik gönderilir (asenkron "Durum Yenile";
KDV şeması satıcıya göre ayarlanabilir).

---

## 2026-08-08 — Faz 5 (devam): SMS bildirimleri (Netgsm) + feed arayüzü Türkçe cilası

**SMS modülü (yeni, graceful):** Netgsm HTTP API ile durum SMS'leri. Ayarlar
altyapısı (`sms_aktif`/`sms_kullanici`/`sms_sifre`/`sms_gonderen` + Ayarlar view
alanları) **zaten mevcuttu** — yalnızca library + sipariş akışı entegrasyonu
eklendi. **Yeni:** `libraries/Sms.php` — Netgsm GET API, GSM normalize (0/90/çiğrak
→ 90XXXXXXXXXX), curl + 12s timeout, "00" başarılı-kod parse + hata log; `hazir()`
/`gonder()`/`siparis_onay()`/`durum_bildirim()` (Eposta library ile paralel API).
**Değişen:** `controllers/Odeme.php` (`tamamla`'ya SMS onayı, e-posta sonrası),
`controllers/yonetim/Siparisler.php` (`durum_guncelle`'ye SMS durum bildirimi;
kargolandı'da takip no mesajda). Anahtarsız/pasifken gönderilmez → sipariş/durum
akışı bozulmaz.

**Güvenlik:** SMS kimlikleri Ayarlar'da (admin-only); curl `SSL_VERIFYPEER`; mesaj
`http_build_query` ile encode. Graceful: `hazir()`=false veya curl/Netgsm hatası →
log + FALSE; akış devam eder.

**Doğrulama:** GSM normalize 7/7; `sms_aktif=0` iken sipariş **303→basarili**
(akış bozulmadı) + log hem `Eposta: SMTP yok (graceful)` hem `Sms: pasif veya
kimlik yok (graceful)` yazdı; 5 dosya `php -l` temiz, UTF-8/Türkçe byte-düzeyinde
doğrulandı. (Gerçek Netgsm çağrısı anahtarlar girilince canlı — yapı hazırlı.)

**Feed arayüzü Türkçe cilası:** önceki ASCII Türkçe admin metinleri ("Anahtarlari",
"Pasiflestir" vb.) proper Türkçe'ye çevrildi — `views/yonetim/feed/index.php`,
`controllers/yonetim/Feed.php`, `controllers/api/Feed.php` hata mesajları,
`models/Api_anahtar_model.php` varsayılan etiketi. UTF-8 doğrulandı.

**[!] Canlıya taşı:** `libraries/Sms.php` (yeni) + `controllers/Odeme.php` +
`controllers/yonetim/Siparisler.php` + feed Türkçe dosyaları FTP. SMS canlı için:
admin Ayarlar → Netgsm kullanıcı/şifre/onaylı gönderen adı + `sms_aktif=1`.

---

## 2026-08-08 — Faz 5 başlangıç: B2B XML/REST ürün feed'i (yeni modül)

Toptancıların kataloğu (ürün + varyant + stok + fiyat) kendi sistemlerine
çekmesi için **erişim-anahtarlı XML/JSON feed** (kaktusmoda B2B mantığı). Faz 5
altyapısı boştu — sıfırdan kuruldu.

**Yeni dosyalar:** `sql/migrate_faz5_feed.sql` (tablo), `models/Api_anahtar_model.php`
(sha256 hash doğrulama/üretim + kullanım sayacı), `libraries/Xml_export.php`
(DOMDocument XML + JSON; 3. parti yok; tüm metin createTextNode/json_encode ile
escape), `controllers/api/Feed.php` (CI_Controller türevi — session/layout/bakım
YOK; `?key=` veya `X-Api-Key` başlığı ile kimlik; 401 anahtarsız / 403 geçersiz),
`controllers/yonetim/Feed.php` (anahtar üret/listele/durum/sil + tek-sefer ham
anahtar gösterimi), `views/yonetim/feed/index.php`.

**Değişen:** `models/Urun_model.php` (+`feed_liste()` aktif ürünler+kategori+varyant,
toplu varyant çekme), `core/MY_Controller.php` (admin menüsüne "API / Feed"),
`config/routes.php` (`feed/urunler` → `api/feed/urunler`), `sql/schema.sql`
(`api_anahtarlari` tablosu eklendi).

**Güvenlik:** anahtar DB'de **plaintext değil, sha256 hash** (sorgulanabilir); ham
değer yalnızca üretim anında gösterilir. Query Builder; CSRF muaf (GET endpoint);
XML/JSON çıktı escape. URL: `/feed/urunler?key=ANAHTAR` (XML varsayılan,
`&format=json` ile JSON).

**Doğrulama (sunucu açıp uçtan uca):** admin girişi + anahtar üretimi (64 hex);
anahtarsız **401**, yanlış anahtar **403**, geçerli **200**; XML `simplexml_load_file`
OK (8 ürün / 128 varyant, `&` doğru escape → `&amp;`, Türkçe UTF-8 bozulmamış);
JSON `json_decode` OK (8 ürün); kullanım sayacı yazıldı (2 çağrı →
`kullanim_sayisi=2` + `son_kullanim`); content-type temiz
(`application/xml; charset=UTF-8`). 6 dosya `php -l` temiz. Test anahtarları silindi.

**[!] Canlıya taşı:** `sql/migrate_faz5_feed.sql` production DB'sinde çalıştırılmalı
(`api_anahtarlari` tablosu); 9 kod dosyası FTP. Site adı yer tutak "TekstilSite".

---

## 2026-08-08 — Dünkü (07-08) test turundan kalan açık maddeler (2 düzeltme)

`application/logs/log-2026-08-07.php` incelendi; B2B storefront checkout + bayi
girişi testi sırasında kalmış açık maddeler kapatıldı. (Erken 15:33 404'leri DB
bağlantısızlık dönemindeydi, geçti; `siparisler.ic_not` hatası da zaten
düzeltilmiş — kaynak `admin_notu` kullanıyor, siparişler 16:40'tan sonra başarılı.)

**(1) `bayiler.son_giris` kolonu eksikti — BLOKER (bayi girişi / admin detay).**
Kod her bayi girişinde `son_giris` yazıyor (`Bayi_model::son_giris_isaretle`,
satır 62) ve admin bayi detayı okuyor (`views/yonetim/bayiler/detay.php:65`), ama
`bayiler` tablosunda bu kolon yoktu → her girişte "Unknown column 'son_giris'"
query error + admin detayında "Undefined property: son_giris" warning. Düzeltme:
live DB'ye (`teksilsite`) `son_giris DATETIME NULL DEFAULT NULL AFTER
olusturma_zaman` eklendi; aynı kolon `sql/schema.sql` `bayiler` tanımına da
işlendi (yeniden üretilebilirlik). Doğrulama: `UPDATE bayiler SET son_giris=...
WHERE id=1` başarılı; `mg_admin_getir` `b.*` seçtiği için detay.php artık dolu.
Dosya: `sql/schema.sql`. DB: `ALTER TABLE bayiler ADD COLUMN son_giris ...`.

**(2) `Urunler::duzenle` argümansız fatal — sağlamlaştırma.**
`duzenle($id)` varsayılan değer almıyordu → bare `/yonetim/urunler/duzenle`
(ID'siz, elle/stale tab) isteği "Too few arguments to function duzenle()" fatal
veriyordu. Düzeltme: `duzenle($id = NULL)` + guard — ID yoksa fatal yerine
liste sayfasına hata flash mesajıyla yönlendirme (kod tabanındaki `set_flashdata`
deseniyle uyumlu). Linkler zaten doğru (`duzenle/' . $u->id`); bu yalnızca bare
istek savunması. Dosya: `application/controllers/yonetim/Urunler.php`. Doğrulama:
`php -l` temiz.

**Yanlış teşhis — düzeltildi (kod dokunulmadan kaldı):**
- *Checkout `validation_errors` fatal (07-08 log satır 126)*: Önce yanlış teoriyle
  `Odeme::__construct()`'a `form_validation` yüklemesi ekledim — **geri aldım.**
  Kök neden: `validation_errors()` aslında `system/helpers/form_helper.php`'de
  (`if (! function_exists(...))` guard'lı) tanımlı ve form_validation kütüphanesi
  yoksa zararsız `''` döndürür. `form` helper autoload'ta olduğu için fonksiyon
  daima mevcut — fatal atmaz. 07-08 fatal'i `form` helper'ın autoload'a henüz
  eklenmediği geçici bir durumdanmış; şu an zaten düzeltilmiş. Aynı mekanizma
  `bayi/kayit`, `bayi/giris`, `hesabim/bilgiler`, `hesabim/sifre` view'larını da
  kapsar — **hiçbirinde bug yok** (statik teoriyi, fonksiyon gövdesini okuyarak
  çürüttük; yanlış düzeltmeyi yaymadan).
- *Arama debug logları*: kaynakta zaten temiz (yalnızca 07-08 log'unda).
- *Seed mojibake "VIP Moda A.?."*: mojibake değil — Git Bash terminal Win-1254
  gösterimi; DB hex `...C59E2E` = doğru UTF-8 (`C59E`=ş). seed.sql'de bayi INSERT
  verisi de yok.

**Ders (iş kuralı):** CI3 iç davranışını teorilemek yerine fonksiyon gövdesini /
log'u doğrula; terminal çıktısına (Türkçe) güvenme
(`memory/windows-terminal-turkish-encoding.md`).

**[!] Canlıya taşı:** DB (`bayiler.son_giris`) zaten live local DB'de +
`sql/schema.sql`. PHP: `application/controllers/yonetim/Urunler.php` production'a
kopyalanmalı. (`Odeme.php`'de net değişiklik kalmadı — spurious ekleme geri alındı.)
