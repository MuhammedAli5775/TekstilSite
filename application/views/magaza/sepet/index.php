<?php defined('BASEPATH') OR exit('No direct script access allowed');
$satirlar = isset($satirlar) ? $satirlar : array();
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <span class="simdiki">Sepetim</span></nav>
        <h1 class="kat-baslik">Sepetim</h1>
    </div>
</section>

<section class="section section--tight"><div class="container">
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>

<?php if (empty($satirlar)): ?>
    <div class="odeme-kart" style="text-align:center;padding:48px">
        <p style="font-size:18px;margin-bottom:16px">Sepetiniz boş.</p>
        <a class="btn btn-primary" href="<?= site_url('katalog') ?>">Alışverişe Başla →</a>
    </div>
<?php else: ?>
    <div class="sepet-layout">
        <div class="odeme-kart" style="margin:0">
            <table class="tablo-sepet">
                <thead><tr><th>Ürün</th><th>Varyant</th><th>Adet</th><th class="sag">Birim</th><th class="sag">Tutar</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($satirlar as $r): ?>
                    <tr>
                        <td>
                            <div style="display:flex;gap:12px;align-items:center">
                                <img src="<?= e(gorsel_url($r->ana_gorsel)) ?>" alt="" class="sepet-gorsel">
                                <div><a class="b" href="<?= site_url('urun/' . $r->slug) ?>"><?= e($r->ad) ?></a><br><small class="mono text-steel"><?= e($r->stok_kodu) ?></small></div>
                            </div>
                        </td>
                        <td><?= e(trim(((string) ($r->renk ?? '')) . ' ' . ((string) ($r->beden ?? '')))) ?: '-' ?></td>
                        <td>
                            <form method="post" action="<?= site_url('sepet/guncelle/' . $r->sepet_id) ?>" style="display:flex;gap:4px;align-items:center">
                                <?= csrf_field() ?>
                                <input type="number" name="adet" value="<?= (int) $r->adet ?>" min="<?= (int) $r->moq ?>" style="width:64px;height:34px;padding:0 6px;border:1px solid var(--hairline-strong);border-radius:6px;text-align:center">
                                <button type="submit" class="btn btn-ghost btn-sm">Güncelle</button>
                            </form>
                            <small class="text-steel">MOQ <?= (int) $r->moq ?> · adım <?= (int) $r->birim_adim ?></small>
                        </td>
                        <td class="sag"><?= para_tr($r->birim) ?></td>
                        <td class="sag"><b><?= para_tr($r->ara) ?></b></td>
                        <td><a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('sepet/sil/' . $r->sepet_id) ?>" onclick="return confirm('Ürün çıkarılsın mı?')">Sil</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div>
            <div class="sepet-ozet">
                <h3 style="margin-bottom:12px">Özet</h3>
                <div class="sepet-ozet-satr"><span>Ara toplam</span><span><?= para_tr($ara_toplam) ?></span></div>
                <div class="sepet-ozet-satr"><span>Kargo</span><span><?= $ara_toplam >= $esik ? 'Ücretsiz' : 'Ödeme adımında' ?></span></div>
                <?php if ($ara_toplam < $esik): ?>
                    <div class="sepet-ozet-satr" style="font-size:12px;color:var(--steel)"><span><?= para_tr($esik - $ara_toplam) ?> daha → ücretsiz kargo</span><span></span></div>
                <?php endif; ?>
                <div class="sepet-ozet-toplam"><span>Toplam</span><span><?= para_tr($ara_toplam) ?></span></div>
                <a class="btn btn-primary btn--block" style="margin-top:16px" href="<?= site_url('odeme') ?>">Ödemeye Geç →</a>
                <a class="btn btn-ghost btn--block" style="margin-top:8px" href="<?= site_url('katalog') ?>">Alışverişe Devam</a>
            </div>
        </div>
    </div>
<?php endif; ?>
</div></section>
