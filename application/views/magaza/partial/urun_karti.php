<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Ürün kartı partial — $urun (array|object) bekler. */
if (!isset($urun) || !$urun) return;
$u = (array) $urun;

$ad    = $u['ad']        ?? '';
$url   = $u['url']       ?? '#';
$gorsel= $u['gorsel']    ?? '';
$fiyat = (float) ($u['fiyat'] ?? 0);
$eskifiyat = (float) ($u['eski_fiyat'] ?? 0);
$sku   = $u['stok_kodu'] ?? '';
$moq   = (int) ($u['moq'] ?? 1);
$tag   = $u['etiket']    ?? null; // ['renk'=>'green','metin'=>'Yeni']
?>
<article class="prodcard">
    <a class="prodcard__media" href="<?= e($url) ?>" aria-label="<?= e($ad) ?>">
        <?php if ($gorsel): ?>
            <img src="<?= e(gorsel_url($gorsel)) ?>" alt="<?= e($ad) ?>" loading="lazy" decoding="async">
        <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);font-size:13px">görsel yok</div>
        <?php endif; ?>
        <?php if (!empty($tag['metin'])): ?>
            <span class="prodcard__tag badge badge--<?= e($tag['renk'] ?? 'green') ?>"><?= e($tag['metin']) ?></span>
        <?php endif; ?>
    </a>
    <div class="prodcard__body">
        <a class="prodcard__name" href="<?= e($url) ?>"><?= e($ad) ?></a>
        <?php if ($sku): ?><span class="prodcard__sku"><?= e($sku) ?></span><?php endif; ?>
        <div class="prodcard__price">
            <?php if ($eskifiyat && $eskifiyat > $fiyat): ?>
                <span class="now"><?= para_tr($fiyat) ?></span>
                <span style="text-decoration:line-through;color:var(--muted);font-size:13px"><?= para_tr($eskifiyat) ?></span>
            <?php else: ?>
                <span class="now"><?= para_tr($fiyat) ?></span>
            <?php endif; ?>
            <span class="prodcard__adet-etiket">/ adet</span>
        </div>
        <?php $seriFiyat = (float) ($u['seri_fiyat'] ?? 0); $seriAdet = (int) ($u['seri_adet'] ?? 0); ?>
        <?php if ($seriFiyat > 0 && $seriFiyat < $fiyat): ?>
        <div class="prodcard__seri">Seri <b><?= para_tr($seriFiyat) ?></b> <small><?= $seriAdet ?>+ adette</small></div>
        <?php endif; ?>
        <div class="prodcard__moq">Min. <b><?= $moq ?></b> adet · toptan</div>
    </div>
</article>
