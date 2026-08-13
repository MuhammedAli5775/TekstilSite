<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Yetki Matrisi</h2>
    <span class="adm-mini">Süper yönetici (rol 1) daima tam yetkili — matriste yer almaz.</span>
</div>

<div class="adm-card" style="margin-bottom:16px">
    <div class="adm-card-baslik"><h3>Rol seç</h3></div>
    <?php foreach ($roller as $r): ?>
        <a class="btn <?= (int) $r->id === (int) $rol ? 'btn-primary' : 'btn-ghost' ?> btn-sm" href="<?= site_url('yonetim/yetkiler?rol=' . (int) $r->id) ?>"><?= e($r->ad) ?></a>
    <?php endforeach; ?>
</div>

<form action="<?= site_url('yonetim/yetkiler/kaydet') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="rol" value="<?= (int) $rol ?>">
    <div class="adm-card">
        <div class="adm-card-baslik">
            <h3>İzinler — rol #<?= (int) $rol ?></h3>
            <button type="submit" class="btn btn-primary btn-sm">Kaydet</button>
        </div>
        <table class="adm-tablo">
            <thead>
                <tr><th style="text-align:left">Modül</th><th>Görüntüle</th><th>Düzenle</th><th>Sil</th></tr>
            </thead>
            <tbody>
                <?php foreach ($matris as $key => $m): ?>
                    <tr>
                        <td style="text-align:left"><?= e($m['etiket']) ?></td>
                        <td><label class="checkbox"><input type="checkbox" name="grid[<?= e($key) ?>][goruntule]" value="1" <?= $m['goruntule'] ? 'checked' : '' ?>></label></td>
                        <td><label class="checkbox"><input type="checkbox" name="grid[<?= e($key) ?>][duzenle]" value="1" <?= $m['duzenle'] ? 'checked' : '' ?>></label></td>
                        <td><label class="checkbox"><input type="checkbox" name="grid[<?= e($key) ?>][sil]" value="1" <?= $m['sil'] ? 'checked' : '' ?>></label></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <small style="color:var(--stone)">İşaretli = izinli. İşaretsiz modül/işlem 403 (erişim engelli). Süper yönetici (rol 1) matristen bağımsız her şeyi yapabilir.</small>
    </div>
</form>
