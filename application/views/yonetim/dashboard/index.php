<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<?php $_donemler = array('bugun' => 'Bugün', 'hafta' => 'Bu Hafta', 'ay' => 'Bu Ay', 'yil' => 'Bu Yıl', 'tumu' => 'Tümü'); ?>
<div class="adm-donem">
    <span class="adm-donem__lbl">Dönem</span>
    <?php foreach ($_donemler as $k => $lbl): ?>
        <a class="adm-donem__btn<?= isset($donem_kod) && $donem_kod === $k ? ' is-aktif' : '' ?>" href="<?= site_url('yonetim/dashboard?donem=' . $k) ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
</div>

<div class="adm-stats">
    <div class="adm-stat"><div class="adm-stat-etiket">Sipariş (<?= e($donem) ?>)</div><div class="adm-stat-sayi"><?= (int) $ozet['siparis'] ?></div><div class="adm-stat-alt"><?= (int) $ozet['bekleyen'] ?> onay bekliyor</div></div>
    <div class="adm-stat"><div class="adm-stat-etiket">Aktif Bayi</div><div class="adm-stat-sayi"><?= (int) $ozet['bayi'] ?></div><div class="adm-stat-alt"><?= (int) $ozet['bekleyen_bayi'] ?> onay bekliyor · anlık</div></div>
    <div class="adm-stat"><div class="adm-stat-etiket">Aktif Ürün</div><div class="adm-stat-sayi"><?= (int) $ozet['urun'] ?></div><div class="adm-stat-alt">katalog · anlık</div></div>
    <div class="adm-stat"><div class="adm-stat-etiket">Ciro (<?= e($donem) ?>)</div><div class="adm-stat-sayi"><?= para_tr($ozet['ciro']) ?></div><div class="adm-stat-alt">tamamlanan tutar</div></div>
</div>

<?php
// Chart verilerini PHP'de hazirla (durum_etiket ile Turkce etiketler).
$trend_e = array(); $trend_a = array(); $trend_t = array();
foreach ((array) $trend as $r){ $trend_e[] = isset($r->etiket) ? $r->etiket : ''; $trend_a[] = (int) $r->adet; $trend_t[] = (float) $r->tutar; }
$durum_e = array(); $durum_a = array();
foreach ((array) $durum as $r){ $de = durum_etiket($r->durum); $durum_e[] = $de[0]; $durum_a[] = (int) $r->adet; }
$satan_e = array(); $satan_a = array();
foreach ((array) $cok_satan as $r){ $satan_e[] = mb_substr((string) $r->ad, 0, 24, 'UTF-8'); $satan_a[] = (int) $r->satis_adet; }
$kat_e = array(); $kat_a = array();
foreach ((array) $kategori as $r){ $kat_e[] = (string) $r->kategori; $kat_a[] = (int) $r->adet; }
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="adm-charts">
    <div class="adm-card adm-chart-full">
        <div class="adm-card-baslik"><h3>Sipariş Trendi (<?= e($donem) ?>)</h3></div>
        <div class="adm-chart"><canvas id="cvTrend"></canvas></div>
    </div>
    <div class="adm-card">
        <div class="adm-card-baslik"><h3>Sipariş Durumu (<?= e($donem) ?>)</h3></div>
        <div class="adm-chart"><canvas id="cvDurum"></canvas></div>
    </div>
    <div class="adm-card">
        <div class="adm-card-baslik"><h3>En Çok Satanlar</h3></div>
        <div class="adm-chart"><canvas id="cvSatan"></canvas></div>
    </div>
    <div class="adm-card">
        <div class="adm-card-baslik"><h3>Kategori Dağılımı</h3></div>
        <div class="adm-chart"><canvas id="cvKat"></canvas></div>
    </div>
</div>

