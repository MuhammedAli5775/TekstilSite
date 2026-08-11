-- Faz 5: B2B XML/REST ürün feed'i için toptancı API anahtar tablosu.
-- Anahtar plaintext DEĞİL: yalnızca sha256 hash saklanır (sorgulanabilir).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS api_anahtarlari (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bayi_id         INT UNSIGNED NULL,                       -- opsiyonel: belirli bayiye bağlı; NULL = genel
  ad              VARCHAR(120) NOT NULL,                   -- etiket (örn "Bayı X feed anahtarı")
  onek            VARCHAR(16)  NOT NULL,                   -- anahtar öneki (tanıma/görüntü)
  anahtar_hash    CHAR(64)     NOT NULL,                   -- sha256(ham_anahtar); plaintext değil
  durum           TINYINT      NOT NULL DEFAULT 1,         -- 1=aktif, 0=pasif
  son_kullanim    DATETIME     NULL,
  kullanim_sayisi INT UNSIGNED NOT NULL DEFAULT 0,
  olusturma_zaman DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_api_onek (onek),
  KEY idx_api_hash (anahtar_hash),
  CONSTRAINT fk_api_bayi FOREIGN KEY (bayi_id) REFERENCES bayiler (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
