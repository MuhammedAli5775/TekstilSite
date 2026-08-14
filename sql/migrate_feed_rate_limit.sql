-- ===========================================================================
-- Feed API brute-force koruması: IP başına başarısız anahtar deneme sayacı.
-- (/feed/urunler — makine-makine uç; session yok → sayaç IP tabanlı.)
--
-- Mantık (Api_anahtar_model):
--   • Yanlış anahtar denemesi → deneme_kaydet(): sayaç +1 (15 dk pencere;
--     pencere dolduysa sayaç yeniden başlar).
--   • basarisiz >= 20 VE son deneme < 15 dk önce → bloklu_mu() = TRUE
--     → API 429 döner, hash sorgusu bile yapılmaz.
--   • Geçerli anahtar → deneme_temizle() sayacı sıfırlar.
--   • Doğru anahtar kullanan meşru tüketici hiç sayaç artırmaz (NAT güvenli).
--
-- Tablo tek satır/IP; pencere dışı eskimiş satırlar sonraki başarısız
-- denemede üzerine yazılır (temizlik cron'u gerekmez; istenirse silinebilir).
-- ===========================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS feed_denemeler (
  ip         VARCHAR(45) NOT NULL,
  basarisiz  INT UNSIGNED NOT NULL DEFAULT 0,
  son_deneme DATETIME    NOT NULL,
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
