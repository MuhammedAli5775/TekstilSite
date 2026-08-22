-- ===========================================================================
-- TekstilSite — B2B toptan kadın giyim · Çekirdek şema (Faz 0)
-- UTF-8 · InnoDB · utf8mb4_unicode_ci · Türkçe karaktersiz tablo/kolon adları
-- İçe aktarma: phpMyAdmin (Import)  VEYA  mysql -u root -p teksilsite < schema.sql
-- ===========================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Coğrafi
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS iller (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plaka     TINYINT UNSIGNED NOT NULL,
  ad        VARCHAR(60) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_iller_plaka (plaka)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ilceler (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  il_id  INT UNSIGNED NOT NULL,
  ad     VARCHAR(90) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ilceler_il (il_id),
  CONSTRAINT fk_ilceler_il FOREIGN KEY (il_id) REFERENCES iller (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Sistem: ayarlar (anahtar/değer), loglar, oturum
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ayarlar (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  anahtar  VARCHAR(80) NOT NULL,
  deger    TEXT,
  grup     VARCHAR(40) NOT NULL DEFAULT 'genel',
  aciklama VARCHAR(190),
  PRIMARY KEY (id),
  UNIQUE KEY uq_ayarlar_anahtar (anahtar),
  KEY idx_ayarlar_grup (grup)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sistem_loglari (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  seviye    VARCHAR(10) NOT NULL DEFAULT 'info',
  modul     VARCHAR(60),
  mesaj     TEXT,
  baglam    TEXT,
  ip        VARCHAR(45),
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_log_seviye (seviye),
  KEY idx_log_zaman (olusturma_zaman)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CI oturumları (Faz 0'da files sürücüsü; DB sürücüsüne geçince kullanılır)
CREATE TABLE IF NOT EXISTS ci_sessions (
  id         VARCHAR(128) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  timestamp  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  data       BLOB,
  PRIMARY KEY (id),
  KEY idx_sess_ts (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Yetki (yonetici) — Faz 4'te detaylanır
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roller (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ad   VARCHAR(60) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS yoneticiler (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol_id        INT UNSIGNED NOT NULL DEFAULT 2,
  ad_soyad      VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  sifre         VARCHAR(255) NOT NULL,
  durum         TINYINT NOT NULL DEFAULT 1,
  son_giris     DATETIME,
  olusturma     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_yonetici_email (email),
  CONSTRAINT fk_yonetici_rol FOREIGN KEY (rol_id) REFERENCES roller (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Katalog
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kategoriler (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ust_id          INT UNSIGNED NULL,
  ad              VARCHAR(120) NOT NULL,
  ad_en           VARCHAR(120) NULL,
  ad_ru           VARCHAR(120) NULL,
  ad_ar           VARCHAR(120) NULL,
  slug            VARCHAR(150) NOT NULL,
  aciklama        TEXT,
  gorsel          VARCHAR(255),
  sira            INT NOT NULL DEFAULT 0,
  durum           TINYINT NOT NULL DEFAULT 1,
  meta_title      VARCHAR(190),
  meta_description VARCHAR(320),
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kategori_slug (slug),
  KEY idx_kategori_ust (ust_id),
  CONSTRAINT fk_kategori_ust FOREIGN KEY (ust_id) REFERENCES kategoriler (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS markalar (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ad    VARCHAR(120) NOT NULL,
  slug  VARCHAR(150) NOT NULL,
  logo  VARCHAR(255),
  durum TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_marka_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urunler (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kategori_id      INT UNSIGNED NULL,
  marka_id         INT UNSIGNED NULL,
  ad               VARCHAR(190) NOT NULL,
  slug             VARCHAR(200) NOT NULL,
  stok_kodu        VARCHAR(80) NOT NULL,
  aciklama         TEXT,
  alis_fiyat       DECIMAL(10,2) NOT NULL DEFAULT 0,
  fiyat            DECIMAL(10,2) NOT NULL DEFAULT 0,
  eski_fiyat       DECIMAL(10,2) NOT NULL DEFAULT 0,
  kdv              TINYINT NOT NULL DEFAULT 20,
  moq              INT NOT NULL DEFAULT 1,        -- minimum sipariş adedi (B2B)
  birim_adim       INT NOT NULL DEFAULT 1,        -- adet basamağı (1,6,12...)
  vitrin           TINYINT NOT NULL DEFAULT 0,
  cok_satan        TINYINT NOT NULL DEFAULT 0,
  durum            TINYINT NOT NULL DEFAULT 1,
  sira             INT NOT NULL DEFAULT 0,
  satis_adet       INT NOT NULL DEFAULT 0,
  ana_gorsel       VARCHAR(255),
  meta_title       VARCHAR(190),
  meta_description VARCHAR(320),
  olusturma_zaman  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  guncelleme_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_urun_slug (slug),
  UNIQUE KEY uq_urun_stok_kodu (stok_kodu),
  KEY idx_urun_kategori (kategori_id),
  KEY idx_urun_vitrin (vitrin, durum),
  CONSTRAINT fk_urun_kategori FOREIGN KEY (kategori_id) REFERENCES kategoriler (id) ON DELETE SET NULL,
  CONSTRAINT fk_urun_marka    FOREIGN KEY (marka_id)    REFERENCES markalar (id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urun_varyantlari (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  urun_id    INT UNSIGNED NOT NULL,
  renk       VARCHAR(60),
  beden      VARCHAR(20),
  stok       INT NOT NULL DEFAULT 0,
  kritik_stok INT NOT NULL DEFAULT 0,
  sku        VARCHAR(80),
  durum      TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_varyant_urun (urun_id),
  CONSTRAINT fk_varyant_urun FOREIGN KEY (urun_id) REFERENCES urunler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS urun_gorselleri (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  urun_id INT UNSIGNED NOT NULL,
  yol     VARCHAR(255) NOT NULL,
  sira    INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_gorsel_urun (urun_id),
  CONSTRAINT fk_gorsel_urun FOREIGN KEY (urun_id) REFERENCES urunler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fiyat basamağı (adet aralığı → indirim %) — B2B
CREATE TABLE IF NOT EXISTS fiyat_basamaklari (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  urun_id   INT UNSIGNED NULL,    -- NULL = global kural
  min_adet  INT NOT NULL,
  indirim_yuzde DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_basamak_urun (urun_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Bayi (B2B müşteri)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bayi_gruplari (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ad            VARCHAR(80) NOT NULL,
  indirim_yuzde DECIMAL(5,2) NOT NULL DEFAULT 0,   -- grubun ek indirimi
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bayiler (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  grup_id        INT UNSIGNED NOT NULL DEFAULT 1,
  yetkili_ad_soyad VARCHAR(120) NOT NULL,
  firma_adi      VARCHAR(160) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  telefon        VARCHAR(30),
  vergi_no       VARCHAR(30),
  vergi_dairesi  VARCHAR(120),
  sifre          VARCHAR(255) NOT NULL,
  bakiye         DECIMAL(12,2) NOT NULL DEFAULT 0,
  para_birimi    CHAR(3) NOT NULL DEFAULT 'TRY',
  son_giris      DATETIME,                -- 2026-08-15: dev'de vardı, şemaya alındı (drift fix)
  durum          TINYINT NOT NULL DEFAULT 0,        -- 0=onay bekliyor, 1=aktif, 2=pasif
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bayi_email (email),
  CONSTRAINT fk_bayi_grup FOREIGN KEY (grup_id) REFERENCES bayi_gruplari (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı (B2C) hesapları — bayiden ayrı sade kimlik (kayıt anında aktif).
CREATE TABLE IF NOT EXISTS kullanicilar (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ad_soyad   VARCHAR(120) NOT NULL,
  kullanici_adi VARCHAR(30),               -- 2026-08-16: kullanıcı adı (unique; NULL = eski kayıt)
  email      VARCHAR(150) NOT NULL,
  telefon    VARCHAR(30),
  sifre      VARCHAR(255) NOT NULL,
  durum      TINYINT NOT NULL DEFAULT 1,
  son_giris  DATETIME,
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kullanici_email (email),
  UNIQUE KEY uq_kullanici_adi (kullanici_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı adres defteri — checkout teslimat alanlarıyla aynı serbest-metin semantiği.
CREATE TABLE IF NOT EXISTS kullanicilar_adresleri (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kullanici_id  INT UNSIGNED NOT NULL,
  ad_soyad      VARCHAR(120),
  adres         VARCHAR(255),
  il            VARCHAR(60),
  ilce          VARCHAR(90),
  telefon       VARCHAR(30),
  varsayilan    TINYINT NOT NULL DEFAULT 0,
  tip           ENUM('teslimat','fatura','her_ikisi') NOT NULL DEFAULT 'her_ikisi',
  PRIMARY KEY (id),
  KEY idx_kadres_kullanici (kullanici_id),
  CONSTRAINT fk_kadres_kullanici FOREIGN KEY (kullanici_id) REFERENCES kullanicilar (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bayi_adresleri (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  bayi_id   INT UNSIGNED NOT NULL,
  ad_soyad  VARCHAR(120),
  adres     VARCHAR(255),
  il_id     INT UNSIGNED NULL,
  ilce_id   INT UNSIGNED NULL,
  telefon   VARCHAR(30),
  varsayilan TINYINT NOT NULL DEFAULT 0,
  tip       ENUM('teslimat','fatura','her_ikisi') NOT NULL DEFAULT 'her_ikisi',
  PRIMARY KEY (id),
  KEY idx_adres_bayi (bayi_id),
  CONSTRAINT fk_adres_bayi FOREIGN KEY (bayi_id) REFERENCES bayiler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sepet (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bayi_id    INT UNSIGNED NULL,
  oturum_id  VARCHAR(128),
  urun_id    INT UNSIGNED NOT NULL,
  varyant_id INT UNSIGNED NULL,
  adet       INT NOT NULL DEFAULT 1,
  eklenme    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sepet_bayi (bayi_id),
  KEY idx_sepet_oturum (oturum_id),
  CONSTRAINT fk_sepet_urun    FOREIGN KEY (urun_id)    REFERENCES urunler (id)          ON DELETE CASCADE,
  CONSTRAINT fk_sepet_varyant FOREIGN KEY (varyant_id) REFERENCES urun_varyantlari (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Sipariş
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS siparisler (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siparis_no      VARCHAR(30) NOT NULL,
  bayi_id         INT UNSIGNED NULL,
  para_birimi     CHAR(3) NOT NULL DEFAULT 'TRY',
  kur             DECIMAL(10,4) NOT NULL DEFAULT 1,
  ara_toplam      DECIMAL(12,2) NOT NULL DEFAULT 0,
  indirim         DECIMAL(12,2) NOT NULL DEFAULT 0,
  kupon_kod       VARCHAR(40)   NULL DEFAULT NULL,   -- uygulanan kupon (XXXVIII — kupon ROI raporu)
  islem_ucreti    DECIMAL(10,2) NOT NULL DEFAULT 0,   -- kart komisyonu / kapıda ek
  kargo_ucreti    DECIMAL(10,2) NOT NULL DEFAULT 0,   -- yalnız kargo
  toplam          DECIMAL(12,2) NOT NULL DEFAULT 0,
  odeme_yontemi   VARCHAR(40),
  odeme_durumu    ENUM('bekliyor','odendi','kismi','iade') NOT NULL DEFAULT 'bekliyor',
  durum           VARCHAR(30) NOT NULL DEFAULT 'onay_bekliyor',
  teslimat_ad     VARCHAR(150),
  teslimat_adres  TEXT,
  teslimat_il     VARCHAR(60),
  teslimat_ilce   VARCHAR(90),
  teslimat_telefon VARCHAR(30),
  email           VARCHAR(150) NULL,     -- 2026-08-15: sipariş e-postası (dev'de vardı, şemaya alındı)
  fatura_ad       VARCHAR(150),
  fatura_adres    TEXT,
  firma_adi       VARCHAR(160),
  vergi_no        VARCHAR(30),
  kargo_firma_id  INT UNSIGNED NULL,
  kargo_takip_no  VARCHAR(60),
  admin_notu      TEXT,
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_siparis_no (siparis_no),
  KEY idx_siparis_bayi (bayi_id),
  KEY idx_siparis_durum (durum),
  CONSTRAINT fk_siparis_bayi    FOREIGN KEY (bayi_id)         REFERENCES bayiler (id)     ON DELETE SET NULL,
  CONSTRAINT fk_siparis_kargo   FOREIGN KEY (kargo_firma_id)  REFERENCES kargo_firmalari (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS siparis_detaylari (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siparis_id  BIGINT UNSIGNED NOT NULL,
  urun_id     INT UNSIGNED NULL,
  urun_adi    VARCHAR(190) NOT NULL,    -- anlık kopya
  stok_kodu   VARCHAR(80),
  varyant_bilgi VARCHAR(90),
  birim_fiyat DECIMAL(10,2) NOT NULL,
  adet        INT NOT NULL,
  kdv         TINYINT NOT NULL DEFAULT 20,
  ara_toplam  DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_detay_siparis (siparis_id),
  CONSTRAINT fk_detay_siparis FOREIGN KEY (siparis_id) REFERENCES siparisler (id) ON DELETE CASCADE,
  CONSTRAINT fk_detay_urun    FOREIGN KEY (urun_id)    REFERENCES urunler (id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS siparis_durum_gecmisi (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  siparis_id BIGINT UNSIGNED NOT NULL,
  durum      VARCHAR(30) NOT NULL,
  notu       VARCHAR(255),
  taraf      ENUM('bayi','admin','sistem') NOT NULL DEFAULT 'sistem',
  zaman      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_gecmis_siparis (siparis_id),
  CONSTRAINT fk_gecmis_siparis FOREIGN KEY (siparis_id) REFERENCES siparisler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Ödeme & Kargo
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS odeme_yontemleri (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kod       VARCHAR(40) NOT NULL,
  ad        VARCHAR(120) NOT NULL,
  tip       ENUM('sanal_pos','havale','kapida_odeme') NOT NULL DEFAULT 'havale',
  ayarlar   TEXT,            -- JSON (POS anahtarları, banka vs.)
  ek_ucret  DECIMAL(10,2) NOT NULL DEFAULT 0,
  ek_ucret_tip ENUM('tutar','yuzde') NOT NULL DEFAULT 'tutar',
  sira      INT NOT NULL DEFAULT 0,
  durum     TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_odeme_kod (kod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS banka_hesaplari (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  banka_adi  VARCHAR(120) NOT NULL,
  hesap_sahibi VARCHAR(150),
  iban       VARCHAR(40),
  sube       VARCHAR(80),
  hesap_no   VARCHAR(40),
  durum      TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kargo_firmalari (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ad        VARCHAR(120) NOT NULL,
  takip_url VARCHAR(255),    -- {takip_no} placeholder
  logo      VARCHAR(255),
  durum     TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kargo_ucretleri (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  firma_id   INT UNSIGNED NOT NULL,
  min_desi   DECIMAL(6,2) NOT NULL DEFAULT 0,
  max_desi   DECIMAL(6,2) NOT NULL DEFAULT 0,
  ucret      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_kargo_ucret_firma (firma_id),
  CONSTRAINT fk_kargo_ucret FOREIGN KEY (firma_id) REFERENCES kargo_firmalari (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- İçerik
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bannerlar (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  yer       VARCHAR(60) NOT NULL DEFAULT 'anasayfa_slider',
  baslik    VARCHAR(190),
  alt_baslik VARCHAR(255),
  gorsel    VARCHAR(255),
  video     VARCHAR(255),
  link      VARCHAR(255),
  buton_yazi VARCHAR(60),
  yazi_konum ENUM('sol','orta','sag') NOT NULL DEFAULT 'sol',
  dil       VARCHAR(2) NULL,
  sira      INT NOT NULL DEFAULT 0,
  durum     TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_banner_yer (yer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sayfalar (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  baslik     VARCHAR(190) NOT NULL,
  slug       VARCHAR(150) NOT NULL,
  icerik     LONGTEXT,
  seo_title  VARCHAR(190),
  seo_description VARCHAR(320),
  durum      TINYINT NOT NULL DEFAULT 1,
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sayfa_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- Kupon / kampanya kodlari (checkout indirimi) — 2026-08-11
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

-- Blog yazıları (D3/XXXV — demo verisi migrate_yazilar.sql'de)
CREATE TABLE IF NOT EXISTS yazilar (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug         VARCHAR(160)  NOT NULL,
  baslik       VARCHAR(200)  NOT NULL,
  ozet         VARCHAR(500)  NOT NULL DEFAULT '',
  icerik       MEDIUMTEXT    NULL,
  gorsel       VARCHAR(500)  NOT NULL DEFAULT '',
  durum        TINYINT       NOT NULL DEFAULT 1,
  yayin_tarihi DATE          NULL,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_yazi_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS giris_denemeleri (
  tip        VARCHAR(12)    NOT NULL,
  ip         VARCHAR(45)    NOT NULL,
  basarisiz  INT UNSIGNED   NOT NULL DEFAULT 0,
  son_deneme DATETIME       NOT NULL,
  PRIMARY KEY (tip, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
