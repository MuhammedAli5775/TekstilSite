# TAMAMLAMA_PLANI.md — TekstilSite %100'e (canlı lansman) tamamlama planı

> Oluşturma: **2026-08-13**. Bu plan `workflow.md` yol haritası ile `DEGISIKLIK.md`
> değişiklik günlüğünü temel alır ve **kod-tamamlanma** ile **canlıya-hazır** arasındaki
> farkı somut görevlere yayar. Görevler **Sahip** (Dev / İş / Ops) ve **Efor** (S/M/L) ile
> etiketlidir. Öncelik-kritik-yol bölümü sıralamayı verir.
>
> **Durum (2026-08-13):** Kod-tamamlanma ~%92-95 · Doğrulama (dev'de) ~%80 · Canlıya-hazır ~%55-60.
> Kapanacak gap'in büyük kısmı **kod değil** — iş kimlikleri + production ortamı + doğrulama.

---

## 0. Kapanacak gap'in özeti (kanıtlı)

| Boyut | Durum | Kanıt |
|---|---|---|
| Kod | Faz 0-5 tamamına yakını yazılı | 13 mağaza + 18 admin controller, 17 model |
| Dış entegrasyonlar | **Hepsi yapılandırılmamış** | smtp_*/sms_*/paytr_*/efatura_* ayarlar boş veya yok |
| Ortam | Dev | `ENVIRONMENT=development`, `base_url=localhost:8000`, session `C:/xampp/tmp` |
| Deploy | Kısmen hazır | `.htaccess` hazır (Apache rewrite+güvenlik+gzip+expires); `uploads/` yok |
| Doğrulama | Çoğu test edildi | B2B 53/53, yetki, feed, stok, kupon, banner, cron, PayTR(yapısal) PASS; **pazaryeri/doğrulanmadı** |

**Planlama sırasında bulunan somut kod gap'leri (Faz D0):**
- **`paytr_*` / `efatura_*` Ayarlar whitelist'te YOK** → `Ayarlar::kaydet()` bu alanları sessizce
  atar (`application/controllers/yonetim/Ayarlar.php:35` yalnızca `$WHITELIST` iterasyonu; view
  alanları var ama kaydedilmez). Bu yüzden DB'de paytr_* yok. Admin panelden ödeme/fatura kimliği
  **girilemiyor**. → D0 düzeltmesi şart.
- `uploads/` dizini yok (banner görseli ilk yüklemede oluşturulur) → prod'da elle oluştur + yazma izni.
- `robots.txt` yok (`sitemap.xml` route'u dinamik: `seo/sitemap`).
- Pazaryeri: yalnız **trendyol** adapter'ı var (Hepsiburada/N11/Amazon = "adapter yok" graceful atlar).

---

## Faz A — İş kimlik bilgileri  (ENGELLEYİCİ · Sahip: **İş** · kod YOK)

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

- **B1.** Hosting + alan adı + **SSL/HTTPS** (ödeme formu var → zorunlu). (Ops·M)
- **B2.** Web sunucu: **Apache + `.htaccess` hazır** (rewrite, `system|application|sql|tests` erişim
  engeli, gzip, expires). Nginx ise eşdeğer kuralları elle çevir. (Ops·M)
- **B3.** Production **DB**: ayrı kullanıcı (min. yetki) + sert parola. Kurulum sırası:
  `schema.sql` → `migrate_faz2` → `migrate_faz4` → `migrate_faz5_fatura` → `migrate_faz5_feed` →
  `migrate_faz5_pazaryeri` → `migrate_kuponlar` → `migrate_para_birimi` → `migrate_2026_08_09` →
  **`migrate_yetkiler`** → **`migrate_feed_rate_limit`** → **`migrate_perf_index`** →
  `seed.sql` (seed ilk; truncate içerir — sıralamayı dikkatli kur). (Ops·M)
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
- **C2.** Hesap (bayi self-servis: siparişlerim / adresler / faturalar / bakiye) — M
- **C3.** Raporlar (admin rapor derinliği + ciro TRY doğruluğu) — M
- **C4.** SEO/sitemap (`seo/sitemap` route + `robots.txt` ekle) — S
- **C5.** Kalan admin CRUD (Markalar / Kategoriler / Sayfalar) — kuponlar/bannerlar pattern'iyle — S/M
- **C6.** Pazaryeri **canlı** test (Trendyol cred gelince): hesap CRUD + eşleştirme + senkron — L (dışa bağlı)
- **C7.** Lansman öncesi **regresyon**: tüm E2E paketlerini (yetki/feed/stok/kupon/banner/B2B 53) tek seferde koş.

---

## Faz D — Kalan kod  (Sahip: **Dev**)

- **D0. (ÖNCELİKLİ) Ayarlar'a PayTR + e-fatura + pazaryeri kimlik alanları ekle**
  (`Ayarlar.php` `$WHITELIST` + TOGGLES güncelle; view alanları zaten var). Olmadan A2/A4 panelden
  girilemez. — S/M. **Test:** kaydet → DB'de `paytr_*`/`efatura_*` kalıcı.
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
- **E2. Performans:** 89 index mevcut; ana sorgulara `EXPLAIN`; opcache açık. — S/M
  **✓ 2026-08-14:** EXPLAIN 3 boşluk buldu → `migrate_perf_index.sql` (urunler(durum,
  olusturma_zaman), urunler(durum, fiyat), siparisler(olusturma_zaman)) uygulandı; filesort
  kalktı, sıralamalar covering index'ten. Opcache: prod PHP'de doğrulanacak (B4 ile).
- **E3. İzleme:** hata log izleme + uptime. — S
- **E4. Hukuki/içerik:** KVKK aydınlatma + üyelik/mesafeli satış sözleşmesi + iade koşulları
  sayfaları (Sayfalar CMS'inde; checkout'ta sözleşme onayı ✓). — İş·M

---

## Kritik yol & sıra

```
D0 (ayar alanları) ──► A (kimlikler) ──┐
                                       ├──► Soft Launch ──► C6/C7 + D1 (pazaryeri derin)
B (production ortam) ─────────────────►┤
                                       │
C1–C5 (doğrulama) ──► E (sertleştirme)─┘
```

1. **D0** hemen (panelden kimlik girişini açar). **A** ∥ **B** paralel (ikisi de engelleyici).
2. **C1–C5** kalan bug avı. **E** lansman öncesi sertleştirme.
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

İş/ops tarafı (kimlik abonelikleri + hosting) süresi **işletmeye/teslimata bağlı**, dev dışı.
