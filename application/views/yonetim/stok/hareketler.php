<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Stok Hareketleri</h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/stok') ?>">← Stok Listesi</a>
</div>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/stok/hareketler') ?>">
    <select name="tip">
        <option value=""         <?= $tip === ''         ? 'selected' : '' ?>>Tüm tipler</option>
        <option value="giris"    <?= $tip === 'giris'    ? 'selected' : '' ?>>Giriş</option>
        <option value="cikis"    <?= $tip === 'cikis'    ? 'selected' : '' ?>>Çıkış</option>
        <option value="satis"    <?= $tip === 'satis'    ? 'selected' : '' ?>>Satış</option>
        <option value="iade"     <?= $tip === 'iade'     ? 'selected' : '' ?>>İade</option>
        <option value="duzeltme" <?= $tip === 'duzeltme' ? 'selected' : '' ?>>Düzeltme</option>
    </select>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ürün / açıklama…">
    <button type="submit" class="btn btn-secondary">Filtrele</button>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Tip</th><th>Ürün</th><th>Varyant</th><th class="sag">Adet</th><th class="sag">Önceki</th><th>Açıklama</th><th>Tarih</th></tr></thead>
        <tbody>
        <?php if (empty($hareketler)): ?><tr><td colspan="7" class="adm-bosluk">Hareket yok</td></tr><?php endif; ?>
        <?php
            $tip_renk = array(
                'giris' => 'yesil', 'satis' => 'mavi', 'iade' => 'sari',
                'cikis' => 'kirmizi', 'duzeltme' => 'gri',
            );
        ?>
        <?php foreach ($hareketler as $h): ?>
            <tr>
                <td><span class="rozet rozet-<?= e($tip_renk[$h->tip] ?? 'gri') ?>"><?= e($h->tip) ?></span></td>
                <td><?= e($h->urun_adi ?: '-') ?></td>
                <td><?= e(trim(((string) ($h->renk ?? '')) . ' ' . ((string) ($h->beden ?? '')))) ?: '-' ?></td>
                <td class="sag"><?= (($h->adet ?? 0) > 0 ? '+' : '') . (int) ($h->adet ?? 0) ?></td>
                <td class="sag"><?= (int) ($h->onceki_stok ?? 0) ?></td>
                <td><?= e($h->aciklama ?: '-') ?></td>
                <td><small><?= e(date('d.m.Y H:i', strtotime($h->olusturma_zaman))) ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($sayfa_sayisi > 1): ?>
<nav class="adm-sayfalama">
    <?php for ($i = 1; $i <= $sayfa_sayisi; $i++): $qs = $_GET; unset($qs['sayfa']); $qs['sayfa'] = $i; $u = site_url('yonetim/stok/hareketler') . '?' . http_build_query($qs); ?>
        <?php if ($i == $sayfa): ?><span class="aktif"><?= $i ?></span><?php else: ?><a href="<?= e($u) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
