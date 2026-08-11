<?php defined('BASEPATH') OR exit('No direct script access allowed');
$durum_rozet = array('0' => 'rozet-turuncu', '1' => 'rozet-yesil', '2' => 'rozet-gri');
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/bayiler') ?>">
    <input type="text" name="q" value="<?= e($filtre['q'] ?? '') ?>" placeholder="Firma, yetkili, e-posta…">
    <select name="durum">
        <option value="">Tüm durumlar</option>
        <?php foreach ($durumlar as $k => $ad): ?><option value="<?= e($k) ?>" <?= ($filtre['durum'] ?? '') === (string) $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filtrele</button>
    <span style="margin-left:auto;color:var(--steel);font-size:13px"><?= (int) $toplam ?> bayi</span>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Firma</th><th>Yetkili</th><th>E-posta</th><th>Grup</th><th>Durum</th><th class="sag">Sipariş</th></tr></thead>
        <tbody>
        <?php if (empty($bayiler)): ?><tr><td colspan="6" class="adm-bosluk">Bayi yok</td></tr><?php endif; ?>
        <?php foreach ($bayiler as $b): ?>
            <tr>
                <td><a class="b" href="<?= site_url('yonetim/bayiler/detay/' . $b->id) ?>"><?= e($b->firma_adi) ?></a><br><small><?= e($b->vergi_no ?: '') ?></small></td>
                <td><?= e($b->yetkili_ad_soyad) ?></td>
                <td><?= e($b->email) ?></td>
                <td><?= e($b->grup_ad ?: '-') ?> <?= $b->indirim_yuzde ? '<small>%' . number_format($b->indirim_yuzde, 0) . '</small>' : '' ?></td>
                <td><span class="rozet <?= e($durum_rozet[(string) $b->durum] ?? 'rozet-gri') ?>"><?= e($durumlar[(string) $b->durum] ?? '?') ?></span></td>
                <td class="sag"><?= e(date('d.m.Y', strtotime($b->olusturma_zaman))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
