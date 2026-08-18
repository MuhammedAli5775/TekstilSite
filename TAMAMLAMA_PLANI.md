# TAMAMLAMA_PLANI.md — TekstilSite %100'e (canlı lansman) tamamlama planı

> Oluşturma: **2026-08-13**. Bu plan `workflow.md` yol haritası ile `DEGISIKLIK.md`
> değişiklik günlüğünü temel alır ve **kod-tamamlanma** ile **canlıya-hazır** arasındaki
> farkı somut görevlere yayar. Görevler **Sahip** (Dev / İş / Ops) ve **Efor** (S/M/L) ile
> etiketlidir. Öncelik-kritik-yol bölümü sıralamayı verir.
>
> **Durum (2026-08-18):** Zorunlu dev işi **TAMAM** — D0 ✓, C1–C5 ✓, C7 ✓, E1–E4 ✓
> (ayrıntılar maddelerin altında); regresyon **136/136 PASS** (dev) + yerel prod ve
> sıfır-DB kurulum provası 74/74. Mağaza çoklu dil artımlı (XXIX kabuk + XXX
> anasayfa/katalog/sepet/odeme + banner dil filtresi). Kalan gap'in tamamı
> **İş/Ops tarafında**: kimlikler (Faz A), hosting+deploy (Faz B), hukuki metin
> kararı — artı bilinçli ertelenmişler (C6 canlı pazaryeri, D1 ek adapter'lar,
> D2 cila, D3 blog).

---

## 0. Kapanacak gap'in özeti (kanıtlı)

| Boyut | Durum | Kanıt |
|---|---|---|
| Kod | Faz 0-5 tamamına yakını yazılı | 13 mağaza + 18 admin controller, 17 model |
| Dış entegrasyonlar | Alanlar panelden girilebilir (D0 ✓); **gerçek kimlik yok** | smtp_*/sms_*/paytr_*/efatura_* Ayarlar formunda hazır, değerler test/boş — İş bekliyor (Faz A) |
| Ortam | Dev | `ENVIRONMENT=development`, `base_url=localhost:8000`, session `C:/xampp/tmp` |
| Deploy | Runbook hazır | `application/config/production/` + `DEPLOY.md` (VIII–IX provalarıyla 74/74); `uploads/` prod'da elle (B6) |
| Doğrulama | Dev tarafı doygun | B2B 53/53, Faz C 88/89 (kod değişikliği yok), yetki/feed/stok/kupon/banner/cron/PayTR(yapısal); regresyon **96/96**; **yalnız pazaryeri canlı (C6) doğrulanmadı** |

