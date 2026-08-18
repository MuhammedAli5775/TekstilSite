-- migrate_kategori_dil.sql — kategoriler.ad_{en,ru,ar}: mağaza menüsü / katalog
-- başlığı / ürün detayı kategori adlarının çevirileri (XXXI). Boş çeviri Türkçe
-- ada düşer (dil_helper::kategori_ad fallback); yönetim Kategoriler'den girilir.
-- Taze kurulumda sql/schema.sql ile gelir; mevcut kurulumlara bu migration uygulanır.
-- Uygulama: mysql -u teksil_app -p teksilsite < sql/migrate_kategori_dil.sql

SET NAMES utf8mb4;

-- İdempotent (XXXVI): taze §3 kurulumunda sütunlar schema.sql'den zaten gelir —
-- ALTER yalnız eksikse koşar (banner_dil ile aynı gerekçe; prova bulgusu).
SET @kolon_var = (SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kategoriler' AND COLUMN_NAME = 'ad_en');
SET @sql = IF(@kolon_var = 0,
              'ALTER TABLE kategoriler ADD COLUMN ad_en VARCHAR(120) NULL AFTER ad, ADD COLUMN ad_ru VARCHAR(120) NULL AFTER ad_en, ADD COLUMN ad_ar VARCHAR(120) NULL AFTER ad_ru',
              'SELECT ''kategoriler.ad_* mevcut — ALTER atlandi'' AS bilgi');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Standart demo kategorileri için hazır çeviriler (slug yoksa etkisiz).
UPDATE kategoriler SET ad_en='New Arrivals',         ad_ru='Новинки',                ad_ar='وصل حديثاً'       WHERE slug='yeni';
UPDATE kategoriler SET ad_en='Tops',                 ad_ru='Верх',                   ad_ar='ملابس علوية'      WHERE slug='ust-giyim';
UPDATE kategoriler SET ad_en='T-Shirt & Bodysuit',   ad_ru='Футболки и боди',        ad_ar='تي شيرت وبادي'    WHERE slug='tisort-body';
UPDATE kategoriler SET ad_en='Blouses & Shirts',     ad_ru='Блузки и рубашки',       ad_ar='بلوزة وقميص'      WHERE slug='bluz-gomlek';
UPDATE kategoriler SET ad_en='Sweatshirts',          ad_ru='Свитшоты',               ad_ar='سويت شيرت'        WHERE slug='sweatshirt';
UPDATE kategoriler SET ad_en='Knitwear & Cardigans', ad_ru='Трикотаж и кардиганы',   ad_ar='تريكو وكارديغان'  WHERE slug='triko-hirka';
UPDATE kategoriler SET ad_en='Bottoms',              ad_ru='Низ',                    ad_ar='ملابس سفلية'      WHERE slug='alt-giyim';
UPDATE kategoriler SET ad_en='Skirts',               ad_ru='Юбки',                   ad_ar='تنانير'           WHERE slug='etek';
UPDATE kategoriler SET ad_en='Trousers',             ad_ru='Брюки',                  ad_ar='بناطيل'           WHERE slug='pantolon';
UPDATE kategoriler SET ad_en='Joggers',              ad_ru='Спортивные брюки',       ad_ar='جوغر'             WHERE slug='esofman';
UPDATE kategoriler SET ad_en='Dresses & Jumpsuits',  ad_ru='Платья и комбинезоны',   ad_ar='فساتين وجامب سوت' WHERE slug='elbise';
UPDATE kategoriler SET ad_en='Outerwear',            ad_ru='Верхняя одежда',         ad_ar='ملابس خارجية'     WHERE slug='dis-giyim';
