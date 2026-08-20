-- migrate_kupon_kod.sql — D2-rapor (XXXVIII): siparisler.kupon_kod — kupon atıflaması.
-- Önceki kurulumlarda indirim TUTARI yazılıyordu ama hangi kupon olduğu kayboluyordu;
-- kupon ROI raporu için kod kalıcı hale gelir. İdempotent (XXXVI deseni).
SET NAMES utf8mb4;

SET @kolon_var = (SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siparisler' AND COLUMN_NAME = 'kupon_kod');
SET @sql = IF(@kolon_var = 0,
              'ALTER TABLE siparisler ADD COLUMN kupon_kod VARCHAR(40) NULL DEFAULT NULL AFTER indirim',
              'SELECT ''siparisler.kupon_kod mevcut — ALTER atlandi'' AS bilgi');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
