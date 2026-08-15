-- ===========================================================================
-- Kullanıcı (B2C) hesapları — bayiler tablosundan ayrı, sade kimlik.
-- Bayi = firma/vergi/MOQ'lu toptan hesap (admin onaylı); kullanıcı = kişisel
-- hesap (kayıt anında aktif), e-posta bazlı sipariş geçmişi görür.
-- Ekleyen: 2026-08-15 kullanıcı girişi talebi (DEGISIKLIK X).
-- ===========================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS kullanicilar (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ad_soyad   VARCHAR(120) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  telefon    VARCHAR(30),
  sifre      VARCHAR(255) NOT NULL,
  durum      TINYINT NOT NULL DEFAULT 1,   -- kayıt anında aktif (onay kuyruğu yok)
  son_giris  DATETIME,
  olusturma_zaman DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kullanici_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