**Planlama sırasında bulunan somut kod gap'leri (Faz D0):**
- ~~`paytr_*` / `efatura_*` Ayarlar whitelist'te YOK~~ **✓ D0 2026-08-13'te kapandı** —
  `$WHITELIST` + `$TOGGLES` + Ayarlar'a PayTR kartı; E2E 20/20 (kimlikler kalıcı, diğer
  ayarlar korunuyor, rol-2 403). Yol üstünde bulunan "formda-olmayan alanları null'lama"
  bug'ı da 08-14'te kapatıldı (posted-olmayan non-toggle key'ler korunur).
- `uploads/` dizini yok (banner görseli ilk yüklemede oluşturulur) → prod'da elle oluştur + yazma izni. (B6)
- ~~`robots.txt` yok~~ **✓ zaten varmış** — `Seo::robots()` + `robots\.txt` route'u (Faz C düzeltmesi, 08-13).
- Pazaryeri: yalnız **trendyol** adapter'ı var (Hepsiburada/N11/Amazon = "adapter yok" graceful atlar). (D1 ertelendi)

---

## Faz A — İş kimlik bilgileri  (ENGELLEYİCİ · Sahip: **İş** · kod YOK)

> **Açılış ✓ 2026-08-14:** dev tarafı hazır ve kanıtlı — panelden giriş E2E
> (29/29 PASS; yol üzerinde Ayarlar kaydının formda-olmayan alanları sildiği bug
> düzeltildi), boş kimlikle dört yolun graceful-beklediği doğrulandı, Ayarlar'a
> **Entegrasyon Durumu** şeridi eklendi. İşletme rehberi: **`FAZ_A_REHBERI.md`**
> (başvuru → toplancak alan → panel kartı → test sırası). **Kalan: başvuruların
> yapılıp kimliklerin girilmesi (İş).**

Hepsi "graceful" tasarımda; kimlik girilince otomatik aktifleşir. **D0'dan sonra** Ayarlar'dan girilebilir;
o zamana kadar elle DB'ye (`ayarlar` tablosu: `anahtar`/`deger`). Lansman için **hepsi şart**.

- **A1. SMTP / e-posta** (İş·S) — `smtp_sunucu/port/sifrelem/kullanici/sifre/gonderen_eposta`.
  Sipariş onay + durum bildirimi. Test: gerçek bir siparişten mail düşüyor mu.
- **A2. PayTR canlı** (İş·S gir + M doğrulama) — `paytr_merchant_id/key/salt` + `paytr_test`→0.
  Test anahtarlarından canlıya geçiş. Doğrulama: küçük tutarlı **gerçek** kartlı ödeme + callback.
- **A3. SMS Netgsm** (İş·S) — `sms_aktif=1` + `sms_kullanici/sifre/gonderen`.
- **A4. E-fatura entegratörü** (İş·S gir + M doğrulama) — abonelik (Uyumsoft/Logo/Paraşüt);
  `efatura_api_url/token/firma_vkn/firma_unvan`. Not: "bekliyor" faturalar cron `efatura_durum`
  tarafından yapılandırılınca otomatik gönderilir.
- **A5. Pazaryeri satıcı hesabı** (İş·M) — önce **Trendyol** (tek adapter). Stok/fiyat + sipariş çek
  canlı testi. (Hepsiburada/N11 → D1 kod bekler.)

---

## Faz B — Production ortam & deploy  (Sahip: **Ops/DevOps**)

> **Hazırlık ✓ 2026-08-14:** kod tarafı hazır — `application/config/production/`
> (config + database şablonları; CI3 ENVIRONMENT override kaynaktan doğrulandı ve
> empirik test edildi), **`DEPLOY.md`** (B1–B8'in komut-level runbook'u: migration
> sırası, izinler, SetEnv, cron, doğrulama listesi, rollback), `scripts/yedek.sh`
> (B8 yedek + rotasyon). **Kalan: B1 satın alma + DEPLOY.md'in fiilen uygulanması**
> (geri kalanı artık mekanik adım).

- **B1.** Hosting + alan adı + **SSL/HTTPS** (ödeme formu var → zorunlu). (Ops·M)
- **B2.** Web sunucu: **Apache + `.htaccess` hazır** (rewrite, `system|application|sql|tests` erişim
  engeli, gzip, expires). Nginx ise eşdeğer kuralları elle çevir. (Ops·M)
- **B3.** Production **DB**: ayrı kullanıcı (min. yetki) + sert parola. Kurulum sırasının
  komut-level listesi **`DEPLOY.md` §3'tedir (otorite orası)** — özet: `schema` → faz
  migrasyonları → `kuponlar/para_birimi/2026_08_09/feed_rate_limit/perf_index/kullanicilar`
  → `seed` → **`migrate_yetkiler` seed'den SONRA** (FK roller'e bağlı; IX bulgusu) →
  hukuki/footer/slider seed'leri EN SONDA (seed truncate içerir). (Ops·M)
  **✓ 2026-08-16 (XVI):** sıra sıfır-DB provasında uçtan uca koşuldu — 17/17 dosya, 39
  tablo, **101/101 regresyon** (`migrate_kullanicilar` dahil). DETAY: DEGISIKLIK.md XVI.
  **✓ 2026-08-17 (XIX):** prova güncel paketle tekrar — XVII şeması (kullanici_adi) +
  XVIII oturum-dönüşü + perf-indeks güncellemesi altında 17/17, 39 tablo, **111/111**.
