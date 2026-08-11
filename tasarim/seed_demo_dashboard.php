<?php
/**
 * Demo dashboard veri tohumu — grafiklerin görünür olması için.
 * Süreç: son 14 güne yayılan ~28 sipariş + detay. Stoğa DOKUNMAZ (varyant_id=NULL,
 * stok_hareketleri YOK) → envanteri bozmaz. siparis_no='DEMO-' önekiyle temizlenebilir.
 *
 * Temizlik:
 *   DELETE FROM siparis_detaylari WHERE siparis_id IN (SELECT id FROM siparisler WHERE siparis_no LIKE 'DEMO-%');
 *   DELETE FROM siparis_durum_gecmisi WHERE siparis_id IN (SELECT id FROM siparisler WHERE siparis_no LIKE 'DEMO-%');
 *   DELETE FROM siparisler WHERE siparis_no LIKE 'DEMO-%';
 */
$m = new mysqli('127.0.0.1', 'root', 'mysql1234', 'teksilsite');
$m->set_charset('utf8mb4');
if ($m->connect_errno) { die("DB yok\n"); }

// --- önce eski demo temizliği ---
$del = $m->query("SELECT id FROM siparisler WHERE siparis_no LIKE 'DEMO-%'");
$ids = array();
while ($r = $del->fetch_assoc()) { $ids[] = (int) $r['id']; }
if ($ids) {
    $in = implode(',', $ids);
    $m->query("DELETE FROM siparis_detaylari WHERE siparis_id IN ($in)");
    $m->query("DELETE FROM siparis_durum_gecmisi WHERE siparis_id IN ($in)");
    $m->query("DELETE FROM siparisler WHERE id IN ($in)");
    echo "eski demo temizlendi: " . count($ids) . " sipariş\n";
}

// --- aktif ürünleri çek ---
$urunler = array();
$r = $m->query("SELECT id, ad, fiyat FROM urunler WHERE durum=1 AND deleted_at IS NULL ORDER BY id LIMIT 8");
while ($u = $r->fetch_assoc()) { $urunler[] = $u; }
if (! $urunler) { die("aktif ürün yok — seed durduruldu\n"); }
echo "ürün havuzu: " . count($urunler) . "\n";

// --- durum dağılımı (gerçekçi B2B ağırlıkları) ---
$durumHavuz = array(
    array('teslim_edildi', 'odendi'), array('teslim_edildi', 'odendi'), array('teslim_edildi', 'odendi'),
    array('teslim_edildi', 'odendi'), array('onaylandi', 'odendi'), array('onaylandi', 'odendi'),
    array('kargolandi', 'odendi'), array('kargolandi', 'odendi'),
    array('hazirlaniyor', 'odendi'),
    array('onay_bekliyor', 'bekliyor'), array('onay_bekliyor', 'bekliyor'),
    array('iptal', 'iade'), array('iptal', 'bekliyor'),
);
$gecerli = array('onaylandi', 'hazirlaniyor', 'kargolandi', 'teslim_edildi');

$sayac = 0;
$simdi = time();
// her gün için 1-3 sipariş (bazı günler 0) — 14 gün
for ($gunGeri = 13; $gunGeri >= 0; $gunGeri--) {
    $gunAdet = ($gunGeri === 0) ? rand(1, 2) : rand(0, 3); // bugün biraz olsun
    for ($j = 0; $j < $gunAdet; $j++) {
        $saat = rand(9, 19); $dk = rand(0, 59);
        $zaman = date('Y-m-d H:i:s', mktime($saat, $dk, 0, date('n'), date('j') - $gunGeri, date('Y')));
        list($durum, $odeme) = $durumHavuz[array_rand($durumHavuz)];

        // 1-2 kalem
        $kalemSay = rand(1, 2);
        $ara = 0.0; $detaylar = array();
        for ($k = 0; $k < $kalemSay; $k++) {
            $u = $urunler[array_rand($urunler)];
            $adet = 6 * rand(1, 3); // MOQ=6 katları
            $birim = (float) $u['fiyat'];
            $satirAra = round($birim * $adet, 2);
            $ara += $satirAra;
            $detaylar[] = array('urun_id' => (int) $u['id'], 'urun_adi' => $u['ad'], 'birim' => $birim, 'adet' => $adet, 'ara' => $satirAra);
        }
        $kargo = ($ara >= 2000) ? 0.0 : 79.90;
        $toplam = round($ara + $kargo, 2);

        $no = 'DEMO-' . date('ymd', strtotime($zaman)) . strtoupper(substr(md5($zaman . $sayac), 0, 4));
        $st = $m->prepare("INSERT INTO siparisler
            (siparis_no,bayi_id,para_birimi,kur,ara_toplam,indirim,islem_ucreti,kargo_ucreti,toplam,
             odeme_yontemi,odeme_durumu,durum,teslimat_ad,teslimat_adres,teslimat_il,email,firma_adi,olusturma_zaman)
            VALUES (?,1,'TRY',1,?,0,0,?,?, 'Havale/EFT',?, ?, 'Demo Bayi','Demo Adres','İstanbul','demo@teksilsite.test','Demo Tekstil Ltd.',?)");
        $st->bind_param('sddssss', $no, $ara, $kargo, $toplam, $odeme, $durum, $zaman);
        $st->execute();
        $sid = $m->insert_id;

        foreach ($detaylar as $d) {
            $dst = $m->prepare("INSERT INTO siparis_detaylari
                (siparis_id,urun_id,varyant_id,urun_adi,birim_fiyat,adet,ara_toplam,kdv)
                VALUES (?,?,NULL,?,?,?,?)");
            $kdv = 20;
            $dst->bind_param('iisddidi', $sid, $d['urun_id'], $d['urun_adi'], $d['birim'], $d['adet'], $d['ara'], $kdv);
            $dst->execute();
        }
        // durum geçmişi
        $gst = $m->prepare("INSERT INTO siparis_durum_gecmisi (siparis_id,durum,taraf,notu) VALUES (?,?,'admin','demo')");
        $gst->bind_param('is', $sid, $durum);
        $gst->execute();
        $sayac++;
    }
}
echo "eklendi: $sayac demo sipariş\n";

// --- özet ---
$o = $m->query("SELECT COUNT(*) n, COALESCE(SUM(toplam),0) t FROM siparisler")->fetch_assoc();
$g = $m->query("SELECT COUNT(*) n FROM siparisler WHERE durum IN ('onaylandi','hazirlaniyor','kargolandi','teslim_edildi')")->fetch_assoc();
echo "toplam sipariş: {$o['n']} | geçerli: {$g['n']} | toplam tutar: {$o['t']} ₺\n";
$d = $m->query("SELECT durum, COUNT(*) n FROM siparisler GROUP BY durum");
while ($r = $d->fetch_assoc()) { echo "  {$r['durum']}: {$r['n']}\n"; }
