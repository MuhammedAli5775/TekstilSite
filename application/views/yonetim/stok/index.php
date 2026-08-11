<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Stok Yönetimi</h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/stok/hareketler') ?>">Hareket Geçmişi →</a>
</div>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/stok') ?>">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ürün adı / SKU / renk / beden…">
    <select name="filtre">
        <option value="all"    <?= $filtre === 'all'    ? 'selected' : '' ?>>Tümü</option>
        <option value="kritik" <?= $filtre === 'kritik' ? 'selected' : '' ?>>Kritik (≤ kritik stok)</option>
        <option value="sifir"  <?= $filtre === 'sifir'  ? 'selected' : '' ?>>Sıfır stok</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filtrele</button>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Ürün</th><th>Varyant</th><th>SKU</th><th class="sag">Stok</th><th class="sag">Kritik</th><th>Stok Düzelt</th></tr></thead>
        <tbody>
        <?php if (empty($varyantlar)): ?><tr><td colspan="6" class="adm-bosluk">Varyant bulunamadı</td></tr><?php endif; ?>
        <?php foreach ($varyantlar as $v): ?>
            <?php
                $kritik = ($v->stok <= 0) ? 'kirmizi' : (($v->kritik_stok > 0 && $v->stok <= $v->kritik_stok) ? 'sari' : '');
            ?>
            <tr>
                <td><?= e($v->ad) ?></td>
                <td><?= e(trim(((string) ($v->renk ?? '')) . ' ' . ((string) ($v->beden ?? '')))) ?: '-' ?></td>
                <td><small class="mono"><?= e($v->sku ?: '-') ?></small></td>
                <td class="sag"><?= $kritik ? '<span class="rozet rozet-' . e($kritik) . '">' . (int) $v->stok . '</span>' : '<b>' . (int) $v->stok . '</b>' ?></td>
                <td class="sag"><?= (int) $v->kritik_stok ?: '-' ?></td>
                <td>
                    <form method="post" action="<?= site_url('yonetim/stok/duzeltle/' . $v->id) ?>" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                        <?= csrf_field() ?>
                        <input type="number" name="yeni_stok" value="<?= (int) $v->stok ?>" min="0" style="width:72px;height:34px;padding:0 8px;border:1px solid var(--hairline-strong);border-radius:var(--r-md)">
                        <input type="text" name="sebep" placeholder="sebep (sayım/devir)" style="width:140px;height:34px;padding:0 10px;border:1px solid var(--hairline-strong);border-radius:var(--r-md);font-size:13px">
                        <button type="submit" class="btn btn-primary btn-sm">Kaydet</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($sayfa_sayisi > 1): ?>
<nav class="adm-sayfalama">
    <?php for ($i = 1; $i <= $sayfa_sayisi; $i++): $qs = $_GET; unset($qs['sayfa']); $qs['sayfa'] = $i; $u = site_url('yonetim/stok') . '?' . http_build_query($qs); ?>
        <?php if ($i == $sayfa): ?><span class="aktif"><?= $i ?></span><?php else: ?><a href="<?= e($u) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
