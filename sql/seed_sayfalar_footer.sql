SET NAMES utf8mb4;

-- Footer link sayfaları (iletisim, toptan-sartlari, xml-feed) — 404 fix (2026-08-09).
DELETE FROM sayfalar WHERE slug IN ('iletisim','toptan-sartlari','xml-feed');

INSERT INTO sayfalar (slug, baslik, icerik, durum) VALUES
('iletisim', 'İletişim',
 '<p>TekstilSite — toptan kadın giyimde üretici fiyatına kaliteli kumaş, gerçek stok ve hızlı kargo.</p><h3>İletişim Bilgileri</h3><p>📞 Telefon: <a href="tel:+902124813692">+90 212 481 36 92</a><br>📧 E-posta: <a href="mailto:info@teksilsite.test">info@teksilsite.test</a><br>📍 Adres: Merter, İstanbul, Türkiye<br>🕒 Çalışma saatleri: Hafta içi 09:00 – 18:00</p><h3>Toptan / Bayi</h3><p>Toptan fiyatlarımıza ve XML/API katalog erişimine ulaşmak için <a href="/bayi/kayit">bayi kaydı</a> oluşturun; onay sonrası hesabınız açılır.</p><p>Sipariş takibi için <a href="/siparis-takip">Sipariş Takibi</a> sayfasını kullanabilirsiniz.</p>',
 1),
('toptan-sartlari', 'Toptan Şartları',
 '<h3>Minimum Sipariş Adedi (MOQ)</h3><p>Her ürünün toptan satış için minimum adedi ürün sayfasında belirtilir. Sepete ekleme sırasında MOQ ve adet basamağı (kutu / düzine katları) kontrol edilir.</p><h3>Toptan Fiyatlandırma</h3><p>Birim fiyat; ürün baz fiyatı ve adet basamağı indirimi ile hesaplanır. Adet arttıkça uygulanan indirim yüzdesi ürün sayfasındaki adet basamağı tablosunda gösterilir. Sipariş tutarı sunucu tarafında, sepetinizdeki gerçek adetlerle hesaplanır.</p><h3>Ödeme</h3><p>Havale / EFT, kapıda nakit veya kartla (PayTR) ödeme yapabilirsiniz. Havale ile ödemelerde tutar banka hesabımıza ulaştıktan sonra siparişiniz onaylanır.</p><h3>Çoklu Para Birimi</h3><p>Hesabım bölümünden para biriminizi seçebilirsiniz; sipariş tutarı anlık kur ile seçtiğiniz para biriminde kaydedilir.</p><h3>Kargo</h3><p>Siparişler anlaşmalı kargo firmalarıyla gönderilir; belirlenen tutar üzeri siparişlerde kargo ücretsizdir.</p><p>Sorularınız için <a href="/iletisim">iletişim</a> sayfasından bize ulaşın.</p>',
 1),
('xml-feed', 'XML / API Katalog Feed',
 '<h3>XML / API Katalog Feed</h3><p>Bayilerimize ürün kataloğunu (ürün + varyant + stok + fiyat) kendi sistemlerine çekmeleri için anahtarlı bir XML/JSON feed sunuyoruz.</p><h3>Nasıl Çalışır?</h3><p>Feed erişimi, yönetim panelinden üretilen benzersiz bir API anahtarı ile sağlanır. Anahtarınızı query parametresi veya HTTP başlığı olarak gönderirsiniz:</p><p><code>GET /feed/urunler?key=ANHTARINIZ</code> (XML varsayılan; <code>&amp;format=json</code> ile JSON)</p><h3>Erişim İçin</h3><p>Feed anahtarı yalnızca onaylı bayilere, talep üzerine yönetim tarafından verilir. Toptan fiyatlarına ve feed erişimine ulaşmak için <a href="/bayi/kayit">bayi kaydı</a> oluşturun ve bizimle iletişime geçin.</p>',
 1);
