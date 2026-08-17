-- ===========================================================================
-- E2 performans: EXPLAIN'in kanıtladığı iki eksik index.
--
-- Bulgu (EXPLAIN, 2026-08-14):
--   1) Storefront katalog varsayılan sıralaması
--      WHERE durum=1 ORDER BY olusturma_zaman DESC LIMIT 24
--      → type=ALL + Using filesort (hiç index kullanılmıyor).
--      fiyat_asc/desc sıralaması da aynı durumda.
--   2) Raporlar/dashboard/cron tarih aralığı
--      WHERE olusturma_zaman >= ? AND <= ? (SUM(toplam*kur), trend GROUP BY)
--      → type=ALL (olusturma_zaman üzerinde index yok).
--
-- Çözüm: iki bileşik index + iki sıralama index'i. Yazma maliyeti önemsiz
-- (ürün/sipariş ekleme sıklığında). MySQL 8.0'da ADD INDEX IF NOT EXISTS
-- YOKTUR → re-run gerekiyorsa önce information_schema'dan varlık kontrolü
-- yapın (aşağıdaki SELECT'ler).
-- ===========================================================================
SET NAMES utf8mb4;

-- Varmı? kontrol: SELECT INDEX_NAME FROM information_schema.STATISTICS
--                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='urunler'
--                 AND INDEX_NAME IN ('idx_urun_sira_yeni','idx_urun_sira_fiyat');
ALTER TABLE urunler
  ADD INDEX idx_urun_sira_yeni  (durum, olusturma_zaman),
  ADD INDEX idx_urun_sira_fiyat (durum, fiyat);

ALTER TABLE siparisler
  ADD INDEX idx_siparis_zaman (olusturma_zaman);

-- 2026-08-17 (E2 tazeleme, DEGISIKLIK XVIII): B2C hesabım sorguları siparişi
-- e-postasıyla çeker (Kullanici_model::mg_siparisler*, Fatura_model join'leri)
-- → EXPLAIN: PRIMARY üzerinde tam geri-tarama (Backward index scan) / join'de
-- type=ALL. idx_siparis_email ref'e indirir.
-- Varmı? kontrol: yukarıdaki SELECT'in IN(...) listesine 'idx_siparis_email' ekleyin.
ALTER TABLE siparisler
  ADD INDEX idx_siparis_email (email);
