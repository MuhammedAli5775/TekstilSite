<?php defined('BASEPATH') OR exit('No direct script access allowed');
$qs = $_GET; unset($qs['sayfa']);
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/siparisler') ?>">
    <input type="text" name="q" value="<?= e($filtre['q'] ?? '') ?>" placeholder="Sipariş no, bayi, e-posta…">
    <select name="durum">
        <option value="">Tüm durumlar</option>
        <?php foreach ($durumlar as $k => $ad): ?><option value="<?= e($k) ?>" <?= ($filtre['durum'] ?? '') === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filtrele</button>
    <span style="margin-left:auto;color:var(--steel);font-size:13px"><?= (int) $toplam ?> sipariş</span>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Sipariş</th><th>Tarih</th><th>Bayi</th><th>Ödeme</th><th>Durum</th><th class="sag">Toplam</th></tr></thead>
        <tbody>
        <?php if (empty($siparisler)): ?><tr><td colspan="6" class="adm-bosluk">Sipariş bulunamadı</td></tr><?php endif; ?>
        <?php foreach ($siparisler as $s): $de = durum_etiket($s->durum); ?>
            <tr>
                <td><a class="b" href="<?= site_url('yonetim/siparisler/detay/' . $s->id) ?>"><?= e($s->siparis_no) ?></a></td>
                <td><?= e(date('d.m.Y H:i', strtotime($s->olusturma_zaman))) ?></td>
                <td><?= e($s->firma_adi ?: ($s->yetkili_ad_soyad ?: ($s->teslimat_ad ?: 'Misafir'))) ?><br><small><?= e($s->bayi_email ?: $s->email) ?></small></td>
                <td><?= e($s->odeme_yontemi) ?></td>
                <td><span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></td>
                <td class="sag"><b><?= para_formatla($s->toplam, $s->para_birimi) ?></b></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($sayfa_sayisi > 1): ?>
<nav class="adm-sayfalama">
    <?php for ($i = 1; $i <= $sayfa_sayisi; $i++): $q = $qs; $q['sayfa'] = $i; $u = site_url('yonetim/siparisler') . '?' . http_build_query($q); ?>
        <?php if ($i == $sayfa): ?><span class="aktif"><?= $i ?></span><?php else: ?><a href="<?= e($u) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
