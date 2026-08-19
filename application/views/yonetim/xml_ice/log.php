<?php defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="sayfa-baslik"><h2>XML Log: <?= e($k->ad) ?></h2></div>

<div class="adm-card adm-card--p0">
    <div class="adm-card-baslik"><h3>Koşular (son <?= count($loglar) ?>)</h3></div>
    <?php if (empty($loglar)): ?>
        <div class="adm-bosluk">Henüz koşu yok.</div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="adm-tablo" style="width:100%;font-size:13px">
            <thead><tr><th>Zaman</th><th>Kip</th><th>Durum</th><th>Yeni</th><th>Günc.</th><th>Atla</th><th>Varyant +/~</th><th>Süre</th><th>Özet</th></tr></thead>
            <tbody>
            <?php foreach ($loglar as $l): ?>
                <tr>
                    <td><?= e(date('d.m.Y H:i:s', strtotime($l->zaman))) ?></td>
                    <td><?= $l->kip === 'gercek' ? 'gerçek' : 'önizleme' ?></td>
                    <td><?= $l->durum === 'basarili' ? '<small class="rozet rozet-yesil">ok</small>' : '<small class="rozet rozet-gri" style="color:var(--danger)">hata</small>' ?></td>
                    <td><?= (int) $l->yeni ?></td>
                    <td><?= (int) $l->guncellenen ?></td>
                    <td><?= (int) $l->atlanan ?></td>
                    <td><?= (int) $l->varyant_eklenen ?> / <?= (int) $l->varyant_guncellenen ?></td>
                    <td><?= e(number_format((float) $l->sure_sn, 2, ',', '.')) ?> sn</td>
                    <td style="max-width:340px"><?= e($l->ozet) ?><?php if ($l->hata_mesaji): ?><br><small style="color:var(--danger)"><?= e($l->hata_mesaji) ?></small><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top:14px;display:flex;gap:10px">
    <a class="btn btn-ghost" href="<?= site_url('yonetim/xml_ice') ?>">Geri</a>
    <a class="btn btn-ghost" href="<?= site_url('yonetim/xml_ice/onizleme/' . (int) $k->id) ?>">Önizle (URL'den)</a>
</div>
