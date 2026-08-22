# CLAUDE.md — TekstilSite oturum başlangıç kısayolu

CodeIgniter 3 B2B tekstil mağazası (PHP 8.1 + MySQL/MariaDB). **Bu dosya otorite
değildir** — oturuma hızlı başlangıç için işaretçi + komut kopya kağıdıdır.
Gerçek rehberler:

- `workflow.md` — yol haritası + doğrulama kuralları (§2: php-l / rota / DB-akış-testi / mojibake)
- `DEGISIKLIK.md` — değişiklik günlüğü; yeni kayıt **en üste**, kayıt kuralı dosya başlığında
- `TAMAMLAMA_PLANI.md` — lansmana tamamlama planı; başlıktaki **Durum** satırı güncel özettir
- `DEPLOY.md` — prod runbook; **§3 DB kurulum sırasının otoritesi orasıdır**

## Kaldığın yerden devam etme

1. `git log --oneline -5` + `git status` — ağaç temiz mi, en son hangi iş bitti
2. `TAMAMLAMA_PLANI.md` başlıktaki Durum satırı — hangi faz/fail kaldı
3. `DEGISIKLIK.md` en üst girdi — son işin özeti ve "[!] Canlıya taşı" listesi

## Komutlar (Windows / XAMPP)

- PHP PATH'te yok: `C:/xampp/php/php.exe` · mysql CLI: `C:/xampp/mysql/bin/mysql.exe`
- Dev sunucu: `npm run dev` → http://localhost:8000 (`scripts/dev.js`, php -S + router.php)
- Tam regresyon (sunucu açıkken): `C:/xampp/php/php.exe tests/regresyon.php` — 185 test,
  localhost dışı hedefte `--force` guard'ı var
- Sıfır-DB provası: scratch DB'ye DEPLOY.md §3 (22 dosya, SIRASI ŞART) uygula →
  `npm run dev:testing` (CI_ENV=testing → `teksilsite_rehearsal`) →
  `REGRESYON_DB=teksilsite_rehearsal C:/xampp/php/php.exe tests/regresyon.php`
- Dev DB: `127.0.0.1` / root / mysql1234 / `teksilsite`

## Bir kez öğrenilen dersler (tekrar etme)

- Arka plan sunucu öldürmeleri **php.exe'yi yetim bırakır**: durdurduktan sonra
  `netstat -ano | grep :8000` → kalan PID'yi `taskkill //F //PID` ile öldür
- **Perf indeksleri yalnız `sql/migrate_perf_index.sql`'de yaşar** — schema.sql'e
  eklemek taze §3 kurulumunda duplicate-key düşürür (XIX'te provada yakalandı)
- `seed.sql` truncate içerir: `migrate_yetkiler` seed'den SONRA, footer/slider
  seed'leri EN SONDA (sıra karışırsa veri ezilir)
- Windows npm→cmd→php zinciri CI_ENV'yi yutuyor → `dev.js` php'yi shell'siz spawn
  eder; `dev:testing` böyle çalışır
- Her değişiklik DEGISIKLIK.md'e kaydolur (dosya + DB + doğrulama); commit
  mesajı kısa Türkçe + test sayısı (örn. "… — 112/112")

## Durum özeti (2026-08-22)

Zorunlu dev işi **TAMAM** (D0/C1–C5/C7/E1–E4) + 2026-08-20/21/22 cila paketleri
tamam: marka "Nesem Tesettür" (XLVIII), ödeme validasyonu (XLIX), oturum
döndürme düzeltmesi (L), footer zenginleştirme (LI), favicon+OG/Twitter kartı
(LII), yönetici sipariş bildirimi (LIII), e-bülten (LV), kupon atıflaması +
raporlar + markalı 404 (LVI), şifre kurtarma panelleri + çerez bandı + onaylı
GA/FB piksel (LVII), Apache yüzey kapatma (LVIII), giriş brute-force IP kilidi
+ şifremi-unuttum self-service (LIX). Regresyon **321/321** (dev) + sıfır-DB
provası **321/321** (2026-08-22, §3 22 dosya — giris_denemeleri +
sifre_sifirlama provayla kanıtlı).
Çoklu dil + blog + SEO önceki fazlarda ✓. Kalan engelleyiciler dev dışı:
**İş** Faz A kimlikleri (`FAZ_A_REHBERI.md` — SMTP gelince şifremi-unuttum
e-postası + e-bülten gönderim + terk-sepet hatırlatma devreye alınacak; LIX
akışı SMTP'siz de çalışır, yalnız e-posta atlanır),
**Ops** Faz B hosting (`DEPLOY.md`), hukuki metin kararı (E4). Bilinçli
ertelenmişler: D1 pazaryeri adapter'ları, C6 canlı Trendyol testi.
Güncel ayrıntı: TAMAMLAMA_PLANI.md.
