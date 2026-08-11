-- TekstilSite — Faz 4 migrasyonu: yönetici audit logu
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS yonetici_loglari (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  yonetici_id  INT UNSIGNED NULL,
  modul        VARCHAR(60),
  islem        VARCHAR(60),
  hedef        VARCHAR(120),
  aciklama     VARCHAR(255),
  ip           VARCHAR(45),
  zaman        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_log_yonetici (yonetici_id),
  KEY idx_log_zaman (zaman)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
