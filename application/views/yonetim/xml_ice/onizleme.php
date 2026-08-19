<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s = $res['sayaclar'];
?>
<div class="sayfa-baslik"><h2>XML Önizleme: <?= e($k->ad) ?></h2></div>

<?php if ($res['ok']): ?>
<div class="adm-uyari adm-uyari--ok">Kuru koşu (hiçbir şey yazılmadı): <?= e($res['mesaj']) ?></div>
<?php else: ?>
<div class="adm-uyari adm-uyari--hata"><?= e($res['mesaj']) ?></div>
<?php endif; ?>

<div class="adm-card adm-card--p0">
    <div class="adm-card-baslik"><h3>Sayaçlar</h3></div>
    <div style="padding:12px;display:flex;gap:8px;flex-wrap:wrap">
        <small class="rozet rozet-gri">XML'de: <?= (int) $s['urun_sayisi'] ?></small>
        <small class="rozet rozet-yesil">Yeni: <?= (int) $s['yeni'] ?></small>
        <small class="rozet rozet-gri">Güncellenecek: <?= (int) $s['guncellenen'] ?></small>
        <small class="rozet rozet-gri">Atlanacak: <?= (int) $s['atlanan'] ?></small>
        <small class="rozet rozet-gri">Varyant +: <?= (int) $s['varyant_eklenen'] ?></small>
        <small class="rozet rozet-gri">Varyant ~: <?= (int) $s['varyant_guncellenen'] ?></small>
        <?php if ((float) $k->fiyat_carpani != 1.0): ?><small class="rozet rozet-gri">fiyat × <?= e($k->fiyat_carpani) ?></small><?php endif; ?>
    </div>
</div>

<?php if (! empty($res['notlar'])): ?>
<div class="adm-card adm-card--p0" style="margin-top:12px">
    <div class="adm-card-baslik"><h3>Notlar</h3></div>
    <div style="padding:8px;font-size:12px;color:var(--slate)">
        <?php foreach ($res['notlar'] as $n): ?><div style="padding:4px 10px">• <?= e($n) ?></div><?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="adm-card adm-card--p0" style="margin-top:12px">
    <div class="adm-card-baslik"><h3>Satırlar (ilk <?= count($res['satirlar']) ?><?= count($res['satirlar']) >= 100 ? '+' : '' ?>)</h3></div>
    <?php if (empty($res['satirlar'])): ?>
        <div class="adm-bosluk">Görüntülenecek satır yok.</div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="adm-tablo" style="width:100%;font-size:13px">
            <thead><tr><th>Stok Kodu</th><th>Ad</th><th>Eylem</th><th>Fiyat</th><th>Kategori</th><th>Not</th></tr></thead>
            <tbody>
            <?php foreach ($res['satirlar'] as $r): ?>
                <tr>
                    <td><?= e($r['stok_kodu']) ?></td>
                    <td><?= e($r['ad']) ?></td>
                    <td><?php if ($r['eylem'] === 'yeni'): ?><small class="rozet rozet-yesil">yeni</small><?php elseif ($r['eylem'] === 'guncelle'): ?><small class="rozet rozet-gri">güncelle</small><?php else: ?><small class="rozet rozet-gri" style="opacity:.7">atla</small><?php endif; ?></td>
                    <td><?= e(number_format((float) $r['fiyat'], 2, ',', '.')) ?> ₺</td>
                    <td><?= e($r['kategori']) ?></td>
                    <td style="color:var(--muted)"><?= e($r['not']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top:14px;display:flex;gap:10px">
    <?php if ($res['ok'] && (int) $s['urun_sayisi'] > 0): ?>
    <form action="<?= site_url('yonetim/xml_ice/calistir/' . (int) $k->id) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="xml_metin" value="<?= e($xml_metin) ?>">
        <button class="btn btn-primary" onclick="return confirm('Gerçek içe aktarılsın mı? Ürünler yazılacak.')">Gerçek İçe Aktar</button>
    </form>
    <?php endif; ?>
    <a class="btn btn-ghost" href="<?= site_url('yonetim/xml_ice') ?>">Geri</a>
    <a class="btn btn-ghost" href="<?= site_url('yonetim/xml_ice/log/' . (int) $k->id) ?>">Log</a>
</div>
