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
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);font-size:13px"><?= t('kart_gorsel_yok', 'görsel yok') ?></div>
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
                <span class="now"><?= para_goster($fiyat) ?></span>
                <span style="text-decoration:line-through;color:var(--muted);font-size:13px"><?= para_goster($eskifiyat) ?></span>
            <?php else: ?>
                <span class="now"><?= para_goster($fiyat) ?></span>
            <?php endif; ?>
            <span class="prodcard__adet-etiket"><?= t('kart_adet', '/ adet') ?></span>
        </div>
        <?php $seriFiyat = (float) ($u['seri_fiyat'] ?? 0); $seriAdet = (int) ($u['seri_adet'] ?? 0); ?>
        <?php if ($seriFiyat > 0 && $seriFiyat < $fiyat): ?>
        <div class="prodcard__seri"><?= t('kart_seri', 'Seri') ?> <b><?= para_goster($seriFiyat) ?></b> <small><?= t('kart_seri_adet', '%s+ adette', $seriAdet) ?></small></div>
        <?php endif; ?>
        <div class="prodcard__moq"><?= t('kart_moq', 'Min. %s adet · toptan', $moq) ?></div>
    </div>
</article>
