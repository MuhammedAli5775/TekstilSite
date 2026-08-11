-- Anasayfa slider bannerları (demo). Admin banner CRUD ileride eklenebilir.
SET NAMES utf8mb4;

DELETE FROM bannerlar WHERE yer = 'anasayfa_slider';

INSERT INTO bannerlar (yer, baslik, alt_baslik, gorsel, link, buton_yazi, yazi_konum, sira, durum) VALUES
  ('anasayfa_slider',
   'Toptan kadın giyimde üretici fiyatı',
   'İstanbul Merter''den taze koleksiyon, gerçek stok, hızlı kargo.',
   'https://picsum.photos/seed/teksil-slider-1/1600/700',
   'katalog', 'Kataloğu İncele', 'sol', 0, 1),
  ('anasayfa_slider',
   'Bayi olun, toptan fiyatlara erişin',
   'Minimum sipariş adediyle toptan fiyatlandırma + XML/API entegrasyonu.',
   'https://picsum.photos/seed/teksil-slider-2/1600/700',
   'bayi/kayit', 'Bayi Olun', 'sag', 1, 1),
  ('anasayfa_slider',
   'Yeni sezon koleksiyonu',
   'Tişört, body, elbise ve dış giyim — anlık stok, güncel fiyat.',
   'https://picsum.photos/seed/teksil-slider-3/1600/700',
   'katalog/yeni', 'Yeni Gelenler', 'orta', 2, 1);
