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
> kayıtlı değildir. Gerçek rehber `workflow.md` + bu dosyadır; `CLAUDE.md`
> 2026-08-17'de oturum-başlangıç kısayolu olarak yeniden yazıldı (otorite değil,
> bu dosyalara işaretçi — XXI).

---

## 2026-08-21 (LI) — Footer zenginleştirme: iletişim bloğu + keşif/dil kolonları + güven şeridi — 270/270 PASS

**Kullanıcı isteği:** "footer'a daha fazla şey ekle".

**Yapılan (kod tarafı):**
- **Footer 4 → 5 kolon** (`footer.php` + `teksil.css` grid `1.5fr 1fr 1fr 1fr .8fr`;
  1100px altı 2 kolon, 640px altı 1 kolon — mevcut kırılımlar):
  1. **Marka kolonu:** tanımın altına ikonlu iletişim bloğu — adres, telefon,
     e-posta (mailto), çalışma saatleri. Değerler **ayarlar tablosundan**
     (`iletisim_adres`/`iletisim_telefon`/`iletisim_eposta` — yönetim → Ayarlar
     formundaki mevcut alanlar); boşsa kalıcı yer tutucular (Merter, İstanbul /
     +90 212 481 36 92 / info@teksilsite.com / Pzt–Cmt 09.00–18.00 — Faz A'da
     gerçek veriyle doldurulacak). `whatsapp` ayarı doluysa yeşil WhatsApp
     butonu (numara uluslararası biçime normalize: yerelse 90 öneki).
  2. **Kategoriler:** DB menüsü + **Tüm Ürünler** (`katalog`) + **Yeni
     Gelenler** (`katalog/yeni`) keşif bağlantıları.
  3. **Toptancı:** değişmedi.
  4. **Yardım & Kurumsal:** + **Blog** (`/blog`) + **Çerez Politikası**
     (`sayfa/cerez` — sayfa vardı, footer'da hiç bağlanmamıştı).
  5. **Dil / Language:** 4 dil bağlantısı (`dil/cevir/*`, aktif dil işaretli) —
     `Dil::cevir` referer-dönüşlü olduğundan footer'dan da sayfayı kaybetmeden
     dil değişir.
- **Güven şeridi (yeni `footer__strip`):** aktif **ödeme yöntemleri** ve
  **kargo firmaları** DB'den rozet olarak (MY_Controller `_ortak_veri` — menü
  deseni; `odeme_yontemleri.durum=1` sırayla, `kargo_firmalari.durum=1`
  ada göre). PayTR yönetimden açılınca kart rozeti kendiliğinden belirir.
- **10 yeni dil anahtarı 4 dilde:** `ftr_adres_varsayilan`, `ftr_calisma`,
  `ftr_whatsapp`, `ftr_tum_urunler`, `ftr_yeni_gelenler`, `ftr_blog`,
  `ftr_cerez`, `ftr_dil`, `ftr_odeme`, `ftr_kargo`.

**DB değişikliği:** yok (ayar anahtarları zaten mevcut; yalnız okunuyor).

**Doğrulama:** tam regresyon **270/270 PASS** (+3: `footer-zengin-yapi`,
`footer-kesif-baglantilari`, `footer-guven-seridi`). Canlı kontrol: TR/EN/RU
footer render — rozetler "Havale / EFT", "Kapıda Nakit Ödeme", "Aras Kargo",
"Yurtiçi Kargo"; dil kolonu aktif işaretli. `php -l` temiz; mojibake temiz.
NOT: ilk koşuda 3 FAIL bayat sunucudan geldi (kullanıcı htdocs tarzı ayrı
`php -S` başlatmıştı — öldürülüp OneDrive kopyasından sunucuyla tekrar
267+3=270 tam geçti).

**[!] Canlıya taşı:** `application/core/MY_Controller.php`,
`application/views/magaza/layout/footer.php`,
`assets/magaza/css/teksil.css`, 4×
`application/language/*/teksil_lang.php`, `tests/regresyon.php`.

---

## 2026-08-21 (L) — Misafir/B2C sepetini boşaltan oturum-ID döndürmesi kapatıldı — 267/267 PASS

**Kullanıcı isteği:** ödeme formunu doldurmak 5+ dakika süren misafir/B2C
kullanıcının sepeti "boş" görünüp sepet sayfasına atılıyordu (validasyon
kuralı XLIX ile çakışıyor sanılıyordu — asıl neden zamanlamaydı).

**Kök neden:** CI3 çekirdeği (`Session.php`) `sess_time_to_update=300` ile her
5 dakikada bir oturum-ID'sini sessizce döndürüyor — veri korunur ama yeni bir
çerez-ID gelir. Bayi sepeti `bayi_id` ile bağlı olduğu için etkilenmiyor;
**misafir ve B2C kullanıcının sepeti `oturum_id` (çerez anahtarı) ile bağlı**
→ 5 dakikadan uzun süren her arada satırlar eski anahtarda yetim kalıyor →
"sepet boş". Regresyon bayi hesabıyla ve hızlı akışlarla koştuğu için
yakalanamıyordu.

**Yapılan (kod tarafı):**
- **`application/config/config.php`:** `sess_time_to_update = 0` (periyodik
  döndürme KAPALI; nedeni satır üstünde açıklamalı). Fixation savunması
  etkilenmiyor: giriş/yetki değişiminde `sess_regenerate` açıkça çağrılıyor
  (sepet devriyle birlikte).
- **`application/controllers/Odeme.php` (`_giris_zorunlu`):** POST-only eyleme
  (`odeme/tamamla`) denk gelen misafir, giriş sonrası boş-POST `tamamla`'ya
  değil doğrudan ödeme formuna insin — `donus` hedefi `odeme/tamamla` →
  `odeme` olarak iniyor (`_guvenli_donus` zaten kabul ediyor).

**DB değişikliği:** yok.

**Doğrulama:** tam regresyon **267/267 PASS** (+1:
`oturum-periyodik-donme-kapali` — config güvencesi, açılırsa yakalar;
`misafir-odeme-tamamla-yonlendirme` güçlendirildi: `donus=odeme` GELİR ve
`%2Ftamamla` ARTIK GELMEZ). `php -l` temiz; mojibake temiz.

**[!] Canlıya taşı:** `application/config/config.php`,
`application/controllers/Odeme.php`, `tests/regresyon.php`.

---

## 2026-08-20 (XLIX) — Ödeme formu validasyon kuralları + yönetim paneli logosu — 266/266 PASS

**Kullanıcı isteği:** "ödeme sayfasındaki forma validasyon kuralları ekle" +
"yönetim panelindeki logo hâlâ aynı, düzelt".

**Yapılan (kod tarafı):**
- **Sunucu tarafı (Odeme::tamamla):** alan biçimi + DB tutarlılığı kuralları —
  ad min 2; adres min 10; telefon `regex_match[/^\+?[0-9 ()-]{10,19}$/]`; ilçe/
  fatura/firma/vergi max uzunluklar; **il** iller tablosunda var mı (Türkçe
  aksan normalizasyonuyla — 'Istanbul'='İstanbul'); **kargo firması** ve **ödeme
  yöntemi** AKTİF (durum=1) kayıt mı (callback'ler PUBLIC — CI3 callback
  sözleşmesi). Hatalı POST artık inline render yerine **PRG** ile odeme'ye döner
  (POST URL kalmasın — yenileme tekrar sipariş denemesin); model katmanındaki
  kapalı-yöntem kontrolü güvenlik ağı olarak duruyor.
- **İstemci tarafı (odeme/index.php):** telefon `pattern` + çevrilmiş `title`
  (tarayıcı ipucu), ad/adres `minlength`, ilce/fatura/firma/vergi `maxlength`,
  vergi `inputmode=numeric`; email/required zaten vardı.
- **Mesajlar 4 dilde:** `val_telefon_gecersiz`, `val_il_gecersiz`,
  `val_kargo_gecersiz`, `val_odeme_yontem_gecersiz`.
