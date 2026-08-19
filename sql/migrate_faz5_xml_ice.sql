-- ============================================================================
-- Faz 5: XML içe aktarım (toptancı/tedarikçi feed'i -> katalog).
--
-- Kaynak (xml_kaynaklari) bir tedarikçi XML adresini tanımlar; alan eşlemesi
-- (esleme, JSON) etiket isimlerini urunler/urun_varyantlari kolonlarına bağlar.
-- Varsayılan eşleme TekstilSite'in KENDİ Xml_export biçimidir: api/Feed
-- çıktısı aynen geri alınabilir. Eşleşme anahtarı urunler.stok_kodu.
-- Her koşu xml_loglari'na yazılır (önizleme koşuları dahil).
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS xml_kaynaklari (
  id                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  ad                     VARCHAR(120)  NOT NULL,               -- etiket
  url                    VARCHAR(500)  NOT NULL,               -- feed adresi (yalnız http/https)
  esleme                 TEXT          NULL,                   -- JSON alan eşlemesi (NULL = varsayılan)
  varsayilan_kategori_id INT UNSIGNED  NULL,                   -- yeni üründe kategori eşleşmezse
  fiyat_carpani          DECIMAL(10,4) NOT NULL DEFAULT 1,     -- XML fiyatına uygulanır
  yeni_urun_olustur      TINYINT       NOT NULL DEFAULT 1,     -- 0 = yalnız mevcut ürün güncelle
  durum                  TINYINT       NOT NULL DEFAULT 1,     -- 1=aktif, 0=pasif (cron bunları işler)
  son_calisma            DATETIME      NULL,
  son_sonuc              VARCHAR(15)   NULL,                   -- basarili / hata
  olusturma_zaman        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncelleme_zaman       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_xml_kaynak_kat (varsayilan_kategori_id),
  CONSTRAINT fk_xml_kaynak_kat FOREIGN KEY (varsayilan_kategori_id) REFERENCES kategoriler (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS xml_loglari (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kaynak_id       INT UNSIGNED NOT NULL,
  kip             VARCHAR(10)  NOT NULL,          -- onizleme / gercek ('mod' SQL reserved)
  durum           VARCHAR(15)  NOT NULL,          -- basarili / hata
  urun_sayisi     INT          NOT NULL DEFAULT 0, -- XML'de görülen toplam
  yeni            INT          NOT NULL DEFAULT 0,
  guncellenen     INT          NOT NULL DEFAULT 0,
  atlanan         INT          NOT NULL DEFAULT 0,
  varyant_eklenen INT          NOT NULL DEFAULT 0,
  varyant_guncellenen INT      NOT NULL DEFAULT 0,
  ozet            VARCHAR(255) NULL,
  hata_mesaji     TEXT         NULL,
  sure_sn         DECIMAL(8,2) NOT NULL DEFAULT 0,
  zaman           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_xml_log_kaynak (kaynak_id),
  CONSTRAINT fk_xml_log_kaynak FOREIGN KEY (kaynak_id) REFERENCES xml_kaynaklari (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rol-2 yetkisi BURADA DEĞİL: yetkiler tablosunu yaratan migrate_yetkiler.sql'in
-- seed listesinde (INSERT IGNORE) — o dosya bu tabloya FK verdiği için zincirde
-- seed.sql'den SONRA koşar; burada koşsaydık taze kurulumda tablo yoktu.
-- Mevcut kurulumu upgrade eden: migrate_yetkiler.sql'i yeniden koş (güvenli).
