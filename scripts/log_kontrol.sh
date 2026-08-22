#!/bin/sh
# ===========================================================================
# TekstilSite log izleme (Faz E / E3) — CI3 loglarindan ERROR ozeti
#
# Kullanim (cron, gunluk):
#   35 7 * * * /home/KULLANICI/scripts/log_kontrol.sh >> /home/KULLANICI/teksil-cron.log 2>&1
#
# Cikti: gun bazli ERROR adedi + son 10 benzersiz hata satiri.
# CI3 log formati: "ERROR - YYYY-AA-GG HH:MM:SS --> mesaj" (application/logs/)
# Sifir hata durumunda tek satir "OK" basar (gevsek yeniden baslatma/rotasyon
# sonrasi dosya yokluu da OK kabul edilir).
# Uptime izleme DISARIDAN yapilir (bkz. DEPLOY.md adim 7) — bu script yalniz
# uygulama hatalarini ozetler.
# ===========================================================================
SITE_ROOT="${1:-/home/KULLANICI/public_html}"    # varsayilan; istenirse argumanla override
LOG_DIR="$SITE_ROOT/application/logs"
BUGUN=$(date +%Y-%m-%d)

if [ ! -d "$LOG_DIR" ]; then
    echo "$(date '+%F %T') log_kontrol: LOG_DIR yok ($LOG_DIR)"
    exit 1
fi

# LXIII: 45 gunden eski log dosyalarini temizle (birikim otomasyonu).
# Ozet akisindan bagimsiz: hata sifir olsa da temizlik kosar (asagidaki
# erken cikislardan_ONCE calisir).
ESKI=$(find "$LOG_DIR" -name 'log-*.php' -mtime +45 2>/dev/null | wc -l | tr -d ' ')
if [ "$ESKI" -gt 0 ]; then
    find "$LOG_DIR" -name 'log-*.php' -mtime +45 -delete 2>/dev/null
    echo "$(date '+%F %T') log_kontrol: $ESKI adet 45+ gunluk log dosyasi silindi"
fi

# Bugunun dosyasi (yoksa: henuz hata loglanmamis = iyi)
# Not: grep -c essizlesme yokken "0" basar VE exit 1 doner — "|| echo 0"
# yazmak adet'i "0\n0" yapip aritmetigi patlatiyordu (LXIII bug fix).
toplam=0
for f in "$LOG_DIR/log-$BUGUN.php"; do
    [ -f "$f" ] || continue
    adet=$(grep -c 'ERROR -' "$f" 2>/dev/null)
    [ -n "$adet" ] || adet=0
    toplam=$((toplam + adet))
done

if [ "$toplam" -eq 0 ]; then
    echo "$(date '+%F %T') log_kontrol: OK — bugun ($BUGUN) 0 ERROR"
    exit 0
fi

echo "$(date '+%F %T') log_kontrol: DIKKAT — $BUGUN icin $toplam ERROR:"
grep -h 'ERROR -' "$LOG_DIR/log-$BUGUN.php" 2>/dev/null \
    | sed 's/^ERROR - [0-9-]* [0-9:]* --> //' \
    | sort | uniq -c | sort -rn | head -10
exit 0
