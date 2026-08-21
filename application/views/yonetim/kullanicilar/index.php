<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= $bilgi ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <h3>Kullanıcılar — B2C (<?= (int) $toplam ?>)</h3>
            <form method="get" style="display:flex;gap:6px">
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="E-posta / ad / kullanıcı adı" style="min-width:200px">
                <button class="btn btn-ghost btn-sm" type="submit">Ara</button>
            </form>
        </div>
        <div style="padding:8px">
            <?php if (empty($kullanicilar)): ?><div class="adm-bosluk">Kullanıcı yok.</div><?php endif; ?>
            <?php foreach ($kullanicilar as $k): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px;border-bottom:1px solid var(--hairline);flex-wrap:wrap">
                    <div style="flex:1;min-width:200px">
                        <b><?= e($k->ad_soyad) ?></b> <small class="text-steel">@<?= e($k->kullanici_adi) ?></small><br>
                        <small class="text-steel"><?= e($k->email) ?><?= $k->telefon ? ' · ' . e($k->telefon) : '' ?> · <?= e($k->olusturma_zaman) ?></small>
                    </div>
                    <?php if ((int) $k->durum === 1): ?>
                        <small class="rozet rozet-gri">aktif</small>
                        <form method="post" action="<?= site_url('yonetim/kullanicilar/durum_guncelle/' . $k->id) ?>">
                            <?= csrf_field() ?><input type="hidden" name="durum" value="0">
                            <button class="btn btn-ghost btn-sm">Pasifleştir</button>
                        </form>
                    <?php else: ?>
                        <small class="rozet" style="background:var(--surface-soft);color:var(--steel)">pasif</small>
                        <form method="post" action="<?= site_url('yonetim/kullanicilar/durum_guncelle/' . $k->id) ?>">
                            <?= csrf_field() ?><input type="hidden" name="durum" value="1">
                            <button class="btn btn-ghost btn-sm">Aktifleştir</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?= site_url('yonetim/kullanicilar/sifre_sifirla/' . $k->id) ?>" onsubmit="return confirm('Yeni rastgele şifre üretilsin mi? (Kullanıcıya iletilmelidir)')">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-sm" style="color:var(--danger)">Şifre Sıfırla</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
