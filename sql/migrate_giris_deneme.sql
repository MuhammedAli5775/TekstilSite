-- migrate_giris_deneme.sql — LIX: giriş brute-force koruması (IP sayaç, uç-bazlı).
-- Feed deseni (migrate_feed_rate_limit) giriş uçlarına uyarlandı.
-- Taze kurulumda GEREK YOK: schema.sql zaten içerir (§3 sırası değişmez).
-- Uygulama: mysql -u root -p teksilsite < sql/migrate_giris_deneme.sql

CREATE TABLE IF NOT EXISTS giris_denemeleri (
  tip        VARCHAR(12)    NOT NULL,
  ip         VARCHAR(45)    NOT NULL,
  basarisiz  INT UNSIGNED   NOT NULL DEFAULT 0,
  son_deneme DATETIME       NOT NULL,
  PRIMARY KEY (tip, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
