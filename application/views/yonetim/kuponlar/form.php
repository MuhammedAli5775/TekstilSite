<?php defined('BASEPATH') OR exit('No direct script access allowed');
$k = $k ?? NULL;
$veri = function ($alan, $def = '') use ($k) { return $k ? ($k->{$alan} ?? $def) : $def; };
$dz = function ($alan) use ($k) { // datetime-local format (YYYY-MM-DDTHH:MM)
    if (! $k || empty($k->{$alan})) { return ''; }
    return str_replace(' ', 'T', substr((string) $k->{$alan}, 0, 16));
};
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>
<?php $_ve = validation_errors(); echo $_ve ? '<div class="adm-uyari adm-uyari--hata">' . strip_tags($_ve) . '</div>' : ''; ?>

<div class="sayfa-baslik">
    <h2><?= $k ? 'Kupon Düzenle' : 'Yeni Kupon' ?></h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/kuponlar') ?>">← Listeye</a>
</div>

<form action="<?= site_url('yonetim/kuponlar/kaydet') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($k): ?><input type="hidden" name="id" value="<?= (int) $k->id ?>"><?php endif; ?>

    <div class="fld-row">
        <div class="fld"><label>Kod <span class="zor">*</span></label><input type="text" name="kod" value="<?= e($veri('kod')) ?>" required maxlength="60" style="text-transform:uppercase" placeholder="YAZ2026"></div>
        <div class="fld"><label>Açıklama</label><input type="text" name="aciklama" value="<?= e($veri('aciklama')) ?>" maxlength="190"></div>
    </div>
    <div class="fld-row">
        <div class="fld"><label>Tip <span class="zor">*</span></label>
            <select name="tip">
                <option value="yuzde" <?= (! $k || $k->tip === 'yuzde') ? 'selected' : '' ?>>Yüzde (%)</option>
                <option value="sabit" <?= $k && $k->tip === 'sabit' ? 'selected' : '' ?>>Sabit tutar (₺)</option>
            </select>
        </div>
        <div class="fld"><label>Değer <span class="zor">*</span></label><input type="number" step="0.01" name="deger" value="<?= e($veri('deger')) ?>" required></div>
    </div>
    <div class="fld-row">
        <div class="fld"><label>Min. Sepet Tutarı (₺, 0=limit yok)</label><input type="number" step="0.01" name="min_sepet_tutar" value="<?= e($veri('min_sepet_tutar', 0)) ?>"></div>
        <div class="fld"><label>Maks. İndirim (₺, 0=sınırsız)</label><input type="number" step="0.01" name="max_indirim" value="<?= e($veri('max_indirim', 0)) ?>"></div>
    </div>
    <div class="fld-row">
        <div class="fld"><label>Başlangıç (boş=hemen)</label><input type="datetime-local" name="baslangic_zaman" value="<?= e($dz('baslangic_zaman')) ?>"></div>
        <div class="fld"><label>Bitiş (boş=süresiz)</label><input type="datetime-local" name="bitis_zaman" value="<?= e($dz('bitis_zaman')) ?>"></div>
    </div>
    <div class="fld-row">
        <div class="fld"><label>Kullanım Limiti (0=sınırsız)</label><input type="number" name="kullanim_limiti" value="<?= e($veri('kullanim_limiti', 0)) ?>" min="0"></div>
        <div class="fld"><label>Durum</label>
            <select name="durum">
                <option value="1" <?= (! $k || $k->durum) ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= $k && ! $k->durum ? 'selected' : '' ?>>Pasif</option>
            </select>
        </div>
    </div>

    <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary"><?= $k ? 'Güncelle' : 'Ekle' ?></button>
    </div>
</form>