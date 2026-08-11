<?php
// Bayi USD sipariş tutarlılık testi için DB yardımcı (setup/verify/restore).
// PHP dosyası → bcrypt hash quoting sorunu yok.
$m = new mysqli('127.0.0.1', 'root', 'mysql1234', 'teksilsite');
$m->set_charset('utf8mb4');
$mode = $argv[1] ?? '';

if ($mode === 'setup') {
    $r = $m->query("SELECT sifre, para_birimi FROM bayiler WHERE id=1")->fetch_assoc();
    file_put_contents('/tmp/bayi_orig.txt', $r['sifre'] . "\t" . $r['para_birimi']);
    $h = password_hash('TestFix1!', PASSWORD_BCRYPT);
    $st = $m->prepare("UPDATE bayiler SET sifre=?, para_birimi='USD' WHERE id=1");
    $st->bind_param('s', $h); $st->execute();
    echo "setup: bayi1 -> USD + temp pw (orijinal /tmp/bayi_orig.txt'e kaydedildi)\n";
}
elseif ($mode === 'verify') {
    $oid = (int) $argv[2];
    $s = $m->query("SELECT ara_toplam,islem_ucreti,kargo_ucreti,indirim,toplam,para_birimi,kur FROM siparisler WHERE id=$oid")->fetch_assoc();
    if (! $s) { echo "siparis bulunamadi\n"; exit; }
    $q = $m->query("SELECT birim_fiyat,adet,ara_toplam FROM siparis_detaylari WHERE siparis_id=$oid");
    $sum = 0.0; $sf = 0.0; $n = 0;
    while ($d = $q->fetch_assoc()) {
        $sum += (float) $d['ara_toplam'];
        $sf = max($sf, abs((float) $d['ara_toplam'] - (float) $d['birim_fiyat'] * (int) $d['adet']));
        $n++;
    }
    $sum = round($sum, 2);
    $ara_ok = abs((float) $s['ara_toplam'] - $sum) < 0.001;
    $tc = round((float) $s['ara_toplam'] + (float) $s['islem_ucreti'] + (float) $s['kargo_ucreti'] - (float) $s['indirim'], 2);
    $toplam_ok = abs((float) $s['toplam'] - $tc) < 0.001;
    echo "pb=$s[para_birimi] kur=$s[kur] | ara=$s[ara_toplam] islem=$s[islem_ucreti] kargo=$s[kargo_ucreti] indirim=$s[indirim] toplam=$s[toplam] | $n satir sum=$sum\n";
    echo "ara==SumSatir: " . ($ara_ok ? 'EVET' : 'HAYIR') . " | toplam==bilesen($tc): " . ($toplam_ok ? 'EVET' : 'HAYIR') . " | satir ara==birimxadet(max $sf): " . ($sf < 0.001 ? 'EVET' : 'HAYIR') . "\n";
    echo ($ara_ok && $toplam_ok && $sf < 0.001) ? "TUTARLILIK: TAM ✓\n" : "TUTARLILIK: BASARISIZ ✗\n";
}
elseif ($mode === 'restore') {
    $oid = (int) $argv[2];
    $m->query("DELETE FROM stok_hareketleri WHERE siparis_id=$oid");
    $m->query("DELETE FROM siparis_detaylari WHERE siparis_id=$oid");
    $m->query("DELETE FROM siparis_durum_gecmisi WHERE siparis_id=$oid");
    $m->query("DELETE FROM siparisler WHERE id=$oid");
    $m->query("UPDATE urun_varyantlari SET stok=248 WHERE id=1");
    $m->query("UPDATE urun_varyantlari SET stok=234 WHERE id=9");
    list($hash, $pb) = explode("\t", trim(file_get_contents('/tmp/bayi_orig.txt')));
    $st = $m->prepare("UPDATE bayiler SET sifre=?, para_birimi=? WHERE id=1");
    $st->bind_param('ss', $hash, $pb); $st->execute();
    @unlink('/tmp/bayi_orig.txt');
    $ck = $m->query("SELECT (SELECT COUNT(*) FROM siparisler WHERE id=$oid) AS s, (SELECT para_birimi FROM bayiler WHERE id=1) AS pb, (SELECT stok FROM urun_varyantlari WHERE id=1) AS v1, (SELECT stok FROM urun_varyantlari WHERE id=9) AS v9")->fetch_assoc();
    echo "restore: siparis_kaldi=$ck[s] bayi_pb=$ck[pb] (TRY olmali) v1=$ck[v1] (248) v9=$ck[v9] (234)\n";
}
