-- TekstilSite — 2026-08-09 migration (admin denetimi bulguları).
-- Üretim (prod) DB'sinde çalıştırılır. Local dev DB'sine zaten uygulandı.
-- Tarihçe: DEGISIKLIK.md (2026-08-09 girdileri). Mimari: workflow.md.

SET NAMES utf8mb4;

-- (1) İptal/iade'de stok geri-ekleme için: sipariş detay snapshot'ına varyant kimliği.
--     mg_olustur artık varyant_id kaydeder; mg_durum_guncelle iptal/iade'de _stok_iade_et çağırır.
ALTER TABLE siparis_detaylari ADD COLUMN varyant_id INT UNSIGNED NULL AFTER urun_id;

-- (2) Ürün soft-delete (workflow §2). Hard delete varyant/görsel/stok_hareketleri CASCADE siliyordu.
--     mg_sil artık deleted_at=NOW()+durum=0; admin/storefront sorguları deleted_at IS NULL filtreler.
ALTER TABLE urunler ADD COLUMN deleted_at DATETIME NULL;

-- Not: Mevcut satırlar etkilenmez (varyant_id=NULL, deleted_at=NULL — görünüm değişmez).
--       Eski siparişlerin varyant_id=NULL kalır (iadeleri stok hareketi loglar ama varyant stoğu geri ekleyemez).
