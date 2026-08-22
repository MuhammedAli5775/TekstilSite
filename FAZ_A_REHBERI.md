# FAZ_A_REHBERI.md — İş kimlikleri rehberi (İşletme tarafına)

> Bu rehber TAMAMLAMA_PLANI.md **Faz A**'nın uygulama kılavuzudur. Kod tarafı hazır:
> tüm kimlikler **Yönetim > Ayarlar** sayfasındaki kendi kartından girilir, girilen
> an etkin olur (yeniden başlatma gerekmez). Ayarlar sayfasının en üstündeki
> **"Entegrasyon Durumu"** şeridi neyin girilmiş olduğunu gösterir — buradaki işler
> bittikçe o şeritte beş satırın hepsi yeşile dönmeli.
>
> Her kimliği girdikten sonra **birlikte test edeceğiz** (aşağıda her bölümde neyi
> test edeceğimiz yazıyor). Giriş sırasında sorun yaşarsan not al, birlikte bakarız.
>
> Sıra önerisi: hepsi bağımsız, paralel yürütebilirsin. Ödeme (A2) lansmanın kritik
> yolu olduğu için başvurusunu ilk gün başlat.

---

## A1 — E-posta (SMTP)

**Neyi sağlar:** sipariş onayı, sipariş durum bildirimi, bayi kayıt bildirimi,
şifre sıfırlama e-postası (LIX).

**Not (LIX):** şifremi-unuttum akışı SMTP beklemeden çalışır — SMTP boşken
yalnız e-posta adımı atlanır (log'a düşer; token akışı, yeni şifre belirleme
hepsi çalışır). SMTP dolunca sıfırlama e-postası da otomatik canlanır; Faz A
tarafından ek yapılacak iş YOKTUR.

**Başvuru/temin (iki yol):**
1. *Hosting'in SMTP'si* (en basit): canlı hosting kurulumu sonrası kontrol panelinden
   bir e-posta hesabı aç (ör. `siparis@alanadin.com`). Bilgiler panelde yazar.
2. *Google Workspace / başka posta sağlayıcısı:* alan adına bağlı hesap; SMTP için
   "uygulama şifresi" üretmen gerekir.

**Toplanacak:** sunucu adresi (ör. `mail.alanadin.com`), port (genelde 587),
şifreleme (TLS), kullanıcı adı (e-posta adresi), şifre, gönderici adres.

**Panel yolu:** Yönetim > Ayarlar > **E-posta (SMTP)** kartı — 6 alan + şifre.

**Test:** bir test siparişi vereceğiz; bayi e-postasına onay düşüyor mu bakacağız.
Boşken sistem sessizce atlıyor (sipariş bozulmuyor) — yani girmeden lansman
yapılırsa müşteriye e-posta gitmez.

**Not:** gönderici adresi alan adına ait olmalı; aksi halde posta spam'e düşer.
Alan adının DNS'inde SPF kaydı (sağlayıcının verdiği) eklenmeli.

## A2 — Ödeme, PayTR canlı  ← **KRİTİK YOL**

**Neyi sağlar:** kartlı ödeme.

**Başvuru:** PayTR mağaza başvurusu (paytr.com). Başvuruda şirket bilgileri,
vergi levhası, banka hesap bilgisi gibi evrak ister — güncel evrak listesini
PayTR başvuru panelinden teyit et. Onay sonrası panelde **Mağaza No (merchant ID)**,
**API Key** ve **API Salt** değerlerine ulaşırsın.

**Toplanacak:** `paytr_merchant_id`, `paytr_merchant_key`, `paytr_merchant_salt`.

**Panel yolu:** Yönetim > Ayarlar > **Ödeme (PayTR)** kartı. Test anahtarlarıyla
deniyorsan **"Test modu"** kutusunu işaretle; canlı anahtarlar geldiğinde kutu
**işaretsiz** olmalı (durum şeridi test modunda "TEST modu" uyarısı basar).

