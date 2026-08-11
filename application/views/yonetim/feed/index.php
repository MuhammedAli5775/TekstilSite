<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<?php if (! empty($yeni_anahtar)): ?>
<div class="adm-uyari adm-uyari--ok" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
    <b>Yeni anahtar (bir daha gösterilmeyecek — şimdi kopyalayın):</b>
    <code style="background:var(--surface-soft);padding:6px 10px;border-radius:6px;font-size:13px;word-break:break-all"><?= e($yeni_anahtar) ?></code>
    <button type="button" class="btn btn-ghost btn-sm" onclick="navigator.clipboard.writeText('<?= e($yeni_anahtar) ?>')">Kopyala</button>
</div>
<?php endif; ?>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Mevcut Anahtarlar (<?= count($anahtarlar) ?>)</h3></div>
        <div style="padding:8px">
            <?php if (empty($anahtarlar)): ?><div class="adm-bosluk">Henüz anahtar yok.</div><?php endif; ?>
            <?php foreach ($anahtarlar as $a): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px;border-bottom:1px solid var(--hairline);flex-wrap:wrap">
                    <span>
                        <b><?= e($a->ad) ?></b>
                        <small class="rozet rozet-gri" style="margin-left:6px"><?= e($a->onek) ?>…</small>
                        <?php if (! empty($a->firma_adi)): ?><small class="rozet rozet-gri" style="margin-left:4px"><?= e($a->firma_adi) ?></small><?php endif; ?>
                        <?php if ((int) $a->durum === 1): ?><small class="rozet rozet-yesil" style="margin-left:4px">Aktif</small><?php else: ?><small class="rozet rozet-gri" style="margin-left:4px">Pasif</small><?php endif; ?>
                    </span>
                    <span style="font-size:12px;color:var(--muted)">
                        <?php if ($a->son_kullanim): ?>Son: <?= e(date('d.m.Y H:i', strtotime($a->son_kullanim))) ?> · <?php endif; ?>
                        <?= (int) $a->kullanim_sayisi ?> kullanım
                    </span>
                    <span>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/feed/durum/' . $a->id) ?>"><?= (int) $a->durum === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></a>
                        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/feed/sil/' . $a->id) ?>" onclick="return confirm('Anahtar silinsin mi? Bu feed erişimini anında kapatır.')">Sil</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-baslik"><h3>Yeni Anahtar Üret</h3></div>
        <form action="<?= site_url('yonetim/feed/olustur') ?>" method="post">
            <?= csrf_field() ?>
            <div class="fld"><label>Etiket <span class="zor">*</span></label><input type="text" name="ad" placeholder="örn. Bayi X feed" required></div>
            <div class="fld"><label>Bağlı bayi (opsiyonel)</label>
                <select name="bayi_id"><option value="0">— Genel (bayisiz) —</option>
                    <?php foreach ($bayiler as $b): ?>
                        <option value="<?= (int) $b->id ?>"><?= e($b->firma_adi) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary">Üret + Göster</button>
        </form>
        <div style="margin-top:14px;padding:10px;background:var(--surface-soft);border-radius:8px;font-size:12px;color:var(--slate)">
            <b>Feed URL:</b> <code><?= e($feed_url) ?>?key=ANAHTAR</code> (varsayılan XML; <code>&amp;format=json</code> ile JSON).<br>
            Anahtarlar veritabanında <b>plaintext değil, sha256 hash</b> olarak saklanır; ham değer yalnızca üretildiği an gösterilir.
        </div>
    </div>
</div>
