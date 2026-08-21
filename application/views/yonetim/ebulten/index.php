<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik" style="display:flex;align-items:center;justify-content:space-between">
            <h3>E-Bülten Aboneleri (<?= (int) $toplam ?> aktif)</h3>
            <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/ebulten/csv') ?>">CSV İndir</a>
        </div>
        <div style="padding:8px">
            <?php if (empty($aboneler)): ?><div class="adm-bosluk">Henüz abone yok.</div><?php endif; ?>
            <?php foreach ($aboneler as $a): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:8px 10px;border-bottom:1px solid var(--hairline)">
                    <b style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($a->eposta) ?></b>
                    <small class="rozet rozet-gri"><?= e(strtoupper($a->dil)) ?></small>
                    <?php if ((int) $a->durum !== 1): ?><small class="rozet" style="background:var(--surface-soft);color:var(--steel)">pasif</small><?php endif; ?>
                    <small class="text-steel" style="flex:0 0 auto"><?= e($a->created_at) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
