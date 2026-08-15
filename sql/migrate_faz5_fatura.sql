-- Faz 5: E-Fatura / E-Arşiv kayıt katmanı (sağlayıcı-bağımsız).
-- Sağlayıcı entegrasyonu (Paraşüt/Uyumsoft/...) Ayarlar'dan yapılandırılır;
-- faturalar her durumda buraya kaydedilir (kayıt/takip kaynağı).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS faturalar (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  siparis_id    BIGINT UNSIGNED NOT NULL,  -- schema.sql: siparisler.id BIGINT UNSIGNED (FK uyumu; INT olsa 3780)
  bayi_id         INT UNSIGNED NULL,
  fatura_no       VARCHAR(40)  NULL,                       -- site içi no (sipariş no tabanlı)
  etn             VARCHAR(60)  NULL,                       -- e-fatura no / ETN (entegratörden)
  uuid            CHAR(36)     NULL,
  tip             ENUM('efatura','earsiv') NOT NULL DEFAULT 'earsiv',
  durum           VARCHAR(20)  NOT NULL DEFAULT 'bekliyor', -- bekliyor/isleniyor/olustu/gonderildi/reddedildi/iptal
  entegrator      VARCHAR(30)  NULL,                       -- parasut/uyumsoft/... (boş=manuel)
  process_id      VARCHAR(80)  NULL,                       -- asenkron işlem takip id'si
  alici_unvan     VARCHAR(180) NULL,
  alici_vkn       VARCHAR(30)  NULL,
  alici_eposta    VARCHAR(150) NULL,
  matrah          DECIMAL(12,2) NOT NULL DEFAULT 0,        -- KDV hariç
  kdv             DECIMAL(12,2) NOT NULL DEFAULT 0,
  toplam          DECIMAL(12,2) NOT NULL DEFAULT 0,        -- KDV dahil (sipariş toplamı)
  para_birimi     CHAR(3)      NOT NULL DEFAULT 'TRY',
  pdf_url         VARCHAR(255) NULL,
  hata_mesaji     TEXT         NULL,
  olusturma_zaman DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncelleme_zaman DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_fatura_siparis (siparis_id),
  KEY idx_fatura_bayi (bayi_id),
  KEY idx_fatura_durum (durum),
  CONSTRAINT fk_fatura_siparis FOREIGN KEY (siparis_id) REFERENCES siparisler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
