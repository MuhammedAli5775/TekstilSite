<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $duzenle ?? NULL;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Slider Slaytları (<?= count($bannerlar) ?>)</h3></div>
        <div style="padding:8px">
            <?php if (empty($bannerlar)): ?><div class="adm-bosluk">Henüz banner yok.</div><?php endif; ?>
            <?php foreach ($bannerlar as $b): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px;border-bottom:1px solid var(--hairline)">
                    <img src="<?= e(gorsel_url($b->gorsel)) ?>" alt="" style="width:96px;height:48px;object-fit:cover;border-radius:6px;flex:0 0 auto;background:var(--surface-soft)">
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                            <b><?= e($b->baslik ?: '(başlıksız)') ?></b>
                            <small class="rozet rozet-gri">sıra: <?= (int) $b->sira ?></small>
                            <small class="rozet rozet-gri"><?= e($b->yazi_konum) ?></small>
                            <small class="rozet rozet-gri"><?= $b->dil ? e(strtoupper((string) $b->dil)) : 'tümü' ?></small>
                            <?php if ((int) $b->durum !== 1): ?><small class="rozet" style="background:var(--surface-soft);color:var(--steel)">pasif</small><?php endif; ?>
                        </div>
                        <?php if ($b->alt_baslik): ?><small class="text-steel" style="display:block;margin-top:2px"><?= e($b->alt_baslik) ?></small><?php endif; ?>
                    </div>
                    <span style="flex:0 0 auto;white-space:nowrap">
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/bannerlar?duzenle=' . $b->id) ?>">Düzenle</a>
                        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/bannerlar/sil/' . $b->id) ?>" onclick="return confirm('Banner silinsin mi?')">Sil</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-baslik"><h3><?= $d ? 'Banner Düzenle' : 'Yeni Banner' ?></h3></div>
        <form action="<?= site_url('yonetim/bannerlar/kaydet') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($d): ?><input type="hidden" name="id" value="<?= (int) $d->id ?>"><?php endif; ?>

            <?php if ($d && $d->gorsel): ?>
                <div class="fld">
                    <label>Mevcut görsel</label>
                    <img src="<?= e(gorsel_url($d->gorsel)) ?>" alt="" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px">
                </div>
            <?php endif; ?>

            <div class="fld">
                <label>Görsel dosyası <?= $d ? '(boş bırakılırsa korunur)' : '<span class="zor">*</span>' ?></label>
                <input type="file" name="gorsel_dosya" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-steel">jpg/png/webp/gif · en fazla 4MB</small>
            </div>
            <div class="fld">
                <label>VEYA görsel URL</label>
                <input type="text" name="gorsel_url" value="<?= $d && strpos((string) $d->gorsel, 'http') === 0 ? e($d->gorsel) : '' ?>" placeholder="https://...">
                <small class="text-steel">Dosya yüklerseniz URL yok sayılır.</small>
            </div>

            <div class="fld"><label>Başlık</label><input type="text" name="baslik" value="<?= e($d->baslik ?? '') ?>"></div>
            <div class="fld"><label>Alt başlık</label><input type="text" name="alt_baslik" value="<?= e($d->alt_baslik ?? '') ?>"></div>

            <div class="fld-row">
                <div class="fld"><label>Buton yazısı</label><input type="text" name="buton_yazi" value="<?= e($d->buton_yazi ?? '') ?>" placeholder="Kataloğu İncele"></div>
                <div class="fld"><label>Buton linki</label><input type="text" name="link" value="<?= e($d->link ?? '') ?>" placeholder="katalog veya https://..."></div>
            </div>

            <div class="fld">
                <label>Dil (vitrin filtresi)</label>
                <select name="dil">
                    <option value="" <?= ($d->dil ?? '') === '' ? 'selected' : '' ?>>Tüm diller</option>
                    <option value="tr" <?= ($d->dil ?? '') === 'tr' ? 'selected' : '' ?>>Türkçe</option>
                    <option value="en" <?= ($d->dil ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="ru" <?= ($d->dil ?? '') === 'ru' ? 'selected' : '' ?>>Русский</option>
                    <option value="ar" <?= ($d->dil ?? '') === 'ar' ? 'selected' : '' ?>>العربية</option>
                </select>
                <small class="text-steel">Yalnız seçili dildeki mağaza vitrininde görünür; boş = her dilde.</small>
            </div>

            <div class="fld-row">
                <div class="fld"><label>Yazı konumu</label>
                    <select name="yazi_konum">
                        <?php foreach (array('sol', 'orta', 'sag') as $kon): ?>
                            <option value="<?= e($kon) ?>" <?= ($d->yazi_konum ?? 'sol') === $kon ? 'selected' : '' ?>><?= e($kon) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label>Sıra</label><input type="number" name="sira" value="<?= e($d->sira ?? '0') ?>"></div>
                <div class="fld"><label>Durum</label><select name="durum"><option value="1" <?= ($d->durum ?? 1) ? 'selected' : '' ?>>Aktif</option><option value="0" <?= $d && ! $d->durum ? 'selected' : '' ?>>Pasif</option></select></div>
            </div>

            <button class="btn btn-primary"><?= $d ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($d): ?><a class="btn btn-ghost" href="<?= site_url('yonetim/bannerlar') ?>">İptal</a><?php endif; ?>
        </form>
    </div>
</div>
