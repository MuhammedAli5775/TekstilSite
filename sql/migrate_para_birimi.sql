-- Faz 5+: Çoklu para birimi — kur tablosu (1 birim = N TRY).
-- TRY daima kur_try=1 (temel). Diğerleri admin tarafından güncellenir (veya otomatik API ileride).
-- Sipariş anlık kopyası: siparisler.para_birimi + kur ile snapshot alınır.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS para_birimleri (
  kod     CHAR(3)     NOT NULL,
  ad      VARCHAR(40) NOT NULL,
  sembol  VARCHAR(8)  NOT NULL,
  kur_try DECIMAL(12,4) NOT NULL DEFAULT 1.0000,   -- 1 birim bu para = N TRY
  durum   TINYINT     NOT NULL DEFAULT 1,
  sira    SMALLINT    NOT NULL DEFAULT 0,
  PRIMARY KEY (kod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed (kur_try admin tarafından ayarlanır; re-seed mevcut kur'u EZMEZ)
INSERT INTO para_birimleri (kod, ad, sembol, kur_try, durum, sira) VALUES
  ('TRY', 'Türk Lirası', '₺', 1.0000,  1, 0),
  ('USD', 'ABD Doları',  '$', 32.5000, 1, 1),
  ('EUR', 'Euro',        '€', 35.2000, 1, 2)
ON DUPLICATE KEY UPDATE ad = VALUES(ad), sembol = VALUES(sembol);
