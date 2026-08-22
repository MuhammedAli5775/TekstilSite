-- migrate_admin_totp.sql — LXIII: yönetici iki adımlı doğrulama (TOTP) şeması.
-- Taze kurulumda GEREK YOK: schema.sql içerir (yoneticiler.totp_secret + yonetici_kurtarma).
-- Uygulama: mysql -u root -p teksilsite < sql/migrate_admin_totp.sql  (idempotent)

SET @kolon := (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yoneticiler' AND COLUMN_NAME = 'totp_secret');
SET @sql := IF(@kolon = 0,
  'ALTER TABLE yoneticiler ADD COLUMN totp_secret VARCHAR(64) NULL AFTER sifre',
  'SELECT ''yoneticiler.totp_secret mevcut — ALTER atlandi'' AS bilgi');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS yonetici_kurtarma (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  yonetici_id INT UNSIGNED NOT NULL,
  kod_hash    CHAR(64) NOT NULL,
  kullanildi  TINYINT NOT NULL DEFAULT 0,
  uretildi    DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_yk (yonetici_id, kullanildi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