- **B4.** PHP: `ENVIRONMENT=production` (`CI_ENV` ile), `base_url`=gerçek domain, hata görüntüleme kapalı. (Ops·S)
- **B5.** `sess_save_path`'i production dizinine çevir (lokalde `C:/xampp/tmp`). (Ops·S)
- **B6.** `uploads/` + `application/logs/` oluştur, web sunucusuna **yazma izni**; log rotasyonu. (Ops·S)
- **B7.** **Cron**: `php index.php cron calis` (terk sepet / pazaryeri senkron / e-fatura durum) — örn. 15 dk. (Ops·S)
- **B8.** Yedekleme: DB dump + `uploads/` otomatiği. (Ops·S)
- **B9.** `application/logs/*.php` → `.gitignore` (şu an untracked birikiyor). (Dev·S)
  **✓ 2026-08-14:** `.gitignore` oluşturuldu (logs + uploads + cookie-jar artıkları);
  birikmiş loglar untrack edildi.

---

## Faz C — Kalan doğrulama  (Sahip: **Dev** · bug bulunursa düzelt)

- **C1.** Arama (mağaza search) — S
  **✓ 2026-08-13:** 10/10 — boş/ASCII/Türkçe(%-enc) sorgu, LIKE alanları, no-match, noindex.
- **C2.** Hesap (bayi self-servis: siparişlerim / adresler / faturalar / bakiye) — M
  **✓ 2026-08-13:** 19/19 — auth gate, IDOR sahiplik izolasyonu, bilgiler/şifre akışları.
  **✓ 2026-08-16 (XIII):** *faturalar* yarısı kapandı — `hesabim/faturalar` (çift modlu;
  bayi: sipariş sahipliği, kullanıcı: sipariş e-postası). **Kalan kapsam notu:** bayi
  *bakiye* — `bayiler.bakiye` kolonu kodda hiç okunmuyor/yazılmıyor (dormant); kredi/bakiye
  iş modeli kararlanırsa yeni iş.
- **C3.** Raporlar (admin rapor derinliği + ciro TRY doğruluğu) — M
  **✓ 2026-08-13:** 19/20 — ciro `SUM(toplam*kur)` çoklu-para normalizasyonu kanıtlı; 1 kozmetik assertion.
- **C4.** SEO/sitemap (`seo/sitemap` route + `robots.txt` ekle) — S
  **✓ 2026-08-13:** 11/11 — sitemap well-formed (42 URL), robots 200, `arama_index` davranışı.
- **C5.** Kalan admin CRUD (Markalar / Kategoriler / Sayfalar) — kuponlar/bannerlar pattern'iyle — S/M
  **✓ 2026-08-13:** 29/29 — CRUD + `slug_tr` + benzersizlik + ağaç koruması + rol-2 gate'leri.
- **C6.** Pazaryeri **canlı** test (Trendyol cred gelince): hesap CRUD + eşleştirme + senkron — L (dışa bağlı)
- **C7.** Lansman öncesi **regresyon**: tüm E2E paketlerini (yetki/feed/stok/kupon/banner/B2B 53) tek seferde koş.
  **✓ 2026-08-14 (lokal):** `tests/regresyon.php` — kalıcı, tek komutla tam paket
  (yayın+bayi akışı+admin smoke+yetki matrisi+feed+rate-limit+log denetimi+temizlik):
  **101/101 PASS** (2026-08-16 itibarıyla; X–XIV ile kullanıcı girişi/hesabı + favori +
  faturalarım testleri eklendi). Canlı koşusu lansman günü: `php tests/regresyon.php https://alanadi --force`.
  **128/128** (2026-08-17; XVIII +7: üç girişte oturum-ID dönüşü, misafir-sepet transfer/devri
  + XX +1: eski-çerez sabitleme pruebası + XXII +1: CSRF-403 sözleşmesi + XXIII +1: CSRF
  çerezi SameSite=Lax + XXV +4: kullanıcı sipariş atıflaması + XXVI +2: PayTR sonuç-sayfası
  sahipliği + XXVII +3: PayTR callback provası + XXIX +5: çoklu dil seçici).
  ~~**Açık bulgu (İş kararı):** bayi kaydı otomatik onaylı (durum=1) ama belgeler
  "admin onayı" vaat ediyor~~ **✓ çözüldü (VI, 08-14):** kayıt `durum=0` başlar, admin
  onayı zorunlu (regresyonda `bayi-kayit-db-onay-bekliyor` + `bayi-onaysiz-giris-red` PASS).

