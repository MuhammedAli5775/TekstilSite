-- migrate_ulke_para.sql — teslimat ülkesi seçicisi için ek para birimleri (XXXIV).
-- Dil dropdown'undaki ülke seçici bu para birimlerine eşlenir (teksil_helper::ulke_listesi).
-- kur_try değerleri placeholder — admin Para Birimi panelinden güncellenir.
-- Re-seed mevcut kur'u EZMEZ (migrate_para_birimi deseni).
SET NAMES utf8mb4;

INSERT INTO para_birimleri (kod, ad, sembol, kur_try, durum, sira) VALUES
  ('GBP', 'İngiliz Sterlini', '£',   41.0000, 1, 3),
  ('RUB', 'Rus Rublesi',      '₽',    0.3600, 1, 4),
  ('AED', 'BAE Dirhemi',      'د.إ',  8.8500, 1, 5)
ON DUPLICATE KEY UPDATE ad = VALUES(ad), sembol = VALUES(sembol);