<script>
(function () {
    if (typeof Chart === 'undefined') { return; }
    Chart.defaults.font.family = "'Figtree',sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#5c6c7a';
    var YESIL = '#00ed64', TEAL = '#001e2b', MOR = '#7b3ff2', TURUNCU = '#fa6e39';
    var renkler = [YESIL, TEAL, MOR, TURUNCU, '#2563eb', '#00684a', '#9a6a00', '#1f9d55'];
    var gridRenk = '#eef1f4';

    new Chart(document.getElementById('cvTrend'), {
        type: 'bar',
        data: { labels: <?php echo json_encode($trend_e); ?>, datasets: [
            { type: 'bar',  label: 'Sipariş', data: <?php echo json_encode($trend_a); ?>, backgroundColor: YESIL, borderRadius: 6, maxBarThickness: 28, yAxisID: 'y' },
            { type: 'line', label: 'Tutar (₺)', data: <?php echo json_encode($trend_t); ?>, borderColor: TEAL, backgroundColor: TEAL, tension: .35, borderWidth: 2, pointRadius: 3, yAxisID: 'y1' }
        ]},
        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Adet' } },
                      y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: '₺' } },
                      x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('cvDurum'), {
        type: 'doughnut',
        data: { labels: <?php echo json_encode($durum_e); ?>, datasets: [{ data: <?php echo json_encode($durum_a); ?>, backgroundColor: renkler, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
    });

    new Chart(document.getElementById('cvSatan'), {
        type: 'bar',
        data: { labels: <?php echo json_encode($satan_e); ?>, datasets: [{ label: 'Satış adedi', data: <?php echo json_encode($satan_a); ?>, backgroundColor: MOR, borderRadius: 6, maxBarThickness: 22 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { color: gridRenk } }, y: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('cvKat'), {
        type: 'bar',
        data: { labels: <?php echo json_encode($kat_e); ?>, datasets: [{ label: 'Ürün adedi', data: <?php echo json_encode($kat_a); ?>, backgroundColor: TURUNCU, borderRadius: 6, maxBarThickness: 28 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: gridRenk } }, x: { grid: { display: false } } } }
    });
})();
</script>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Son Siparişler (<?= e($donem) ?>)</h3><a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/siparisler') ?>">Tümü →</a></div>
        <?php if (! empty($son_siparisler)): ?>
        <div class="adm-tbl-sar">
            <table class="adm-tbl">
                <thead><tr><th>Sipariş</th><th>Bayi</th><th>Durum</th><th class="sag">Toplam</th></tr></thead>
                <tbody>
                <?php foreach ($son_siparisler as $s): $de = durum_etiket($s->durum); ?>
                    <tr>
                        <td><a class="b" href="<?= site_url('yonetim/siparisler/detay/' . $s->id) ?>"><?= e($s->siparis_no) ?></a><br><small><?= e(date('d.m.Y', strtotime($s->olusturma_zaman))) ?></small></td>
                        <td><?= e($s->firma_adi ?: ($s->yetkili_ad_soyad ?: 'Misafir')) ?></td>
                        <td><span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></td>
                        <td class="sag"><?= para_tr($s->toplam) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?><div class="adm-bosluk">Bu dönemde sipariş yok</div><?php endif; ?>
    </div>

    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Onay Bekleyen Bayiler</h3></div>
            <?php if (! empty($bekleyen_bayiler)): ?>
                <?php foreach ($bekleyen_bayiler as $b): ?>
                    <div class="adm-kv"><span><?= e($b->firma_adi) ?><br><small><?= e($b->email) ?></small></span><a class="btn btn-sm btn-primary" href="<?= site_url('yonetim/bayiler/detay/' . $b->id) ?>">İncele</a></div>
                <?php endforeach; ?>
            <?php else: ?><div class="adm-bosluk" style="padding:18px">Onay bekleyen yok 🎉</div><?php endif; ?>
        </div>
        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Kritik Stok (≤15)</h3></div>
            <?php if (! empty($kritik_stok)): ?>
                <?php foreach ($kritik_stok as $k): ?>
                    <div class="adm-kv"><span><?= e($k->ad) ?> <small><?= e(trim(($k->renk ?: '') . ' ' . ($k->beden ?: ''))) ?></small></span><b class="rozet rozet-kirmizi"><?= (int) $k->stok ?></b></div>
                <?php endforeach; ?>
            <?php else: ?><div class="adm-bosluk" style="padding:18px">Kritik stok yok</div><?php endif; ?>
        </div>
    </div>
</div>
