<?php defined('BASEPATH') OR exit('No direct script access allowed');
$plt = $platformlar[$h->platform] ?? $h->platform;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2><?= e($h->ad) ?> <small class="rozet rozet-gri"><?= e($plt) ?></small></h2>
    <span>
        <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/pazaryeri') ?>">← Listeye</a>
        <a class="btn btn-primary btn-sm" href="<?= site_url('yonetim/pazaryeri/stok_fiyat/' . $h->id) ?>">Stok/Fiyat Gönder</a>
        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/pazaryeri/siparis_cek/' . $h->id) ?>">Sipariş Çek</a>
    </span>
</div>

<div class="adm-detay-grid">
    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Ürün Eşleştirme</h3></div>
            <?php if (empty($eslesmeler)): ?><div class="adm-bosluk">Henüz eşleştirme yok.</div><?php endif; ?>
            <?php foreach ($eslesmeler as $e): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid var(--hairline)">
                    <span><?= e($e->urun_adi ?: '#' . $e->urun_id) ?> <small class="mono"><?= e($e->stok_kodu ?: '') ?></small><br><small class="mono">pazaryeri: <?= e($e->pazaryeri_urun_id ?: '—') ?></small></span>
                    <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/pazaryeri/eslesme_sil/' . $e->id) ?>">Kaldır</a>
                </div>
            <?php endforeach; ?>
            <form action="<?= site_url('yonetim/pazaryeri/eslesme_kaydet/' . $h->id) ?>" method="post" style="margin-top:12px">
                <?= csrf_field() ?>
                <div class="fld-row">
                    <div class="fld"><label>Ürün</label>
                        <select name="urun_id"><option value="">— seç —</option>
                            <?php foreach ($urunler as $u): ?><option value="<?= (int) $u->id ?>"><?= e($u->ad) ?> (<?= e($u->stok_kodu) ?>)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld"><label>Pazaryeri ID / Barkod</label><input type="text" name="pazaryeri_urun_id" placeholder="opsiyonel (yoksa stok kodu kullanılır)"></div>
                </div>
                <button class="btn btn-primary btn-sm">Eşleştir</button>
            </form>
        </div>

        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Senkron Logu</h3></div>
            <?php if (empty($loglar)): ?><div class="adm-bosluk">Henüz senkron çalıştırılmadı.</div><?php endif; ?>
            <?php foreach ($loglar as $l): $cls = $l->durum === 'basarili' ? 'yesil' : ($l->durum === 'hata' ? 'kirmizi' : 'gri'); ?>
                <div style="padding:7px 0;border-bottom:1px solid var(--hairline);font-size:13px">
                    <span class="rozet rozet-<?= e($cls) ?>"><?= e($l->durum) ?></span>
                    <b><?= e($l->islem) ?></b> — <?= e($l->ozet ?: '') ?>
                    <small style="color:var(--muted)"><?= e(date('d.m.Y H:i', strtotime($l->zaman))) ?></small>
                    <?php if ($l->hata_mesaji): ?><br><small style="color:var(--danger)"><?= nl2br(e(mb_substr($l->hata_mesaji, 0, 200))) ?></small><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Hesap Bilgisi</h3></div>
            <div class="adm-kv"><span>Etiket</span><b><?= e($h->ad) ?></b></div>
            <div class="adm-kv"><span>Platform</span><b><?= e($plt) ?></b></div>
            <div class="adm-kv"><span>Supplier ID</span><b><?= e($h->supplier_id ?: '—') ?></b></div>
            <div class="adm-kv"><span>API Key</span><b class="mono"><?= $h->api_key ? '••şifreli•• (' . substr($h->api_key, 0, 6) . '…)' : '—' ?></b></div>
            <div class="adm-kv"><span>Durum</span><b><?= (int) $h->durum === 1 ? 'Aktif' : 'Pasif' ?></b></div>
            <div class="adm-kv"><span>Son senkron</span><b><?= $h->son_sin ? e(date('d.m.Y H:i', strtotime($h->son_sin))) : '—' ?></b></div>
            <small style="color:var(--stone)"><?php if ($h->platform !== 'trendyol'): ?>Bu platformun adapter'ı henüz yok (yalnız Trendyol). <?php endif; ?>Kimlikler CI Encryption ile şifreli saklanır; yalnızca API çağrısında çözülür.</small>
        </div>
    </div>
</div>
