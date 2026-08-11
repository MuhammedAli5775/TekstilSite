<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Markalar</h2>
    <a class="btn btn-primary btn-sm" href="<?= site_url('yonetim/markalar/ekle') ?>">+ Yeni Marka</a>
</div>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/markalar') ?>">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Marka ara…">
    <button type="submit" class="btn btn-secondary">Ara</button>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Marka</th><th>Slug</th><th>Logo</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($markalar)): ?><tr><td colspan="5" class="adm-bosluk">Marka yok</td></tr><?php endif; ?>
        <?php foreach ($markalar as $m): ?>
            <tr>
                <td><b><?= e($m->ad) ?></b></td>
                <td><small class="mono"><?= e($m->slug) ?></small></td>
                <td><?= $m->logo ? '<img src="' . e(gorsel_url($m->logo)) . '" alt="" style="height:26px;max-width:90px;object-fit:contain">' : '<small class="text-steel">—</small>' ?></td>
                <td><?= (int) $m->durum === 1 ? '<span class="rozet rozet-yesil">aktif</span>' : '<span class="rozet rozet-gri">pasif</span>' ?></td>
                <td>
                    <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/markalar/duzenle/' . $m->id) ?>">Düzenle</a>
                    <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/markalar/sil/' . $m->id) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>