- **Yönetim paneli logosu:** sidebar + giriş sayfasındaki eski yeşil yaprak
  SVG'leri kaldırıldı → mağaza markasıyla AYNI paylaşım parçası
  (`partial/brand`); amblem renkleri CSS var **fallback**'li hale getirildi
  (admin.css'te teksil değişkenleri yoktur); admin.css'e boyut/renk uyarlaması
  (sidebar: 24px beyaz yazı; giriş: 26px).

**DB değişikliği:** yok.

**Doğrulama:** tam regresyon **266/266 PASS** (+2: `odeme-telefon-format-reddi`,
`odeme-il-gecersiz-reddi`; `odeme-kapali-yontem-reddi` artık geçerli dolguyla
tam olarak yöntem callback'ini sınar). Admin sayfaları (sidebar partial'ıyla
hepsi) regresyonda 200. Canlı: admin girişinde `brand__mark` + "Nesem" yazı
markası. `php -l` temiz; mojibake temiz.

**[!] Canlıya taşı:** `application/controllers/Odeme.php`,
`application/views/magaza/odeme/index.php`,
`application/views/yonetim/{layout/sidebar.php,giris/index.php}`,
`application/views/magaza/partial/brand.php`, `assets/yonetim/css/admin.css`,
4× `application/language/*/teksil_lang.php`, `tests/regresyon.php`.

---

## 2026-08-20 (XLVIII) — Marka değişimi: "Nesem Tesettür" + yeni logo — 264/264 PASS

**Kullanıcı isteği:** "logoyu kaldır. sitenin adı bundan sonra 'Nesem Tesettür'
olacak. bunun için güzel bir logo yap."

**Yapılan (kod tarafı):**
- **Yeni logo (örtü kubbesi + altın tomurcuk amblemi):** `partial/brand.php`
  paylaşım parçası — teal-deep örtü silüeti + altın tomurcuk/nokta (CSS
  değişkenli, tema rengiyle yaşar) + İKİ TONLU serif yazı markası ("Nesem" koyu +
  "Tesettür" altın; Georgia tabanlı). Yeni `--brand-gold` vurgu rengi. Eski
  yeşil yaprak simgesi ve hardcode "TekstilSite" yazısı 6 yüzeyden
  (header/footer/bayi+kullanıcı giriş ve kayıt) kaldırıldı.
- **Site adı:** `ayarlar.site_adi` = "Nesem Tesettür" (dev DB güncellendi);
  seed.sql aynı değeri eker (sıfır-DB provası uyumlu). Yazı markası ayardan
  gelir — ilk kelime vurgulu basılır; kod fallback'leri ('TekstilSite') genel
  sed ile 'Nesem Tesettür'e çevrildi.
- **Görünür marka kalıntıları temizlendi:** `meta_title_default` 4 dilde +
  Anasayfa default; telif satırı `ftr_telif` artık marka-parametreli
  (%1$s yıl, %2$s site adı — çevirilerden marka bağımlılığı kalktı, 4 dil);
  ev markası `markalar#1` "TekstilSite"→"Nesem Tesettür" (slug
  nesem-tesettur; seed + dev DB); banka hesap sahibi "…Toptan Ltd." (seed +
  dev DB); Hakkımızda + blog yazısı seed içerikleri; UA dizgileri
  (NesemTesettur). Yorum satırları (rotalar/SQL başlıkları) bilinçli dokunulmadı.

**DB değişikliği:** `ayarlar.site_adi`; `markalar#1` ad+slug;
`banka_hesaplari.hesap_sahibi` (2 satır); `sayfalar`/`yazilar` içerik
değişimi (dev DB'de UPDATE, taze kurulumda seed'den gelir).

**Doğrulama:** tam regresyon **264/264 PASS** (+1: `anasayfa-marka-nesem` —
anasayfada yeni marka var, "TekstilSite" hiçbir yerde yok; ilk koşuda meta
başlıktaki kalıntıyı yakaladı → temizlendi). Canlı spot: `<title>Nesem Tesettür
— Toptan Kadın Giyim</title>`, amblem + `<b>Nesem</b> <i>Tesettür</i>`,
EN footer "© 2026 Nesem Tesettür. All rights reserved.". `php -l` temiz;
mojibake temiz (DB hex doğrulaması: C3BC = ü).

**[!] Canlıya taşı:** kod tarafı commit'te; **prod DB'de elle:**
`UPDATE ayarlar SET deger='Nesem Tesettür' WHERE anahtar='site_adi'` +
markalar#1 + banka_hesapları.hesap_sahibi (yukarıdaki UPDATE'ler) — prod'da
seed'den kurulmadıysa.

---

## 2026-08-20 (XLVII) — Sepet adet girişine stok tavanı + Arapça RTL flip kaldırıldı — 263/263 PASS

**Kullanıcı isteği:** "sepet sayfasındaki input'u ürünün stoğuyla sınırla, ürün
sayfasında olduğu gibi" + "Arapça'ya geçince sayfadaki her şey yer değiştiriyor,
düzelt".

**Yapılan (kod tarafı):**
- **Sepet stok tavanı:** varyantlı satırlarda adet girişine `max` + `data-stok`
  (yerli oklar stokta durur, tarayıcı doğrulaması da üst sınırlar) + satır altına
  uyarı div'i (`detay_stok_ust` mesajı stok sayısıyla gömülü, 4 dilde). Yeni
  `initSepet()` JS'i: yazım/değişimde stok üstü değer STOKTA KALIR ve uyarı açılır
  (ürün sayfasındaki sayaç davranışının aynısı); sayfa açılışında değer zaten
  aşıyorsa yalnız uyarı gösterilir — mevcut değer kullanıcı dokunana kadar
  sessizce değiştirilmez. Sunucu tarafı (guncelle stok cap'i) zaten vardı —
  katmanlar artık tutarlı. Varyantsız satır (stok bilinmez) tavansız kalır.
- **Arapça RTL flip kaldırıldı (sahibin kararı):** `head.php` artık tüm dillerde
  `dir="ltr"` — AR dahil düzen yer değiştirmiyor, yalnız metinler Arapça.
  `[dir="rtl"]` CSS kuralları (2 kural, dil seçici menü) ölü kaldı — silindi.

**DB değişikliği:** yok.

**Doğrulama:** tam regresyon **263/263 PASS** (+2: `sepet-stok-max-attr`
[max/data-stok/stok-uyari render], `dil-ar-ltr-duzen` [AR sayfada dir="ltr" var,
dir="rtl" yok]). Canlı spot: sepet satırında `max="230" data-stok="230"` +
gizli uyarı; AR anasayfa `<html lang="ar" dir="ltr">`. `php -l` temiz; mojibake
temiz; JS sözdizimi denetlendi. Bu turda da kullanıcı sunucusu portu tutmuştu —
öldürülüp taze sunucuyla koşuldu (her turda port kontrolü rutini korundu).

**[!] Canlıya taşı:** `application/views/magaza/layout/head.php`,
`application/views/magaza/sepet/index.php`, `assets/magaza/{css/teksil.css,js/teksil.js}`,
`tests/regresyon.php`.

---

## 2026-08-20 (XLVI) — Stepper okları + sepet paket kuralı (floor) + renk çevirisi + sipariş ürün linki — 261/261 PASS

**Kullanıcı isteği:** "ürün sayfası adet inputunda oklar gereksiz (−/+ var)" + "sepet
inputu hâlâ +1 artıyor, kötüye kullanılabilir" + "katalog sidebar renkleri her dilde
Türkçe kalıyor" + "hesabim/siparis sayfasında ürün adı ürüne götürsün".

**Yapılan (kod tarafı):**
- **Stepper okları kalktı:** `.pd-stepper input` yerli okları CSS ile gizlendi
  (`-moz-appearance:textfield` + webkit pseudo); −/+ butonları tek kontrol.
- **Sepet paket kuralı (XLV revizyonu):** XLV'de round-yuvarlaması kalkınca ızgara
  tamamen serbest kalmıştı — sahibin isteği: **paket (adım) kuralı sunucuda
  ZORUNLU, ama FLOOR ile** (asla yukarı zıplama yok). `Sepet_model::ekle` VE
  `guncelle` artık adedi `moq + k*adım` ızgarasına `intdiv` ile OTURTUR: 9 yazımı
  adım-6 üründe 12 değil 6 olur; MOQ tabanı + varyant stok tavanı (son parti
  ızgara dışı kalabilir) aynen. Sepet inputu `step=adım` aldı (oklar artık
  paketlemtteb artar); ürün sayfası JS `snap()` de round→floor (sunucuyla birebir).
- **Renk çevirisi:** `renk_adi()` helper'ı (`renk_<ad>` dil anahtarı, yoksa ham
  ad). Katalog filtre sidebar'ı + ürün sayfası renk etiketleri (başlangıç değeri,
  swatch title/aria, JS'te `pdVeri.renk_ad` haritasıyla canlı güncelleme) aktif
  dilde. Filtre VALUE'ları ham kalır (DB eşleşmesi bozulmaz). 18 renk anahtarı
  4 dil dosyasına eklendi (DB'deki 12 + renk_hex setindeki yaygın 6).
- **Sipariş → ürün linki:** `Siparis_model::detay_slug_isaretle()` (paylaşımlı;
  `mg_getir` + `Bayi_model/Kullanici_model::mg_siparis_getir` çağırır — hesabım
  sayfası bayi modunda Bayi_model'den geliyordu, ilk denemede yanlış metoda
  konmuştu, test yakaladı). Satıştaki (durum=1, silinmemiş) ürünün adı linke
  döner; değilse düz metin.

**DB değişikliği:** yok.

**Doğrulama:** tam regresyon **261/261 PASS** (+3: `sepet-ekle-izgara-floor`
[ekle 9→6], `dil-en-renk-etiket` [EN katalog '>Black<' var, 'Siyah <small>' yok],
`bayi-siparis-detay-urun-link`; XLV'in `sepet-guncelle-art-bir-aynen` testi
`sepet-guncelle-izgara-floor` oldu [güncelle 9→6]). Canlı spot: EN katalog
'>Black <small>', pdVeri `renk_ad:{Beyaz:White,…Taş:Stone}`, CSS'te
appearance:textfield. `php -l` temiz; mojibake temiz; JS `new Function` ile
sözdizimi denetlendi. NOT: bu turda da kullanıcının htdocs'tan başlattığı bayat
sunucu port 8000'i tuttu (4 FAIL) — öldürülünce temiz; commit sonrası iki kopya
da eşitlendi (aşağıda).

**[!] Canlıya taşı:** `application/models/Sepet_model.php`,
`application/models/Siparis_model.php`, `application/models/Bayi_model.php`,
`application/models/Kullanici_model.php`, `application/helpers/teksil_helper.php`,
`application/views/magaza/{sepet/index.php,urun/detay.php,partial/filtre.php,
hesabim/siparis_detay.php}`, `assets/magaza/{css/teksil.css,js/teksil.js}`,
4× `application/language/*/teksil_lang.php`, `tests/regresyon.php`.

---

## 2026-08-20 (XLV) — Sepet +1 zıplaması + özet boşluğu + ürün sayfası stok tavanı — 258/258 PASS

**Kullanıcı isteği:** "sepet'te arttırınca bazen 6'şar artıyor" + "sepet-ozet-toplam'da
spanlar bitişik (Toplam1.518,00 ₺)" + "ürün sayfasında sayaç stok üstüne çıkmasın, uyarı versin".

**Yapılan (kod tarafı):**
- **Sepet +6 zıplaması (kök neden):** `Sepet_model::guncelle` adedi `moq + k*adim`
  ızgarasına **round ile yuvarlıyordu** — adım-6 üründe 6'dan 9 yazımı 12'ye
  zıplatıyordu. Yuvarlama KALDIRILDI: +1 artış aynen yazılır; MOQ tabanı ve varyant
  stok tavanı korunur. Adım, ürün sayfası stepper'ının görünen varsayılan artışı
  olarak yaşamaya devam eder (görünür kural, sessiz sürpriz yok). `ekle()`'de zaten
  ızgara yoktu — iki yol artık tutarlı.
- **`sepet-ozet-toplam` boşluğu:** CSS'e `display:flex;justify-content:space-between;
  align-items:baseline` eklendi (`.sepet-ozet-satr` ile hizalı). Tek kural; sınıfı
  kullanan 4 görünümü (sepet, odeme, odeme/basarili, hesabım sipariş detayı) TÜM
  DİLLERDE düzeltir — bitişiklik yapısal (yazılım çevirisi değil) olduğundan dile
  özgü işlem gerekmedi.
- **Ürün sayfası stok tavanı (XLV):** seçili varyantın stoğu sayaç tavanı olur —
  `+`/yazma ile stok üstü denemede sayaç stokta kalır, altında çevrilmiş uyarı
  çıkar ("En fazla N adet alabilirsiniz"); sayaç yanında "Stok: N adet" bilgisi
  görünür ve renk/beden değişiminde tazelenir. Stok yalnız varyant düzeyinde
  olduğundan (urun_varyantlari.stok; ürün düzeyi kolon yok) varyant seçili değilse
  tavan yoktur. Uyarı/bilgi metinleri `pdVeri` JSON'una aktif dilden taşınır.
- **Çoklu dil:** `detay_stok` + `detay_stok_ust` anahtarları 4 dil dosyasına eklendi
  (TR/EN/RU/AR).

**DB değişikliği:** yok.

**Doğrulama:** tam regresyon **258/258 PASS** (+4: `sepet-guncelle-art-bir-aynen`
9→9 aynen yazar [eski kodda 12 olurdu]; `sepet-guncelle-moq-tabani` 1→MOQ 6;
`sepet-guncelle-stok-tavani` 999999→stok; `sepet-guncelle-geri-6`). Ek canlı
doğrulama: ürün detayı `stokBilgi`/`adetUyari` elemanları + çevirili `pdVeri.metin`
render; CSS kuralı servis ediliyor. `php -l` temiz; mojibake temiz. NOT: ilk
regresyon koşusu kullanıcının htdocs kopyasındaki bayat sunucuya (port 8000 dolu,
`npm run dev` bağlanamadı) düştü — FAIL tam eski yuvarlamayı gösterdi; sunucu
yenilenince 258/258.

**[!] Canlıya taşı:** `application/models/Sepet_model.php`,
`assets/magaza/css/teksil.css`, `application/views/magaza/urun/detay.php`,
`assets/magaza/js/teksil.js`, 4× `application/language/*/teksil_lang.php`,
`tests/regresyon.php`.

---

## 2026-08-20 (XLIV) — Misafir siparişi kapatıldı: ödeme adımı giriş zorunlu — 254/254 PASS

**Kullanıcı isteği:** "giriş yapmayan kullanıcılar sipariş veremesin."

**Yapılan (kod tarafı):**
- **`Odeme` controller'a giriş guard'ı**: `__construct`'ta `_giris_zorunlu()`
  (Hesap deseninin aynısı) — bayi VEYA kullanıcı girişi yoksa flash hata +
  `kullanici/giris?donus=<uri>` yönlendirmesi. Tek guard tüm ödeme yüzeyini
  kapatır: `index`, `tamamla` (mağazadaki TEK sipariş oluşum girişi),
  `kupon_uygula/kaldir`, `basarili`.
- Sepet aşaması bilinçli SERBEST: misafir sepeti giriş anında hesaba devrolur
  (mevcut `_oturum_dondur`/`transfer_to_bayi` mekanizması) + `donus=odeme` ile
  kullanıcı ödeme adımına geri döner — akış kırılmaz.
- PayTR sahiplik kontrolüne dokunulmadı: bayi_id + session `son_siparis_id`
  bayi/kullanıcı akışını zaten kapsıyor; misafir artık `son_siparis_id`
  edinemez.
- `tamamla()`'daki e-posta zorunluluğu artık bayi-özel (misafir dalı ölü —
  yorum güncellendi, kod aynı); kullanıcının siparişi hesap e-postasına
  işlenmeye devam ediyor (XXV).
- **Çoklu dil:** `flash_odeme_giris_gerekli` anahtarı 4 dil dosyasına eklendi
  (TR/EN/RU/AR — `flash_odeme_sepet_bos_b`'nin ardına).

**DB değişikliği:** yok.

**Doğrulama:** tam regresyon **254/254 PASS** (+4: `misafir-odeme-giris-yonlendirme`
GET /odeme → login redirect; `misafir-odeme-tamamla-yonlendirme` geçerli görünümlü
POST bile guard'a düşer; `misafir-siparis-olusmadi` siparisler satır sayısı değişmez;
`misafir-odeme-flash-mesaj` login sayfasında mesaj görünür). `php -l` 6 dosya temiz;
mojibake temiz.

**[!] Canlıya taşı:** `application/controllers/Odeme.php`,
`application/language/{turkish,english,russian,arabic}/teksil_lang.php`,
`tests/regresyon.php`.

---

## 2026-08-19 (XLIII) — Ertelenenler: havale "ödendi" butonu + D2 rapor cilası — 250/250 PASS

**Kullanıcı isteği:** "ertelenenleri yap."

**Yapılan (kod tarafı):**
- **Havale "ödendi işaretle"** (XL'deki nüans kapatıldı): `Siparis_model::mg_odeme_isaretle`
  (odeme_durumu='odendi' + geçmiş; **idempotent** — zaten ödendiyse dokunma, yeni
  geçmiş satırı eklenmez) + `Siparisler::odeme_isaretle` POST eylemi + detay view'da
  "Ödeme Alındı" butonu (odeme_durumu !== 'odendi' iken). PayTR callback'ten BAĞIMSIZ
  (admin manuel kararı — havale/kapıda ödeme aldığında).
- **D2 rapor cilası:** `Rapor_model::satis_ozet` artık `iptal_oran` (iptal+iade_edildi /
  toplam %) + `pb_dagilim` (para birimi başına brüt ciro + sipariş) döndürür; CSV dışa
  aktarmaya bu iki metrik eklendi; raporlar view'a **hızlı tarih seçiciler** (Son 7/30/90
  gün, Bu ay, Bu yıl) + iade/iptal oranı stat kartı + para birimi dağılımı tablosu eklendi.

**Doğrulama:** tam regresyon **250/250 PASS** (+4: `admin-siparis-odeme-isaretle`,
`admin-siparis-odeme-idempotent`, `admin-rapor-satis-yeni-metrikler`,
`admin-rapor-csv-yeni-metrikler`). `php -l` temiz; mojibake temiz.

**Bloke (dış kaynak gerektirir — yapılamadı):**
- **C6 canlı Trendyol testi** — gerçek Trendyol supplier kimliği gerek; yerelde
  graceful-skip yolu zaten kanıtlı (cron `pazaryeri_senkron` → "aktif hesap yok").
- **D1 Hepsiburada/N11/Amazon adapter'ları** — her platformun API dokümanı + test
  kimliği gerek; kodda yalnız Trendyol adapter'ı var (diğerleri "adapter yok" graceful
  atlar).

**[!] Canlıya taşı:** `Siparis_model.php`, `yonetim/Siparisler.php`,
`views/yonetim/siparisler/detay.php`, `Rapor_model.php`, `yonetim/Raporlar.php`,
`views/yonetim/raporlar/index.php`, `tests/regresyon.php`.

---

## 2026-08-19 (XLII) — Admin panel: son 3 yazı akışı kapandı (Ayarlar/Yetkiler/Bayiler) — 246/246 PASS

**Kullanıcı isteği:** "sonraki adıma geç" (XLI'de bilinçli ertelediğim 3 admin
yazı eylemini kapat → admin panel denetimini tamamla).

**Yapılan:** `tests/regresyon.php` "G" bölümüne 3 yazı akışı eklendi:
- **Ayarlar `kaydet`** — 4 toggle'ın tamamı post edilir (kısmi post diğer
  toggle'ları sıfırlar — checkbox semantiği); `arama_index` çevrilip doğrulanıp
  geri alındı.
- **Yetkiler `kaydet`** (davranışsal) — rol-2 tam matrisi DB'den kurulup `stok`
  görüntüleme kapatıldı → admin2 `/yonetim/stok` **403** düştü → geri açıldı →
  **200**. Yazı yolunun izin uygulamasını gerçekten değiştirdiği uçtan uca kanıtlandı.
- **Bayiler `durum_guncelle` + `grup_guncelle`** — test bayisinin durumu 2'ye
  çekilip doğrulanıp geri alındı; grubu başka bir gruba değişip doğrulanıp geri alındı.

**Doğrulama:** tam regresyon **246/246 PASS** (+5). `php -l` + mojibake temiz.

Böylece admin panel denetimi TAMAMLANDI: 18 modülün her GET rotası + her yazı
eylemi (CRUD oluşturma/düzenleme/silme, sipariş durum yönetimi + kargolandı
takip-zorunlu, ayar, yetki matrisi, bayi durum/grup, para_birimi, feed anahtarı,
stok düzeltme) uçtan uca kanıtlandı.

**[!] Canlıya taşı:** `tests/regresyon.php`.

---

## 2026-08-19 (XLI) — Admin panel özellik taraması: tüm modüller + 1 bug kapandı — 241/241 PASS

**Kullanıcı isteği:** "admin panelindeki bütün özelliklerin doğru çalıştığına 100% emin ol."

**Yapılan:** `tests/regresyon.php`'ye "G) admin özellik taraması" bölümü eklendi — admin
olarak **her admin rotası GET'lendi** (dashboard, siparişler+detay, ürünler+ekle/düzenle,
kategoriler+düzenle, markalar+düzenle, stok+hareketler, bayiler+detay, faturalar+detay,
pazaryeri+detay, feed, xml_ice+log, raporlar+dışa_aktar, bannerlar+düzenle, sayfalar+
ekle+düzenle, kuponlar+ekle+düzenle, yazılar+düzenle, para_birimi, ayarlar, yetkiler) —
hepsi 200 + PHP fatal/render kırılması yok. **Yazı akışları** (oluştur→DB doğrula→sil→
silindi doğrula): Markalar, Sayfalar, Kuponlar, Bannerlar, Yazılar; Para_birimi
(kur güncelle+geri al, ekle→sil, uzun-kod reddi); Feed (api anahtarı oluştur→sil);
Stok (manuel düzeltme no-op+temizlik); Siparişler (durum güncelle: onaylandı+
geri al; kargolandı'da takip-no zorunlu reddi).

**Bulgu + onarım:** Para_birimi `kaydet` admin 4+ harf para kodu girince DB `CHAR(3)`
reddediyor ama flash yine "güncellendi" diyordu (sessiz başarısız + yanıltıcı onay).
Gardi eklendi: >3 harf kod atlanır + "N para birimi kodu geçersiz (en fazla 3 harf) —
atlandı" hata flash'ı basılır. Test: `admin-pb-uzun-kod-reddi` (UZUNKOD → eklenmez,
DB truncate etmez).

**Doğrulama:** tam regresyon **241/241 PASS** (+53: ~37 GET rotası + 16 yazı akışı/
durum kontrolü). `php -l` temiz; mojibake temiz.

**Bilinçli kapsam (yazı-test edilmedi; GET/render + okuma yolu kanıtlı):** Ayarlar
`kaydet` (ayar değişimi diğer testleri bozar; D0 whitelist+toggle E2E tarihsel
kanıtlı), Yetkiler `kaydet` (matris grid POST biçimi; rol-2 403/200 okuma yolu
testli), Bayiler `grup_guncelle`/`durum_guncelle` (B bölümü bayi hesabıyla iç içe).
Bunlar düşük-sıklık admin eylemleri; formları render olur, okuma yolları testli.

**[!] Canlıya taşı:** `application/controllers/yonetim/Para_birimi.php`, `tests/regresyon.php`.

---

## 2026-08-19 (XL) — Para tarafı denetimi: 3 bug + 1 sağlamlaştırma kapandı — 188/188 PASS

**Kullanıcı isteği:** "son kez projeyi iyice kontrol et, özellikle para tarafını."

**Denetlenen (sağlam bulundu):** PayTR callback — HMAC timing-safe (`hash_equals`)
+ **kuruş tutar çapraz kontrolü** (ödenen = `sipariş.toplam × kur × 100`, XXVI/XXVII
düzeltmesi) + idempotent + bilinmeyen sipariş reddi; get_token tutarı callback ile
aynı formül → tutarlı. Kupon — zaman/limit/min/max_indirim/ara'ya kelepçe (negatif
sipariş toplamı üretilemez), kullanım sayacı yalnız sipariş başarılı olunca artar.
Fiyatlar **tamamen sunucu-taraflı** (DB'den; POST yalnız adet taşır, MOQ+birim_adım
sunucuda uygulanır). Stok — koşullu düşüm + rollback (yarış güvenliği, XXVI),
iptal/iadede geri ekleme (çift-iade koruması). Yuvarlama — birim-yuvarla-sonra-çarp,
sepet↔sipariş birebir (kuruş düzeltmesi XXVII/VIII).

**Bulgu + onarım:**
1. **Para birimi tutarsızlığı (XXXIV entegrasyon gap'i):** sepet/ödeme görüntüsü
   `aktif_para_birimi()` (seçili teslimat ülkesi kazanır, yoksa bayi) kullanırken,
   `mg_olustur` sipariş snapshot para birimini yalnız bayi hesabından türetiyordu
   — ülke seçilince görüntü (EUR) ↔ kayıtlı sipariş (TRY) ayrışırdı, ödeme
   özetinin siparişle uyuşmaması. `mg_olustur` artık `aktif_para_birimi()` kullanır.
2. **KDV hardcoded 20:** `siparis_detaylari.kdv` daima 20 yazılıyordu;
   `urunler.kdv` kolonu yok sayılıyordu. `liste()` artık `u.kdv` seçer, detay ondan gelir.
3. **Kapalı ödeme yöntemi kabul:** `mg_olustur` `odeme_yontemleri` sorgusuna
   `durum=1` koymuyordu — kapalı/bilinmeyen kod POST'lanınca (form listelemez ama
   elle POSTlanabilir) sipariş yine oluşuyordu. Artık reddedilir.
4. **Sağlamlaştırma:** `_birim_fiyat` `max(0, ...)` ile zeminlendi — admin
   yanlış yapılandırmasında (basamak+grup indirim toplamı >%100) negatif birim
   fiyat düşmez.

**Doğrulama:** tam regresyon **188/188 PASS** (+3: `odeme-kapali-yontem-reddi`,
`siparis-detay-kdv-urun`=10, `kullanici-siparis-para-birimi-ulke`=EUR — ülke=de
seçiliyken sipariş snapshot'ı EUR düşüyor, görüntüyle birebir). `php -l` temiz;
mojibake temiz. Bilinen modelleme nüansı (bug değil): havale siparişlerinde
`odeme_durumu` 'bekliyor' kalır (admin `durum='onaylandi'` işaretler; bu bayrak
PayTR ödendi-sinyali özelinde) — manuel ödeme işaretlemesi istenirse ayrı admin
eylemi eklenir (Faz A kapsamında).

**[!] Canlıya taşı:** `Siparis_model.php`, `Sepet_model.php`, `tests/regresyon.php`.

---

## 2026-08-19 (XXXIX) — Birleşik hat kanıtı: taze-DB zinciri (22 dosya) + prod provası 185/185

**Kullanıcı isteği:** devam (canlıya taşıma hazırlığı; XXXVIII birleşiminden sonra).

**Yapılan:** (1) DEPLOY.md §3'ün **22 dosyalık birleşik zinciri** sıfır scratch
DB'de belgelenen sırayla koştu — 42 tablo; xml tabloları + rol-2 xml_ice yetkisi
+ footer/hukuki/yazilar seed'leri düştü; banner_dil/yazilar/kategori_dil
migrate'ları taze kurulumda bilgi mesajıyla zarif atlıyor (kolonlar schema.sql'de
— XXXVI tasarımı). (2) §0b yerel prod provası **birleşik hatta** yeniden koşuldu
(Apache + öz-imzalı HTTPS + `SetEnv CI_ENV production`): çerezler `secure;
HttpOnly` (CSRF Lax — XXIII hizalaması), `/sql/schema.sql` 403, **tam regresyon
185/185 PASS** (`https://localhost:8443 --insecure`; log denetimi dahil).
Teardown: Apache durduruldu, vhost yedeğinden geri alındı, kopya silindi.
(3) CLAUDE.md sayıları güncellendi (§3 21→22 dosya, regresyon 112→185).

**[!] Canlıya taşı:** yok (belge + prova; XXXVIII listesi geçerli).

---

## 2026-08-19 (XXXVIII) — Hat birleştirme + XML içe aktarım (tedarikçi feed'i → katalog) — 185/185 PASS

**Kullanıcı isteği:** Faz 5'e geç (XML içe aktarım — Faz 5'in tek eksik parçası;
API feed/pazaryeri/e-fatura/SMS/cron zaten kuruluydu) + push onayı. Push reddiyle
ortaya çıktı: repo ÇATALLANMIŞTI — 16-18 Ağustos işleri başka bir kopyada ilerleyip
GitHub'a itilmiş (XX–XXXVII: çoklu dil TR/EN/RU/AR + RTL, blog, SEO cilası,
güvenlik denetimleri, 167/167); bu yerel kopya 15-08 (XI)'te kalmıştı. Bugünkü
yerel işlerin XII (favori/toast) ve XVI (filtre) kısımları uzaktaki ecaca01'de
zaten vardı — atıldı; özgün değer (XML içe aktarım + kurulum zinciri düzeltmesi)
uzak satır üzerine yeniden uygulandı (yerel 5 commit yedek dalda:
`yedek-gun19-local`).

**Yeni:** `xml_kaynaklari` + `xml_loglari` (`sql/migrate_faz5_xml_ice.sql`) —
kaynak: URL + alan eşlemesi (JSON; NULL = kendi Xml_export biçimiz → api/Feed
çıktısı aynen geri alınabilir) + varsayılan kategori + fiyat çarpanı + yeni-ürün
izni. `Xml_ice_model`: URL çekme (yalnız http/https, 20 sn, 8 MB), çözümleme
(SimpleXML + eşleme, TR fiyat biçimleri), içe aktarım (transaction; **önizleme =
rollback'li kuru koşu**; stok_kodu anahtarlı güncelle/yeni; varyant eşleşmesi
sku → (renk,beden) PHP tarafında — QB'da COALESCE/LOWER alan ifadesi bozuk SQL
üretiyor). `yonetim/Xml_ice` (CRUD + önizleme + yalnız-POST gerçek aktarım + log)
+ 3 view + menü + `Yetki_model` sözlüğü + rol-2 seed'i **migrate_yetkiler.sql'de**
(yetkiler tablosu zincirde seed.sql'den SONRA yaratılır — xml migrate'ında koşsaydı
taze kurulum kilitlenirdi). `Cron::xml_ice_aktar` (calis() zincirine bağlı).

**Değişen:** `MY_Controller.php` (menü), `Cron.php`, `Yetki_model.php`,
`migrate_yetkiler.sql`, `DEPLOY.md` (§3 22 dosya + §6 cron), `tests/regresyon.php`
(+18 kontrol, E2 bölümü).

**Doğrulama:** tam regresyon **185/185 PASS** (167 uzak satır + 18 XML: kaynak
CRUD, URL hata yolu, kendi feed gövdesiyle geri-okuma simetrisi — log urun_sayısı
= feed'deki `<urun id=` sayısı, kuru koşu iz bırakmıyor, GET ile gerçek aktarım
reddi, yeni ürün + TR fiyat 12,34→12.34 + moq + 2 varyant, mevcut ürün güncelleme,
atlanan sayacı, idempotency, fiyat çarpanı ×1.25 → 123.45, temizlik). `php -l` temiz;
mojibake temiz. Dev DB zaten uzak satır durumundaydı (yazilar vb. mevcut); xml
tabloları bugün eklenmişti — uyumlu. Ders: Windows `php -S` tek iş parçacıklı —
sunucu içinden localhost'a curl KENDİNE kilitlenir; testler feed gövdesini
`xml_metin` ile verir.

**[!] Canlıya taşı:** `sql/migrate_faz5_xml_ice.sql` + `migrate_yetkiler.sql`
(yeniden koş — INSERT IGNORE xml_ice satırı ekler) + `Xml_ice_model.php`,
`yonetim/Xml_ice.php`, `views/yonetim/xml_ice/*`, `MY_Controller.php`,
`Cron.php`, `Yetki_model.php`, `tests/regresyon.php`, `DEPLOY.md`.

---

## 2026-08-18 (XXXVII) — D2 SEO cilası: sitemap'e blog+CMS + canonical + Product JSON-LD — 167/167

**Bağlam:** "sonraki adıma geç" — TAMAMLAMA_PLANI D2 (Rapor/SEO cilası) altından
somut SEO bitişi. Mevcut durum: sitemap.xml (anasayfa/katalog/kategori/ürün) ve
robots.txt (Disallow kuralları + Sitemap direktifi + arama_index duyarlılığı)
zaten vardı; blog (XXXV) sitemap'te yoktu, canonical ve yapısal veri hiç yoktu.

**Parçalar:**
- `Seo::sitemap()` — blog listesi + yayındaki yazılar (lastmod: updated_at →
  yayin_tarihi; table_exists guard) ve CMS sayfaları eklendi. CMS URL'leri
  footer'ın bağladığı kanonik biçimde: pretty route'u olanlar (iletisim,
  toptan-sartlari, xml-feed) kendi yolunda, diğerleri sayfa/{slug} — sitemap
  ile iç linkler tutarlı.
- `head.php` — tüm mağaza sayfalarında `<link rel="canonical">`:
  filtre/sıralama parametreleri (beden/renk/min/max/sira) atılır, sayfalama
  (sayfa) korunur — filtreli görünümler temiz kategori URL'sine, sayfa 2
  kendine işaret eder.
- `detay.php` — schema.org Product JSON-LD: ad, görsel (mutlak URL'e
  çözülür), sku, Offer (TRY bazlı fiyat — oturum para birimi yalnız görüntü
  dönüşümüdür, yapısal veri gerçek satış para biriminde), stok durumuna göre
  InStock/OutOfStock, açıklama varsa (500 karakter).
- robots.txt'e dokunulmadı — Sitemap direktifi zaten vardı.

**Bilinen sınır:** dil seçimi URL değil oturum/çerez tabanlı olduğundan
hreflang alternatifleri uygulanabilir değil — arama motorları varsayılan dili
(TR) indeksler. URL tabanlı locale gerekirse ileride ayrı artım.

**Doğrulama:** lint; tam regresyon +7 (`seo-sitemap-200`, `seo-sitemap-blog`,
`seo-sitemap-cms`, `seo-sitemap-pasif-urun-yok`, `seo-kanonik-detay`,
`seo-detay-jsonld`, `seo-kanonik-filtre-atilir`) → **167/167**. Test INSERT'i
ilk koşuda `stok_kodu` (NOT NULL, defaultsuz) yüzünden fatal attı — kolon
eklendi.

**[!] Canlıya taşı:** `controllers/Seo.php`, `views/magaza/layout/head.php`,
`views/magaza/urun/detay.php`, `tests/regresyon.php`. DB değişikliği yok.

---

## 2026-08-18 (XXXVI) — Sıfır-DB provası: §3 dizisinde 4 hata kapatıldı + meta çoklu dil gap'i + test self-sufficiency — 160/160 (sıfır-DB ve dev)

**Bağlam:** §3 kurulum listesi bu oturumda 17→21 dosyaya büyüdü (4 yeni
migration + yeniden yazılan seed_slider); son sıfır-DB provası çoklu dil
işlerinden önceydi (74/74). Scratch DB'ye tam §3 dizisi uygulanıp 160 testlik
regresyon ona karşı koşuldu — **prova 7 bulgu verdi, hepsi kapatıldı:**

1. `migrate_banner_dil.sql` — taze §3'te "Duplicate column 'dil'" (kolon
   schema.sql'de zaten var; ALTER koşulsuzdu) → information_schema + PREPARE
   guard'ı (ALTER yalnız eksikse koşar).
2. `migrate_kategori_dil.sql` — aynı sorun (ad_en/ad_ru/ad_ar) → aynı guard.
3. `migrate_yazilar.sql` — yetkiler INSERT'i §3'te henüz OLMAYAN tabloya
   yazıyordu (migrate_yetkiler seed'den SONRA koşar) → tablo-var guard'ı +
   satır migrate_yetkiler tohumuna da eklendi (taze kurulum yolu).
4. §3 sırası — migrate_kategori_dil'in UPDATE'leri seed'in kategorilerine
   işler; 14. sırada koşunca tablo boştu → 12 çeviri hiç yazılmıyordu →
   DEPLOY.md §3'te seed'den sonraya taşındı (dev DB'de kategoriler zaten
   vardı, o yüzden yakalanmamıştı).
5. PayTR testleri — `UPDATE ayarlar … paytr_*` scratch'te olmayan satırları
   güncelliyordu (seed etmez) → UPDATE 0 satır → hash 'bad hash'e düşerdi →
   INSERT … ON DUPLICATE KEY UPDATE (test artık her DB'de self-sufficient).
6. **Meta çoklu dil gap'i (ürün hatası):** seed.sql meta_title/
   meta_description'ı TR metinle tohumluyordu → taze kurulumda HER DİL
   Türkçe meta alıyordu (dev'de satırlar tarihsel olarak boşaldığından
   görünmüyordu). Anasayfa artık DB override'u boşsa
   `t('meta_title_default')` / `t('meta_desc_default')` ile dile göre düşer;
   seed değerleri boş tohumlanır (admin doldurursa tüm dillerde override);
   `meta_title_default` anahtarı ×4 eklendi.
7. **Koşum dersi:** REGRESYON_DB env'i verilmeden koşulursa q()/q1()
   varsayılan `teksilsite`'e bakar — sunucu scratch'i gösterirken 37 yanıltıcı
   FAIL üretir. Prova komutu: `REGRESYON_DB=<scratch> php tests/regresyon.php`
   (dosya başlığında zaten belgeliydi; uygulama unutulmuş).

**Sonuç:** §3 (21 dosya) scratch DB'ye temiz kuruluyor (40 tablo; 12 banner ×
4 dil, 6 para birimi ₺=E282BA, 12 kategori çevirisi, 3 demo yazı, rol-2
yazilar yetkisi, footer+hukuki sayfalar); **sıfır-DB provası 160/160**; config
geri alınınca **dev 160/160**. Yetim süreç, scratch DB ve geçici dosyalar
temizlendi.

**[!] Canlıya taşı:** `sql/migrate_banner_dil.sql`, `sql/migrate_kategori_dil.sql`,
`sql/migrate_yazilar.sql`, `sql/migrate_yetkiler.sql`, `sql/seed.sql`,
`application/controllers/Anasayfa.php`, `application/language/` (4),
`tests/regresyon.php`, `DEPLOY.md` (§3 sırası). Mevcut canlı kurulumlarda ek
işlem gerekmez (guard'lar idempotent; meta seed'i yalnız taze kurulumu
etkiler — canlıda meta doluysa override olarak kalır).

---

## 2026-08-18 (XXXV) — D3 blog: yazilar tablosu + admin CRUD + vitrin liste/detay — 160/160

**İstek (kullanıcı):** "Bilinçli ertelenmişlerden birine geçebilirsin." —
TAMAMLAMA_PLANI D3 (Blog — Sayfa.php stub) seçildi; D2 cilalar ihtiyaç
halinde, C6/D1 canlı kimlik gerektiriyor (İş tarafı).

**DB:** `yazilar` tablosu (slug UNIQUE, baslik, ozet, icerik MEDIUMTEXT,
gorsel, durum, yayin_tarihi) — `sql/schema.sql` + `sql/migrate_yazilar.sql`
(rol-2 "Yönetici" yetkisi + 3 demo yazı; INSERT IGNORE ile re-çalıştırma
güvenli). DEPLOY.md §3 → **21 dosya**. Dev DB'ye uygulandı.

**Admin:** `yonetim/Yazilar.php` — index (?duzenle) / kaydet / sil; Bannerlar
deseni. Slug boşsa başlıktan `slug_tr()` ile; benzersiz değilse -2, -3…
(kendisi hariç). Özet boşsa içerikten ilk 500 karakter. Kapak görseli yalnız
dış https URL (banner XXVIII kuralı — dosya yükleme bu modülde yok). İçerik
admin HTML'idir (CMS sayfaları deseni — vitrinde raw, güvenilir kaynak).
Menüye "Blog Yazıları" + `Yetki_model::$MODULLER` sözlüğüne eklendi (yetki
matrisi satırı otomatik belirir).

**Vitrin:** `Sayfa::blog()` — yayındaki yazılar (yayin_tarihi DESC, NULL'lar
sonda; db_hazir + table_exists guard). `Sayfa::yazi($slug)` — yalnız
yayındakiler, CMS deseni show_404 + meta_desc = özet (ilk 300); blog liste ve
detay artık indexlenebilir (stub'daki noindex kalktı — SEO). Yeni rota
`blog/(:any)`; utility bardaki mevcut Blog linki listeye, kartlar detaya
bağlanır. Görünüm: kart grid'i (blog-grid/blog-kart stilleri) + detay (prose,
kapak, kırıntı). 4 yeni dil anahtarı ×4 (yazi_oku/yok/yayin/bloga_don; AR'de
yön oku ←).

**Doğrulama:** lint (dokunulan her dosya); parite 4×389 (yazımda karışan 1
U+FFFD AR 'yazi_yok'ta yakalandı+düzeltildi); tam regresyon +6
(`blog-liste-dolu`, `blog-detay-200`, `blog-detay-404`,
`yazi-admin-kaydet-redirect`, `yazi-admin-vitrinde`, `yazi-pasif-404` —
admin E2E: kaydet → vitrinde → pasifleştir → 404 → test verisi silindi) →
**160/160**.

**Kapsam notu:** yazı başlıkları/içerikleri DB'de TR kalır (ürün/CMS deseni);
çoklu dil içerik girişi gerekirse kategoriler gibi ad_en/… kolonlu artım olur.

**[!] Canlıya taşı:** `yonetim/Yazilar.php` (yeni), `views/yonetim/yazilar/`
(yeni), `views/magaza/sayfa/{blog,yazi}.php`, `controllers/Sayfa.php`,
`config/routes.php`, `core/MY_Controller.php`, `models/Yetki_model.php`,
`assets/magaza/css/teksil.css`, `application/language/` (4),
`tests/regresyon.php`, `sql/schema.sql` + **DB: `sql/migrate_yazilar.sql`
uygula**.

---

## 2026-08-18 (XXXIV) — Teslimat ülkesi seçici: dil menüsü altında ülke → ürün para birimi — 154/154

**İstek (kullanıcı):** "div.dil-sec__menu'nun en altında kullanıcı teslimat
ülkesini seçebilsin. kullanıcının seçtiği teslimat adresine göre ürünlerin
para birimi değişsin."

**Çözüm mimarisi:** Mevcut çoklu-para altyapısına (para_birimleri + kur_try +
sepet/sipariş snapshot) tek noktadan bağlandık — `aktif_para_birimi()`
yeniden yazıldı: açıkça seçilmiş teslimat ülkesi (Türkiye dahil) kazanır;
seçilmemişse bayi hesap para birimi; o da yoksa TRY. Sepet, checkout ve
sipariş kur snapshot'ı bu değeri kullandığından tüm zincir otomatik uydu.

**Parçalar:**
- `teksil_helper`: `ulke_listesi()` (10 ülke → TRY/EUR/USD/GBP/RUB/AED statik
  eşleme + bayrak), `ulke_secili()` (oturum → çerez; NULL = seçilmemiş),
  `aktif_ulke()`, `ulke_para_birimi()`, `para_sembol()`; TRY sıfır-DB
  fallback sembolü 'TL'→'₺' hizalandı.
- `controllers/Ulke.php` (yeni): Dil::cevir deseni — oturum + 1 yıllık çerez,
  beyaz liste, geçersiz → 'tr', yalnız aynı-site referere dönüş.
- `layout/header.php`: dil-sec__menu alt bölümü — ayraç + "Teslimat Ülkesi"
  başlığı + ülke linkleri (bayrak + çevrili ad + sembol); CSS ayraç/başlık/
  sembol stilleri; 11 yeni dil anahtarı ×4 (ulke_baslik + 10 ülke adı).
- Fiyat gösterimi `para_tr` → `para_goster`: urun_karti (3), favorilerim (3),
  detay (fiyat/eski/toplam/birim + kargo eşiği ×2) + Odeme kupon flash'i.
- `detay.php` pdVeri'ye `kur`+`sembol`; `teksil.js` pd `fmt()` artık `n/kur`
  + aktif sembolle biçimliyor (adet/basamak matematiği TRY kalır).
  Checkout modülündeki ikinci `fmt` ölü kod (`.odeme-form` markup'ı hiçbir
  view'da yok) — dokunulmadı.
- Fiyat filtresi: `filtre.php` etiketi `(%s)` + aktif sembol (4 dil değeri
  %s kalıbına çevrildi); `Katalog::_liste` min/max'ı vitrin para biriminden
  kur ile TRY'ye çevirir — kullanıcı gördüğü para biriminde filtreler.
- `sql/migrate_ulke_para.sql`: GBP/RUB/AED seed (kur placeholder — admin Para
  Birimi panelinden güncellenir; re-seed mevcut kur'u ezmez). DEPLOY.md §3 →
  20 dosya.

**Kaza/ders ×2:** (1) Dev DB'de TRY sembolü 'TL' kalmış — ₺ konvansiyonuna
hizalandı; ilk düzeltmede bash→mysql CLI argümanında UTF-8 kırıldı (sembol
0x3F '?' oldu) — onaltılık bayt literaliyle (`CONVERT(UNHEX('E282BA') USING
utf8mb4)`) düzeltildi. Ders: CLI argümanına UTF-8 dizgi geçme; dosya ya da
hex kullan. (2) Header ülke dropdown'unda semboller boşluksuz göründüğü için
"sayfada ₺ yok" iddialı testler yanlış kırıldı — iddialar para-biçimi
ayrımına çekildi (' ₺' boşluklu fiyat biçimi vs `<small>₺</small>`).

**Doğrulama:** lint (dokunulan her dosya); parite 4×385 anahtar, 0 FFFD; DB
6 para birimi doğru UTF-8 sembollerle (HEX ile doğrulandı); tam regresyon +4
(`ulke-sec-redirect`, `ulke-katalog-usd`, `ulke-detay-usd`,
`ulke-gecersiz-tr-geri`) → **154/154**.

**[!] Canlıya taşı:** `teksil_helper.php`, `controllers/Ulke.php` (yeni),
`controllers/Katalog.php`, `controllers/Odeme.php`,
`views/magaza/layout/header.php`, `views/magaza/partial/urun_karti.php`,
`views/magaza/partial/filtre.php`, `views/magaza/urun/detay.php`,
`views/magaza/sayfa/favorilerim.php`, `assets/magaza/css/teksil.css`,
`assets/magaza/js/teksil.js`, `application/language/` (4), `tests/regresyon.php`
+ **DB: `sql/migrate_ulke_para.sql` uygula** (GBP/RUB/AED) — ve canlıda TRY
sembolünü '₺' yap (panel ya da `UPDATE ... CONVERT(UNHEX('E282BA') ...)`).

---

## 2026-08-18 (XXXIII) — Dil-bazlı slider setleri (demo seed ×4) + footer kategoriler DB'den + kalan footer dizgileri — 150/150

**İstek (kullanıcı):** (1) "there are the same sliders for every language and
I don't want that. I want there to be sliders for each language" — demo
slider'lar dil=NULL'du → her dilde aynı Türkçe set görünüyordu. (2) Footer'ın
`footer__grid` 2. div'i (Kategoriler sütunu) İngilizce'de hâlâ Türkçe.

**Slider:** `sql/seed_slider.sql` yeniden yazıldı — 3 slayt × 4 dil
(tr/en/ru/ar; başlık/alt başlık/buton metni çevrili, sira her dilde 0-1-2).
Dev DB'ye uygulandı (4 dil × 3 banner doğrulandı). NULL = "tüm diller"
davranışı admin için bilinçli seçenek olarak kalır; vitrin sorgusu değişmedi
(XXX'ten: `dil = aktif_dil() OR dil IS NULL`).

**Footer:**
- Kategoriler sütunu artık DB menüsünden render ediliyor (`$menu` →
  `mg_menu()` → `kategori_ad()`) — adminin kategori adı değişikliği ve dil
  çevirileri footer'a otomatik yansır; eskiden 5 link elle sabitti.
- `Toptan Şartlar (MOQ)` → `ftr_toptan_sartlar`; yukarı-çık düğmesi
  aria-label/title → `ftr_yukari_aria` / `ftr_yukari`.

**Dil dosyaları:** 4 dosya × **374 anahtar** (+3).

**Doğrulama:** lint + parite (4×374, 0 FFFD); seed dev DB'de 4×3 doğrulandı;
tam regresyon +3 (`slider-en-kendi-seti` [EN vitrinde EN slayt başlığı VAR,
TR başlık YOK], `slider-tr-kendi-seti` [TR vitrinde TR VAR, EN YOK],
`ftr-en-cevrildi` [EN footer'da 'Wholesale Terms (MOQ)' VAR, 'Toptan
Şartlar' YOK]) → **150/150**.

**[!] Canlıya taşı:** `sql/seed_slider.sql` (yeniden uygula — eski NULL seti
silip dil-bazlı setleri kurar), `views/magaza/layout/footer.php`,
`application/language/` (4), `tests/regresyon.php`. DB şema değişikliği YOK
(migrate_banner_dil XXX'ten zaten var).

---

## 2026-08-18 (XXXII) — CI3 doğrulama mesajları çoklu dil: form_validation ×4 + etiketler t() — 147/147

**İstek (kullanıcı):** XXXI kapanışında not edilen son çeviri boşluğu — form
doğrulama hataları her dilde İngilizce CI3 mesajı + Türkçe alan etiketi
görünüyordu ("The Teslimat adı field is required").

**Altyapı:**
- `application/language/{turkish,english,russian,arabic}/form_validation_lang.php`
  — CI3 çekirdek mesajları (~29 satır × 4 dil). Sistem yalnız english
  gönderir; uygulama klasöründen yüklenmesi garanti altına alındı.
- `config.php`: `$config['language'] = 'turkish'` (önceki 'english' — admin
  paneli ve varsayılan artık Türkçe mesaj alır).
- `MY_Controller::_ortak_veri()`: aktif dile göre
  `config->set_item('language', dil_klasor(aktif_dil()))` — form_validation
  kütüphanesi controller metodlarında yüklendiğinden dil atamasından sonra
  gelir (autoload'da form_validation yok — doğrulandı).
- 35 `set_rules` etiketi t()'ye sarıldı (Bayi/Kullanici/Odeme/Hesap; etiketler
  mevcut odeme_*/auth_*/hesap_* anahtarlarını yeniden kullanır) + 3 özel
  mesaj yeni `val_*` anahtarlarıyla (val_sozlesme_uyelik, val_kuladi_kural,
  val_sozlesme_odeme).

**Dil dosyaları:** 4 dosya × **371 anahtar** (+3).

**Kaza + kurtarma:** config.php'yi değiştiren ilk preg_replace denemesi regex
derleme hatasıyla NULL döndürdü ve NULL dosyaya yazıldı → dosya boşaldı
(0 bayt; boş dosya php -l'den geçiyor!). `git restore` ile geri yüklendi
(7587 bayt), değişim sayım-doğrulamalı str_replace ile uygulandı. Ders:
php -l içerik doğrulaması değildir.

**Doğrulama:** lint ×10; parite 4×371 (0 FFFD — RU/AR form_validation
dosyalarına yazarken karışan 2+2 U+FFFD yakalandı+düzeltildi); autoload
kontrolü; tam regresyon +2 (`validation-en-mesaj` [boş POST /bayi/kayit_kaydet
EN'de 'The Full Name field is required.' + Türkçe kalıntı yok],
`validation-tr-mesaj` [TR'de 'Ad Soyad alanı zorunludur.' + İngilizce kalıntı
yok]) → **147/147**.

**[!] Canlıya taşı:** `application/language/*/form_validation_lang.php` (4 yeni
dosya), `application/config/config.php` (language=turkish), `MY_Controller.php`,
controllers (Bayi, Kullanici, Odeme, Hesap), `tests/regresyon.php`.
DB değişikliği YOK.

---

## 2026-08-18 (XXXI) — Mağaza çeviri katmanı 3: kalan tüm yüzeyler + kategori adları çoklu dil — 145/145

**İstek (kullanıcı):** "some parts aren't translated" — XXX'ten sonra mağazada
hâlâ Türkçe kalan tüm yüzeyler (ürün detayı, arama, giriş/kayıt, hesabım, ödeme
sonuç sayfaları, yardım/takip/favoriler/blog, flash mesajları, sayfa başlıkları)
ve DB kategori adları (üst menü).

**Kategoriler çoklu dil (DB):**
- `kategoriler` + `ad_en/ad_ru/ad_ar VARCHAR(120) NULL` — `sql/schema.sql` +
  `sql/migrate_kategori_dil.sql` (standart 12 kategoriye hazır çeviriler; dev
  DB'ye uygulandı). DEPLOY.md §3 → **19 dosya**.
- `dil_helper::kategori_ad($k)` — aktif dilin kolonu; boşsa/kolonsa TR fallback.
- `kategori_model`: `mg_menu()` + `mg_ust_yol()` çevirili ad döner (dil
  yardımcısı metod içinde yüklenir); `Katalog::_liste` başlık+meta,
  `Urun::detay` `$u->kategori_adi` override.
- Admin Kategoriler formunda EN/RU/AR ad alanları (boşsa mağazada TR).

**Görünüm (t() sarması, ~25 dosya):** `urun/detay.php`, `partial/urun_karti.php`,
`arama/index.php`, `bayi/giris+kayit`, `kullanici/giris+kayit`, `hesabim/*` (9
dosya), `odeme/basarili+paytr+paytr_basarisiz`, `sayfa/yardim+siparis_takip+
favorilerim+blog+sayfa`, `layout/header.php` statik fallback menü. Bağlam içi
linkli cümleler printf %1$s/%2$s kalıbıyla (sözleşme onayı, SSS cevapları).

**Controller/model katmanı:**
- Flash mesajları (26 anahtar): Sepet/Odeme/Bayi/Kullanici/Hesap + bayi_model/
  kullanici_model kayıt mesajları (modellerde function_exists('t') guard'ı).
- Sayfa başlıkları: 9 controller'daki 19 sabit `meta_title` t()'ye sarıldı
  (sekme başlığı + SEO). Anasayfa/ürün/CMS başlıkları ayar/DB kaynaklı — TR kalır.
- `teksil_helper::durum_etiket()`: 8 sipariş durumu çevrilir (guard'lı — admin'de
  TR); fatura durumları view-side `fdurum_*`. `siparis_takip` ödeme durumu
  `t('odurum_' . değer, değer)` dinamik fallback.

**Dil dosyaları:** 4 dosya × **368 anahtar** (135 + 233 yeni). AR'de ileri-yönlü
dizgilerde ←.

**Doğrulama:** lint (dokunulan her dosya) + anahtar parite (4×368; koddaki 359
statik anahtarın hepsi tanımlı; `durum_`/`odurum_` dinamik önekler bilinçli);
mojibake byte-grep — yazım sırasında karışan 4+6+1 U+FFFD + 1 bozuk anahtar adı
(`hesap_merhabا`→`hesap_merhaba`) yakalanıp düzeltildi (4 dosyada 0 FFFD);
printf→sprintf hatası (dönüş uzunluğu sayfaya sızıyordu) 5 view'da düzeltildi.
Tam regresyon **+9** (`dil-en-kategori-baslik`, `dil-en-urun-detay`,
`dil-en-arama`, `dil-en-bayi-giris`, `dil-en-header-menu`,
`kategori-admin-kaydet-redirect`, `kategori-admin-dil-kayitli`,
`kategori-vitrin-en-baslik`, `kategori-vitrin-tr-baslik`) → **145/145**; E2E
kategori admin kaydetme (ad_en DB'ye, EN vitrinde EN ad, TR vitrinde TR ad;
test verisi temizlendi); manuel AR: kategori h1 DB `ad_ar` ile birebir + ürün
detayı Arapça.

**Kapsam notu:** DB içerik (ürün adları/açıklamaları, CMS sayfaları, ayarlardan
gelen anasayfa title/meta) ve yönetim paneli bilinçli TR kalır. CI3 validation
hata mesajları (set_rules etiketleri) TR — sonraki artım adayı.

**[!] Canlıya taşı:** `application/language/` (4), `dil_helper.php`,
`teksil_helper.php`, `kategori_model.php`, controllers (Katalog, Urun, Sepet,
Odeme, Bayi, Kullanici, Hesap, Arama, Sayfa, yonetim/Kategoriler), models
(bayi_model, kullanici_model), `views/magaza/` (urun, partial, arama, bayi,
kullanici, hesabim, odeme, sayfa, layout/header), `views/yonetim/kategoriler/`,
`tests/regresyon.php`, `sql/schema.sql` + **DB: `sql/migrate_kategori_dil.sql`
uygula**.

---

## 2026-08-18 (XXX) — Mağaza artımlı çeviri katmanı 2: anasayfa/katalog/sepet/odeme + banner dil filtresi — 136/136

**İstek (kullanıcı):** XXIX kabuk çevirisinin devamı — çalışma ağacında yarım
kalmış işi (anasayfa/katalog/sepet t() sarmaları, odeme'de yarım satır, banner
`dil` sorgusu) eksikleriyle tamamla: dil dosyaları, banner schema/migration,
admin Bannerlar dil seçimi, doğrulama.

**Görünüm (`application/views/magaza/`):**
- `anasayfa.php` — kategori vitrini/yorumlar/istatistik/valuestrip/slider aria/
  bölüm başlıkları/CTA t()'ye sarıldı; slider sorgusu `dil = aktif_dil() OR
  dil IS NULL` (group_start ile).
- `katalog/index.php` — sıralama seçenekleri/kirinti/ürün sayısı/topbar/boş durum.
- `partial/filtre.php` — beden/renk/fiyat başlıkları + min/maks placeholder'ları;
  `partial/sayfalama.php` — 3 aria etiketi (katalog sayfası bütün çevrildi).
- `sepet/index.php` — tablo başlıkları/MOQ notu/özet satırları/düğmeler;
  `'Toplam '` fazladan boşluğu giderildi.
- `odeme/index.php` — dünkü yarım edit tamamlandı: kirinti, teslimat/fatura
  alanları, kargo&ödeme, ödeme yöntemi + ek ücret `(%s ek)`, banka hesapları,
  sözleşme, sipariş özeti, kupon bloğu (kullanıcı verisi sprintf sonrası `e()`);
  stray `"Ara toplam "` boşluğu düzeltildi.

**Dil dosyaları (`application/language/*/teksil_lang.php`):** 4 dosya × **135
anahtar** (26 kabuk XXIX + 109 yeni: anasayfa 37, katalog+filtre+sayfalama 21,
sepet 21, odeme 30). AR'de ileri-yönlü eylem dizgilerinde → yerine ← (RTL'de
"ileri" solda); JS confirm dizgilerinde (`sepet_sil_onay`) apostrof yasak.

**Banner dil filtresi:** `bannerlar.dil VARCHAR(2) NULL` (NULL = tüm diller).
Admin `Bannerlar.php` kaydet beyaz listeli (`tr/en/ru/ar`, geçersiz/boş → NULL);
formda dil selecti + listede rozet. DB: `sql/schema.sql` (taze kurulum) +
`sql/migrate_banner_dil.sql` (mevcut kurulumlar; dev DB'de kolon zaten vardı);
DEPLOY.md §3 listesine eklendi → **18 dosya**.

**Kapsam notu:** DB içerik (kategori/ürün/CMS/banner metinleri) ve controller
mesajları (validation/flash) bilinçli Türkçe kalır; yönetim paneli yalnız
Türkçe. Sonraki artımlar: urun detay, arama, hesabım, bayi kayıt/giriş,
sipariş takibi, favoriler, ödeme sonuç sayfaları.

**Doğrulama:** lint ×15; mojibake byte-grep (RU/AR'ye yazarken karışan 2+2
adet U+FFFD yakalandı + düzeltildi → 4 dosyada 0); anahtar parite kontrolü
(4×135, view'lerdeki 135 t() çağrısının hepsi tanımlı); tam regresyon **+8**
(`dil-en-anasayfa` [DB menüsü Türkçe kalabildiği için absent-check 'Kategorilere
göz atın' statik dizgisi üzerinden], `dil-en-katalog`, `dil-en-sepet`,
`banner-dil-en-gizli`, `banner-dil-ru-gorunur`, `banner-admin-kaydet-redirect`,
`banner-admin-dil-kayitli`, `banner-admin-gecersiz-dil-null`) → **136/136**;
manuel: AR → `dir="rtl"`+`lang="ar"`+Arapça dizgiler (anasayfa/sepet), RU →
katalog tamamen Rusça. E2E admin banner kaydetme → DB `dil='en'`, geçersiz kod
→ NULL (suite içinde + test verisi temizlendi).

**[!] Canlıya taşı:** `application/language/` (4 dosya), `anasayfa.php`,
`katalog/index.php`, `sepet/index.php`, `odeme/index.php`, `partial/filtre.php`,
`partial/sayfalama.php`, `controllers/yonetim/Bannerlar.php`,
`views/yonetim/bannerlar/index.php`, `tests/regresyon.php`, `sql/schema.sql`,
DEPLOY.md/CLAUDE.md (§3 sayısı) + **DB: `sql/migrate_banner_dil.sql` uygula**.

---

## 2026-08-17 (XXIX) — Mağaza çoklu dil seçici: TR varsayılan, EN/RU/AR (kabuk çevirisi + RTL) — 128/128

**İstek (kullanıcı):** utility bar'da "Sipariş Takibi"nin yanına dil seçici;
EN/RU/AR; varsayılan daima TR.

**Altyapı:**
- `application/helpers/dil_helper.php`: `aktif_dil()` (oturum → çerez → 'tr';
  kod beyaz listeli) + `t($anahtar, $trFallback, …sprintf)`. Önce TR dosyası
  yüklenir, aktif dil üstüne yazar → **çevrilmemiş anahtar Türkçe kalır**;
  görünüm katmanı artımlı çevrilir, sayfa asla çevirisiz/boş kalmaz.
- `application/language/{turkish,english,russian,arabic}/teksil_lang.php` —
  kabuk dizgileri (~26 anahtar: utility bar, header eylemleri, arama, footer,
  meta açıklama).
- `Dil::cevir/{kod}` — oturum + 1 yıllık çerez (`teksil_dil`, prod Secure
  bayrağı config'ten); geri dönüş yalnız aynı-site referer (ref_ic deseni).
  Geçersiz kod → TR.
- `_ortak_veri()` dil yardımcısını yükler (`$v['dil']`, `$v['dil_adi']`).
- Seçici: `<details class="dil-sec">` — JS'siz, erişilebilir native dropdown;
  mevcut dili gösterir, dört seçenek, aktif işaretli. Mobil gizleme kuralı
  güncellendi (yalnız Blog gizlenir — önceki davranış korundu).
- AR → `<html lang="ar" dir="rtl">` (head.php dinamik); `[dir="rtl"]` dropdown
  hizası CSS'te.

**Kapsam notu:** Çevrilen yüzey = mağaza kabuğu. Kategori/ürün/CMS içerikleri
DB'den Türkçe kalır (bilinçli — artımlı çeviri yolu açık); yönetim paneli
bilinçli olarak yalnız Türkçe.

**Doğrulama:** lint ×11 + mojibake temiz (XVII'den kalma bir footer yorum
bozulması da düzeltildi); tam regresyon +5 (`dil-varsayilan-tr`,
`dil-secici-menusu`, `dil-gecis-redirect`, `dil-en-etkin` [EN'de 'Order
Tracking' var, 'Sipariş Takibi' yok], `dil-gecersiz-tr-doner`) → **128/128**;
manuel: AR çerezi → `dir="rtl"` + Arapça dizgiler, RU çerezi → Rusça,
çerezsiz → TR/ltr.

**[!] Canlıya taşı:** `dil_helper.php`, `application/language/` (4 dosya),
`Dil.php`, `MY_Controller.php`, `header.php`, `head.php`, `footer.php`,
`teksil.css`, `tests/regresyon.php`.

---

## 2026-08-17 (XXVIII) — Denetim ajanları kapanışı: SVG yükleme kapandı + banner dosya-silme path-traversal kapatıldı + sertleştirmeler — 123/123

**Kapsam:** iki paralel tarama ajanı — (1) XSS/raw-çıkış: 70 view + teksil.js,
889 PHP çıktısı, 113 ham echo tamamı kaynağına izlendi; (2) yönetim
IDOR/yetki/SQL: 18 controller + model katmanı + upload'lar. Yetki matrisi
tutarlı, ham SQL sıfır, eksik yetki-check yok.

**Bulgu 1 (orta) — Markalar SVG yükleme (stored-XSS vektörü):** sanitizasyonsuz
SVG `uploads/markalar/`'a yazılıyordu; .htaccess PHP'yi engeller ama SVG'in
kendi `<script>`'ini doğrudan URL'den açınca site origin'inde çalışır. svg
whitelist'ten çıkarıldı (+getimagesize svg-isti'si kalktı). **E2E kanıt:**
script'li .svg admin olarak yüklendi → kayıt işlendi (303) ama dosya diske
yazılmadı (klasör 0 dosya) + DB'de logo boş.

**Bulgu 2 (orta) — Bannerlar serbest dosya silme (path traversal):** `gorsel_url`
serbest POST + `_yerel_gorsel_sil` DB'den gelen yolla unlink ediyordu → '../'
ile docroot dışı dahil rastgele dosya silme. Kapatma: dış URL yalnız `https://`;
silme realpath ile `uploads/` altına hapsoldu (`Urunler::gorsel_sil`'e aynı
kapatma).

**Sertleştirmeler:** feed kartındaki onclick-JS-string gömmesi → data-attribute
deseni; `donus` üç yerde (yonetim Giriş + Kullanici + Bayi) karakter beyaz
listesine bağlandı ('https:/evil.com' tek-slash açık-yönlendirme baypası
kapandı); `gorsel_ana` görselin ürününe aidiyetini doğrular (A'nın ana görseli
B yapılamaz); 5 yerde slug'lı `site_url()` çıktıları `e()` ile sarıldı.

**Kabul edildi (bilinçli, dokümante):** CMS ham HTML çıktısı (admin yazarlı,
view'da yorumlu); Dashboard'ın tüm rollere iş verisi göstermesi (İş kararı);
controller katmanı (int) cast eksikleri (model katmanı kapatıyor); CI'nin
kendi hata view'ları.

**Doğrulama:** lint ×16 temiz; E2E SVG reddi; tam regresyon **123/123**.

**[!] Canlıya taşı:** `Markalar.php`, `Bannerlar.php`, `Urunler.php`,
`Giris.php` (yonetim), `Kullanici.php`, `Bayi.php`, `feed/index.php`,
`sepet/index.php`, `favorilerim.php`, `sayfalar/index.php`, `sayfalar/form.php`.

---

## 2026-08-17 (XXVII) — PayTR callback kuruş-varsayım hatası düzeltildi (resmî dokümantasyon kanıtı) + callback provası testleri — 123/123

**Gerekçe:** XXVI'da eklenen tutar çapraz kontrolü `total_amount`'u TL-ondalık
varsayıp ×100 yapıyordu. Resmî dokümantasyon (dev.paytr.com, iframe-api 2. adım)
doğrulandı: **total_amount zaten kuruş geliyor** ("34.56 → 3456") — meşru her
ödeme reddedilirdi (lansman günü ödeme kesintisi olurdu). Düzeltildi: doğrudan
kuruş karşılaştırması. Dokümanın diğer gereksinimleri mevcut kodla karşılanıyor:
hash formülü resmî örnek kodla birebir, tekrarlı bildirim idempotent + OK,
callback'te session yok, yanıt düz metin.

**Callback provası (regresyon +3):** test anahtarları geçici girilir — geçerli
hash + YANLIŞ tutar → `tutar uyusmazligi` + odeme_durumu değişmez; DOĞRU tutar
(kuruş) → `OK` + `odendi` (DB kanıtı). Temizlikte anahtarlar geri alınır. Log
denetimine 'PayTR bildirim' satırları bilgilendirici olarak eklendi.

**Doğrulama:** lint temiz; tam regresyon **123/123**.

**[!] Canlıya taşı:** `Paytr_bildirim.php`, `tests/regresyon.php`.

---

## 2026-08-17 (XXVI) — Kapsamlı güvenlik/bug denetimi: 3 bulgu kapatıldı (PayTR sahiplik + callback tutar çaprazı + stok yarış güvenliği) — 120/120

**Kapsam (kullanıcı isteği "iyice kontrol et"):** klasik taramalar (eval/exec/
unserialize, sabit kodlu anahtar, debug artığı — temiz; CSRF muafiyeti yalnız
`paytr/bildirim`), para yolu derin inceleme (sipariş tutarı, ödeme callback'i,
kupon, stok), iki paralel tarama ajanı (view XSS/çıkış + yönetim IDOR/yetki).

**Doğrulanan temiz noktalar:** sipariş tutarları TAMAMEN sunucu tarafında
(Sepet_model::liste DB fiyatı + basamak/bayi indirimi hesaplar — istemciden
fiyat gelmiyor); PayTR callback hash formülü resmî spec ile birebir
(HMAC-SHA256 + hash_equals); kupon doğrulaması eksiksiz (pencere/limit/
min-sepet/tavan + "indirim ara toplamı aşamaz" — negatif toplam imkânsız);
sepet ekleme/güncelleme stok denetimli.

**Bulgu 1 (orta) — Paytr basarili/basarisiz sahipliksiz:** `basarili($id)`
herhangi bir sıralı id'li siparişi sahiplik kontrolüsüz render ediyordu VE
session `son_siparis_id`'yi kendisi atıyordu → yabancı, `ode()`'nin `_sahip`
ikinci dalından geçip başkasının siparişinin ödeme sayfasına erişebiliyordu.
Çözüm: iki uç nokta da `_sahip()` ister (meşru akış bozulmaz — checkout
`son_siparis_id` yazar, bayi `bayi_id` ile geçer).

**Bulgu 2 (sertleştirme) — callback tutar çapraz kontrolü:** hash yalnız
PayTR'un gönderdiğine kanıttı; ödenen tutar siparişle karşılaşılmıyordu.
Artık `total_amount` (TL→kuruş) ≠ `toplam*kur` (kuruş) ise `tutar uyusmazligi`
+ ERROR günlüğü; ödendi işaretlenmez (PayTR tekrar dener, panelde görünür).

**Bulgu 3 (yarış güvenliği) — koşulsuz stok düşümü:** `mg_olustur` stoku
`önceki - adet` ile koşulsuz düşüyordu → eşzamanlı checkout'ta negatif stok/
aşırı satış. Artık `SET stok = stok - adet WHERE id = ? AND stok >= adet`
koşullu düşüm; satır etkilenmezse trans_rollback + "Yetersiz stok" (Odeme
akışı !ok'u zaten karşılıyor; ifade int-cast'li — enjeksiyon yüzeyi yok).

**Doğrulama:** lint ×4 temiz; regresyon +2 (`paytr-basarili-sahibine-200`,
`paytr-basarili-yabanci-red` [yabancı redirect'e düşer]) → **120/120**.

**[!] Canlıya taşı:** `Paytr.php`, `Paytr_bildirim.php`, `Siparis_model.php`,
`tests/regresyon.php`.

---

## 2026-08-17 (XXV) — Kullanıcı siparişi hesabına işleniyor (checkout e-posta atıflaması) + yönetimde misafir satırına e-posta — 118/118

**Belirti (kullanıcı):** hesabim/siparisler'de siparişi görünmüyor; yönetim
panelinde sipariş "Misafir" olarak duruyor — oysa giriş yaptıktan sonra sipariş
vermişti.

**Kök neden (DB kanıtlı):** checkout e-postasını TAMAMEN formdan alıyordu
(`Odeme.php` `input->post('email')`); görünüm alanı yalnız BAYİ için
doluyordu — giriş yapmış KULLANICI boş zorunlu alan görüp elle (hatalı)
e-posta yazdı (sipariş #87 `asd@gmail.com`, #88 `afdsfads@gmail.com` — hesap
`asdasd@gmail.com`). Hesabım eşleşmesi `siparisler.email` üzerinden → eşleşmez
→ görünmez; yönetim satırı bayiler join alanlarını gösterdiğinden "Misafir"
(siparişin kendi e-postası hiç gösterilmiyordu).

**Düzeltme:**
1. `Odeme::tamamla()` — giriş yapmış kullanıcıda sipariş e-postası HESAPTAN
   gelir (otoriter; form değeri ezemez). E-posta doğrulama kuralı yalnız
   misafir/bayi için. Bilgilerim'deki değişmez-e-posta ilkesinin checkout
   tamamlaması.
2. `odeme/index.php` — kullanıcı girişliyken e-posta alanı hesap adresiyle
   dolu + readonly.
3. Yönetim: `mg_admin_liste` seçimine `s.email, s.teslimat_ad`; liste satırı
   teslimat adını ve bayi e-postası yoksa SİPARİŞ e-postasını gösterir
   ("Misafir" artık e-postasız çıplak kalmaz); arama filtresine `s.email`.

**Veri onarımı:** kullanıcının iki test siparişi (#87, #88) hesap e-postasına
bağlandı (niyeti buydu; dev verisi).

**Doğrulama:** lint ×5 temiz; regresyon +4 (`kullanici-odeme-form-200`,
`kullanici-siparis-redirect`, `kullanici-siparis-hesap-eposta` [formda YANLIŞ
e-posta → sipariş hesap e-postasıyla; yanlış e-postayla satır yok],
`kullanici-siparisler-gorunur` [hesabim/siparisler'de sipariş_no görünüyor]);
temizliğe B2C siparişi silme eklendi → **118/118**.

**[!] Canlıya taşı:** `Odeme.php`, `odeme/index.php`, `Siparis_model.php`,
`yonetim/siparisler/index.php`, `tests/regresyon.php`. DB: mevcut canlı B2C
siparişleri varsa elle e-posta düzeltme gerekebilir (yeni kodla gelecek
siparişler otomatik doğru).

---

## 2026-08-17 (XXIV) — Sepet CSRF takibi: ölçüm hatası bulundu (CI3 $_POST'tan token'ı siler) + AJAX gövdesi urlencoded + çerez-yedekli token — 114/114

**Belirti (kullanıcı, XXIII sonrası):** "Güvenlik anahtarı hâlâ eskimiş. Sayfayı
elle yenileyin…" — otomatik yenileme sonrası da 403 sürüyordu.

**Ölçüm hatası (kritik ders):** XXIII'ün tanı satırlarında `posted=YOK` hep
çıkıyordu — stok CI3 `csrf_verify()` reddetmeden HEMEN ÖNCE `$_POST[token]`'ı
unset ediyor; `csrf_show_error()` artık silinmiş diziyi görüyordu. Kanıt:
curl'la token'ı AÇIKÇA gövdeye koyup attım — log yine `posted=YOK
post_anahtar=urun_id,varyant_id,adet` (teksil_csrf anahtarı yok). Yani
tarayıcı loglarındaki `posted=YOK` hiçbir şey kanıtlamıyordu.

**Düzeltmeler:**
1. `MY_Security::csrf_verify()` — token'ı unset'ten ÖNCE yakalar (`_tan_posted`);
   `csrf_show_error()` artık anlamlı `posted=`/`eslesme=` basar (kanıtlandı: bozuk
   hash atınca `posted=var eslesme=hayir`) + `post_anahtar=`/`icerik_tipi=`/
   `uzunluk=` (gövde hiç parse edilmedi mi, hangi biçimde geldi mi — kesin ayırt).
2. `teksil.js ajaxPost` — gövde URLSearchParams: `application/x-www-form-urlencoded`
   (FormData multipart basıyordu; tarayıcı multipart'ının php -S altında $_POST'a
   boş düşmesi ihtimali tamamen ortadan kalkar — kanıtlı çalışan tüm istekler
   urlencoded'dı). Token kaynağı çift kol: sayfaya gömülü `tkCsrf`, yoksa
   `document.cookie`'den `teksil_csrf_cookie` (HttpOnly değil — double-submit
   modeli zaten bu). Tek çağrı sepet — dosya yükleme yok; gelirse multipart
   yeniden değerlendirilir.

**Doğrulama:** lint ×2 temiz; mutlu yol urlencoded 200 JSON; bozuk hash → 403 +
`posted=var eslesme=hayir icerik_tipi=application/x-www-form-urlencoded
uzunluk=74`; tam regresyon **114/114**.

**[!] Canlıya taşı:** `application/core/MY_Security.php`,
`assets/magaza/js/teksil.js`.

---

## 2026-08-17 (XXIII) — Sepet CSRF 403 kökü: stok CI3'ün SameSite=Strict çerezi → Lax + ret tanı günlüğü — 114/114

**Belirti (kullanıcı, XXII sonrası):** sepete eklemede "Güvenlik anahtarı
eskimiş — sayfa yenileniyor…" (403 sürüyordu).

**Kök neden (kanıtlı):** stok CI3 `system/core/Security.php` CSRF çerezine
**sabit SameSite=Strict** basar (dosya ilk commit'ten beri dokunulmamış — stok
davranış; `cookie_samesite` hiçbir yapılandırmada yok). Oturum çerezi ise
Lax. Strict'in çerez göndermeme kuralı bazı tarayıcı akışlarında (BFCache'li
geri dönüş, çapraz-site bağlantıyla gelen ilk GET) sayfaya gömülü
`tkCsrf.hash` ile kavanozdaki çerezi senkron dışı bırakabiliyor → tarayıcıda
POST 403; curl repro üretemez (çerez öznitelik semantiği yok — XXII'deki
multipart vb. testler hep geçer).

**Düzeltme:**
1. `application/core/MY_Security.php` (CI3 onaylı uygulama-katmanı uzantısı;
   `system/` dosyasına DOKUNULMADI): `csrf_set_cookie()` SameSite=**Lax** —
   oturum çereziyle hizalı; CSRF koruması değişmez (çapraz-site POST'a çerez
   yine gitmez). `csrf_show_error()` ret anında tanı satırı düşer
   (`CSRF reddi: cerez=… posted=… eslesme=… uri=… ua=…`) — kalıcı tanı aracı,
   E3 izlemesine girer.
2. `teksil.js`: otomatik yenileme döngü-kilidi (sessionStorage işareti; sayfa
   ömründe bir kez — sürerse "elle F5" yönlendirmesi).

**Doğrulama:** sayfa yanıtı `teksil_csrf_cookie … SameSite=Lax` (curl -D —
MY_Security'nin yüklendiğinin kanıtı); mutlu yol multipart 200 JSON; bayat
hash → 403 + günlükte tanı satırı görüldü; regresyon +1 `csrf-cerez-samesite-lax`
→ **114/114** (log denetimine kasıtlı ret satırı DAR kapsamda eklendi: yalnız
`CSRF reddi` + `uri=/sepet/ekle`; başka uçta gerçek kırılma yakalanmaya devam
eder — ilk koşuda denetim bu satırı yakaladı, beklendiği gibi).

**[!] Canlıya taşı:** `application/core/MY_Security.php`,
`assets/magaza/js/teksil.js`, `tests/regresyon.php`.

---

## 2026-08-17 (XXII) — "Bağlantı hatası" düzeltmesi: sepet AJAX'ı yanıt-tipi farkında — 113/113

**Belirti (kullanıcı):** sepete eklemede toast "Bağlantı hatası." (div.tk-toast.gor).

**Teşhis (kanıtlı):** tarayıcı-taklidi uçtan uca akış sağlıklı — ürün sayfası →
sayfaya gömülü `tkCsrf.hash` → POST `{"ok":true,"adet":6}` (200, JSON). Bayat/
eskimiş CSRF anahtarında CI3 **403 + HTML** döner → `r.json()` parse'ı patlar →
`.catch` → yanıltıcı toast. Tetikleyici büyük olasılıkla bayat sekme
(`csrf_expire=7200` — çerez 2 saatte esker) veya açık sekme altında sunucunun
yeniden başlaması (ölü sunucuya fetch → gerçek ağ hatası → aynı mesaj). Tek
mesaj üç farklı arızayı birleştiriyordu.

**Düzeltme (`teksil.js`):** `ajaxPost` gövdeyi okuyup JSON'a çevirir;
çevrilemezse **tip'li hata** fırlatır (`tip:'csrf'` — 403, `'yanit'` — diğer).
Sepet `catch`'i üç dal: (1) csrf → "Güvenlik anahtarı eskimiş — sayfa
yenileniyor…" + 2 sn sonra `location.reload()` (istek denetleyiciye hiç
varmadı, yan etki yok; taze hash'le kullanıcı tekrar tıklar); (2) yanit →
"Sunucu beklenmeyen yanıt döndürdü. Sayfayı yenileyip tekrar deneyin.";
(3) ağ hatası → "Bağlantı hatası."

**Teslim:** `asset()` filemtime sürümlemesi yeni JS'i otomatik çeker (sayfa
`teksil.js?v=1786952305` basıyor) — elle önbellek temizliği gerekmez.

**Sözleşme kilidi:** regresyon +1 — `sepet-ekle-csrf-403-html` (geçerli çerez +
bayat hash → 403 + text/html): JS'in 'csrf' dalı bu sunucu davranışına dayanır;
ileri değişirse test düşer → JS ile birlikte güncellenir. **113/113 PASS**.

**[!] Canlıya taşı:** `assets/magaza/js/teksil.js` + `tests/regresyon.php`.

---

## 2026-08-17 (XXI) — CLAUDE.md yeniden yazıldı: oturum-başlangıç kısayolu

Soğuk başlayan oturumlar ("nerede kaldıysan devam et") durumu her seferinde
yeniden türetiyordu. CLAUDE.md oturum başına otomatik yüklendiğinden işaretçi +
komut kopya kağıdı olarak yeniden yazıldı (eski hali silinmiş bir Nesem
referansıydı): otorite dosyaları (workflow / DEGISIKLIK / TAMAMLAMA_PLANI /
DEPLOY), kaldığın-yerden-devam adımları, XAMPP komutları, bir-kez-öğrenilen
dersler (yetim php.exe, perf-indeks-yalnız-migrasyonda, §3 sırası, CI_ENV
zinciri), tarihli durum özeti. **Otorite değil** — içerik bu dosyalardan
türetilmiş kısayoldur.

**Davranış değişikliği YOK** — yeni dosya + bu günlüğün başlığındaki eski
"CLAUDE.md bu projeye ait değildir" notu güncellendi. Kod/DB etkisiz.

---

## 2026-08-17 (XX) — Sabitleme özelliği testi (eski çerez → misafir) — 112/112

XVIII'in rotasyon testleri mekânizmayı (çerez dönüyor) kilitliyordu; bu ek
**güvenlik özelliğini** kilitler: bayi girişinden sonra ESKİ `teksil_sess`
çereziyle gelen istek `/hesabim`'a misafir olarak düşer (30x → login) —
kimliklendirme verisi yalnız yeni oturum ID'sine yazılır, eski dosyada kalmaz.
Ham curl bloğu hh()'den bağımsız tek çerezle atar (SSL bayrakları $INSECURE
ile hizalı — yerel prod provasında da koşar).

Aynı gün kayıp kayıt: `ff3085d` — regresyon.php kapsam başlığına kullanıcı
(B2C) akışları + oturum dönüşü eklendi (paketin X–XVIII'den beri kapsadığının
özeti; davranış değişikliği yok).

**Doğrulama:** tam regresyon **112/112** (yeni: bayi-eski-oturum-giris-degil).

**[!] Canlıya taşı:** `tests/regresyon.php` (C7 lansman-günü koşusu).

---

## 2026-08-17 (XIX) — E2 tazeleme: siparisler.email indeksi + sıfır-DB provası tekrarı — 111/111

**Gerekçe:** E2'nin EXPLAIN denetimi (08-14) de kullanıcı-hesabı kodundan önceydi;
X–XVII'nin eklediği sorgular (Kullanici_model::mg_siparisler*, Fatura_model
join'leri) e-posta anahtarıyla sipariş çekiyor.

**Bulgu (EXPLAIN kanıtlı):** `siparisler.email` indekssizdi — hesabım sorgusu
PRIMARY üzerinde tam geri-tarama (type=index, Backward index scan, tüm satırlar);
faturalar join'inde type=ALL. `faturalar.siparis_id` zaten indeksli
(idx_fatura_siparis), `kullanicilar` (email/kullanici_adi UNIQUE) ve `sepet`
(bayi_id/oturum_id) de tamamdı — dokunulmadı.

**Çözüm:** `migrate_perf_index.sql`'e `idx_siparis_email (email)` ALTER'ı +
dev DB'ye uygulama. Sonrası EXPLAIN: type=ref, key=idx_siparis_email, rows
18→1 (join'de kalan type=ALL, 1 satırlık `faturalar`'ı süren tablo — bu boyutta
optimal plan; ölçekte optimizer email-indeksli yönü seçebilir).

**Yol üstü ders (schema çakışması):** indeksi önce schema.sql'e de ekledim —
taze §3 kurulumunda schema yaratıyor + migrasyonun ALTER'ı çift ekliyor →
duplicate-key hatası (provada yakalandı). E2 deseni doğrulandı: **perf
indeksleri yalnız migrate_perf_index.sql'de yaşar**; taze kurulum onları §3'teki
migrasyondan alır. schema.sql'deki satır geri alındı. (XVII'nin kullanici_adi
kolonu farklıydı: migrasyondaki ALTER yorum içinde duruyor.)

**Sıfır-DB provası tekrarı (XVI tazelemesi):** XVI'ın kanıtı XVII şema
değişikliği + XVIII kodu/testleri + perf-indeks güncellemesinden önceydi. Taze
scratch DB: §3 **17/17** dosya temiz, 39 tablo, idx_siparis_email +
kullanici_adi doğrulandı, seed'ler dolu (3 rol, admin, 8 sayfa);
`npm run dev:testing` + `REGRESYON_DB` ile **111/111 PASS**. Prova sonrası
scratch DB düşürüldü.

**Doğrulama:** dev DB regresyonu indeks öncesi/sonrası 111/111; EXPLAIN
öncesi/sonrası yukarıda; §3 uçtan uca yeniden kanıtlandı.

**[!] Canlıya taşı:** `migrate_perf_index.sql` — mevcut kurulumda elle
`ALTER TABLE siparisler ADD INDEX idx_siparis_email (email);` (taze kurulum
§3'ten otomatik alır).

---

## 2026-08-17 (XVIII) — E1 taraması sonrası-koda uygulandı + oturum sabitleme (fixation) koruması — 111/111 PASS

**Gerekçe:** E1 güvenlik review'u 08-14'te yapıldı; kullanıcı-hesabı dönemi
kodu (X–XVII: kullanıcı girişi, hesabım, favoriler, faturalarım, kullanıcı adı)
ondan SONRA yazıldı. Aynı kontrol listesi yeni koda uygulandı.

**Tarama sonucu (temiz):** SQLi — `where(...,NULL,FALSE)` taraması 27 ham ifade,
tümü sabit SQL parçası (`deleted_at IS NULL` vb.), kullanıcı girdisi birleşeni
yok. XSS — yeni view'lardaki `<?=` çıktıları tamamen e()/helper/(int) cast
(`$moq`/`$adim` view başında int'e bağlanmış); `tkBase` json_encode'lu; `pdVeri`
JSON_HEX_TAG yerinde. IDOR — hesabım sahipliği bayi_id/e-posta anahtarlı sorgu
izolasyonu (regresyonda kanıtlı). Brute-force kilidi üç girişte de var
(oturum-sayaçlı, 5 deneme → 15 dk). Açık yönlendirme: `donus`
(_guvenli_donus) + favori `ref_ic()` aynı-site referer kontrolü. Prod
config'te `cookie_secure=TRUE` (dev FALSE bilinçli).

**Bulgu — oturum sabitleme:** hiçbir giriş oturum ID'sini döndürmüyordu; CI3
yalnız `sess_time_to_update=300` ile periyodik döndürür. Yetki değişiminde
(misafir→girişli) ID sabit kalıyorsa sabitleme saldırısına açık.

**Düzeltme:** `sess_regenerate()` yetki-değişim noktalarına — `bayi_giris_yap()`
(MY_Controller; sepet transferi çağrısı da içeri alındı), yeni `_oturum_dondur()`
yardımcısı (`kullanici_giris_yap`/`kullanici_cikis`/`Hesap::sifre_kaydet`),
`Auth_admin::giris_yap()`/`cikis()`.

**Sepet sürekliliği (dönüşün kritik yan-etkisi):** B2C sepet anahtarı
`oturum_id` (= session_id) — ID dönünce misafir sepeti yetim kalırdı.
(a) Kullanıcı girişi/çıkışı: `Sepet_model::oturum_tasi(eski, yeni)` satırları
yeni anahtara taşır. (b) Bayi girişi: `transfer_to_bayi($bayi_id, $eski_sid)` —
dönüşten ÖNCEKİ anahtar açıkça verilir; çağrı `Bayi.php`'den yardımcının içine
taşındı (sıra hatası imkânsızlaştı).

**Doğrulama:** `php -l` ×6 temiz; tam regresyon **111/111 PASS** (+7:
bayi-misafir-sepet-ekle, bayi-giris-oturum-doner [teksil_sess çerezi dönüşüyor],
bayi-giris-sepet-transferi, admin-giris-oturum-doner, kullanici-misafir-sepet-ekle,
kullanici-giris-oturum-doner, kullanici-giris-sepet-devredi); temizliğe
kullanıcı-havuzu sepet satırı silme eklendi. Oturum verisi dönüşümde korunuyor
(hesabim-200, flash mesajları; csrf_regenerate=FALSE olduğundan CSRF etkilenmiyor).

**[!] Canlıya taşı:** `MY_Controller.php`, `Bayi.php`, `Hesap.php`,
`Auth_admin.php`, `Sepet_model.php` + `tests/regresyon.php` (lansman-günü C7
koşusu için) — DB değişikliği YOK.

---

## 2026-08-16 (XVII) — Kullanıcı adı alanı + boş arama kilidi + arama-buyuk kaldırıldı + sepet AJAX kökü (tkBase) — 104/104 PASS

**Kullanıcı isteği (4 madde):**

**1) Kullanıcı adı (ad soyad yanında):** `kullanicilar.kullanici_adi VARCHAR(30)`
UNIQUE (NULL serbest — eski kayıtlar etkilenmez): `schema.sql` +
`migrate_kullanicilar.sql` (yorumda upgrade ALTER) + dev DB'de ALTER uygulandı.
Kayıt ve Bilgilerim formlarına zorunlu alan (`alpha_dash`, 3-30, harf/rakam/-/_);
benzersizlik kayıtta model katmanında (e-posta deseniyle aynı), düzenlemede
`kullanici_adi_musait($v, $haric_id)` — kendi adı hariç. Hesabım menüsünde adın
altında `@kullanici_adi` görünür (yalnız kullanıcı modunda). Giriş e-posta ile —
değişmedi.

**2) Boş arama kilidi:** `teksil.js` `initHeaderArama()` — header arama kutusu
boşken submit engellenir (büyüteç/Enter hiçbir şey yapmaz).

**3) form.arama-buyuk:** /arama sayfasındaki büyük arama formu kaldırıldı (header
araması yeterli; başlık + sonuç ızgarası kalır).

**4) Sepet "Bağlantı hatası" — kök tkBase'ti:** `window.tkBase` HİÇ tanımlı
değildi; teksil.js `'/sepet/ekle'` KÖK-göreli atıyordu. Site Apache alt-dizininden
açılınca (ör. http://localhost/TekstilSite) istek http://localhost/sepet/ekle'a
gider → 404 HTML → JSON parse çöker → toast "Bağlantı hatası". Çözüm: `footer.php`
tkBase'i SCRIPT_NAME'den türetip origin-göreli basar ('/' veya '/TekstilSite/').
**Yol üstünde ikinci bir Windows tuzağı (od ile bayt-kanıtlı):**
`dirname('/index.php')` bu makinede `'\'` döndürüyor (platform ayraç
normalizasyonu) → taban `'\/'` çıkıyordu; dirname yerine `strrpos+substr` saf
dizgi hesabına geçildi → çıktı bayt bayt `"/"`. (JS'te `"\/"` de `/` demekti —
:8000 kökünde şans eseri çalışıyordu ama Apache alt-dizinde kırılacaktı.)

**Doğrulama:** `php -l` ×10 temiz; mojibake taraması temiz; tam regresyon
**104/104 PASS** (+kullanici-adi-db, +kullanici-adi-alinmis [aynı ad ikinci kayıt
reddi, DB'de tek satır], +kullanici-adi-guncellendi); E2E misafir sepete ekleme
iki kez (ürün sayfası → CSRF → POST → `{"ok":true,"adet":6}`); /arama'da
arama-buyuk yok; servis edilen teksil.js'te initHeaderArama mevcut; tkBase çıktısı
od ile bayt-bayt doğrulandı.

**[!] Canlıya taşı:** `Kullanici.php`, `Kullanici_model.php`, `Hesap.php`,
`kullanici/kayit.php`, `hesabim/kullanici_bilgiler.php`, `hesabim/_menu.php`,
`arama/index.php`, `layout/footer.php`, `teksil.js` (9 dosya) + canlı DB:
`ALTER TABLE kullanicilar ADD COLUMN kullanici_adi VARCHAR(30) NULL AFTER
ad_soyad, ADD UNIQUE KEY uq_kullanici_adi (kullanici_adi);`
(migrate_kullanicilar.sql yorumunda da kayıtlı).

---

## 2026-08-16 (XVI) — Sıfır-DB kurulum provası tekrarı: 101/101 PASS + Windows env/yetim süreç dersleri

**Amaç:** IX provası (08-15, 74 test) kullanıcı özelliğinden önceydi — `migrate_kullanicilar`
dahil §3 sırası + güncel 101-test paketi hiç sıfır DB'de koşulmamıştı; ilk koşu lansman
günü olacaktı.

**Sonuç: 101/101 PASS** — taze scratch DB (`teksilsite_rehearsal`), 17/17 SQL dosyası
§3 sırasıyla (39 tablo). Kurulum sırası sağlam; IX'ten bu yana yeni kurulum bulgusu yok.

**Kalıcı prova altyapısı:** `application/config/testing/database.php` (CI_ENV=testing
DB override — CI3'ün `system/database/DB.php` ENVIRONMENT dizin desteği kaynaktan
doğrulandı), `router.php`'e CI_ENV süreç-env → $_SERVER geçişi, `tests/regresyon.php`'ye
`REGRESYON_DB` parametresi (kullanım başlıkta), `npm run dev:testing` (dev.js: arg →
açık child env → shell'siz direkt spawn).

**Yol üstü bulgular (üçü de düzeltildi):**
1. **Windows env zinciri kırılgan:** npm→cmd→php zincirinde CI_ENV yutuldu (php
   getenv=false — router'a geçici sondayla kanıtlandı; CI3 config mekanizması doğruydu,
   sorun zincirdeydi). Çözüm: dev.js php'yi shell KULLANMADAN direkt spawn ediyor
   (mutlak yol + args dizisi + açık env; DEP0190 uyarısı da kalktı).
2. **Yetim php.exe sunucuları:** sarmalayıcı sert öldürülünce (terminate) php çocukları
   Windows'ta yetim kalıyor — :8000'de 5 yetim dinler bulundu; istekler rastgele eski
   (env'siz, dev-config'li) süreçlere düşüp "env kayıp" yanılsaması yaratıyordu. Yetimler
   öldürüldü; dev.js'e sinyal işleyicileri + **port ön-kontrolü** eklendi: port doluysa
   PID'yle gürültülü red (doğrulandı: yetim varken ikinci başlatma "HATA: localhost:8000
   ... PID 18812" + taskkill ipucuyla exit 1) — sessiz istek-ruleti modu kapandı. Not:
   sert öldürmede sinyal işleyicileri çalışmayabilir; ön-kontrol asıl sigortadır.
3. **İlk koşu dev DB'sine yazdı:** yanlış (yetim) sunucular kirlilik yarattı — prova
   bayi/kullanıcı/adres/sipariş satırları + 6 adet stok eksilmesi temizlendi (242→248,
   scratch referansıyla); 08-14'ten kalma bağımlılıksız test bayisi de kaldırıldı.

**Doğrulama:** taze kurulum 17/17 → scratch'te **101/101 PASS**; dev yolu smoke
(`npm run dev` direkt spawn ile 200); dev.js `node --check` temiz + tam yaşam döngüsü
(başlat → 200 → durdur → yeniden başlatma gürültülü red → yetim temizliği); prova
sonrası scratch DROP edildi, php.exe yok, port boş.

**[!] Canlıya taşı:** yok — salt dev/test altyapısı (router.php yalnız `php -S` içindir;
testing config, dev.js, regresyon parametresi prod'u etkilemez).

---

## 2026-08-16 (XV) — npm run dev: dev sunucusu tek komutla (localhost:8000)

**Kullanıcı isteği:** terminalde `npm run dev` → proje localhost:8000'de çalışsın.

**Yeni:** `package.json` (tek script `dev`; bağımlılık YOK — node_modules/lock
üretilmez) + `scripts/dev.js` başlatıcı: PHP önce PATH'te aranır, yoksa sabit
XAMPP yolu denenir (bu makinede php PATH'te DEĞİL — `C:\xampp\php\php.exe`).
Komut shell'e tek dizgi olarak verilir (args dizisi + `shell:true` Node'da
DEP0190 uyarısı üretirdi — ilk denemede görüldü, düzeltildi); `PORT` yalnız
sayısal kabul edilir (enjeksiyon kapısı yok); Ctrl+C alt süreci birlikte kapatır.

**Değişen:** `workflow.md` §8 — çalıştırma komutu artık `npm run dev`; mevcut
"elle" satırı `-t .` ile yazılmıştı, pretty-URL'ler için şart olan `router.php`
eksikti — eşdeğer komut olarak düzeltildi.

**Doğrulama:** `npm run dev` arka planda → "PHP 8.1.25 Development Server
started"; anasayfa/katalog/statik CSS **200**; DEP0190/DeprecationWarning
eşleşmesi **0**; durdurma temiz. Node v24.14.1 / npm 11.11.0 ile.

**[!] Canlıya taşı:** yok — salt dev aracı; prod'u ilgilendirmez.

---

## 2026-08-16 (XIV) — Sipariş detayında fatura bölümü (XIII'ün tamamlayıcısı) — 101/101 PASS

**Kapsam:** müşterinin faturaya bakacağı asıl yer kendi sipariş detayı — XIII ile
açılan liste sayfasının doğal tamamlayıcısı. (Kalıcı pakette `hesabim/siparis/(:num)`
sayfasının testi de yoktu; birlikte eklendi.)

**Değişen:** `Hesap::siparis_detay()` — sahiplik kontrolünden geçen siparişin
faturalarını `Fatura_model::siparis_faturalari()` ile view'a geçirir (IDOR yok:
`$s` zaten bayi-id/e-posta izolasyonlu çekiliyor). `hesabim/siparis_detay.php` —
fatura varsa "Faturalar" kartı (fatura no / tip / durum rozeti / tutar / PDF);
fatura yoksa bölüm hiç render edilmez (boş gürültü yok). `tests/regresyon.php`
+2: `bayi-siparis-detay-200`, `bayi-siparis-detay-fatura` (admin'in kestiği
fatura bayinin sipariş detayında 'Bekliyor' rozetiyle).

**Doğrulama:** `php -l` ×3 temiz; tam regresyon **101/101 PASS**; UTF-8/FFFD temiz.

**[!] Canlıya taşı:** `Hesap.php`, `hesabim/siparis_detay.php` — 2 dosya; DB değişikliği yok.

---

## 2026-08-16 (XIII) — Faturalarım (bayi + kullanıcı): C2'nin faturalar yarısı — 99/99 PASS

**Kapsam:** C2 kapsam notundaki boşluk — fatura kayıtları yalnız admin panelde
görünürdü; bayi/kullanıcı kendi faturasını göremiyordu (workflow Faz 3 vaadi).

**Yeni:** `hesabim/faturalar` rota + `Hesap::faturalar()` (çift modlu) +
`Fatura_model::mg_bayi_liste()/mg_kullanici_liste()` + `hesabim/faturalar.php`
view (durum rozetli tablo: fatura no / sipariş linki / e-Fatura·e-Arşiv / tutar /
tarih / PDF linki + boş durum kartı).

**Sahiplik:** bayi → `siparisler.bayi_id` join'i üzerinden (f.bayi_id yedek kopya,
salt ona güvenilmedi); kullanıcı → `siparisler.email` (mg_siparisler ile aynı
anahtar). IDOR yüzeyi yok: id-parametreli detay yok, liste sorgusu oturum sahibine
sıkı; sipariş linki zaten sahiplik kontrollü `hesabim/siparis/(:num)`.

**Değişen:** `hesabim/_menu.php` (iki moda da 🧾 Faturalarım), `tests/regresyon.php`
(+3: bayi-faturalar-200, bayi-faturalar-liste [admin'in kestiği fatura bayinin
listesinde: sipariş no + 'Bekliyor'], kullanici-faturalar-200).

**Bakiye notu (bilinçli dokunmadık):** `bayiler.bakiye` kolonu kodda hiç
okunmuyor/yazılmıyor (dormant) — sıfır gösteren sahte kart yerine yeni iş; kredi/
bakiye iş modeli kararlanınca (bayi limiti, ödeme kaydı) ayrı inşa edilmeli.

**Doğrulama:** `php -l` ×6 temiz; tam regresyon **99/99 PASS**; view UTF-8 /
FFFD taraması temiz.

**[!] Canlıya taşı:** `Hesap.php`, `Fatura_model.php`, `hesabim/faturalar.php`,
`hesabim/_menu.php`, `routes.php` — 5 dosya; DB değişikliği yok.

---

## 2026-08-16 (XII) — Durumlu favori butonu (ürün detayı) + toast CSS + filtre sadeliği — 96/96 PASS

**Kullanıcı isteği:** dünkü (XI sonrası) yarım işi bitir — favori butonu durumlu
olsun: controller `favorilerde` bayrağı ve `.pd-favoride` CSS'i yazılmıştı ama
view hâlâ statik "♡ Favorilere Ekle" linkiydi.

**Değişen:** `views/magaza/urun/detay.php` — favori butonu oturum state'ine göre
render: ♡ Favorilere Ekle ↔ ♥ Favorilerde (`.pd-favoride` yeşil vurgu, hedef
`favoriler/sil/{id}`, title ipucu). `controllers/Urun.php` — detay view'ına
`favorilerde` bayrağı (session wishlist üyeliği, strict int karşılaştırma).
`controllers/Sayfa.php` — `favoriler_sil` artık referer-aware: ürün sayfasından
çıkarınca kullanıcı `favorilerim`'e fırlatılmıyor; yeni `ref_ic()` yalnız
**same-host** http(s) referer döndürür — `favoriler_ekle`'deki ham
`HTTP_REFERER` redirect'i (dış siteye açık redirect) de kapandı. Host
karşılaştırması **port harfiç** (tuzak: `parse_url` host'u portsuz, `HTTP_HOST`
portlu döner; :8000 provasında yakalandı — düzeltme öncesi hep fallback'e
düşüyordu). `teksil.css` — `.tk-toast` kuralları (kural HİÇ yoktu: sepete ekle
çalışıyordu ama geri bildirim görünmezdi), `.pd-favoride`; `katalog/index.php`'deki
inline `margin-top:18px` CSS'e taşındı (yalnız mobil medya sorgusunda — toggle
butonu ile panel arası boşluk). `views/magaza/partial/filtre.php` — "Alt
Kategoriler" bloğu (header mega menüsüyle kopya) ve "Seçimler anında uygulanır"
ipucu kaldırıldı.

**Doğrulama:** `php -l` temiz (5 koşu). Tam regresyon **96/96 PASS** (ref_ic port
düzeltmesi sonrası son kodla tekrar koşuldu). E2E curl 7 adım: temiz oturumda ♡ →
`favoriler/ekle/1` (referer=ürün) **307 ürün sayfasına geri** → sayfada
`♥ Favorilerde` + `.pd-favoride` + `favoriler/sil/1` → sil (referer=ürün) 307
geri → ♡; DIŞ referer (evil.example) → `favorilerim` fallback (dış redirect yok);
`favorilerim` sayfasında ürün + "Favoriden Çıkar" listeleniyor. Servis edilen
CSS'te yeni kurallar mevcut; katalog HTML'inde kaldırılan bloklar yok; mojibake
byte-grep temiz.

**[!] Canlıya taşı:** `Urun.php`, `Sayfa.php`, `urun/detay.php`,
`katalog/index.php`, `partial/filtre.php`, `teksil.css` — 6 dosya; DB değişikliği
yok. (Not: `Katalog.php` `alt_kategoriler`'i hâlâ hesaplayıp view'a geçiyor —
partial artık kullanmıyor, zararsız ölü veri, dokunulmadı.)

---

## 2026-08-15 (XI) — Hesabım (kullanıcı) sidebar: bilgilerim/adreslerim/şifre + çıkış onayı — 96/96 PASS

**Kullanıcı isteği:** kullanıcı hesabına Bilgilerim / Adres Bilgilerim / Şifre
Güncelleme; çıkış tıklamasında "çıkış yapmak istediğinizden emin misiniz?"
onayı (desen olarak tüm çıkış noktalarına uygulandı: hesabım menüsü + header
Çıkış — bayi VE kullanıcı).

**Yeni:** `kullanicilar_adresleri` tablosu (migrate_kullanicilar.sql GENİŞLETİLDİ —
henüz yayına çıkmadığı için aynı dosyada; schema.sql + dev DB'de) — checkout
teslimat alanlarıyla aynı serbest-metin il/ilçe semantiği, varsayılan-adres
mantığı (işaretlenince diğerleri temizlenir), sahiplik tüm sorgularda izole.
`hesap/adresler|adres_kaydet|adres_sil($id)` + rota üçlüsü + `adresler.php`
view (kart listesi + ekle/düzenle formu + onaylı silme).

**Değişen:** Hesap `bilgiler`/`sifre` artık ÇİFT MODLU (bayi eski formunu
korumalı; kullanıcı kendi formunu/modelini kullanır; `_bayi_ozel` kaldırıldı,
adresler için `_kullanici_ozel`); kullanıcı bilgilerinde e-posta bilinçli
salt-okunur (sipariş eşleşmesi e-posta üzerinden — değişirse geçmiş kopar);
`kullanici_bilgiler.php` view; `_menu.php` kullanıcı dalına 3 yeni öğe;
kullanıcı şifre formu mevcut genel `sifre.php`'yi kullanır.

**Doğrulama:** tam regresyon **96/96 PASS** (+11 kullanıcı hesap yönetimi:
bilgiler güncelle→DB, adres ekle→varsayılan=1→sil→DB=0, şifre değiştir→yeni
şifreyle giriş, çıkış-confirm markup; +1 bayi-bilgiler-200 çift-modlu
düzenleme güvenlik ağı). `php -l` ×7 temiz; mojibake ×10 temiz. Yol üstü
bulgu: kendi yazım hatamla `set_select(` parantezini kapatmamışım — php -l
yakaladı (hex taraması değil; görüntü doğrulaması bu dosyada yanıltıcıydı).

**[!] Canlıya taşı:** `Hesap.php`, `Kullanici_model.php`, `hesabim/adresler.php`,
`hesabim/kullanici_bilgiler.php`, `hesabim/_menu.php`, `layout/header.php`,
`routes.php` + canlı DB'de güncel `sql/migrate_kullanicilar.sql` (adres tablosu
eklendi — X'ten beri dosyayı koşan yoksa yeniden koş).

---

## 2026-08-15 (X) — Kullanıcı (B2C) girişi + yukarı-çık butonu — 84/84 PASS

**Kullanıcı isteği:** (1) bayi girişinin yanı sıra kullanıcı girişi; navbar'daki
giriş artık kullanıcı girişine gider (bayi girişi footer + köprülerden erişilebilir
kaldı). (2) Sayfa dibinde beliren, en üste taşıyan ok butonu.

**Yeni:** `kullanicilar` tablosu (migration + schema + DEPLOY §3'e eklendi),
`Kullanici` controller (kayıt/giriş/çıkış; bayi deseninde brute-force kilidi 5/15dk,
açık-yönlendirme koruması, durum kontrolü), `Kullanici_model` (siparişler e-posta
eşleşmeli — misafir siparişleri de hesabım'da görünür), iki view. Kayıt anında
aktif (durum=1) — bayi admin-onay kapısından FARKLI bilinçli tasarım.

**Değişen:** MY_Controller'e kullanıcı oturum yardımları (`kullanici_id/kullanici/
giris_yap/cikis`, `v['kullanici']`); navbar üç durumlu (bayi/kullanıcı/misafir);
Hesap iki modlu — kullanıcı modunda dashboard/siparisler/siparis_detay çalışır,
bilgiler/sifre bayiye özel (yönlendirilir), menü koşullu; footer'a `#yukariBtn`
(dibe 40px kala görünür, smooth-scroll üste), CSS+JS (init zincirine `initYukariBtn`).

**Yazım bozulması bulgusu (yeni sınıf):** Write ile üretilen iki view'de `?>`
kapanışı bayt düzeyinde `}}` olarak yazılmış (hex: `29 20 7d 7d`; yalnız `→` oku
içeren satırlarda; php -l "Unmatched '}" — grep görüntüsü ALDATIR, hex doğrula!).
Bayt-güvenli php -r str_replace ile düzeltildi.

**Doğrulama:** tam regresyon **84/84 PASS** (74 + 10 yeni: kayıt→DB durum=1→yanlış
şifre reddi→giriş→hesabım (ad+çıkış linki)→çıkış→hesabım kapalı→navbar köprüsü);
`php -l` ×6 temiz, mojibake byte-grep ×11 temiz. Bayi akışı etkilenmedi (aynı
pakette PASS).

**[!] Canlıya taşı:** `application/controllers/Kullanici.php`, `application/models/
Kullanici_model.php`, `application/views/magaza/kullanici/` (2 dosya), `Hesap.php`,
`MY_Controller.php`, `header.php`, `footer.php`, `_menu.php`, `teksil.js`,
`teksil.css`, `sql/migrate_kullanicilar.sql`, `sql/schema.sql`, `tests/regresyon.php`,
`DEPLOY.md` + canlı DB'de `sql/migrate_kullanicilar.sql` koş.

---

## 2026-08-15 (IX) — Sıfır-DB kurulum provası: DEPLOY.md §3 düzeltildi (5 bulgu) — 74/74 PASS

**B3'ün (canlı kurulum SQL sırası) hiçbir zaman sıfır DB'de koşulmadığı görüldü —
dev DB adım adım evrildiği için drift görünmezdi.** Prova: dev DB yedeklendi
(mysqldump, 37 tablo doğrulandı) → sıfır DB → §3 sırası → tam regresyon →
dev geri yüklendi → tekrar 74/74. **İlk koşu lansman günü olsaydı dosya 4/16'da
dururdu.** Beş bulgu:

1. **`migrate_faz5_fatura.sql`** — `faturalar.siparis_id` INT UNSIGNED, ama
   `schema.sql`'de `siparisler.id` BIGINT UNSIGNED → FK hatası 3780, kurulum
   durur. → kolon BIGINT UNSIGNED'a çevrildi.
2. **`seed.sql`** — varyant seed'i hiç yoktu (dev'deki 4 satır elle girilmiş,
   repoda izsizdi). Varyantsız kurulumda hiçbir ürün satın alınamaz; sepet/
   checkout akışı ölür. → ürün 1-4'e Siyah/S varyantları (devdeki birebir
   stoklarla) seed'e alındı.
3. **`schema.sql`** — dev'de olup şemada olmayan 2 kolon (tam kolon-diff'iyle
   bulundu: dev 308 vs taze 306): `bayiler.son_giris` (login kaydı), 
   `siparisler.email` (sipariş e-postası; suite temizliği bu kolona bağlı).
   İkisi de şemaya işlendi.
4. **DEPLOY.md §3 sırası** — `migrate_yetkiler.sql` seed'den ÖNCE koşuyordu;
   yetkiler FK'sı `roller(id)`'ye bağlı ve roller'i dolduran seed → önce koşunca
   INSERT IGNORE boş roller'e **sessizce 0 satır** ekler (hata yok, exit 0!) →
   rol-2 yönetici her modüle 403 alır. → migrate_yetkiler seed sonrasına taşındı.
5. **DEPLOY.md §3 eksik dosyalar** — `seed_sayfalar_footer.sql` (iletisim/
   toptan-sartlari/xml-feed; YOKSA footer 404 — suite bu sayfaları assert ediyor)
   ve `seed_slider.sql` (demo anasayfa slider'ı) listede yoktu → eklendi.

**Doğrulama:** düzeltilmiş sırayla tek geçiş 16/16 OK → **74/74 PASS** (taze DB);
dev DB geri yükleme sonrası da **74/74 PASS** (yedek→restore güvenliği kanıtlı).
`git status` temiz kaynak + 4 SQL/DEPLOY dosyası.

**[!] Canlıya taşı:** `sql/schema.sql`, `sql/seed.sql`, `sql/migrate_faz5_fatura.sql`,
`DEPLOY.md`.

---

## 2026-08-15 (VIII) — Yerel PROD provası: Apache+HTTPS+SetEnv altında 74/74 PASS + Cloudflare tuzak bulgusu

**Faz B'yi riske atmamak için lansmandan önce konan adım (DEPLOY.md 0b oldu):**
tam üretim yığını — XAMPP Apache 2.4 + öz-imzalı SSL + `SetEnv CI_ENV production`
(vhost) + repo `.htaccess`'i (ilk kez GERÇEK Apache'de) + `application/config/
production/` seti — yerelde ayağa kaldırıldı, `tests/regresyon.php` bu ortama
karşı koştu: **74/74 PASS** (temizlik + stok restore + log denetimi dahil).

**Yol üstünde yakalanan gerçek tuzak:** HTTPS'siz prod modunda (cookie_secure=TRUE)
CI3 `Security.php:271` CSRF çerezini HİÇ yazmaz (`$secure_cookie && ! is_https()` →
`return FALSE`) → tüm formlar 403. İlk koşu 40+ FAIL ile çöktü; nedeni bu.
HTTPS'te çerez düzgün düşer, provadan geçer. **Canlı riski:** Cloudflare Flexible
SSL (origin http görür) aynı 403'ü üretir → DEPLOY.md §5'e "Full (Strict) şart"
uyarısı eklendi. Bu, prova yapılmadan lansman günü bulunamazdı.

**Değişiklik:**
- **`tests/regresyon.php`** — `--insecure` bayrağı (CURLOPT_SSL_VERIFYPEER/HOST;
  yalnızca yerel öz-imzalı sertifika provası, canlı koşuda yasak). +7 satır.
- **`DEPLOY.md`** — yeni §0b (prova reçetesi, birebir bu koşu) + §5 proxy uyarısı.
- Prova artefaktları (vhost bloğu, C:\teksilprova kopyası, geçici config dolgusu,
  prova_router.php) koşu sonrası TAMAMEN geri alındı; `git status` yalnızca
  yukarıdaki iki dosyayı gösteriyor.

**Doğrulama:** koşu çıktısı `74 PASS / 0 FAIL`, exit=0; `.htaccess` guard'ı
canlı kanıt (`/sql/schema.sql` → 403, `/system/...` → 403); çerezler `secure;
HttpOnly` düşüyor; `php -l` temiz; mojibake byte-grep temiz.

**[!] Canlıya taşı:** `tests/regresyon.php`, `DEPLOY.md` (belge — kod değişikliği yok).

---

## 2026-08-15 (VII) — Repo hijyeni: kökteki probe.php repodan kaldırıldı

**14-08 cookie-jar temizliğinin aynı sınıfından kalan tek parça** — kökteki
`probe.php`: türkçe LIKE davranımını izole etmek için yazılmış tek kullanımlık
mysqli probu; içinde yerel dev DB kök parolası düz metin vardı. Hiçbir belge/
deploy adımı referans vermiyor (DEPLOY/CANLIYA-TASIMA/workflow grep temiz) →
`git rm`. Tek dosya; kod/DB değişikliği yok.

**Kalan risk (bilinen, değişmedi):** dev kimlikleri (`mysql1234` yerel kök
parolası, `Tekstil2026!` seed admin parolası) hem GitHub tarihçesinde hem kasıtlı
olarak HEAD'de (`tests/regresyon.php` + seed + workflow.md bunları kullanıyor —
test paketi kimliksiz koşamaz). Hafifletme halihazırda DEPLOY.md'de: prod admin
parolası ilk girişte değişir (m.180), prod DB parolası `SERT_PAROLA_BURAYA`
yer tutucusundan girilir. Tarihçe temizliği (filter-branch) **kararı hâlâ açık**
— alınırsa probe.php + 2 cookie-jar dosyası tek seferde süpürülür.

**Doğrulama:** referans grep'i temiz; `git status` yalnızca silme içeriyor;
repo artık public GitHub'da kök parolası içeren izlenen dosya taşımıyor.

**[!] Canlıya taşı:** yok (repodan çıkarma; bu dosya canlıya gitmemeliydi zaten).

---

## 2026-08-14 (VI) — Bayi onay kapısı açıldı (otomatik onay kapatıldı) — (V)'teki bulgu karara bağlandı

**(V)'teki bulgu kapatıldı: kayıt → admin onayı akışı gerçekten devreye alındı**
(kod yorumu + tüm belgeler bunun hedeflendiğini yazıyordu; "demo: otomatik onay"
etiketi unutulmuştu — canlıda onaysız bayi giriş yapabilirdi).

**Değişiklik:**
- **`application/models/Bayi_model.php`** — `kayit()` insert `durum: 1 → 0`
  (onay bekliyor; admin Bayiler panelinden durum toggle'ı ile açılır).
- **`application/controllers/Bayi.php`** — `kayit_kaydet()` artık otomatik giriş
  yapmıyor (eski akış durum kontrolünü bypass eden session açıyordu + sepet taşıyordu);
  yerine "Kaydınız alındı, onay sonrası giriş yapabilirsiniz" flash'ı + `bayi/giris`
  yönlendirmesi. Giriş tarafı değişmedi — `durum!==1` "henüz onaylanmamış" reddi
  zaten vardı (Bayi.php:94), şimdi anlamlı hâle geldi.
- **`tests/regresyon.php`** — akış yeniden sabitlendi: kayıt durum=0 assert +
  **onaysız giriş reddi** + **onay öncesi hesabim kapalı** + admin onayı + giriş
  → 74 assert oldu.

**Doğrulama:** tam regresyon **74/74 PASS** (bayi akışı onay kapısıyla uçtan uca;
sepet/checkout/fatura/yetki/feed/rate-limit etkilenmedi). `php -l` ×3 temiz,
mojibake byte-grep ×3 temiz. İletişim sayfası + FAZ_A_REHBERI + DoD metinleri artık
davranışla tutarlı (metin değişikliği gerekmedi).

**[!] Canlıya taşı:** `application/models/Bayi_model.php`,
`application/controllers/Bayi.php`, `tests/regresyon.php`.

---


**C7 (lokal) ✓ — `tests/regresyon.php` (yeni, kalıcı).** Daha önceki E2E'ler tek kullanımlık
script'lerdi; C7 artık repo içinde, lansman günü canlıya karşı `--force` ile aynı paket
koşacak. Kapsam (72 assert): yayın sayfaları + sıralama/filtre + arama + 7 CMS sayfası
(hukuki taslak içeriği dahil) + 404 + robots/sitemap + feed 401 + anonim yönetim formu;
bayi kayıt→giriş→sepet(MOQ)→checkout(havale)→sipariş DB→sepet boşalması→başarılı sayfası;
admin 16 sayfa smoke + sipariş detay + durum güncelle + e-fatura "bekliyor"; yetki
matrisi (rol-2 giriş: siparişler 200 / yetkiler **403**); feed geçerli anahtar 200 +
kullanım sayacı + rate-limit 20×403→429 + blok sonrası temizlik; graceful log denetimi
(Eposta/Sms/Efatura atlamaları + testin kendi 404'ü filtreli, kalan hata = FAIL);
temizlik (sipariş+fatura+detay+sepet+bayi+rol2 yönetici+API anahtarı+feed_denemeler+stok
restore, kalan-satır=0 assert). **Sonuç: 72/72 PASS.** Reçete dersleri uygulandı:
CSRF cookie havuzundan, bayi 2-segment/admin 3-segment, redirect 303 kabul, Windows
php -S'te curl jar yok → oturumlar in-memory cookie havuzlarında (4 oturum paralel:
guest/bayi/admin/admin2). Test verisi ASCII + benzersiz e-posta; koşu sonrası DB
eski haline döner (stok dahil). NOT: script içindeki DB kimliği dev lokal kök —
canlı koşuda (C7 lansman günü) prod kimliğine uyarlanır / read-only yeterli değildir
(kayıt yapan akışlar var) — idealde canlıda test bayisi + test kartı ile koşulur.

**BULGU (ürün kararı bekler) — bayi kaydı otomatik onaylı, belgeler aksini vaat ediyor.**
`Bayi_model::kayit()` satır 45: `'durum' => 1, // demo: otomatik onay` → kayıt olur olmaz
bayi giriş yapabilir. Oysa iletişim sayfası ("onay sonrası hesabınız açılır"),
FAZ_A_REHBERI ve DoD akışı "kayıt → **admin onayı**" diyor. Altyapı hazır
(Bayiler admin'de durum toggle var) — eksik yalnızca default. **Karar (İş):** (a) manuel
onay: kayıt `durum=0` yazar, admin onaylar — spam/uygunsuz bayi riskine karşı standart
B2B yaklaşım; ya da (b) otomatik onay: metinler buna göre düzeltilir. Regresyon suite'i
MEVCUT davranışı (otomatik onay) sabitledi; karar verilince assertion + kod/metin
birlikte güncellenir.

**CSS sınıf-uyumsuzluk düzeltmeleri (beden/ödeme/hesabım vurguları).** JS ve PHP
tutarlı biçimde `aktif` sınıfı kullanırken CSS `is-active` kuralları yazılmıştı →
seçili beden butonu / hesabım sekmesi vurguları hiç görünmüyordu, ödeme yöntemi
vurgusunu ekleyen de yoktu. Kullanıcı `beden-btn` kuralını elle düzeltti (is-active→
aktif); aynı hatanın iki yoldaşı kapatıldı: `hesabim-menu a.is-active → a.aktif`
(PHP `aktif` basıyor) ve `odeme-yontem.is-active → :has(input:checked)` (ölü kuralı
radio işaretine bağlandı). Dosya: `assets/magaza/css/teksil.css` (3 kural).

**[!] Canlıya taşı:** `tests/regresyon.php` repoya girer; canlı koşusu lansman günü
`php tests/regresyon.php https://alanadi --force` (DB kimliği uyarlanarak); CSS
dosyası FTP.

---

## 2026-08-14 (IV) — Faz E kapanışı: E3 log izleme + E4 hukuki sayfa taslakları

**E4 — hukuki sayfalar (taslak).** Dört sayfa 26-91 karakterlik stub'dı (checkout
onay kutusu 91 karakterlik sayfaya link veriyordu). **`sql/seed_hukuki_sayfalar.sql`**
(yeni, uygulandı): mesafeli-satis (2872 kr — 6502 + Mesafeli Sözleşmeler Yönetmeliği
+ B2B/tacir mahiyet beyanı), iade-degisim (1774 — hasar/ayıp bildirim süreleri,
muafiyetler, PayTR iade kanalı), gizlilik (1921 — KVKK 6698: veriler/amaçlar/
aktarım/payTR-kargo-entegratör, VUK saklama, m.11 hakları), cerez (922 — yalnızca
zorunlu teksil_sess/teksil_csrf çerezleri gerçeğe uygun; analytics açılırsa onay
mekanizması gerektiği notu). **Yer tutucular:** [FİRMA ÜNVANI]/[ADRES]/[VKN]/
[E-POSTA]/[TARİH] + [POLİTİKA:...] (iade süreleri, teslim süresi, hijyen kategorileri)
— işletme dolduracak; dosya başında AVUKAT ONAYI zorunluluk uyarısı. Doğrulama:
dört sayfa 200 + FFFD=0 + içerik render; DB uzunlukları 922-2872. Sayfalar durum=1
(site canlı değil; canlı öncesi gözden geçirme DEPLOY.md checklist'e eklendi).

**E3 — log izleme.** **`scripts/log_kontrol.sh`** (yeni, POSIX sh): günlük CI3 log'undan
ERROR sayısı + mesaj bazlı gruplu son 10 hata; sıfır hatada tek OK satırı (cron
yakalar). Fonksiyonel test: geçersiz yol → uyarı; boş gün → OK; 3 ERROR'lu simüle
gün → doğru sayım + gruplama (2×bir, 1×iki); test logu silindi. DEPLOY.md **7b**:
cron satırı (07:35) + uptime izleme dışarıdan (UptimeRobot tipi: anasayfa 200 +
/feed/urunler 401 beklenen — API canlılık göstergesi); checklist'e uptime + hukuki
sayfa gözden geçirme maddeleri eklendi.

**Durum:** Faz E'nin dev-tarafı kapandı (E1-E4); E4'ün kalanı iş kararı (yer tutucu
doldurma + hukuk onayı). B3 kurulum sırasına `seed_hukuki_sayfalar.sql` eklendi
(seed.sql'den sonra, adım 3).

**[!] Canlıya taşı:** `sql/seed_hukuki_sayfalar.sql` (prod DB'de seed sonrası),
`scripts/log_kontrol.sh`, `DEPLOY.md` güncel haliyle.

---

## 2026-08-14 (III) — Faz A açılışı: panel kimlik girişi E2E + sessiz veri kaybı fix + durum şeridi + işletme rehberi

Faz A "kod yok" sahipliği İş'te — ama açılışta dev tarafının gerçekten hazır
olması için dört şey yapıldı: panelden giriş uçtan uca kanıtlandı, yol üzerinde
bir veri-kaybı bug'ı düzeltildi, Ayarlar'a durum göstergesi eklendi, işletme
rehberi yazıldı.

**Bug fix — Ayarlar kaydı formda olmayan alanları siliyordu.** `Ayarlar::kaydet()`
whitelist'in TAMAMINI her POST'ta upsert ediyordu; whitelist'te olup ayarlar
formunda alanı bulunmayan `meta_title`, `duyuru_2`, `duyuru_3` her kayıtta NULL ile
eziliyordu (sessiz veri kaybı — içerik girildikten sonra lansmanda patlayacaktı).
**Düzeltme:** POST'ta hiç gelmeyen non-toggle anahtar atlanır (`input->post() ===
NULL → continue`); toggle davranışı korunur (checkbox yok = 0), bilinçli boşaltma
(boş string) çalışmaya devam eder. Dosya:
`application/controllers/yonetim/Ayarlar.php`. Doğrulama: E2E'de yalnızca Faz A
alanlarını POST'larken `site_adi`/`meta_title`/`duyuru_1` değişmedi (3 assert PASS).

**E2E — panel kimlik girişi (29/29 PASS).** Test (in-memory cookie PHP script,
sunucu localhost:8000): admin girişi → Ayarlar'dan 20 Faz A alanı (SMTP 6, SMS 4,
PayTR 4, e-fatura 6; 4'ü toggle) test değerleriyle kaydet → 3xx + hepsi DB'de
kalıcı (20 assert) → formda-olmayan-alan koruması (3 assert) → temizlik POST'u →
dolu kimlik kalmadı. Not: CI3 `redirect()` bu projede **303** dönüyor (302 değil) —
E2E assert'leri 3xx aralığına yazıldı; Windows php -S'te curl cookie-jar yazmıyor
(jar yerine in-memory cookie yönetimi — ci3-http-test-recipe'ye ek ders).
Test scripti koşulduktan sonra silindi.

**Boş kimlik davranışı doğrulaması (kod okuması — hepsi graceful):**
- Eposta: `hazir()` FALSE → error-log + atla; çağıranlar `@` ile bastırıyor;
  sipariş akışı bozulmuyor (`Odeme.php:96-102`).
- Sms: `hazir()` FALSE → atla (Sms.php:44).
- PayTR: sipariş ÖNCE oluşur (`mg_olustur`), `hazir()` değilse `odeme/basarili`'ye
  düşer — müşteri mahsur kalmaz (`Odeme.php:107-114`); token sayfası NULL token +
  hata gösterir (Paytr.php:26).
- E-fatura: `hazir()` değilse fatura **"bekliyor"** kalır, kimlik girilince cron
  gönderir (Efatura.php:134-135); admin fatura detayında "yapılandırılmadı" uyarısı
  görünür.

**Yeni — Ayarlar'da "Entegrasyon Durumu" şeridi.** Ayarlar sayfası üstünde 5 satır
(E-posta/SMS/PayTR/E-Fatura/Pazaryeri): doluluk kütüphanelerin `hazir()` koşullarıyla
paralel; PayTR/e-fatura test modundayken uyarı rozeti; pazaryeri satırı aktif hesap
sayısından okur (`Ayarlar::index()` sayacı + view şeridi). Doğrulama: boşken 5×
"girilmedi", PayTR doluyken ✓ + "TEST modu" rozeti (canlı curl), test verisi temizlendi.

**Yeni — `FAZ_A_REHBERI.md`** (repo kökü): işletme tarafına A1-A5 başvuru rehberi —
her kimlik için nereden alınır, hangi alanlar toplanır, panele hangi karttan girilir,
giriş sonrası birlikte ne test edilecek; sıra önerisi + dikkatler (PayTR callback
canlı domain ister, SMS başlık onay süresi, e-fatura VKN/ünvan birebirliği, SPF).

**[!] Canlıya taşı:** `application/controllers/yonetim/Ayarlar.php`,
`application/views/yonetim/ayarlar/index.php`, `FAZ_A_REHBERI.md` — sonraki push'la.

---


## 2026-08-14 (II) — Faz B hazırlığı: production config seti + DEPLOY.md + yedek scripti

Amaç: hosting alındığı gün yapılacak işi yarıya indirmek — kod tarafı hazır,
kalan adımlar mekanik checklist'e insin.

**CI3 ENVIRONMENT override mekanizması (kaynaktan doğrulandı + empirik test edildi).**
Önemli asimetri: `get_config()` (system/core/common.php:253) önce TEMEL
config.php'i yükler, sonra `config/production/config.php` VARSA ÜZERİNE bindirir →
production config'i parçalı olabilir. Ama `DB()` (system/database/DB.php:58)
environment database.php VARSA temel database.php'yi HİÇ yüklemez → production
database.php EKSİKSİZ $db tanımı içermek zorunda. Her iki dosya bu asimetriye göre
yazıldı.

**Yeni dosyalar:**
- **`application/config/production/config.php`** — yalnızca prod farkları:
  `base_url` (alan adı yer tutucusu), `sess_save_path = APPPATH.'sessions'`
  (Linux yolu; dev'deki C:/xampp/tmp değil), `cookie_secure=TRUE` +
  `cookie_httponly=TRUE` (JS CSRF'i `window.tkCsrf` + gizli input'tan alıyor,
  cookie'den değil — teksil.js:27 doğrulandı → httponly güvenli), `compress_output`
  açıklaması (mod_deflate var, çifte gzip yok), `proxy_ips` notu.
- **`application/config/production/database.php`** — eksiksiz tanım + ayrıcalıklı
  kullanıcı şablonu (CREATE USER teksil_app + tek-DB GRANT; root yok) + `stricton=TRUE`.
- **`DEPLOY.md`** — Faz B'nin (B1–B8) komut-level runbook'u: kod taşıma, config
  doldurma, DB kullanıcı + 12 migration'lı kurulum sırası (seed SON), dizin izinleri,
  `SetEnv CI_ENV production` (cPanel/vhost/Plesk), cron satırı (CI_ENV öneki şart,
  PHP CLI env'leri okur), yedek cron'u, ilk-kurulum doğrulama listesi (9 madde),
  rollback. NOT: cron/CLI'daki CI_ENV davranışı Linux'ta ilk koşuda teyit edilecek
  (Windows cli-server SAPI'de env'in $_SERVER'a düşmediği görüldü — web testi
  front-controller zorlamasıyla yapıldı; Apache SetEnv yolu aynı mekanizma).
- **`scripts/yedek.sh`** — mysqldump --single-transaction + uploads tar + 14 gün
  rotasyon; POSIX sh; uzak kopya rsync örneği yorumda. `sh -n` temiz.
- **`.gitignore`** += `application/sessions/`.

**Empirik doğrulama (2026-08-14):** production config gerçek (local) değerlerle
geçici dolduruldu → `CI_ENV=production` önekiyle sunucu ayağa kaldırıldı. Bulgular:
(1) Windows php -S'te env değişkeni $_SERVER'a DÜŞMÜYOR → ENVIRONMENT development
kaldı, form action'ları :8000'den geldi (ilk deneme); (2) `$_SERVER['CI_ENV']`
zorlamalı geçici front-controller ile: anasayfa 200, form action `:8001`den
(production base_url override'ının kanıtı), `teksil_sess` cookie'de `secure;
HttpOnly` (önceki denemede bu bayraklar yoktu — yan not: session HttpOnly CI3'ün
satır-179 sabidiymiş, Secure config'e bağlı). Test sonrası config'ler yer tutucuya
geri çevrildi, geçici dosya silindi, `php -l` ×2 + mojibake byte-grep temiz.

**[!] Canlıya taşı:** repodaki 5 dosya (2 production config şablonu — sunucuda
doldurulacak, DEPLOY.md, scripts/yedek.sh, .gitignore) bir sonraki push'la gitmiş
olur; hosting'de DEPLOY.md sırayla uygulanır.

---


## 2026-08-14 — Faz E sertleştirme: Feed API rate-limit + SQLi/XSS taraması + perf index'leri + repo hijyeni

**E1 — SQLi taraması (SONUÇ: temiz, kod değişikliği yok).** Tüm Query-Builder-dışı
dizinler tarandı: `->query()` **0** adet; `where(...,NULL,FALSE)` raw ifadeleri 27 adet —
hepsi ya sabit SQL parçası (`deleted_at IS NULL` vb.) ya da güvenli kaynaklı:
`Sayfa.php:32` `$ids` = `array_map('intval', ...)`; `Urun_model.php:144` `$idler` =
`(int)` cast; `Rapor_model.php:86` `$alan` iki sabit literale whitelist'li;
`Dashboard_model.php:81-87` `$sel` üç sabit literalden seçili. `order_by` interpolasyonları
ya sabit ya `(int)` cast'li.

**E1 — Feed API rate-limit (yeni).** `/feed/urunler` makine-makine ucunda IP tabanlı
brute-force koruması yoktu (256-bit random + sha256 anahtar brute-force'a pratikte
dayanıklı; katman savunma-derinliği + DB şişirme koruması). Session kullanılamaz (CI_Controller,
cookie'siz istek) → `feed_denemeler` tablosu: pencere içinde 20 başarısız deneme → 15 dk blok,
429 + hash sorgusu bile yapılmaz; doğru anahtar sayacı sıfırlar (NAT arkası meşru tüketici
etkilenmez — yalnız yanlış deneme sayılır). Dosyalar:
**`sql/migrate_feed_rate_limit.sql`** (yeni tablo, uygulandı),
**`application/models/Api_anahtar_model.php`** (`bloklu_mi`/`deneme_kaydet`/`deneme_temizle`
+ `DENEME_ESIK`/`PENCERE` sabitleri), **`application/controllers/api/Feed.php`** (`_anahtar()`
içine 429 yolu). Doğrulama (canlı, localhost:8000): anahtarsız → 401 (sayaçsız);
21 yanlış key → 20×403 + 21.'si **429**; `feed_denemeler` = (::1, 20); blokliyken doğru
key bile 429; sayaç temizlenince doğru key → 200 + `kullanim_sayisi=2`; 1 yanlış + doğru →
sayaç silindi. Test anahtarı (id=8) silindi. `php -l` temiz ×2, mojibake byte-grep temiz.

**E1 — XSS + upload taraması (SONUÇ: 2 küçük sertleştirme).** 791 `<?=` çıktısının
tümü `e()`/helper/`(int)` cast; raw `echo $` yalnız sistem `errors/` view'larında;
textarea'larda kaçırılmış çıktı yok; CMS `icerik` bilinçli-tasarım admin-HTML (view'da
belgeli). Sertleştirmeler: (1) **`application/views/magaza/urun/detay.php`** — `pdVeri`
JSON'una `JSON_HEX_TAG` eklendi (`</script>` kırılımını kapatır; admin girişli ürün
adı/renk dizesi mağaza tarafında script bloğuna gömülüyor). Doğrulama: `/urun/suprem-v-yaka-body`
200, JSON sağlam. (2) **`uploads/.htaccess`** (yeni, takipli) — yüklenen dosyalarda PHP/
CGI çalıştırmayı Apache düzeyinde yasaklar (polyglot görsel savunması; Nginx'te eşdeğeri
B2'de elle verilecek). Bannerlar `_gorsel_yukle()` mevcut doğrulama sağlam: is_uploaded_file +
katı uzantı whitelist + getimagesize + 4MB + random dosya adı.

**E2 — EXPLAIN + perf index'leri (yeni).** EXPLAIN boşlukları: (1) katalog varsayılan
sıralama `durum=1 ORDER BY olusturma_zaman DESC` → `type=ALL` + `Using filesort`;
(2) fiyat sıralaması aynı durumda; (3) rapor/trend tarih aralığı (`olusturma_zaman`)
index'siz `type=ALL`. **`sql/migrate_perf_index.sql`** (uygulandı): `urunler(durum,
olusturma_zaman)`, `urunler(durum, fiyat)`, `siparisler(olusturma_zaman)`. Sonrası EXPLAIN:
yeni/fiyat sıralaması `ref` + covering + **filesort yok** (Backward index scan);
siparisler `FORCE INDEX` ile `range` + `Using index condition` kanıtlandı (18 satırda
optimizer tam taramayı seçiyor — veri büyüyünce otomatik range'e döner). Katalog
sayfaları 200 (sırasız/filtreli). Beden/renk facet ve filtreli sayım sorguları zaten
index'li join — dokunulmadı.

**Repo hijyeni.** (a) Depoda **`.gitignore` hiç yoktu** → eklendi: `application/logs/*.php`,
`uploads/*` (`.htaccess` muaf), curl cookie-jar artıkları (`teksil_csrf*`), OS çöpü.
(b) Kökte yanlışlıkla oluşmuş **2 curl cookie-jar dosyası** (dosya ADINDA admin e-posta +
şifre, İÇİNDE aktif session cookie) git takibindeydi VE **origin/main'e (GitHub) itilmişti**
→ `git rm` + diskten silindi + ignore edildi; `application/logs/log-2026-08-1[23].php`
untrack edildi (B9 ✓). Not: GitHub tarafındaki tarihçe bu yüzden şifre içeriyor —
repo özel olsa da öneri: admin şifresini lansman öncesi değiştir / istenirse tarihçe
filter-branch ile yeniden yazılır (kullanıcı kararı).

**[!] Canlıya taşı:** `sql/migrate_feed_rate_limit.sql` + `sql/migrate_perf_index.sql`
production DB'de çalıştırılmalı; PHP: `application/controllers/api/Feed.php`,
`application/models/Api_anahtar_model.php`, `application/views/magaza/urun/detay.php`;
repo kökü: `.gitignore`, `uploads/.htaccess`.

---

## 2026-08-13 — Mağaza JS'i hiç yüklenmiyordu (teksil.js bağlantısı eksik) + seri fiyatı

**Kritik latent bug:** `views/magaza/layout/head.php` yalnız `teksil.css` yükler, **`teksil.js` hiçbir
view'da `<script>` ile bağlı değildi** → mağazada TÜM JS sessizce çalışmıyordu (katalog anlık filtre,
ürün detay varyant/adet/basamak, `sepet/ekle` AJAX, checkout canlı tutar, sepet sayacı). curl tabanlı
testler JS çalıştıramadığından ve betik hiç inmediğinden bu fark edilmemişti.
**Düzeltme:** `footer.php`'ye `</body>` öncesi `<script src="<?= asset('magaza/js/teksil.js') ?>"></script>`
eklendi (`asset()` filemtime ile cache-bust ekler → eski JS önbellek sorunu da çözülür).
**Doğrulama:** /katalog HTML'sinde `teksil.js?<mtime>` artık render; ürün detayında tüm JS hook'ları
mevcut (`pdVeri` JSON, `anaGorsel`, 4×`renk-sw`, 4×`beden-btn`, `adetInput`, `pdSepet`, `pd-basamak`);
`sepet/ekle` AJAX → `{ok:1, adet:6, mesaj:"Ürün sepete eklendi."}` (JS'in beklediği şekil), sepet
ürünü gösteriyor.

**Seri fiyatı (toptan) ürün kartlarına:** `Urun_model::seri_ekle()` (yeni public metot — ürün-özel VEYA
global `fiyat_basamaklari`'nin en yüksek indirim oranını bulur) → `mg_liste`/`mg_arama` + `_normalize`
(`seri_fiyat`=fiyat×(1−yüzde), `seri_adet`); `urun_karti.php` + favorilerim kartı "Seri X ₺ (N+ adette)"
satırı; `teksil.css` `.prodcard__seri` (yeşil vurgu) + `.prodcard__adet-etiket`. Doğrulama: 12 kartta
"100+ adette" + seri render (ör. 79.90→71.91 = ×0.90, global %10 @100+); favorilerimde de.

**[!] Canlıya taşı:** `layout/footer.php` (script) + `models/Urun_model.php` + `partial/urun_karti.php` +
`views/magaza/sayfa/favorilerim.php` + `controllers/Sayfa.php` + `css/teksil.css`. (Seri fiyatı yorumu:
"seri" = en iyi miktar-indirimi birim fiyatı olarak uygulandı; MOQ toplamı kastedildiyse kolay düzeltim.)

---

## 2026-08-13 — Favoriler ana_gorsel hatası + katalog sidebar anlık filtre/scroll

**Bug:** `favorilerim.php:25` `$u->gorsel` kullanıyordu ama `Sayfa::favorilerim()` ham sorgu çalıştırır,
görsel sütunu `ana_gorsel` (katalog `_normalize` ile `gorsel` map eder). → "Undefined property
stdClass::$gorsel" PHP uyarısı. Düzeltme: `ana_gorsel`.

**Katalog sidebar:** "Filtrele" butonu + "Filtreleri temizle" linki kaldırıldı; anlık filtre (checkbox
+ fiyat input `change` → `form.submit()`, sayfa 1'e sıfırlar — `teksil.js initFiltre` genişletildi);
`.filtre-sarma` özel scroll bölgesi (`max-height`+`overflow`+`overscroll-behavior`+stil scrollbar);
mobil açıcı sınıf uyumsuzluğu düzeltildi (JS `.acik` ↔ CSS `.is-open`) — mobil filtreler açılmıyordu;
mobil düğme "⚙ Filtrele" → "⚙ Filtreler". (Not: JS bu sırada hiç yüklenmediği için yukarıdaki script
düzeltmesine kadar sidebar anlık filtresi tarayıcıda çalışmıyordu.)

**Değişen:** `favorilerim.php`, `partial/filtre.php`, `katalog/index.php`, `js/teksil.js`, `css/teksil.css`.
Doğrulama: favoriler uyarısı gitti + görsel render; 16/16. UTF-8/curly/lint temiz.

---

## 2026-08-13 — Faz C doğrulaması: Arama + SEO + Markalar/Kategoriler/Sayfalar + Raporlar + Hesap

TAMAMLAMA_PLANI Faz C'nin doğrulanabilir 5 alt sistemi uçtan uca test edildi. **Kod değişikliği
YOK — hepsi doğruydu.** (C6 pazaryeri cred gerektirir → ertelendi; C7 lansman öncesi regresyon.)

**C1 Arama — 10/10:** boş/ASCII/Türkçe(%-enc) sorgu, no-match boş sonuç, `ad/stok_kodu/aciklama`
LIKE, noindex meta. (Plan notu düzeltildi: "robots.txt yok" yanlıştı — `Seo::robots()` var + routed.)
**C4 SEO — 11/11:** `sitemap.xml` well-formed urlset (42 URL: ürün+kategori+katalog), `robots.txt`
200 (route `robots\.txt => seo/robots`), `arama_index=1` → `/yonetim` engelli + Sitemap referansı.
**C5 Markalar/Kategoriler/Sayfalar — 29/29:** CRUD + `slug_tr` üretimi + `-2` benzersizlik, ağaç
(üst/alt kategori, `mg_sil_kontrol` alt'ı/ürünü olanı engeller), HTML içerik korur, validasyon,
rol-2 gate'leri. (Not: boş kategori adı `show_404` döner — tasarım, hata değil.)
**C3 Raporlar — 19/20 (1 kozmetik):** 6 rapor render + CSV(UTF-8 BOM,`;`)+PDF(HTML) export, tarih
swap, fallback; `satis_ozet` ciro == `SUM(toplam*kur)` brut kuralı (43.346,70 ₺; iptal/iade dışlanır,
18 toplam/17 brut). **kur çarpanı KANITLANDI:** #39 TRY→USD(32.5) → ciro tam 5936.40×31.5=186.996,60
arttı (toplam*kur, salt toplam değil) → çoklu-para-birimi normalizasyonu çalışıyor; geri yüklendi.
("ciro gösterimi" assertion'ı kusurluydu: view `Brüt Ciro`+para_tr render ediyor — satır 31.)
**C2 Hesap (bayi self-servis) — 19/19:** auth gate (no session→bayi/giris), dashboard/siparişler/
bilgiler/şifre render, **IDOR koruması** (B1 kendi #22→200, başkası #23→404, yok #9999→404 —
`mg_siparis_getir` sahiplik izolasyonu), bilgiler güncelle+validasyon, şifre değiştir (yanlış eski
reddi + hash değişmez, doğru eski→yeni hash+yeni şifrele giriş, matches validasyonu). (Test hatası
düzeltildi: hesabim/* route'ları temiz alias kullanır — `siparis/{id}`, `bilgiler/kaydet`,
`sifre/kaydet`; controller metod adları DEĞİL — view'ler doğru linkliyor.)

Toplam Faz C: **88/89** (1 kozmetik assertion). Sunucu/CI log temiz; test verisi tam geri alındı.

**[!] Canlıya taşı:** kod değişikliği yok (salt doğrulama).

---

## 2026-08-13 — D0: Ayarlar'a PayTR + e-fatura kimlik alanları (panelden kaydedilemiyordu)

`Ayarlar::kaydet()` yalnızca `$WHITELIST` iterasyonu yapıyordu; whitelist SMTP/SMS'yi içeriyor
ama **`paytr_*` / `efatura_*` içermiyordu** → admin panelden girdiği PayTR/e-fatura kimlikleri
**sessizce drop edilip** hiç kaydedilmiyordu (DB'de `paytr_*` "ayar yok" olmasının nedeni bu).
Ayrıca **PayTR kartı view'da hiç yoktu** (e-fatura kartı vardı ama o da whitelist dışı).

**Değişen:**
- `controllers/yonetim/Ayarlar.php` — `$WHITELIST`'e eklendi: `paytr_merchant_id`,
  `paytr_merchant_key`, `paytr_merchant_salt`, `paytr_test`, `efatura_entegrator`,
  `efatura_api_url`, `efatura_token`, `efatura_firma_vkn`, `efatura_firma_unvan`, `efatura_test`;
  `$TOGGLES`'a `paytr_test`, `efatura_test`.
- `views/yonetim/ayarlar/index.php` — yeni **PayTR (Kartlı Ödeme)** kartı (Mağaza ID text,
  anahtar/tuz `type=password`+`autocomplete=new-password`, test checkbox) e-fatura kartından sonra.

(Pazaryeri hariç: hesaplar ayrı `pazaryeri_hesaplari` tablosunda, Pazaryeri::hesap_kaydet
ile yönetilir — Ayarlar'a ait değil.)

**Doğrulama (HTTP, 20/20 PASS):** super login → tüm form POST (mevcut değerler + test kimlikleri)
→ `kaydet` 30x → DB assert: 10 kimlik (4 paytr + 6 efatura) **kalıcı** (toggle'lar `paytr_test`/
`efatura_test` → '1'); diğer ayarlar **korunuyor** (site_adi/smtp/sms unchanged); GET ayarlar 200
+ PayTR kartı input'ları render + değer doluyor; rol-2 `ayarlar/goruntule=0` → 403 (gate sağlam).

**Yan bulgu 1 (düzeltildi):** view'a eklediğim PayTR kartında `"` tırnaklar Edit transitinde
**curly (U+201D)** olmuştu — FFFD/UTF-8 valid/Türkçe sağlam, yalnızca `strpos` + tarayıcı
parse'ı ifşa etti. Byte-level toplu `str_replace(curly→")` ile düzeltildi (55 sağ + 1 sol).
**Yan bulgu 2 (not, kapsam dışı):** whitelist `meta_title`/`duyuru_2`/`duyuru_3` içeriyor ama
view bu alanları render etmiyor → her kaydet bunları null'a çeker. Şu an hepsi boş + duyuru_2/3
okunmuyor, meta_title fallback'li → etkisiz (latent). İleri düzeltme: `kaydet()`'te posted-
olmayan non-toggle key'leri skip (null yerine koru).

Lint temiz; sunucu/CI log temiz; UTF-8 + curly-quote byte-tarama temiz; test verisi temizlendi.

**[!] Canlıya taşı:** `controllers/yonetim/Ayarlar.php` + `views/yonetim/ayarlar/index.php`.

---

## 2026-08-13 — Stok/Kuponlar/Bannerlar admin akış doğrulaması + kupon negatif-toplam bug fix

Pazaryeri **ertelendi**: 0 hesap yapılandırılmış + senkron metotları `hazir()` ile gerçek
platform cred'i (Trendyol/Hepsiburada api_key/secret/supplier_id) zorunlu kıldığı için canlı
senkron yerelde doğrulanamaz (yalnız graceful-skip). Onun yerine **Stok, Kuponlar, Bannerlar**
admin akışları doğrulandı.

**Bulunan ve düzeltilen bug — kupon negatif sipariş toplamı (money):**
`Kupon_model::dogrula()` indirimi yalnızca `max(0, …)` ile alttan kapatıyordu (üst sınır yok).
`Siparis_model` ise `toplam = ara_toplam − indirim + islem + kargo` hesaplıyordu (kapatma yok).
Sonuç: **sabit kupon subtotal'i aşınca** (ör. "200 TL indirim" 150 TL sepette) VEYA **yuzde>100**
kuponu → sipariş toplamı **negatif**. CI3 bootstrap + `dogrula()` doğrudan çağrısıyla kanıtlandı:
sabit 500 / ara_toplam 300 → indirim 500 → **toplam −200**; yuzde 150 / 300 → toplam −150.
**Düzeltme:** `dogrula()` artık `indirim = min(indirim, ara_toplam)` kapatması yapıyor (3 çağrı
yeri: Odeme görüntüleme, `kupon_uygula` flash, `Siparis_model._kupon_indirim` + snapshot —
hepsi güvende). Boş sepette (ara_toplam=0) indirim 0. Kontrol davranışları bozulmadı (8/8).

**Doğrulama (HTTP, 29/29 PASS):**
- **Stok (9):** liste + `filtre=sifir` + hareketler; transactional `duzelt` (248→258→0→248) —
  `stok_hareketleri` satırı `tip=duzeltme`, `onceki_stok` doğru, `adet` imzalı fark (+10); negatif
  `yeni_stok=-5`→0 clamp; `yonetici_loglari` audit; gate (rol-2 duzenle=0→403).
- **Kuponlar (9):** CRUD + kod sanitize (`'e2e test-1'`→`'E2ETEST-1'`, büyük+geçersiz-karakter
  silme); boş kod validation red; gate; sil.
- **Bannerlar (11):** URL görsel CRUD + `yazi_konum` whitelist (`sag`/`orta`/`sol`); görsel
  zorunlu (boş→redirect, eklenmez); edit (sira/durum); gate; sil (URL görsel→disk temizliği yok).

Lint temiz; sunucu/CI log temiz; UTF-8 temiz; test verisi + varyant stoğu geri yüklendi.

**[!] Canlıya taşı:** `models/Kupon_model.php` (negatif-toplam fix). Stok/Bannerlar kod değişikliği yok.

---

## 2026-08-13 — Faz 5: B2B Feed (API/XML) alt sistemi uçtan uca doğrulama

B2B toptancı katalog feed'i (`/feed/urunler`, makine-makine) + admin anahtar yönetimi
(`yonetim/feed`) uçtan uca doğrulandı. **Kod değişikliği YOK — alt sistem doğruydu.**

**Kapsam (HTTP, cookie-jar CSRF + direkt API çağrısı):**
- **Admin CRUD (super):** anahtar üret → plaintext tek sefer gösterilir, DB'de yalnız
  `sha256(key)` + 8-karakter `onek` saklanır (plaintext sütunu YOK, sızma yok); liste/toggle/sil.
- **Güvenlik (public auth matrisi):** anahtar yok → **401**, geçersiz/pasif/silinmiş → **403**,
  hepsinde ürün verisi sızdırılmaz; geçerli anahtar → **200**. `?key=` VE `X-Api-Key` başlığı
  ikisi de çalışır.
- **Format:** XML (DOMDocument, 28 `<urun>` well-formed) ve JSON (`?format=json`, 28) aynı
  veriyi verir; `feed_liste()` gizli/silinmiş ürün sızdırmaz (`durum=1 AND deleted_at IS NULL`).
- **Kullanım takibi:** her başarılı çağrı `kullanim_sayisi`+1 ve `son_kullanim` yazar (atomik).
- **Yaşam döngüsü:** disable→403, re-enable→200, delete→403.
- **Yetki gate'leri (bugün eklenen constructor gate dahil):** rol-2 `feed/goruntule=0`→403;
  `goruntule=1,duzenle=0`→liste 200 ama `olustur` 403.

**Sonuç:** 33/35 assertion PASS; 2 "fail" test-harness hatasıydı (XML `<urun>` sayımında
`substr_count('<urun>')` kullanıldı, oysa açılış tag'i nitelikli `<urun id="…">` →
`preg_match_all('/<urun\b/')`=28 ile JSON sayısı (28) birebir uyuştu; ham çıktı incelenerek
doğrulandı, uygulama hatası yok). Sunucu/CI log temiz; UTF-8 temiz; test verisi temizlendi.

**[!] Canlıya taşı:** kod değişikliği yok (salt doğrulama).

---

## 2026-08-13 — Faz 5: Rol bazlı yetki matrisi (goruntule/duzenle/sil zorunluluğu)

**Süper admin (rol 1) harici tüm roller** için rol×modul×işlem ({görüntüle,düzenle,sil})
matrisi devreye alındı. Önceden `Auth_admin::yetki()` rol 2'ye koşulsuz `TRUE` döndürüyordu
("yönetici her şeyi yapar"); artık `yetkiler` tablosundan kontrol ediliyor.

**Dosyalar:**
- `libraries/Auth_admin.php` — `yetki()` gerçek matris kontrolü (rol 1 = daima tam; diğerleri
  `yetkiler` tablosu; istek başına tek sorgu, `$_yetki_cache`; bilinmeyen işlem/eksik tablo →
  `FALSE`). Yeni `_yukle()`.
- `core/MY_Controller.php` — `render()` menüyü rol bazlı filtreler: `dashboard` her zaman
  görünür, `yetkiler` yalnız süperde, diğerleri `yetki(modul,'goruntule')` ile; `para_birimi`
  → `ayarlar` iznine eşlenir. Yeni menü öğesi "Yetki Matrisi" (⊕).
- `controllers/yonetim/Yetkiler.php` (yeni) — süper-only matris UI (rol seç + grid kaydet).
- `models/Yetki_model.php` (yeni) — `::$MODULLER` (14 modül kelime dağarcığı) + `liste()`/`kaydet()`
  (kapatılan kutuları 0'a yazar; toplu upsert).
- `views/yonetim/yetkiler/index.php` (yeni) — rol seçici + 14×3 checkbox matrisi.
- **Zorunluluk (gap fix):** 13 yönetim controller'ının `__construct()`'una `yetki_gerek(modul,
  'goruntule')` eklendi (Siparisler, Urunler, Kategoriler, Markalar, Bayiler, Faturalar,
  Pazaryeri, Feed, Bannerlar, Sayfalar, Kuponlar, Ayarlar, Para_birimi). Daha önce yalnızca
  `duzenle`/`sil` eylemleri ve Stok/Raporlar index'i gatedi; liste+detay sayfaları goruntule=0
  olsa bile doğrudan URL'den erişilebiliyordu. Constructor gate'i modüldeki tüm rotaları kapatır
  (UI sözü: "işaretsiz modül/işlem = 403"). `duzenle`/`sil` metodlarındaki mevcut gate'ler
  katmanlı korunur.

**DB:** `yetkiler` tablosu (id, rol_id→roller, modul, goruntule/duzenle/sil TINYINT;
`UNIQUE(rol_id,modul)`). Seed: rol 2 "Yönetici" = tam erişim (eski davranışın korunması; süper
kısıtlayabilir). Süper (rol 1) tabloda yer almaz (kodde sabit tam). Migration:
`sql/migrate_yetkiler.sql` (INSERT IGNORE, güvenli re-çalıştırma).

**Doğrulama (3 HTTP test paketi, cookie-jar CSRF yöntemi):**
1. Süper admin matris CRUD — 12/12: giriş→matris render (14 modül × 3 = 42 input)→kaydet
   (302→?rol=2)→DB assert (siparişler g1d1s0, kategoriler g1d0s0, ürünler tamamen kapandı).
2. Zorunluluk (rol-2, yalnız siparişler+kategoriler açık) — 18/19: açık modüller 200, **13
   kapalı modülün tümü 403** (urunler/markalar/stok/bayiler/faturalar/pazaryeri/feed/raporlar/
   bannerlar/sayfalar/kuponlar/ayarlar/para_birimi), `urunler/duzenle/1`=403, `yetkiler`=403,
   menü Yetki Matrisi'ni gizler. (1 "fail" = `siparisler/detay/1` non-200 — id=1 siparişi yok,
   gate değil veri sorunu; gerçek sipariş id ile 200 — paket 3.)
3. Yetkili akış regresyonu (rol-2 full) — 4/4: `siparisler/detay/22`=200, `urunler/duzenle/1`=200,
   faturalar/ayarlar index=200 (gate yetkiliyi bozmaz).

Lint 17 dosya temiz; sunucu/CI log temiz (fatal yok); test sonrası rol-2 varsayılana (full)
geri yüklendi, temp admin silindi; UTF-8 (FFFD) temiz.

**[!] Canlıya taşı:** FTP → 13 controller + `MY_Controller.php` + `Auth_admin.php` + 3 yeni dosya
(Yetkiler.php, Yetki_model.php, views/yonetim/yetkiler/index.php). DB → `sql/migrate_yetkiler.sql`
çalıştır (tablo yoksa oluşturur + rol-2 seed; varsa INSERT IGNORE no-op).

---

## 2026-08-12 — Admin ürün CRUD doğrulama + varyant-ID korunumu (iade stok sızıntısı)

Admin ürün CRUD'u uçtan uca doğrulandı: ekle (slug otomatik + benzersiz, varyant + fiyat
basamağı), düzenle, durum toggle, soft-delete (`deleted_at`+`durum=0`, admin listesinden
gizli), validasyon red (adsız), slug benzersizlik (aynı ad → `-2` soneki). 14/14 PASS.
Slug/endeks/doğrulama mantığında bug yoktu.

**Ama gerçek bir data-integrity bug'ı bulundu ve düzeltildi — varyant-ID değişimi (iade
stok sızıntısı):** `mg_varyant_kaydet` her ürün düzenlemede tüm varyantları **silip yeniden
ekliyordu** (ID yenileniyordu). Siparişi olan bir ürün admin düzenlerse (örn. fiyat),
`siparis_detaylari.varyant_id` + `stok_hareketleri.varyant_id` referansları boşta
kalıyordu. Kritik sonuç: **iade akışında stok geri-yükleme** (`UPDATE urun_varyantlari SET
stok=stok+N WHERE id=varyant_id`) 0 satır etkileyip iade edilen stoğu geri eklemiyordu —
envanter sızıntısı.

**Düzeltme:** `mg_varyant_kaydet` artık **değiştir-atıl yerine birleştir** — (renk,beden)
eşleşen mevcut varyant GÜNCELLENİR (ID + referanslar korunur), yeniler eklenir, çıkarılanlar
silinir. Yeni `_vkey()` yardımcısı (renk \x1F beden anahtarı).

**Doğrulama:** (A) korunum — [KM,ML]→[KM-koru,YS]: KM id aynı (stok güncellendi), ML silindi,
YS eklendi; (B) iade senaryosu — sipariş (stok 100→94) → ürün düzenle (vid korundu) → admin
iade → stok 94→100 (eski kodda vid silinip sızıntı olurdu); (C) CRUD regression (durum
toggle + soft-delete). 13/13 PASS. Lint/FFFD temiz; sunucu logu temiz.

**[!] Canlıya taşı:** `models/Urun_model.php` FTP.

---

## 2026-08-12 — Cron işleri doğrulama + web guard 403

Cron controller (`php index.php cron calis`) uçtan uca doğrulandı. 3 iş de graceful:
`terk_sepet` (eski misafir sepeti siler), `pazaryeri_senkron` (aktif hesap yoksa atlar),
`efatura_durum` (isleniyor fatura yoksa atlar).

**Doğrulama:**
- `cron calis` + tek tek 3 iş CLI'da hata vermeden çalıştı.
- `terk_sepet 7` hedefli test: 10 günlük **misafir** sepeti silindi, yeni misafir + 10
  günlük **bayi** sepeti korundu (yalnızca `bayi_id IS NULL` olanlar silinir — doğru).
- Web guard (`is_cli()`) web erişimini engelliyor.

**Hardening:** web guard 200 yerine **403** döndürüyor (`http_response_code(403)`). İşlev
aynı (cron web'ten çalışmaz), yalnızca tarayıcı/bot için doğru HTTP durum kodu.

**[!] Canlıya taşı:** `controllers/Cron.php` FTP. Cron tetikleme (CANLIYA-TASIMA §6):
`php index.php cron calis` periyodik (Linux crontab / Windows Task Scheduler), web erişimi
engelli.

---

## 2026-08-12 — PayTR: callback typo (kritik) + get_token TRY tutar normalizasyonu

PayTR kartlı ödeme akışı doğrulandı; iki gerçek bug bulundu ve düzeltildi (biri
kritik — üretimde hiçbir kart ödemesi onaylanamazdı).

**(1) KRİTİK — callback hash typo (`libraries/Paytr_api.php:121`):** `callback_dogrula`
`$beklenen` tanımlayıp `hash_equals($bekenen, ...)` çağırıyordu ('l' eksik) → tanımsız
değişken → TypeError → her callback 500. Yani PayTR'dan gelen başarılı ödeme bildirimi
ASLA doğrulanamıyor, sipariş ASLA "ödendi" işaretlenemiyordu. `$bekenen` → `$beklenen`.
(Test anahtarlarıyla uçtan uca doğrulandı — öncesinde TypeError.)

**(2) get_token TRY tutar normalizasyonu:** PayTR iFrame TL-only (`currency='TL'`), ama
`payment_amount = toplam*100` sipariş para birimini (USD) alıyordu → USD sipariş 14.76 TL
charge edilirdi (gerçek 479.70 TL yerine — ciddi gelir kaybı). `toplam*kur` ve kalem
`birim_fiyat*kur` (TRY kuruş). TRY siparişler kur=1 (etkilenmez). Aynı multi-currency
bug sınıfı.

**Doğrulama (test anahtarlarıyla, USD sipariş 14.76 / kur 32.5):** get_token tutarı
47970 kuruş TRY (eski 1476). Callback: yanlış hash → "bad hash" (red), doğru hash →
"OK" + `odeme_durumu=odendi` + `durum=onaylandi` + geçmişe "PayTR ödendi" notu,
idempotent (tekrar → hala tek geçmiş). 12/12 PASS. Lint/FFFD temiz; sunucu logu temiz.

**[!] Canlıya taşı:** `libraries/Paytr_api.php` FTP. **(1) acil** — canlıda PayTR açıksa
kart ödemeleri onaylanmıyordu.

---

## 2026-08-12 — E-fatura TRY normalizasyonu (para birimi)

E-fatura/e-arşiv yasal olarak TRY olmalı; ama USD/EUR siparişten o para birimiyle fatura
kesilirdi (matrah/KDV/toplam sipariş para biriminde, `para_birimi` yine TRY diye
etiketliydi — tutarsız). Düzeltme: `Efatura` kütüphanesi tüm tutarları siparişin `kur`'uyla
TRY'ye normalize ediyor; `faturalar.para_birimi='TRY'` açıksetti.

**Dosyalar:**
- `libraries/Efatura.php`: `payload()` + `olustur()` — `$kur = $s->kur ?: 1.0`;
  ara/matrah/KDV/toplam + kalem `birim_fiyat`/`ara`/`matrah`/`KDV`/`tutar` `* $kur`
  (TRY). `olustur` insert'ine `para_birimi => 'TRY'` eklendi. TRY siparişler kur=1
  (etkilenmez).
- `views/yonetim/faturalar/index.php` + `detay.php`: fatura tutarları artık TRY →
  `para_tr` (₺). `detay` "Sipariş toplamı" satırı yine sipariş para biriminde
  (`para_formatla($s->toplam, $s->para_birimi)`) — admin TRY fatura + bayinin ödediği
  para birimini birlikte görür. `$pb` yardımcısı kaldırıldı.
- `models/Fatura_model.php::liste`: `siparis_para_birimi` alias'ı kaldırıldı (fatura
  artık TRY, sipariş pb'sine gerek yok).

**Doğrulama:** USD sipariş (toplam 14.76 USD, kur 32.5) → fatura: `para_birimi=TRY`,
matrah=399,75 ₺, KDV=79,95 ₺, toplam=479,70 ₺ (toplam ≠ 14.76 USD — normalize oldu).
Fatura detay matrah/toplam ₺, "Sipariş toplamı" 14,76 $. Liste 479,70 ₺. 11/11 PASS.
4 dosya lint temiz + FFFD=0; sunucu logu temiz.

**[!] Canlıya taşı:** `libraries/Efatura.php` + `models/Fatura_model.php` + 2 fatura
view FTP.

---

## 2026-08-12 — Ciro/agregat TRY normalizasyonu (para birimi karışım bug'ı)

Raporlar + dashboard "Ciro"/tutar agregatları farklı para birimli sipariş `toplam`'larını
doğrudan topluyordu (USD 14.76 + TRY 479.40 gibi elma-armut). Düzeltme: her tutarı
siparişin `kur`'uyla çarpıp TRY'ye normalize edip topluyoruz (TRY siparişler kur=1,
etkilenmez). View'larda `para_tr` (₺) zaten TRY agregat için doğru — değişiklik
yalnızca modellerde.

**Dosyalar (3 model):**
- `models/Rapor_model.php`: `satis_ozet` (ciro/kargo/indirim), `urun_satis` &
  `kategori_satis` (detay `ara_toplam * kur`), `bayi_satis`/`bolge_satis`/`odeme_satis`
  (ciro) — hepsi `SUM(x * kur)`, FALSE ile ham SQL. Tarih aralığı + iptal/iade filtresi
  korundu.
- `models/Dashboard_model.php`: `ozet.ciro` + `siparis_trendi.tutar` →
  `SUM(toplam * kur)`.
- `models/Bayi_model.php::bayi_siparis_ozet`: bayi cirosu `SUM(toplam * kur)`
  (bayiler/detay "Toplam ciro" ₺ gösterimi artık doğru).

**Doğrulama:** USD bayi siparişi (toplam 14.76 USD, kur 32.5 → 479.70 TRY) onaylandı
yapıldı. Dashboard Ciro (Tümü) = `SUM(toplam*kur)` 34.374,40 ₺ (yanlış `SUM(toplam)`
33.909,46 ₺ DEĞİL — sayfada yok); raporlar Brüt Ciro = 43.826,40 ₺; ödeme raporu havale
ciro = 479,70 ₺ (USD sipariş 14,76 değil 479,70 olarak yansıdı). 6/6 PASS. 3 model lint
temiz + FFFD=0; sunucu logu temiz.

**Not:** e-fatura yasal TRY sorunu hâlâ açık (USD siparişten USD fatura kesiliyor) —
Faz 5 e-fatura konusu.

**[!] Canlıya taşı:** 3 model FTP.

---

## 2026-08-12 — Sipariş/fatura view'ları çoklu para birimi (admin + onay sayfaları)

Önceki tur sepet/ödeme'yi düzeltmişti; bu tur kalan tüm sipariş-tutarı gösterimlerini
siparişin snapshot para birimine çevirdi. (hesabim/* zaten doğruydu; yanlış olan admin
tarafı + sipariş onay sayfalarıydı.)

**Dosyalar:**
- `views/magaza/odeme/basarili.php` + `paytr.php`: sipariş tutarları `para_tr` →
  `para_formatla(..., $s->para_birimi)`.
- `views/yonetim/siparisler/index.php` + `detay.php`: liste + detay tutarları
  (kalem/ara/işlem/kargo/toplam) sipariş para biriminde.
- `views/yonetim/dashboard/index.php` (son siparişler) + `views/yonetim/bayiler/detay.php`
  (bayi son siparişler): tutar sipariş para biriminde.
- `views/yonetim/faturalar/index.php` + `detay.php`: fatura tutarları (matrah/KDV/toplam)
  bağlı siparişin para biriminde (detayda `$pb`, $s yoksa TRY fallback).
- `models/Dashboard_model.php::son_siparisler` + `models/Fatura_model.php::liste`:
  sorgulara `s.para_birimi` eklendi (fatura listesinde `s.para_birimi AS
  siparis_para_birimi` alias — `faturalar.para_birimi` TRY-default ile çakışmasın).

**Doğrulama:** USD bayi siparişi (toplam 14.76 USD) — basarili `14,76 $` (₺ yok), admin
siparişler liste+detay `14,76 $`, dashboard son siparişler `14,76 $`, bayiler detay
`14,76 $`, faturalar liste `14,76 $`, fatura detay matrah `12,30 $` + toplam `14,76 $`.
11/11 PASS. 10 dosya lint temiz + FFFD=0; sunucu logu temiz.

**Kapsam dışı (ayrı konu, not):** Raporlar + dashboard "Ciro" KPI'sı farklı para
birimli sipariş `toplam`larını TRY ₺ altında topluyor (elma-armut toplamı) — para-birimi
agregasyon bug'ı, ayrı çözülmeli (ciro TRY'ye normalize edilmeli). Ayrıca e-fatura yasal
TRY olmalı ama USD siparişten USD tutarla kesiliyor — Faz 5 e-fatura konusu.

**[!] Canlıya taşı:** 8 view + 2 model FTP.

---

## 2026-08-12 — Sepet/ödeme çoklu para birimi gösterimi (bayi pb, siparişle birebir)

Önceki turda bulunan gap kapatıldı: sepet ve ödeme görünümü `para_tr()` ile TRY
gösteriyordu, ama sipariş bayinin para biriminde kaydediliyordu. Artık giriş yapmış
bayi için sepet/ödeme bayinin para biriminde (USD/EUR/…) gösterir; TRY/misafir
aynı (₺). Gösterim tutarı kaydedilen siparişle **birebir aynı**.

**Dosyalar:**
- `models/Sepet_model.php::liste()`: TRY `birim`/`ara`/`ara_toplam` (esik/kupon
  mantığı için korundu) yanına bayi para biriminde `birim_pb`/`ara_pb` (her satır) +
  `pb`/`kur`/`pb_ara_toplam` eklendi. Dönüşüm **`mg_olustur` ile aynı yuvarlama**:
  `birim_pb = round(birim/kur,2)`, `ara_pb = round(birim_pb*adet,2)` (önce birim
  yuvarlanır, sonra adetle çarpılır). Neden: `para_goster(ara_try)` toplu çevirir
  (`round(ara_try/kur,2)`) ve siparişten 1-2 kuruş saptığı için kullanılmadı.
- `views/magaza/sepet/index.php`: 5 `para_tr` → `para_formatla(...pb)` / kargo-eşiği
  kalanı için `para_goster(...)`. Kargo eşik **TRY** mantığıyla korundu
  (`ara_toplam >= esik` TRY karşılaştırması), yalnızca gösterim pb.
- `views/magaza/odeme/index.php`: 4 `para_tr` → `para_formatla/para_goster`
  (satır ara, ara toplam, kupon indirimi, ödeme yöntemi ek ücreti).

**Doğrulama:** USD bayi (ürün #1, qty 6) — sepet `2,46 $` birim / `14,76 $` ara+toplam,
ödeme `14,76 $`; **sipariş DB `USD 14.76` ile birebir aynı**; sepette ₺ sızıntısı yok.
Misafir/TRY — sepet `79,90 ₺` / `479,40 ₺`, $ sızıntısı yok (regresyon yok). TRY
sembolü DB'de ₺ (EUR=€, USD=$). 3 dosya lint temiz + FFFD=0; sunucu logu temiz.

**[!] Canlıya taşı:** `models/Sepet_model.php` + `views/magaza/sepet/index.php` +
`views/magaza/odeme/index.php` FTP.

---

## 2026-08-12 — Çoklu para birimi sipariş E2E (USD kur snapshot) + sepet gösterim açığı

B2B doğrulamasında yalnızca TRY (kur=1) test edilmişti; USD bayi için
`Siparis_model::mg_olustur`'daki para birimi + kur snapshot/dönüşüm mantığı
doğrulandı. **15/15 PASS.** Kod değişikliği yok (yalnızca test).

**Doğrulanan (USD bayi, ürün #1 79.90 TRY, qty 6):** sipariş `para_birimi=USD`
`kur=32.5` ile snapshot'landı; detay `birim_fiyat=2.46 USD` (79.90/32.5),
`ara_toplam=14.76 USD`; sipariş `ara_toplam`/`toplam` = 14.76 USD (kargo/işlem 0);
USD değer TRY eşdeğerinden (479.40) küçük; stok düşüşü para biriminden bağımsız.
Dönüşüm `round(try/kur,2)` birim + `round(birim*adet,2)` ara satır — doğru.

**Gözlem (açık gap, sipariş bug'ı değil):** sepet (`views/magaza/sepet/index.php`)
ve ödeme görünümü `para_tr(...)` ile **TRY** gösterir, bayi para birimine
dönüştürmez. Oysa `mg_olustur` siparişi bayinin para biriminde kaydeder. Yani USD
bayi sepette/ödemede TRY fiyat görür, sipariş USD kaydedilir — CANLIYA-TASIMA §Para
birimi'nin "sepet/ödeme/sipariş bayi para biriminde" notuyla tutarsız (yalnızca
sipariş kısmı uygulanmış). `teksil_helper::para_goster($try, $kod)` hazır;
sepet/ödeme görünümünde `para_goster($x, $bayi->para_birimi)` kullanılırsa kapanır.

**Doğrulama:** test betiği 15/15 PASS; test verisini temizledi + varyant #1 stoğu
248'e geri. Bağımsız baseline: bayiler 2, varyant #1 stok 248, test artığı 0.

**[!] Canlıya taşı:** kod değişikliği yok.

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
