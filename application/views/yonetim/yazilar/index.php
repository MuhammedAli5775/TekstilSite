<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $duzenle ?? NULL;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Blog Yazıları (<?= count($yazilar) ?>)</h3></div>
        <div style="padding:8px">
            <?php if (empty($yazilar)): ?><div class="adm-bosluk">Henüz yazı yok.</div><?php endif; ?>
            <?php foreach ($yazilar as $y): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px;border-bottom:1px solid var(--hairline)">
                    <img src="<?= e($y->gorsel !== '' ? $y->gorsel : 'data:image/svg+xml;charset=utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2296%22 height=%2248%22%3E%3Crect width=%2296%22 height=%2248%22 fill=%22%23eef4f6%22/%3E%3C/svg%3E') ?>" alt="" style="width:96px;height:48px;object-fit:cover;border-radius:6px;flex:0 0 auto;background:var(--surface-soft)">
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                            <b><?= e($y->baslik) ?></b>
                            <?php if ($y->yayin_tarihi): ?><small class="rozet rozet-gri"><?= e($y->yayin_tarihi) ?></small><?php endif; ?>
                            <?php if ((int) $y->durum !== 1): ?><small class="rozet" style="background:var(--surface-soft);color:var(--steel)">pasif</small><?php endif; ?>
                        </div>
                        <small class="text-steel" style="display:block;margin-top:2px">/blog/<?= e($y->slug) ?></small>
                    </div>
                    <span style="flex:0 0 auto;white-space:nowrap">
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/yazilar?duzenle=' . $y->id) ?>">Düzenle</a>
                        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/yazilar/sil/' . $y->id) ?>" onclick="return confirm('Yazı silinsin mi?')">Sil</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-baslik"><h3><?= $d ? 'Yazıyı Düzenle' : 'Yeni Yazı' ?></h3></div>
        <form action="<?= site_url('yonetim/yazilar/kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <?php if ($d): ?><input type="hidden" name="id" value="<?= (int) $d->id ?>"><?php endif; ?>

            <div class="fld"><label>Başlık <span class="zor">*</span></label><input type="text" name="baslik" value="<?= e($d->baslik ?? '') ?>" maxlength="200" required></div>
            <div class="fld">
                <label>Slug (URL)</label>
                <input type="text" name="slug" value="<?= e($d->slug ?? '') ?>" placeholder="boş bırakılırsa başlıktan üretilir">
                <small class="text-steel">Türkçe karakterler sadeleşir; benzersiz değilse otomatik -2 eklenir.</small>
            </div>
            <div class="fld">
                <label>Kısa özet</label>
                <textarea name="ozet" rows="2" maxlength="500" placeholder="boş bırakılırsa içerikten üretilir"><?= e($d->ozet ?? '') ?></textarea>
                <small class="text-steel">Liste kartlarında ve arama motoru açıklamasında kullanılır.</small>
            </div>
            <div class="fld">
                <label>İçerik (HTML) <span class="zor">*</span></label>
                <textarea name="icerik" rows="10" required><?= e($d->icerik ?? '') ?></textarea>
                <small class="text-steel">p, h2, ul gibi etiketler kullanılabilir — CMS sayfaları gibi güvenilir admin içeriğidir.</small>
            </div>
            <div class="fld">
                <label>Kapak görseli URL</label>
                <input type="text" name="gorsel" value="<?= e($d->gorsel ?? '') ?>" placeholder="https://…">
                <small class="text-steel">Yalnız https URL — bu modülde dosya yükleme yok.</small>
            </div>
            <div class="fld-row">
                <div class="fld"><label>Yayın tarihi</label><input type="date" name="yayin_tarihi" value="<?= e($d->yayin_tarihi ?? '') ?>"></div>
                <div class="fld">
                    <label>Durum</label>
                    <label style="display:flex;gap:6px;align-items:center;padding:8px 0"><input type="checkbox" name="durum" value="1" <?= (int) ($d->durum ?? 1) === 1 ? 'checked' : '' ?>> Yayında</label>
                </div>
            </div>
            <div class="fld"><button class="btn btn-primary" type="submit"><?= $d ? 'Güncelle' : 'Ekle' ?></button></div>
        </form>
    </div>
</div>
