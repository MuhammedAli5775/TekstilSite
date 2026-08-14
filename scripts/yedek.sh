#!/bin/sh
# ===========================================================================
# TekstilSite gecelik yedegi (Faz B / B8) — DB dumplu + uploads arsivi
#
# Kullanim (kuronca bir kez usteki 4 degiskeni doldur, sonra cron):
#   17 3 * * * /home/KULLANICI/scripts/yedek.sh >> /home/KULLANICI/teksil-yedek.log 2>&1
#
# Cikti: $YEDEK_DIR/db-YYYYAA-GG-HHMMSS.sql.gz + uploads-YYYYAA-GG.tar.gz
# Rotasyon: SAKLA_GUN gunden eski yedekler silinir.
# Uzak kopya istenirse RSCMD satirini ac (rsync ornegi yorumda).
# POSIX sh uyumlu (bash sart degil).
# ===========================================================================
set -u

DB_USER="teksil_app"
DB_PASS="SERT_PAROLA_BURAYA"
DB_NAME="teksilsite"
SITE_ROOT="/home/KULLANICI/public_html"    # index.php'nin bulundugu dizin

YEDEK_DIR="$SITE_ROOT/../yedekler"         # document root DISINDA (web'e kapali)
SAKLA_GUN=14
Tarih=$(date +%Y%m%d-%H%M%S)

mkdir -p "$YEDEK_DIR" || { echo "HATA: $YEDEK_DIR olusturulamadi"; exit 1; }

# --- DB (InnoDB guvenli: kilitlemeden tutarli dolum) ---
if mysqldump --single-transaction --quick --routines \
      -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$YEDEK_DIR/db-$Tarih.sql.gz"; then
    echo "$(date '+%F %T') OK  db-$Tarih.sql.gz ($(du -h "$YEDEK_DIR/db-$Tarih.sql.gz" | cut -f1))"
else
    echo "$(date '+%F %T') HATA: mysqldump basarisiz (parola/kullanici kontrol et)"
    [ -s "$YEDEK_DIR/db-$Tarih.sql.gz" ] || rm -f "$YEDEK_DIR/db-$Tarih.sql.gz"
    exit 1
fi

# --- uploads (banner gorselleri) ---
if tar -czf "$YEDEK_DIR/uploads-$Tarih.tar.gz" -C "$SITE_ROOT" uploads 2>/dev/null; then
    echo "$(date '+%F %T') OK  uploads-$Tarih.tar.gz"
else
    echo "$(date '+%F %T') UYARI: uploads arsivi basarisiz (dizin var mi?)"
fi

# --- Rotasyon ---
find "$YEDEK_DIR" -name 'db-*.sql.gz'      -mtime +$SAKLA_GUN -delete
find "$YEDEK_DIR" -name 'uploads-*.tar.gz' -mtime +$SAKLA_GUN -delete

# --- Uzak kopya (opsiyonel; ac ve kendi hedefini yaz) ---
# RSCMD="rsync -a --delete $YEDEK_DIR/ yedeksunucu::teksil/"
# $RSCMD || echo "UYARI: uzak kopya basarisiz"

exit 0
