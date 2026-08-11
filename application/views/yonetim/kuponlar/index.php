<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Kuponlar</h2>
    <a class="btn btn-primary btn-sm" href="<?= site_url('yonetim/kuponlar/ekle') ?>">+ Yeni Kupon</a>
</div>

<form class="adm-arama" method="get" action="<?= site_url('yonetim/kuponlar') ?>">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Kod ara…">
    <button type="submit" class="btn btn-secondary">Ara</button>
</form>

<div class="adm-tbl-sar">
    <table class="adm-tbl">
        <thead><tr><th>Kod</th><th>İndirim</th><th>Min. Sepet</th><th>Geçerlilik</th><th>Kullanım</th><th>Durum</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($kuponlar)): ?><tr><td colspan="7" class="adm-bosluk">Kupon yok</td></tr><?php endif; ?>
        <?php foreach ($kuponlar as $k):
            $simdi = date('Y-m-d H:i:s');
            $sure = (! $k->baslangic_zaman || $simdi >= $k->baslangic_zaman) && (! $k->bitis_zaman || $simdi <= $k->bitis_zaman);
            $limit_ok = (! $k->kullanim_limiti || (int) $k->kullanim_sayisi < (int) $k->kullanim_limiti);
        ?>
            <tr>
                <td><b class="mono"><?= e($k->kod) ?></b><?php if ($k->aciklama): ?><br><small class="text-steel"><?= e($k->aciklama) ?></small><?php endif; ?></td>
                <td><?= $k->tip === 'sabit' ? para_tr((float) $k->deger) : '%' . number_format((float) $k->deger, 0) ?><?= $k->max_indirim > 0 ? '<br><small class="text-steel">maks ' . para_tr((float) $k->max_indirim) . '</small>' : '' ?></td>
                <td><?= $k->min_sepet_tutar > 0 ? para_tr((float) $k->min_sepet_tutar) : '—' ?></td>
                <td><small><?= ($k->baslangic_zaman ? date('d.m.Y', strtotime($k->baslangic_zaman)) : '—') . ' → ' . ($k->bitis_zaman ? date('d.m.Y', strtotime($k->bitis_zaman)) : '∞') ?></small></td>
                <td><?= (int) $k->kullanim_sayisi . ($k->kullanim_limiti > 0 ? ' / ' . (int) $k->kullanim_limiti : '') ?></td>
                <td><?= (int) $k->durum !== 1 ? '<span class="rozet rozet-gri">pasif</span>' : ($sure && $limit_ok ? '<span class="rozet rozet-yesil">aktif</span>' : '<span class="rozet rozet-sari">süresi/limiti dolmuş</span>') ?></td>
                <td>
                    <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/kuponlar/duzenle/' . $k->id) ?>">Düzenle</a>
                    <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/kuponlar/sil/' . $k->id) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>