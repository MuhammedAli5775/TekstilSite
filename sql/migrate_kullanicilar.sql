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

-- Adres defteri — checkout'taki teslimat alanlarıyla aynı serbest-metin semantiği
-- (siparisler.teslimat_il/ilce VARCHAR; il/ilçe FK'su yok, ön-doldurma birebir).
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
