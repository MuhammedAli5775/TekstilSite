-- migrate_banner_dil.sql — bannerlar.dil: anasayfa slider'ının görüneceği dil
-- (NULL = tüm diller). XXIX dil altyapısının vitrin uzantısı (DEGISIKLIK XXX).
-- Taze kurulumda sql/schema.sql ile gelir; mevcut kurulumlara bu migration uygulanır.
-- Uygulama: mysql -u teksil_app -p teksilsite < sql/migrate_banner_dil.sql
-- (Dev DB'ye zaten uygulandı — sütun sırası schema.sql ile aynı: yazi_konum'dan sonra.)

SET NAMES utf8mb4;

-- İdempotent (XXXVI): taze §3 kurulumunda sütun schema.sql'den zaten gelir —
-- ALTER yalnız eksikse koşar; koşulsuz olsaydı "Duplicate column" ile §3
-- sırasını kırardı (2026-08-18 sıfır-DB provasında yakalandı).
SET @kolon_var = (SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bannerlar' AND COLUMN_NAME = 'dil');
SET @sql = IF(@kolon_var = 0,
              'ALTER TABLE bannerlar ADD COLUMN dil VARCHAR(2) NULL AFTER yazi_konum',
              'SELECT ''bannerlar.dil mevcut — ALTER atlandi'' AS bilgi');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Not: Mevcut satırlar NULL kalır → banner tüm dillerde görünmeye devam eder
-- (davranış değişmez; admin Bannerlar'dan dil seçilince filtre devreye girer).
