-- migrate_banner_dil.sql — bannerlar.dil: anasayfa slider'ının görüneceği dil
-- (NULL = tüm diller). XXIX dil altyapısının vitrin uzantısı (DEGISIKLIK XXX).
-- Taze kurulumda sql/schema.sql ile gelir; mevcut kurulumlara bu migration uygulanır.
-- Uygulama: mysql -u teksil_app -p teksilsite < sql/migrate_banner_dil.sql
-- (Dev DB'ye zaten uygulandı — sütun sırası schema.sql ile aynı: yazi_konum'dan sonra.)

SET NAMES utf8mb4;

ALTER TABLE bannerlar ADD COLUMN dil VARCHAR(2) NULL AFTER yazi_konum;

-- Not: Mevcut satırlar NULL kalır → banner tüm dillerde görünmeye devam eder
-- (davranış değişmez; admin Bannerlar'dan dil seçilince filtre devreye girer).
