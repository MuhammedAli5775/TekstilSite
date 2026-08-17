<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s = $s ?? NULL;
$veri = function ($alan, $def = '') use ($s) { return $s ? ($s->{$alan} ?? $def) : $def; };
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2><?= $s ? 'Sayfa Düzenle' : 'Yeni Sayfa' ?></h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/sayfalar') ?>">← Listeye</a>
</div>

<form action="<?= site_url('yonetim/sayfalar/kaydet') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($s): ?><input type="hidden" name="id" value="<?= (int) $s->id ?>"><?php endif; ?>

    <div class="fld-row">
        <div class="fld"><label>Başlık <span class="zor">*</span></label><input type="text" name="baslik" value="<?= e($veri('baslik')) ?>" required maxlength="190"></div>
        <div class="fld"><label>Slug (boşsa başlıktan)</label><input type="text" name="slug" value="<?= e($veri('slug')) ?>" placeholder="hakkimizda"></div>
    </div>

    <div class="fld">
        <label>İçerik (HTML)</label>
        <textarea name="icerik" rows="16" style="width:100%;font-family:'Source Code Pro',monospace;font-size:13px;line-height:1.6;padding:12px;border:1px solid var(--hairline-strong);border-radius:var(--r-md);resize:vertical"><?= e($veri('icerik')) ?></textarea>
        <small>HTML içeriği (örn. &lt;p&gt;...&lt;/p&gt;). Güvenilir admin girişi — sitede escape edilmeden gösterilir.</small>
    </div>

    <div class="fld-row">
        <div class="fld"><label>SEO Başlık</label><input type="text" name="seo_title" value="<?= e($veri('seo_title')) ?>" maxlength="190"></div>
        <div class="fld"><label>SEO Açıklama</label><input type="text" name="seo_description" value="<?= e($veri('seo_description')) ?>" maxlength="320"></div>
    </div>

    <div class="fld" style="max-width:200px">
        <label>Durum</label>
        <select name="durum">
            <option value="1" <?= (! $s || $s->durum) ? 'selected' : '' ?>>Yayında</option>
            <option value="0" <?= $s && ! $s->durum ? 'selected' : '' ?>>Taslak</option>
        </select>
    </div>

    <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary"><?= $s ? 'Güncelle' : 'Ekle' ?></button>
        <?php if ($s): ?><a class="btn btn-ghost" href="<?= e(site_url('sayfa/' . $s->slug)) ?>" target="_blank">Sitede Gör →</a><?php endif; ?>
    </div>
</form>