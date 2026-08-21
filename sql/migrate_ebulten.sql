-- migrate_ebulten.sql — LV: e-bülten abonelik tablosu (MEVCUT DB'ler için).
-- Taze kurulumda GEREK YOK: schema.sql bu tabloyu zaten içerir
-- (DEPLOY.md §3 dosya sırası değişmez; bu dosya yalnız önceden kurulmuş
-- dev/prod DB'lerine sonradan uygulanır).
-- Uygulama: mysql -u root -p teksilsite < sql/migrate_ebulten.sql

CREATE TABLE IF NOT EXISTS ebulten_aboneler (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  eposta     VARCHAR(150)  NOT NULL,
  dil        VARCHAR(2)    NOT NULL DEFAULT 'tr',
  durum      TINYINT       NOT NULL DEFAULT 1,
  kayit_ip   VARCHAR(45)   NULL,
  created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ebulten_eposta (eposta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
