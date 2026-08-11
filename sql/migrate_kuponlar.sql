-- migrate_kuponlar.sql — Kupon / kampanya kodları (checkout indirimi)
-- Taze kurulumda sql/schema.sql ile gelir; mevcut kurulumlara bu migration uygulanır.
-- Uygulama: mysql -u root -p teksilsite < sql/migrate_kuponlar.sql

CREATE TABLE IF NOT EXISTS kuponlar (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kod VARCHAR(60) NOT NULL,
  aciklama VARCHAR(190) NULL,
  tip ENUM('yuzde','sabit') NOT NULL DEFAULT 'yuzde',
  deger DECIMAL(12,2) NOT NULL DEFAULT 0,
  min_sepet_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
  max_indirim DECIMAL(12,2) NOT NULL DEFAULT 0,
  baslangic_zaman DATETIME NULL,
  bitis_zaman DATETIME NULL,
  kullanim_limiti INT NOT NULL DEFAULT 0,
  kullanim_sayisi INT NOT NULL DEFAULT 0,
  durum TINYINT NOT NULL DEFAULT 1,
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kupon_kod (kod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
