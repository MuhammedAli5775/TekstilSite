<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<div class="sayfa-baslik"><h2>Para Birimleri &amp; Kurlar</h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/ayarlar') ?>">← Ayarlar</a>
</div>

<div class="adm-card adm-card--p0">
    <div class="adm-card-baslik"><h3>Kur Tanımları</h3></div>
    <div style="padding:12px;color:var(--stone);font-size:13px;line-height:1.6">
        <b>kur_try</b> = 1 birim bu para kaç TRY. (Örn. USD=32.5 → 1 USD = 32,5 ₺). TRY daima 1 (temel para).
        Siparişler bayinin para birimi + bu kur ile <b>anlık kopya</b> (snapshot) olarak kaydedilir.
    </div>
    <div class="adm-tbl-sar">
        <form action="<?= site_url('yonetim/para_birimi/kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <table class="adm-tbl">
                <thead><tr><th>Kod</th><th>Ad</th><th>Sembol</th><th class="sag">kur_try</th><th>Sıra</th><th>Aktif</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($birimler as $b): ?>
                    <tr>
                        <td><input type="text" name="kod[]" value="<?= e($b->kod) ?>" style="width:60px" <?= $b->kod === 'TRY' ? 'readonly' : '' ?>></td>
                        <td><input type="text" name="ad[]" value="<?= e($b->ad) ?>" style="width:140px"></td>
                        <td><input type="text" name="sembol[]" value="<?= e($b->sembol) ?>" style="width:60px"></td>
                        <td class="sag"><input type="number" step="0.0001" name="kur_try[]" value="<?= e($b->kur_try) ?>" style="width:90px;text-align:right" <?= $b->kod === 'TRY' ? 'value="1" readonly' : '' ?>></td>
                        <td><input type="number" name="sira[]" value="<?= (int) $b->sira ?>" style="width:50px"></td>
                        <td><input type="checkbox" name="durum[]" value="1" <?= (int) $b->durum === 1 ? 'checked' : '' ?>></td>
                        <td><?php if ($b->kod !== 'TRY'): ?><a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/para_birimi/sil/' . $b->kod) ?>" onclick="return confirm('Silinsin mi?')">Sil</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background:var(--surface-soft)">
                    <td><input type="text" name="kod[]" value="" placeholder="GBP" style="width:60px"></td>
                    <td><input type="text" name="ad[]" value="" placeholder="İngiliz Sterlini" style="width:140px"></td>
                    <td><input type="text" name="sembol[]" value="" placeholder="£" style="width:60px"></td>
                    <td class="sag"><input type="number" step="0.0001" name="kur_try[]" value="" placeholder="0.0000" style="width:90px;text-align:right"></td>
                    <td><input type="number" name="sira[]" value="" style="width:50px"></td>
                    <td><input type="checkbox" name="durum[]" value="1" checked></td>
                    <td><small>yeni ekle</small></td>
                </tr>
                </tbody>
            </table>
            <div style="padding:12px"><button class="btn btn-primary">Kaydet</button></div>
        </form>
    </div>
</div>