---

## Faz D — Kalan kod  (Sahip: **Dev**)

- **D0. (ÖNCELİKLİ) Ayarlar'a PayTR + e-fatura + pazaryeri kimlik alanları ekle**
  (`Ayarlar.php` `$WHITELIST` + TOGGLES güncelle; view alanları zaten var). Olmadan A2/A4 panelden
  girilemez. — S/M. **Test:** kaydet → DB'de `paytr_*`/`efatura_*` kalıcı.
  **✓ 2026-08-13:** `$WHITELIST` + `$TOGGLES` (paytr_test/efatura_test) + Ayarlar'a PayTR kartı;
  E2E 20/20 — kimlikler kalıcı, diğer ayarlar korunuyor, rol-2 403.
- **D1.** Pazaryeri adapter'ları: Hepsiburada, N11, Amazon (yalnız trendyol var) — her biri L (API başına).
  *Erteleme uygundur* — Trendyol tek başına ilk lansman için yeter.
- **D2.** Rapor/SEO cilası (ihtiyaç halinde) — M
- **D3.** Blog (`Sayfa.php` stub) — **ertele**, çekirdek dışı.

---

## Faz E — Lansman sertleştirme  (Sahip: **Dev** · güvenlik/performans)

- **E1. Güvenlik review:** SQL injection (Query Builder yaygın; **raw `where('…', NULL, FALSE)`**
  ifadeleri tara — örn. `deleted_at IS NULL` güvenli, ama kullanıcı girdisi birleşen var mı);
  XSS (`e()` helper yaygın — textarea/raw çıktı tara); CSRF (aktif ✓); dosya yükleme (banner native
  validation ✓ — tekrar gözden geçir); **Feed API rate-limit/brute-force yok** (key deneme koruması
  opsiyonel ekle). — M
  **✓ 2026-08-14:** SQLi taraması temiz (0 raw query; 27 raw-FALSE hepsi sabit/int-cast/whitelist'li);
  XSS taraması temiz (791 `<?=` hepsi e()/helper/cast) + `detay.php` pdVeri JSON'a `JSON_HEX_TAG`;
  upload doğrulaması sağlam + `uploads/.htaccess` PHP-yasak guard'ı; Feed API'ye IP-tabanlı
  rate-limit (20 yanlış/15 dk → 429) eklendi ve canlı doğrulandı. Ayrıntı: DEGISIKLIK.md 2026-08-14.
  **✓ 2026-08-17 (tazeleme):** E1'den sonra yazılan kullanıcı-hesabı koduna (X–XVII)
  aynı liste uygulandı — tek bulgu **oturum sabitleme** (hiçbir giriş ID döndürmüyordu);
  `sess_regenerate()` tüm giriş/çıkış/şifre noktalarına + B2C sepet anahtar sürekliliği
  (`oturum_tasi` / transfer `eski_sid`). Regresyon 112/112. Ayrıntı: DEGISIKLIK.md XVIII.
- **E2. Performans:** 89 index mevcut; ana sorgulara `EXPLAIN`; opcache açık. — S/M
  **✓ 2026-08-14:** EXPLAIN 3 boşluk buldu → `migrate_perf_index.sql` (urunler(durum,
  olusturma_zaman), urunler(durum, fiyat), siparisler(olusturma_zaman)) uygulandı; filesort
  kalktı, sıralamalar covering index'ten. Opcache: prod PHP'de doğrulanacak (B4 ile).
  **✓ 2026-08-17 (tazeleme):** X–XVII sorgularına aynı EXPLAIN denetimi — tek bulgu
  `siparisler.email` indekssizliği (B2C hesabım tam geri-tarama); `idx_siparis_email`
  migrate_perf_index.sql'e eklendi (taze kurulum §3'ten alır). Sıfır-DB provası da
  güncel paketle 111/111 tekrarlandı. Ayrıntı: DEGISIKLIK.md XIX.