**Test:** önce test anahtarı + küçük tutarlı gerçek kartla uçtan uca ödeme;
sonra canlı anahtar + yine küçük tutarlı GERÇEK ödeme + iptal/iade. PayTR
panelinde callback/bildirim URL'si canlı alan adına bağlıdır — bu test
**canlı hosting kurulduktan sonra** (DEPLOY.md sonrası) yapılır.

## A3 — SMS (Netgsm)

**Neyi sağlar:** sipariş durumu SMS'i (kargo/teslim bilgisi gibi).

**Başvuru:** Netgsm (netgsm.com.tr) SMS bayiliği. **Gönderici başlığı** (ör.
"SIRKETADI") onay ister — onay süresi birkaç gün sürebilir, erken başvur.

**Toplanacak:** kullanıcı adı, şifre, onaylanmış gönderici başlığı.

**Panel yolu:** Yönetim > Ayarlar > **SMS (Netgsm)** kartı — kutuyu işaretle +
3 alan. Boşken/pasifken sistem sessizce atlar.

**Test:** bir siparişin durumunu değiştirip bayi telefonuna SMS düşüyor mu.

## A4 — E-Fatura entegratörü

**Neyi sağlar:** sipariş faturalarının e-fatura/e-arşiv olarak gönderilmesi.

**Ön koşul:** şirketin **GİB e-fatura mükellefi** olması (GİB portalinden
ön kayıt; mali müşavirin varsa onunla 2 dakikalık iş). B2B müşterilerin
çoğu e-fatura/e-arşiv mükellefi olur; değilse fatura yine oluşur, gönderim
kurallarına entegratör karar verir.

**Başvuru (seçenekler):** Uyumsoft / Logo / Paraşüt gibi bir **e-fatura
entegratörü** aboneliği. Hangisini seçersen seç API erişimi (URL + token) iste.

**Toplanacak:** API URL, API token, firma VKN (vergi numarası), firma ünvanı
— ünvan/VKN **vergi levhasıyla birebir** olmalı (uyuşmazlıkta gönderim reddedilir).

**Panel yolu:** Yönetim > Ayarlar > **E-Fatura** kartı (test ortamı veriyorsa
"Test modu" kutusu). Boşken faturalar **"bekliyor"** durumunda birikir; kimlik
girilince cron bekleyenleri otomatik gönderir — birikmiş faturalar kaybolmaz.

**Test:** bir siparişin faturasını elle "gönder" deyip entegratör panelinde
göründüğünü doğrulama + durum "gönderildi"ne dönüyor mu.

## A5 — Pazaryeri (Trendyol)

**Neyi sağlar:** ürün/stok/fiyat aktarımı + pazaryeri siparişlerini çekme.

**Başvuru:** Trendyol satıcı (Onbaşı/Er) başvurusu. Onay sonrası Trendyol
satıcı panelinden **API anahtarı** (veya API kullanıcı adı+şifre) üret.

**Panel yolu:** farklı yer — Ayarlar DEĞİL, **Yönetim > Pazaryeri > Hesaplar**'dan
hesap ekle (satıcı no + API anahtarı). Ayarlar'daki durum şeridi aktif hesap
sayısından okur.

**Test:** bir ürün eşleştirip senkron (elle ya da cron) — stok/fiyat Trendyol'a
gidiyor mu; test siparişi çekiliyor mu.

---

## Giriş sonrası birlikte yapacaklarımız (bana haber ver)

1. Ayarlar > Entegrasyon Durumu şeridinde beş satır yeşil mi diye bakarız.
2. A1/A3 için test siparişi + bildirim doğrulaması.
3. A4 için bekleyen fatura gönderim testi.
4. A2 için canlı domain + DEPLOY sonrası gerçek kartlı küçük tutar testi
   (A2'nin tam doğrulaması Faz B'den sonradır).
