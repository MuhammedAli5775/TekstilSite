<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $duzenle ?? NULL;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik"><h2>Pazaryeri Hesapları</h2></div>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Hesaplar (<?= count($hesaplar) ?>)</h3></div>
        <div style="padding:8px">
            <?php if (empty($hesaplar)): ?><div class="adm-bosluk">Henüz hesap yok.</div><?php endif; ?>
            <?php foreach ($hesaplar as $h): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px;border-bottom:1px solid var(--hairline);flex-wrap:wrap">
                    <span>
                        <b><?= e($h->ad) ?></b>
                        <small class="rozet rozet-gri" style="margin-left:6px"><?= e($platformlar[$h->platform] ?? $h->platform) ?></small>
                        <?php if ($h->supplier_id): ?><small class="rozet rozet-gri" style="margin-left:4px">supplier: <?= e($h->supplier_id) ?></small><?php endif; ?>
                        <?php if ((int) $h->durum === 1): ?><small class="rozet rozet-yesil" style="margin-left:4px">Aktif</small><?php else: ?><small class="rozet rozet-gri" style="margin-left:4px">Pasif</small><?php endif; ?>
                        <?php if ($h->son_sin): ?><small style="margin-left:4px;color:var(--muted)">son senk: <?= e(date('d.m.Y H:i', strtotime($h->son_sin))) ?></small><?php endif; ?>
                    </span>
                    <span>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/pazaryeri/detay/' . $h->id) ?>">Yönet</a>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/pazaryeri/durum/' . $h->id) ?>"><?= (int) $h->durum === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></a>
                        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/pazaryeri/sil/' . $h->id) ?>" onclick="return confirm('Hesap silinsin mi? Eşleştirme ve log da silinir.')">Sil</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-baslik"><h3><?= $d ? 'Hesabı Düzenle' : 'Yeni Hesap' ?></h3></div>
        <form action="<?= site_url('yonetim/pazaryeri/hesap_kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <?php if ($d): ?><input type="hidden" name="id" value="<?= (int) $d->id ?>"><?php endif; ?>
            <div class="fld-row">
                <div class="fld"><label>Platform <span class="zor">*</span></label>
                    <select name="platform">
                        <?php foreach ($platformlar as $k => $ad): ?><option value="<?= e($k) ?>" <?= $d && $d->platform === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label>Etiket</label><input type="text" name="ad" value="<?= e($d->ad ?? '') ?>" placeholder="örn. Trendyol ana mağaza"></div>
            </div>
            <div class="fld"><label>Supplier ID (Trendyol)</label><input type="text" name="supplier_id" value="<?= e($d->supplier_id ?? '') ?>"></div>
            <div class="fld"><label>API Key <?= $d ? '(boş = değişmez)' : '' ?></label><input type="text" name="api_key" value="" autocomplete="off"></div>
            <div class="fld"><label>API Secret <?= $d ? '(boş = değişmez)' : '' ?></label><input type="password" name="api_secret" value="" autocomplete="new-password"></div>
            <button class="btn btn-primary"><?= $d ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($d): ?><a class="btn btn-ghost" href="<?= site_url('yonetim/pazaryeri') ?>">İptal</a><?php endif; ?>
        </form>
        <div style="margin-top:14px;padding:10px;background:var(--surface-soft);border-radius:8px;font-size:12px;color:var(--slate)">
            <b>Stok/fiyat senkron + sipariş çekme</b> Trendyol Partner API (sapigw) ile çalışır. Kimlikler DB'de <b>şifreli</b> saklanır. Trendyol dışı platformlar şimdilik “bekliyor” olarak atlar. Senkron detayda manuel tetiklenir (cron ileride).
        </div>
    </div>
</div>
