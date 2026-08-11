<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s = isset($sip) ? $sip : null;
if (! $s) { return; }
$islem = (float) $s->islem_ucreti;
$kargo = (float) $s->kargo_ucreti;
?>
<section class="kat-hero" style="background:var(--surface-feature);border-bottom:1px solid #b7e8cc">
    <div class="container center">
        <div class="basari-daire">✓</div>
        <h1 class="kat-baslik">Siparişiniz Alındı!</h1>
        <p class="kat-alt">Sipariş no: <b>#<?= e($s->siparis_no) ?></b> · <?= e($s->odeme_yontemi) ?><?php if ($s->kargo_firma): ?> · <?= e($s->kargo_firma) ?><?php endif; ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="basari-grid">
            <div class="basari-ana">
                <div class="card card--feature">
                    <h3>Sipariş Detayları</h3>
                    <table class="tablo-sepet" style="margin-top:12px">
                        <thead><tr><th>Ürün</th><th>Varyant</th><th>Adet</th><th style="text-align:right">Tutar</th></tr></thead>
                        <tbody>
                        <?php foreach ($s->detaylar as $d): ?>
                            <tr>
                                <td><?= e($d->urun_adi) ?><br><small class="mono text-steel"><?= e($d->stok_kodu) ?></small></td>
                                <td class="text-steel"><?= e($d->varyant_bilgi ?: '-') ?></td>
                                <td><?= (int) $d->adet ?></td>
                                <td style="text-align:right"><b><?= para_tr($d->birim_fiyat * $d->adet) ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="basari-ozet">
                        <div class="sepet-ozet-satr"><span>Ara toplam</span><span><?= para_tr($s->ara_toplam) ?></span></div>
                        <?php if ($islem > 0): ?><div class="sepet-ozet-satr"><span>İşlem ücreti</span><span><?= para_tr($islem) ?></span></div><?php endif; ?>
                        <div class="sepet-ozet-satr"><span>Kargo</span><span><?= $kargo > 0 ? para_tr($kargo) : 'Ücretsiz' ?></span></div>
                        <div class="sepet-ozet-satr sepet-ozet-toplam"><span>Toplam</span><span><?= para_tr($s->toplam) ?></span></div>
                    </div>
                </div>