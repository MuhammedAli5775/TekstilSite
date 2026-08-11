-- Faz 5: Pazaryeri entegrasyonu (Trendyol/Hepsiburada/N11).
-- Hesap kimlikleri (api_key/api_secret) CI Encryption ile şifreli saklanır.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pazaryeri_hesaplari (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  platform        VARCHAR(20)  NOT NULL,                -- trendyol / hepsiburada / n11 / amazon
  ad              VARCHAR(120) NOT NULL,                -- etiket
  supplier_id     VARCHAR(40)  NULL,                    -- Trendyol supplierId
  api_key         TEXT         NULL,                    -- CI Encryption ile şifreli
  api_secret      TEXT         NULL,                    -- CI Encryption ile şifreli
  durum           TINYINT      NOT NULL DEFAULT 1,      -- 1=aktif, 0=pasif
  son_sin         DATETIME     NULL,
  olusturma_zaman DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncelleme_zaman DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pazaryeri_urun_eslestirme (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  hesap_id           INT UNSIGNED NOT NULL,
  urun_id            INT UNSIGNED NOT NULL,
  pazaryeri_urun_id  VARCHAR(80)  NULL,                 -- marketplace ürün ID / barkod
  durum              TINYINT      NOT NULL DEFAULT 1,
  olusturma_zaman    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_paz_eslesme (hesap_id, urun_id),
  KEY idx_paz_urun (urun_id),
  CONSTRAINT fk_paz_eslesme_hesap FOREIGN KEY (hesap_id) REFERENCES pazaryeri_hesaplari (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pazaryeri_loglari (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  hesap_id    INT UNSIGNED NOT NULL,
  islem       VARCHAR(30)  NOT NULL,    -- stok_fiyat / siparis_cek / urun_gonder
  durum       VARCHAR(15)  NOT NULL,    -- basarili / hata / bekliyor
  ozet        VARCHAR(255) NULL,
  hata_mesaji TEXT         NULL,
  zaman       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_paz_log_hesap (hesap_id),
  CONSTRAINT fk_paz_log_hesap FOREIGN KEY (hesap_id) REFERENCES pazaryeri_hesaplari (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
