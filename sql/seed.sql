-- ===========================================================================
-- TekstilSite — Çekirdek tohum verisi (Faz 0)
-- UTF-8 dosya · phpMyAdmin Import  VEYA  mysql --default-character-set=utf8mb4 ...
-- ===========================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE roller;
INSERT INTO roller (id, ad) VALUES
  (1, 'Süper Yönetici'),
  (2, 'Yönetici'),
  (3, 'Bayi');

TRUNCATE TABLE yoneticiler;
INSERT INTO yoneticiler (id, rol_id, ad_soyad, email, sifre, durum) VALUES
  (1, 1, 'Site Yöneticisi', 'admin@teksilsite.test',
   '$2y$10$ajLBZPnFaTo17Hr5fJNgpOSfqxKdsbcMy1nrb4Pd7b1yE3HvV.ICa', 1);
-- şifre: Tekstil2026!  (bcrypt)

TRUNCATE TABLE bayi_gruplari;
INSERT INTO bayi_gruplari (id, ad, indirim_yuzde) VALUES
  (1, 'Standart Toptan', 0.00),
  (2, 'VIP Toptan',      5.00);

TRUNCATE TABLE ayarlar;
INSERT INTO ayarlar (anahtar, deger, grup) VALUES
  ('site_adi',          'TekstilSite', 'genel'),
  -- XXXVI: meta değerleri boş tohumlanır — boş = vitrinde dile göre çevrilmiş
  -- varsayılan (t('meta_title/desc_default')); admin doldurursa tüm dillerde override olur.
  ('meta_title',        '', 'seo'),
  ('meta_description',  '', 'seo'),
  ('iletisim_telefon',  '+90 212 481 36 92', 'iletisim'),
  ('iletisim_eposta',   'info@teksilsite.com', 'iletisim'),
  ('iletisim_adres',    'Merter Giyim Merkezi, Zeytinburnu / İstanbul', 'iletisim'),
  ('whatsapp',          '905000000000', 'iletisim'),
  ('ucretsiz_kargo_esik','2000', 'kargo'),
  ('arsiv_default_para_birimi','TRY','genel');

TRUNCATE TABLE kategoriler;
-- üst kategoriler
INSERT INTO kategoriler (id, ust_id, ad, slug, sira, durum) VALUES
  (1, NULL, 'Üst Giyim',     'ust-giyim',  1, 1),
  (2, NULL, 'Alt Giyim',     'alt-giyim',  2, 1),
  (3, NULL, 'Elbise & Tulum','elbise',     3, 1),
  (4, NULL, 'Dış Giyim',     'dis-giyim',  4, 1),
  (5, NULL, 'Yeni Gelenler', 'yeni',       0, 1);
-- alt kategoriler
INSERT INTO kategoriler (ust_id, ad, slug, sira, durum) VALUES
  (1, 'Tişört & Body',    'tisort-body',    1, 1),
  (1, 'Bluz & Gömlek',    'bluz-gomlek',    2, 1),
  (1, 'Sweatshirt',       'sweatshirt',     3, 1),
  (1, 'Triko & Hırka',    'triko-hirka',    4, 1),
  (2, 'Etek',             'etek',           1, 1),
  (2, 'Pantolon',         'pantolon',       2, 1),
  (2, 'Eşofman',          'esofman',        3, 1);

TRUNCATE TABLE markalar;
INSERT INTO markalar (id, ad, slug, durum) VALUES
  (1, 'TekstilSite', 'teksilsite', 1);

-- Vitrin ürünleri (homepage mg_vitrin çeker; vitrin=1)
TRUNCATE TABLE urunler;
INSERT INTO urunler (kategori_id, marka_id, ad, slug, stok_kodu, aciklama, alis_fiyat, fiyat, moq, birim_adim, vitrin, cok_satan, durum, sira, satis_adet, ana_gorsel) VALUES
  (6, 1, 'Süprem V Yaka Body',   'suprem-v-yaka-body',   'TKS-1001', 'Süprem kumaş, rahat kalıp. 6 adet katı toptan satış.', 45, 79.90,  6, 6, 1, 1, 1, 1, 120, 'https://picsum.photos/seed/tks0/600/800'),
  (6, 1, 'Basic O Yaka Tişört',  'basic-o-yaka-tisort',  'TKS-1002', 'Pamuklu basic tişört, her mevsim.',                    40, 69.90,  6, 6, 1, 1, 1, 2, 180, 'https://picsum.photos/seed/tks1/600/800'),
  (9, 1, 'Wide Leg Pantolon',    'wide-leg-pantolon',    'TKS-2001', 'Geniş paça, yüksek bel pantolon.',                     90, 149.90, 4, 4, 1, 1, 1, 3,  60, 'https://picsum.photos/seed/tks2/600/800'),
  (8, 1, 'Volanlı Mini Etek',    'volanli-mini-etek',    'TKS-2002', 'Volan detaylı şık mini etek.',                         70, 119.90, 4, 4, 1, 0, 1, 4,  40, 'https://picsum.photos/seed/tks3/600/800'),
  (3, 1, 'Şifon Elbise',         'sifon-elbise',         'TKS-3001', 'Hafif şifon, yazlık elbise.',                          130,219.90, 3, 3, 1, 1, 1, 5,  35, 'https://picsum.photos/seed/tks4/600/800'),
  (7, 1, 'Kapüşonlu Sweatshirt', 'kapusonlu-sweatshirt', 'TKS-1003', 'Penye kapüşonlu sweatshirt.',                          105,179.90, 4, 4, 1, 0, 1, 6,  75, 'https://picsum.photos/seed/tks5/600/800'),
  (6, 1, 'Oversize Gömlek',      'oversize-gomlek',      'TKS-1004', 'Oversize kesim pamuklu gömlek.',                       80, 139.90, 6, 6, 1, 0, 1, 7,  50, 'https://picsum.photos/seed/tks6/600/800'),
  (7, 1, 'Triko Hırka',          'triko-hirka',          'TKS-1005', 'Yumuşacık triko hırka.',                               110,189.90, 4, 4, 1, 1, 1, 8,  45, 'https://picsum.photos/seed/tks7/600/800');

