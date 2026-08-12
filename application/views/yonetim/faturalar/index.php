<?php defined('BASEPATH') OR exit('No direct script access allowed');
$durum_map = array(
    'bekliyor'   => array('Bekliyor', 'gri'),
    'isleniyor'  => array('İşleniyor', 'mavi'),
    'olustu'     => array('Oluştu', 'yesil'),
    'gonderildi' => array('Gönderildi', 'yesil'),
    'reddedildi' => array('Reddedildi', 'kirmizi'),
    'iptal'      => array('İptal', 'gri'),
);
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Faturalar</h2>
</div>

<div class="adm-card adm-card--p0">
    <div class="adm-card-baslik" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <h3>Tüm Faturalar (<?= count($faturalar) ?>)</h3>
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <select name="durum" onchange="this.form.submit()">
                <option value="">Tüm durumlar</option>
                <?php foreach ($durum_map as $k => $dm): ?>
                    <option value="<?= e($k) ?>" <?= ($filtre['durum'] ?? '') === $k ? 'selected' : '' ?>><?= e($dm[0]) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="q" value="<?= e($filtre['q'] ?? '') ?>" placeholder="Fatura / ETN / sipariş / alıcı">
            <button type="submit" class="btn btn-ghost btn-sm">Ara</button>
        </form>
    </div>
    <div class="adm-tbl-sar">
        <table class="adm-tbl">
            <thead><tr><th>Fatura</th><th>Sipariş</th><th>Alıcı</th><th>Tip</th><th>Durum</th><th class="sag">Tutar</th><th>Tarih</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($faturalar)): ?>
                <tr><td colspan="8" class="adm-bosluk">Henüz fatura yok. Sipariş detayından “Fatura Kes” ile oluşturun.</td></tr>
            <?php endif; ?>
            <?php foreach ($faturalar as $f): $dm = $durum_map[$f->durum] ?? array($f->durum, 'gri'); ?>
                <tr>
                    <td><b>#<?= (int) $f->id ?></b><br><small class="mono"><?= e($f->fatura_no ?: '-') ?></small><?php if ($f->etn): ?><br><small class="mono">ETN: <?= e($f->etn) ?></small><?php endif; ?></td>
                    <td><a href="<?= site_url('yonetim/siparisler/detay/' . $f->siparis_id) ?>"><?= e($f->siparis_no ?: '#' . $f->siparis_id) ?></a></td>
                    <td><?= e($f->alici_unvan ?: '-') ?><br><small class="mono"><?= e($f->alici_vkn ?: '-') ?></small></td>
                    <td><?= $f->tip === 'efatura' ? 'e-Fatura' : 'e-Arşiv' ?></td>
                    <td><span class="rozet rozet-<?= e($dm[1]) ?>"><?= e($dm[0]) ?></span></td>
                    <td class="sag"><?= para_tr($f->toplam) ?></td>
                    <td><small><?= e(date('d.m.Y H:i', strtotime($f->olusturma_zaman))) ?></small></td>
                    <td><a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/faturalar/detay/' . $f->id) ?>">Detay</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
