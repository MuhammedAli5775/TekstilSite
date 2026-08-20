-- migrate_yazilar.sql — D3 blog (XXXV): yazilar tablosu + rol-2 yetkisi + demo yazılar.
-- Blog içeriği DB'de TR kalır (ürün/CMS deseni); kapak görseli yalnız https URL (admin formu).
-- Re-çalıştırma güvenli: tablo IF NOT EXISTS; yetki ve demo satırları INSERT IGNORE.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS yazilar (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug         VARCHAR(160)  NOT NULL,
  baslik       VARCHAR(200)  NOT NULL,
  ozet         VARCHAR(500)  NOT NULL DEFAULT '',
  icerik       MEDIUMTEXT    NULL,
  gorsel       VARCHAR(500)  NOT NULL DEFAULT '',
  durum        TINYINT       NOT NULL DEFAULT 1,
  yayin_tarihi DATE          NULL,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_yazi_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rol 2 "Yönetici" tam erişim (süper rol 1 matriste yer almaz).
-- İdempotent + sıra-bağımsız (XXXVI): §3'te yetkiler tablosu migrate_yetkiler'le
-- (seed'den SONRA) oluşur — taze kurulumda bu INSERT o ana değin tablo yoktur;
-- guard'la atlanır ve satır migrate_yetkiler tohumundan düşer. Mevcut kurulumda
-- (yetkiler mevcut) burada eklenir.
SET @yetki_tablo = (SELECT COUNT(*) FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yetkiler');
SET @sql = IF(@yetki_tablo > 0,
              'INSERT IGNORE INTO yetkiler (rol_id, modul, goruntule, duzenle, sil) VALUES (2, ''yazilar'', 1, 1, 1)',
              'SELECT ''yetkiler tablosu sonra gelir — satir migrate_yetkiler tohumundan duser'' AS bilgi');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Demo yazılar (mevcut satırları ezmez).
INSERT IGNORE INTO yazilar (slug, baslik, ozet, icerik, gorsel, durum, yayin_tarihi) VALUES
  ('yeni-sezon-2026-trendleri',
   'Yeni Sezon 2026: Toptan Kadın Giyimde Öne Çıkan Trendler',
   '2026 koleksiyonlarında öne çıkan renkler, kumaşlar ve silüetler — bayiler için alım rehberi.',
   '<p>2026 toptan kadın giyim koleksiyonlarında doğal kumaşlar ve rahat silüetler öne çıkıyor. Bu sezon bayilerin alım planında neler olmalı? Vitrininize öncelikle almanız gereken parçaları derledik.</p>
<h2>Öne çıkan renk paleti</h2>
<p>Toprak tonları, lavanta ve derin yeşil sezonun belirleyici renkleri. Basic tişört ve body gruplarında bu palete mutlaka yer verin; kombine edilebilirliği yüksek olduğu için devir riski düşüktür.</p>
<h2>Kumaş ve silüet trendleri</h2>
<p>Pamuk-viskon karışımları ve penye ağırlıklı basic gruplar yükselişte. Üst giyimde oversize kalıplar, alt giyimde rahat kesim jogger ve mom jean öne çıkıyor. Elbise grubunda midi boy bu sezonun açık ara favorisi.</p>
<ul>
<li>Oversize basic tişört ve body</li>
<li>Triko hırka ve twill pantolon</li>
<li>Midi boy elbise ve tulum</li>
</ul>
<p>Yeni sezon ürünleri kataloğumuzun "yeni gelenler" bölümünde; toptan fiyatlar bayi girişinden sonra görünür.</p>',
   'https://picsum.photos/seed/teksil-blog-1/1200/630', 1, '2026-08-18'),

  ('merter-toptan-alisveris-rehberi',
   'Merter Toptan Alışveriş Rehberi: Bayiler İçin 7 İpucu',
   'İlk kez Merter''de toptan alım yapacak bayiler için hazırlık, pazarlık ve lojistik üzerine pratik öneriler.',
   '<p>Merter, Türkiye''nin en yoğun toptan tekstil merkezlerinden biri. İlk alım deneyimi zorlu geçmesin diye derlediğimiz ipuçları:</p>
<h2>1. Sezon başından planlayın</h2>
<p>Koleksiyon öncesinde mağazanızın vitrin planını çıkarın; kaç model, hangi renk oranlarıyla alım yapacağınızı önceden belirleyin.</p>
<h2>2. MOQ ve basamaklı fiyatları karşılaştırın</h2>
<p>Aynı modeli farklı firmalarda farklı minimum adetlerle bulabilirsiniz. Toplam bütçenize göre basamaklı fiyat avantajını hesaplayın.</p>
<h2>3. Kumaş kalitesini fiziksel kontrol edin</h2>
<p>Gramaj, dikiş ve çekme payı numunede kontrol edin; fotoğraf üzerinden karar vermeyin.</p>
<h2>4. XML/API entegrasyonu sunan firmaları tercih edin</h2>
<p>Stok ve fiyat güncellemeleri manuel giriş yerine entegrasyonla akarsa mağazanız boş vitrinle satış yapmaz.</p>
<p>Nesem Tesettür olarak XML/API fidi ile ürün ve stoklarınızı anında kendi mağazanıza aktarabilirsiniz.</p>',
   'https://picsum.photos/seed/teksil-blog-2/1200/630', 1, '2026-08-10'),

  ('moq-ve-basamakli-fiyatlandirma',
   'MOQ ve Basamaklı Fiyatlandırma Nasıl Çalışır?',
   'Toptan alımda minimum sipariş adedi ve miktar indirimleri: hesabı, örnekleri ve sepetteki yansıması.',
   '<p>Toptan alımda en çok sorulan iki konu: MOQ (Minimum Order Quantity) ve basamaklı fiyatlandırma. Bu yazıda her ikisinin nasıl işlediğini anlatıyoruz.</p>
<h2>MOQ nedir?</h2>
<p>Bir üründen verilebilecek en düşük sipariş adedidir. Örneğin MOQ 10 olan bir üründen 10 adedin altında sipariş verilemez; adetler MOQ''nun katları ya da belirtilen adım büyüklüğünün katları olur.</p>
<h2>Basamaklı fiyat nasıl işler?</h2>
<p>Adet arttıkça birim fiyat düşer. Örneğin bir üründe MOQ 10, adım 5 ise: 10-24 adette liste fiyatı, 25+ adette indirimli basamak fiyatı uygulanır. Ürün detay sayfasındaki canlı hesap, girdiğiniz adete göre uygulanan basamağı anında gösterir.</p>
<h2>Sepette nasıl görünür?</h2>
<p>Sepete eklediğiniz anda uygulanan basamak fiyatı satıra işlenir; sonradan adet değiştirirseniz hesap yeniden yapılır. Böylece ödeme adımında sürpriz olmaz.</p>
<p>Sorularınız için yardım sayfamızdaki iletişim kanallarından bize ulaşabilirsiniz.</p>',
   'https://picsum.photos/seed/teksil-blog-3/1200/630', 1, '2026-07-28');
