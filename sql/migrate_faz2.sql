-- ===========================================================================
-- TekstilSite — Faz 2 migrasyonu: stok_hareketleri + iller (81 il)
-- UTF-8 dosya · phpMyAdmin Import  VEYA  mysql --default-character-set=utf8mb4 teksilsite < migrate_faz2.sql
-- ===========================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Stok hareketleri (sipariş/iade/düzeltme logu)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stok_hareketleri (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  urun_id          INT UNSIGNED NOT NULL,
  varyant_id       INT UNSIGNED NULL,
  tip              ENUM('giris','cikis','satis','iade','duzeltme') NOT NULL,
  adet             INT NOT NULL,
  onceki_stok      INT,
  siparis_id       BIGINT UNSIGNED NULL,
  aciklama         VARCHAR(255),
  olusturma_zaman  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_hareket_urun (urun_id),
  KEY idx_hareket_siparis (siparis_id),
  CONSTRAINT fk_hareket_urun FOREIGN KEY (urun_id) REFERENCES urunler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- İller (81 plaka)
-- ---------------------------------------------------------------------------
TRUNCATE TABLE iller;
INSERT INTO iller (plaka, ad) VALUES
  (1,'Adana'),(2,'Adıyaman'),(3,'Afyonkarahisar'),(4,'Ağrı'),(5,'Amasya'),
  (6,'Ankara'),(7,'Antalya'),(8,'Artvin'),(9,'Aydın'),(10,'Balıkesir'),
  (11,'Bilecik'),(12,'Bingöl'),(13,'Bitlis'),(14,'Bolu'),(15,'Burdur'),
  (16,'Bursa'),(17,'Çanakkale'),(18,'Çankırı'),(19,'Çorum'),(20,'Denizli'),
  (21,'Diyarbakır'),(22,'Edirne'),(23,'Elazığ'),(24,'Erzincan'),(25,'Erzurum'),
  (26,'Eskişehir'),(27,'Gaziantep'),(28,'Giresun'),(29,'Gümüşhane'),(30,'Hakkari'),
  (31,'Hatay'),(32,'Isparta'),(33,'Mersin'),(34,'İstanbul'),(35,'İzmir'),
  (36,'Kars'),(37,'Kastamonu'),(38,'Kayseri'),(39,'Kırklareli'),(40,'Kırşehir'),
  (41,'Kocaeli'),(42,'Konya'),(43,'Kütahya'),(44,'Malatya'),(45,'Manisa'),
  (46,'Kahramanmaraş'),(47,'Mardin'),(48,'Muğla'),(49,'Muş'),(50,'Nevşehir'),
  (51,'Niğde'),(52,'Ordu'),(53,'Rize'),(54,'Sakarya'),(55,'Samsun'),
  (56,'Siirt'),(57,'Sinop'),(58,'Sivas'),(59,'Tekirdağ'),(60,'Tokat'),
  (61,'Trabzon'),(62,'Tunceli'),(63,'Şanlıurfa'),(64,'Uşak'),(65,'Van'),
  (66,'Yozgat'),(67,'Zonguldak'),(68,'Aksaray'),(69,'Bayburt'),(70,'Karaman'),
  (71,'Kırıkkale'),(72,'Batman'),(73,'Şırnak'),(74,'Bartın'),(75,'Ardahan'),
  (76,'Iğdır'),(77,'Yalova'),(78,'Karabük'),(79,'Kilis'),(80,'Osmaniye'),(81,'Düzce');

SET FOREIGN_KEY_CHECKS = 1;
