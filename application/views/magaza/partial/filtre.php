<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Filtre sidebar partial. Beklediği değişkenler: liste_url, alt_kategoriler,
 *  kategori, facet_beden, facet_renk, secili_beden, secili_renk, filtre */
$sira = isset($filtre['sira']) ? $filtre['sira'] : 'yeni';
?>
<form class="filtre-form" action="<?= e(site_url($liste_url)) ?>" method="get">
    <input type="hidden" name="sira" value="<?= e($sira) ?>">

    <?php if (! empty($alt_kategoriler) && ! empty($kategori)): ?>
    <div class="filtre-grup">
        <h4>Alt Kategoriler</h4>
        <?php foreach ($alt_kategoriler as $a): ?>
            <a class="filtre-alt-kat" href="<?= e(site_url('katalog/' . $kategori->slug . '/' . $a->slug)) ?>"><?= e($a->ad) ?> →</a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (! empty($facet_beden)): ?>
    <div class="filtre-grup">
        <h4>Beden</h4>
        <?php foreach ($facet_beden as $b): ?>
            <label class="filtre-check">
                <input type="checkbox" name="beden[]" value="<?= e($b->beden) ?>" <?= in_array($b->beden, $secili_beden, TRUE) ? 'checked' : '' ?>>
                <span class="filtre-etiket"><?= e($b->beden) ?> <small>(<?= (int) $b->adet ?>)</small></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (! empty($facet_renk)): ?>
    <div class="filtre-grup">
        <h4>Renk</h4>
        <?php foreach ($facet_renk as $r): ?>
            <label class="filtre-check">
                <input type="checkbox" name="renk[]" value="<?= e($r->renk) ?>" <?= in_array($r->renk, $secili_renk, TRUE) ? 'checked' : '' ?>>
                <span class="swatch" style="background:<?= e(renk_hex($r->renk)) ?>"></span>
                <span class="filtre-etiket"><?= e($r->renk) ?> <small>(<?= (int) $r->adet ?>)</small></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="filtre-grup">
        <h4>Fiyat Aralığı (₺)</h4>
        <div class="filtre-fiyat">
            <input type="number" name="min" placeholder="en az" value="<?= e($this->input->get('min')) ?>" min="0" inputmode="numeric">
            <span>–</span>
            <input type="number" name="max" placeholder="en çok" value="<?= e($this->input->get('max')) ?>" min="0" inputmode="numeric">
        </div>
        <small class="filtre-canli" style="display:block;margin-top:10px;color:var(--steel);font-size:12px">Seçimler anında uygulanır</small>
    </div>
</form>
