<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Sayfalama partial. Bekler: sayfa, sayfa_sayisi */
if (! isset($sayfa_sayisi) || $sayfa_sayisi <= 1) { return; }
$onceki  = max(1, $sayfa - 1);
$sonraki = min($sayfa_sayisi, $sayfa + 1);
// çok sayfa varsa daralt
$bas = max(1, $sayfa - 2);
$son = min($sayfa_sayisi, $sayfa + 2);
?>
<nav class="sayfalama" aria-label="<?= t('kat_sayfalama', 'Sayfalama') ?>">
    <a class="sayfa-link<?= $sayfa == 1 ? ' pasif' : '' ?>" rel="prev" href="<?= e(qs_url(array('sayfa' => $onceki))) ?>" aria-label="<?= t('kat_sayfa_onceki', 'Önceki') ?>">‹</a>
    <?php if ($bas > 1): ?>
        <a class="sayfa-link" href="<?= e(qs_url(array('sayfa' => 1))) ?>">1</a>
        <?php if ($bas > 2): ?><span class="sayfa-ellipsis">…</span><?php endif; ?>
    <?php endif; ?>
    <?php for ($i = $bas; $i <= $son; $i++): ?>
        <?php if ($i == $sayfa): ?>
            <span class="sayfa-link aktif"><?= $i ?></span>
        <?php else: ?>
            <a class="sayfa-link" href="<?= e(qs_url(array('sayfa' => $i))) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($son < $sayfa_sayisi): ?>
        <?php if ($son < $sayfa_sayisi - 1): ?><span class="sayfa-ellipsis">…</span><?php endif; ?>
        <a class="sayfa-link" href="<?= e(qs_url(array('sayfa' => $sayfa_sayisi))) ?>"><?= $sayfa_sayisi ?></a>
    <?php endif; ?>
    <a class="sayfa-link<?= $sayfa == $sayfa_sayisi ? ' pasif' : '' ?>" rel="next" href="<?= e(qs_url(array('sayfa' => $sonraki))) ?>" aria-label="<?= t('kat_sayfa_sonraki', 'Sonraki') ?>">›</a>
</nav>
