-- ===========================================================================
-- Faz 5: Rol bazlı yetki matrisi (yetkiler tablosu).
-- Süper (rol 1) matriste yer ALMAZ — kod içinde daima tam yetkili (Auth_admin::yetki).
-- Diğer roller (rol 2 "Yönetici", ...) için rol_id × modul × {goruntule,duzenle,sil}.
-- Eksik satır / eksik modül = 0 (erişim yok). UI: yonetim/yetkiler (süper only).
--
-- Modül kelime dağarcığı Yetki_model::\$MODULLER ile birebir tutarlıdır;
-- menü eşlemesi MY_Controller'da (para_birimi -> ayarlar izni).
-- ===========================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS yetkiler (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol_id    INT UNSIGNED NOT NULL,
  modul     VARCHAR(40)  NOT NULL,
  goruntule TINYINT      NOT NULL DEFAULT 0,
  duzenle   TINYINT      NOT NULL DEFAULT 0,
  sil       TINYINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rol_modul (rol_id, modul),
  CONSTRAINT fk_yetki_rol FOREIGN KEY (rol_id) REFERENCES roller (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: rol 2 "Yönetici" = tam erişim (eski davranışın korunması; süper kısıtlayabilir).
-- Güvenli re-çalıştırma: mevcut satırları EZMEZ (INSERT IGNORE).
INSERT IGNORE INTO yetkiler (rol_id, modul, goruntule, duzenle, sil) VALUES
  (2, 'siparisler', 1, 1, 1),
  (2, 'urunler',    1, 1, 1),
  (2, 'kategoriler',1, 1, 1),
  (2, 'markalar',   1, 1, 1),
  (2, 'stok',       1, 1, 1),
  (2, 'bayiler',    1, 1, 1),
  (2, 'faturalar',  1, 1, 1),
  (2, 'pazaryeri',  1, 1, 1),
  (2, 'feed',       1, 1, 1),
  (2, 'raporlar',   1, 1, 1),
  (2, 'bannerlar',  1, 1, 1),
  (2, 'yazilar',    1, 1, 1),
  (2, 'sayfalar',   1, 1, 1),
  (2, 'kuponlar',   1, 1, 1),
  (2, 'ayarlar',    1, 1, 1);
