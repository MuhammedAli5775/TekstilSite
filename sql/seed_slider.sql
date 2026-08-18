-- Anasayfa slider bannerları (demo) — XXXIII: her dil kendi setini görür.
-- Admin Bannerlar'dan düzenlenir; bir bannerın dil kolonu NULL kalırsa o
-- banner HER dilde görünür (bilinçli "tüm diller" seçeneği). Standart demo:
-- 3 slayt × 4 dil (tr/en/ru/ar), sira her dilde 0-1-2.
SET NAMES utf8mb4;

DELETE FROM bannerlar WHERE yer = 'anasayfa_slider';

INSERT INTO bannerlar (yer, baslik, alt_baslik, gorsel, link, buton_yazi, yazi_konum, dil, sira, durum) VALUES
  -- Türkçe
  ('anasayfa_slider', 'Toptan kadın giyimde üretici fiyatı', 'İstanbul Merter''den taze koleksiyon, gerçek stok, hızlı kargo.', 'https://picsum.photos/seed/teksil-slider-1/1600/700', 'katalog', 'Kataloğu İncele', 'sol', 'tr', 0, 1),
  ('anasayfa_slider', 'Bayi olun, toptan fiyatlara erişin', 'Minimum sipariş adediyle toptan fiyatlandırma + XML/API entegrasyonu.', 'https://picsum.photos/seed/teksil-slider-2/1600/700', 'bayi/kayit', 'Bayi Olun', 'sag', 'tr', 1, 1),
  ('anasayfa_slider', 'Yeni sezon koleksiyonu', 'Tişört, body, elbise ve dış giyim — anlık stok, güncel fiyat.', 'https://picsum.photos/seed/teksil-slider-3/1600/700', 'katalog/yeni', 'Yeni Gelenler', 'orta', 'tr', 2, 1),
  -- English
  ('anasayfa_slider', 'Factory prices in wholesale womens clothing', 'Fresh collections from Istanbul Merter — real stock, fast shipping.', 'https://picsum.photos/seed/teksil-slider-1/1600/700', 'katalog', 'Browse the Catalog', 'sol', 'en', 0, 1),
  ('anasayfa_slider', 'Become a dealer, unlock wholesale prices', 'Wholesale pricing with minimum order quantities + XML/API integration.', 'https://picsum.photos/seed/teksil-slider-2/1600/700', 'bayi/kayit', 'Become a Dealer', 'sag', 'en', 1, 1),
  ('anasayfa_slider', 'New season collection', 'T-shirts, bodysuits, dresses and outerwear — live stock, current prices.', 'https://picsum.photos/seed/teksil-slider-3/1600/700', 'katalog/yeni', 'New Arrivals', 'orta', 'en', 2, 1),
  -- Русский
  ('anasayfa_slider', 'Цены производителя в оптовой женской одежде', 'Свежие коллекции из Стамбула (Мертер) — реальный склад, быстрая доставка.', 'https://picsum.photos/seed/teksil-slider-1/1600/700', 'katalog', 'Смотреть каталог', 'sol', 'ru', 0, 1),
  ('anasayfa_slider', 'Станьте дилером — оптовые цены', 'Оптовые цены с минимальными партиями + интеграция XML/API.', 'https://picsum.photos/seed/teksil-slider-2/1600/700', 'bayi/kayit', 'Стать дилером', 'sag', 'ru', 1, 1),
  ('anasayfa_slider', 'Коллекция нового сезона', 'Футболки, боди, платья и верхняя одежда — живой склад, актуальные цены.', 'https://picsum.photos/seed/teksil-slider-3/1600/700', 'katalog/yeni', 'Новинки', 'orta', 'ru', 2, 1),
  -- العربية
  ('anasayfa_slider', 'أسعار المصنع في ملابس النساء بالجملة', 'مجموعات طازجة من مرتر إسطنبول — مخزون حقيقي وشحن سريع.', 'https://picsum.photos/seed/teksil-slider-1/1600/700', 'katalog', 'تصفح الكتالوج', 'sol', 'ar', 0, 1),
  ('anasayfa_slider', 'كن موزعاً واطلع على أسعار الجملة', 'أسعار جملة مع كميات دنيا + تكامل XML/API.', 'https://picsum.photos/seed/teksil-slider-2/1600/700', 'bayi/kayit', 'كن موزعاً', 'sag', 'ar', 1, 1),
  ('anasayfa_slider', 'مجموعة الموسم الجديد', 'تي شيرت وبادي وفساتين وملابس خارجية — مخزون لحظي وأسعار محدثة.', 'https://picsum.photos/seed/teksil-slider-3/1600/700', 'katalog/yeni', 'وصل حديثاً', 'orta', 'ar', 2, 1);
