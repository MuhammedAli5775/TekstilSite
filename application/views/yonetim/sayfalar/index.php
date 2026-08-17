<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Sayfalar</h2>
    <a class="btn btn-primary btn-sm" href="<?= site_url('yonetim/sayfalar/ekle') ?>">+ Yeni Sayfa</a>
</div>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/sayfalar') ?>">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Başlık / slug ara…">
    <button type="submit" class="btn btn-secondary">Ara</button>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Başlık</th><th>Slug</th><th>Durum</th><th>İçerik</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($sayfalar)): ?><tr><td colspan="5" class="adm-bosluk">Sayfa yok</td></tr><?php endif; ?>
        <?php foreach ($sayfalar as $s): ?>
            <tr>
                <td><b><?= e($s->baslik) ?></b></td>
                <td><small class="mono">/<?= e($s->slug) ?></small></td>
                <td>
                    <?php if ((int) $s->durum === 1): ?>
                        <a class="rozet rozet-yesil" href="<?= e(site_url('sayfa/' . $s->slug)) ?>" target="_blank">yayında ↗</a>
                    <?php else: ?>
                        <span class="rozet rozet-gri">taslak</span>
                    <?php endif; ?>
                </td>
                <td><small><?= (int) mb_strlen((string) $s->icerik, 'UTF-8') ?> krk</small></td>
                <td>
                    <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/sayfalar/duzenle/' . $s->id) ?>">Düzenle</a>
                    <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/sayfalar/sil/' . $s->id) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>