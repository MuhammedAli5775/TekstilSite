-- migrate_sifre_sifirlama.sql — LIX: şifre sıfırlama token tablosu.
-- Tokenlar tek kullanımlık, 30 dk geçerli (Sifre denetçisi).
-- Taze kurulumda GEREK YOK: schema.sql içerir.
-- Uygulama: mysql -u root -p teksilsite < sql/migrate_sifre_sifirlama.sql

CREATE TABLE IF NOT EXISTS sifre_sifirlama (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tip        ENUM('kullanici','bayi') NOT NULL,
  eposta     VARCHAR(150) NOT NULL,
  token      CHAR(64) NOT NULL,
  uretildi   DATETIME NOT NULL,
  kullanildi TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sifre_token (token),
  KEY idx_sifre_eposta (tip, eposta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
