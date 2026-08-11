<?php defined('BASEPATH') OR exit('No direct script access allowed');
$m = $m ?? NULL;
$veri = function ($alan, $def = '') use ($m) { return $m ? ($m->{$alan} ?? $def) : $def; };
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2><?= $m ? 'Marka Düzenle' : 'Yeni Marka' ?></h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/markalar') ?>">← Listeye</a>
</div>

<form action="<?= site_url('yonetim/markalar/kaydet') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($m): ?><input type="hidden" name="id" value="<?= (int) $m->id ?>"><?php endif; ?>

    <div class="fld-row">
        <div class="fld"><label>Marka Adı <span class="zor">*</span></label><input type="text" name="ad" value="<?= e($veri('ad')) ?>" required maxlength="120"></div>
        <div class="fld"><label>Slug (boşsa ad'dan)</label><input type="text" name="slug" value="<?= e($veri('slug')) ?>" placeholder="marka-slug"></div>
    </div>

    <div class="fld"><label>Logo (URL veya uploads/ yolu)</label><input type="text" name="logo" value="<?= e($veri('logo')) ?>" placeholder="https://... veya uploads/markalar/x.png"></div>
    <?php if ($m && $m->logo): ?>
        <div style="margin:-4px 0 14px"><img src="<?= e(gorsel_url($m->logo)) ?>" alt="" style="max-height:64px;border:1px solid var(--hairline);border-radius:6px;padding:4px"></div>
    <?php endif; ?>

    <div class="fld" style="max-width:260px">
        <label>Durum</label>
        <select name="durum">
            <option value="1" <?= (! $m || $m->durum) ? 'selected' : '' ?>>Aktif (ürün formunda görünür)</option>
            <option value="0" <?= $m && ! $m->durum ? 'selected' : '' ?>>Pasif</option>
        </select>
    </div>

    <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary"><?= $m ? 'Güncelle' : 'Ekle' ?></button>
    </div>
</form>