-- Varyantlar (ürün 1-4; dev'de elle girilmişti — 2026-08-15 provasında seed'e alındı:
-- varyantsız kurulumda hiçbir ürün satın alınamaz, sepet/checkout akışı ölür)
TRUNCATE TABLE urun_varyantlari;
INSERT INTO urun_varyantlari (id, urun_id, renk, beden, stok, kritik_stok, sku, durum) VALUES
  (1, 1, 'Siyah', 'S', 248, 10, 'TKS-1001-SS', 1),
  (2, 2, 'Siyah', 'S', 224, 10, 'TKS-1002-SS', 1),
  (3, 3, 'Siyah', 'S',  89, 10, 'TKS-2001-SS', 1),
  (4, 4, 'Siyah', 'S', 202, 10, 'TKS-2002-SS', 1);

-- Fiyat basamağı örneği (global): 50+ adette %5, 100+ adette %10
TRUNCATE TABLE fiyat_basamaklari;
INSERT INTO fiyat_basamaklari (urun_id, min_adet, indirim_yuzde) VALUES
  (NULL, 50,  5.00),
  (NULL, 100, 10.00);

TRUNCATE TABLE odeme_yontemleri;
INSERT INTO odeme_yontemleri (id, kod, ad, tip, ek_ucret, ek_ucret_tip, sira, durum) VALUES
  (1, 'havale',  'Havale / EFT',        'havale',       0,   'tutar', 1, 1),
  (2, 'kapida',  'Kapıda Nakit Ödeme',  'kapida_odeme', 50,  'tutar', 2, 1),
  (3, 'paytr',   'Kredi/Banka Kartı (PayTR)', 'sanal_pos', 3, 'yuzde', 3, 0);

TRUNCATE TABLE banka_hesaplari;
INSERT INTO banka_hesaplari (banka_adi, hesap_sahibi, iban, sube, durum) VALUES
  ('Türkiye İş Bankası', 'TekstilSite Toptan Ltd.', 'TR00 0000 0000 0000 0000 0000 00', 'Merter', 1),
  ('Ziraat Bankası',     'TekstilSite Toptan Ltd.', 'TR11 1111 1111 1111 1111 1111 11', 'Merter', 1);

TRUNCATE TABLE kargo_firmalari;
INSERT INTO kargo_firmalari (id, ad, takip_url, durum) VALUES
  (1, 'Yurtiçi Kargo', 'https://www.yurticikargo.com/bireysel/shipment-tracking?tracking-number={takip_no}', 1),
  (2, 'Aras Kargo',    'https://www.araskargo.com/tr/tr/cargo-tracking/?query={takip_no}', 1);

INSERT INTO kargo_ucretleri (firma_id, min_desi, max_desi, ucret) VALUES
  (1, 0, 5, 79.90),
  (1, 5, 10, 119.90),
  (2, 0, 5, 84.90);

TRUNCATE TABLE sayfalar;
INSERT INTO sayfalar (baslik, slug, icerik, durum) VALUES
  ('Hakkımızda', 'hakkimizda', '<p>TekstilSite — 2006''dan bu yana toptan kadın giyim. Merter''de üretim, dünya geneline sevkiyat.</p>', 1),
  ('Mesafeli Satış Sözleşmesi', 'mesafeli-satis', '<p>6502 sayılı kanuna göre mesafeli satış sözleşmesi. Cayma süresi 14 gündür.</p>', 1),
  ('İade ve Değişim', 'iade-degisim', '<p>İade ve değişim koşulları.</p>', 1),
  ('Gizlilik ve KVKK', 'gizlilik', '<p>6698 sayılı KVKK kapsamında gizlilik politikası.</p>', 1),
  ('Çerez Politikası', 'cerez', '<p>Çerez politikası.</p>', 1);

SET FOREIGN_KEY_CHECKS = 1;
