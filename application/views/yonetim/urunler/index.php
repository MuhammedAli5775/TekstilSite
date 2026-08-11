<?php defined('BASEPATH') OR exit('No direct script access allowed');
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Ürünler <small style="color:var(--steel);font-weight:400">(<?= (int) $toplam ?>)</small></h2>
    <a class="btn btn-primary btn-sm" href="<?= site_url('yonetim/urunler/ekle') ?>">+ Yeni Ürün</a>
</div>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/urunler') ?>">
    <input type="text" name="q" value="<?= e($filtre['q'] ?? '') ?>" placeholder="Ürün adı veya stok kodu…">
    <select name="kategori_id">
        <option value="">Tüm kategoriler</option>
        <?php foreach ($kategoriler as $k): ?><option value="<?= (int) $k->id ?>" <?= ($filtre['kategori_id'] ?? '') === (string) $k->id ? 'selected' : '' ?>><?= str_repeat('— ', $k->ust_id ? 1 : 0) . e($k->ad) ?></option><?php endforeach; ?>
    </select>
    <select name="durum">
        <option value="">Tüm durumlar</option>
        <option value="1" <?= ($filtre['durum'] ?? '') === '1' ? 'selected' : '' ?>>Aktif</option>
        <option value="0" <?= ($filtre['durum'] ?? '') === '0' ? 'selected' : '' ?>>Pasif</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filtrele</button>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Görsel</th><th>Ürün</th><th>Stok Kodu</th><th>Kategori</th><th class="sag">Fiyat</th><th>MOQ</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($urunler)): ?><tr><td colspan="8" class="adm-bosluk">Ürün bulunamadı</td></tr><?php endif; ?>
        <?php foreach ($urunler as $u): ?>
            <tr>
                <td><img src="<?= e(gorsel_url($u->ana_gorsel)) ?>" alt="" style="width:38px;height:48px;object-fit:cover;border-radius:4px;background:var(--surface)"></td>
                <td><a class="b" href="<?= site_url('yonetim/urunler/duzenle/' . $u->id) ?>"><?= e($u->ad) ?></a><?= $u->vitrin ? ' <small class="rozet rozet-yesil">vitrin</small>' : '' ?></td>
                <td class="mono"><?= e($u->stok_kodu) ?></td>
                <td><?= e($u->kategori ?: '-') ?></td>
                <td class="sag"><?= para_tr($u->fiyat) ?></td>
                <td><?= (int) $u->moq ?></td>
                <td><span class="rozet <?= $u->durum ? 'rozet-yesil' : 'rozet-gri' ?>"><?= $u->durum ? 'Aktif' : 'Pasif' ?></span></td>
                <td class="sag"><a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/urunler/duzenle/' . $u->id) ?>">Düzenle</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($sayfa_sayisi > 1): ?>
<nav class="adm-sayfalama">
    <?php $qs = $_GET; for ($i = 1; $i <= $sayfa_sayisi; $i++): $qs['sayfa'] = $i; $url = site_url('yonetim/urunler') . '?' . http_build_query($qs); ?>
        <?php if ($i == $sayfa): ?><span class="aktif"><?= $i ?></span><?php else: ?><a href="<?= e($url) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