- **E3. İzleme:** hata log izleme + uptime. — S
  **✓ 2026-08-14:** `scripts/log_kontrol.sh` (günlük ERROR özeti, mesaj bazlı gruplu —
  fonksiyonel test edildi); DEPLOY.md 7b: cron satırı + uptime monitör önerisi
  (anasayfa + feed 401). Kurulum DEPLOY ile canlıda yapılır.
- **E4. Hukuki/içerik:** KVKK aydınlatma + üyelik/mesafeli satış sözleşmesi + iade koşulları
  sayfaları (Sayfalar CMS'inde; checkout'ta sözleşme onayı ✓). — İş·M
  **✓ 2026-08-14 (taslak):** `sql/seed_hukuki_sayfalar.sql` — dört sayfa (mesafeli-satis,
  iade-degisim, gizlilik, cerez) B2B-nüanslı tam taslak metinle DB'ye işlendi ve
  render doğrulandı. **Kalan (İş):** [FİRMA/ADRES/VKN] yer tutucularını doldur,
  [POLİTİKA:...] maddelerini kararlaştır, hukuk müşaviri onayı ver.

---

## Kritik yol & sıra

```
D0 (ayar alanları) ──► A (kimlikler) ──┐
                                       ├──► Soft Launch ──► C6/C7 + D1 (pazaryeri derin)
B (production ortam) ─────────────────►┤
                                       │
C1–C5 (doğrulama) ──► E (sertleştirme)─┘
```

1. ~~**D0** hemen~~ ✓ bitti. **A** ∥ **B** paralel — ikisi de engelleyici, **dev dışı (İş/Ops)**.
2. ~~**C1–C5** kalan bug avı~~ ✓ bitti. ~~**E** sertleştirme~~ ✓ bitti. Dev tarafında zorunlu iş kalmadı.
3. **Soft launch** → **C6/C7** canlı regresyon + **D1** pazaryeri genişletme lansman sonrası da olabilir.

---

## "Definition of Done" — %100 = canlı, para akıyor

- [ ] Canlı alan adı HTTPS'te açılıyor; `ENVIRONMENT=production`
- [ ] Bayi: kayıt → admin onay → sepet → **PayTR canlı ödeme** → e-posta + SMS düşüyor
- [ ] Sipariş → e-fatura **entegratöre gönderiliyor** (bekliyor değil)
- [ ] Admin yetki matrisi canlıda çalışıyor (rol-2 kısıtlı erişim)
- [ ] Cron çalışıyor (terk sepet / senkron / e-fatura durum)
- [ ] `uploads/` yazılabilir, log rotasyonu + **yedek** var, log izleme var
- [ ] Tüm E2E paketleri canlı ortamda PASS
- [ ] Hukuki sayfalar (KVKK / sözleşme / iade) yayında

---

## Efor tahmini (zorunlu dev işi)

| Görev | Efor |
|---|---|
| D0 (Ayarlar whitelist) | ~0.5 gün |
| C1–C5 (doğrulama) | ~2-3 gün |
| E1–E2 (sertleştirme) | ~1-2 gün |
| **Toplam zorunlu dev** | **~4-6 gün** |
| D1 (her pazaryeri adapter'ı) | 1-2 gün/adet (opsiyonel, ertelenebilir) |

> **2026-08-16:** Zorunlu dev işi (ilk dört satır) **tamamlandı**; kalan dev kalemleri
> ertelenmiş opsiyoneller (D1+) ve lansman günü canlı C7 koşusu.

İş/ops tarafı (kimlik abonelikleri + hosting) süresi **işletmeye/teslimata bağlı**, dev dışı.
