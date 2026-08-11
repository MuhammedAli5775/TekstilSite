<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $duzenle ?? NULL;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Kategori Ağacı</h3></div>
        <div style="padding:8px">
            <?php if (empty($agac)): ?><div class="adm-bosluk">Kategori yok</div><?php endif; ?>
            <?php foreach ($agac as $k): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 10px;border-bottom:1px solid var(--hairline)">
                    <span><b><?= e($k->ad) ?></b> <small class="rozet rozet-gri" style="margin-left:6px"><?= e($k->slug) ?></small></span>
                    <span>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/kategoriler?duzenle=' . $k->id) ?>">Düzenle</a>
                        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/kategoriler/sil/' . $k->id) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                    </span>
                </div>
                <?php foreach ($k->altlar as $a): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px 8px 28px;border-bottom:1px solid var(--hairline)">
                        <span>↳ <?= e($a->ad) ?> <small class="rozet rozet-gri" style="margin-left:6px"><?= e($a->slug) ?></small></span>
                        <span>
                            <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/kategoriler?duzenle=' . $a->id) ?>">Düzenle</a>
                            <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/kategoriler/sil/' . $a->id) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-baslik"><h3><?= $d ? 'Kategori Düzenle' : 'Yeni Kategori' ?></h3></div>
        <form action="<?= site_url('yonetim/kategoriler/kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <?php if ($d): ?><input type="hidden" name="id" value="<?= (int) $d->id ?>"><?php endif; ?>
            <div class="fld"><label>Ad <span class="zor">*</span></label><input type="text" name="ad" value="<?= e($d->ad ?? '') ?>" required></div>
            <div class="fld"><label>Slug (boş bırakılırsa ad'dan üret)</label><input type="text" name="slug" value="<?= e($d->slug ?? '') ?>"></div>
            <div class="fld"><label>Üst Kategori</label>
                <select name="ust_id"><option value="">— Üst kategori (root) —</option>
                    <?php foreach ($ust_kategoriler as $u): if ($d && (int) $u->id === (int) $d->id) { continue; } ?>
                        <option value="<?= (int) $u->id ?>" <?= $d && (int) $d->ust_id === (int) $u->id ? 'selected' : '' ?>><?= e($u->ad) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fld-row">
                <div class="fld"><label>Sıra</label><input type="number" name="sira" value="<?= e($d->sira ?? '0') ?>"></div>
                <div class="fld"><label>Durum</label><select name="durum"><option value="1" <?= ($d->durum ?? 1) ? 'selected' : '' ?>>Aktif</option><option value="0" <?= $d && ! $d->durum ? 'selected' : '' ?>>Pasif</option></select></div>
            </div>
            <button class="btn btn-primary"><?= $d ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($d): ?><a class="btn btn-ghost" href="<?= site_url('yonetim/kategoriler') ?>">İptal</a><?php endif; ?>
        </form>
    </div>
</div>
