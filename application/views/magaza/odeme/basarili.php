<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s = isset($sip) ? $sip : null;
if (! $s) { return; }
$islem = (float) $s->islem_ucreti;
$kargo = (float) $s->kargo_ucreti;
?>
<section class="kat-hero" style="background:var(--surface-feature);border-bottom:1px solid #b7e8cc">
    <div class="container center">
        <div class="basari-daire">✓</div>
        <h1 class="kat-baslik"><?= t('sonuc_alindi', 'Siparişiniz Alındı!') ?></h1>
        <p class="kat-alt"><?= t('sonuc_siparis_no', 'Sipariş no:') ?> <b>#<?= e($s->siparis_no) ?></b> · <?= e($s->odeme_yontemi) ?><?php if ($s->kargo_firma): ?> · <?= e($s->kargo_firma) ?><?php endif; ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="basari-grid">
            <div class="basari-ana">
                <div class="card card--feature">
                    <h3><?= t('sonuc_detaylar', 'Sipariş Detayları') ?></h3>
                    <table class="tablo-sepet" style="margin-top:12px">
                        <thead><tr><th><?= t('sepet_th_urun', 'Ürün') ?></th><th><?= t('sepet_th_varyant', 'Varyant') ?></th><th><?= t('sepet_th_adet', 'Adet') ?></th><th style="text-align:right"><?= t('sepet_th_tutar', 'Tutar') ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($s->detaylar as $d): ?>
                            <tr>
                                <td><?= e($d->urun_adi) ?><br><small class="mono text-steel"><?= e($d->stok_kodu) ?></small></td>
                                <td class="text-steel"><?= e($d->varyant_bilgi ?: '-') ?></td>
                                <td><?= (int) $d->adet ?></td>
                                <td style="text-align:right"><b><?= para_formatla($d->birim_fiyat * $d->adet, $s->para_birimi) ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="basari-ozet">
                        <div class="sepet-ozet-satr"><span><?= t('sepet_ara_toplam', 'Ara toplam') ?></span><span><?= para_formatla($s->ara_toplam, $s->para_birimi) ?></span></div>
                        <?php if ($islem > 0): ?><div class="sepet-ozet-satr"><span><?= t('hesap_islem_ucreti', 'İşlem ücreti') ?></span><span><?= para_formatla($islem, $s->para_birimi) ?></span></div><?php endif; ?>
                        <div class="sepet-ozet-satr"><span><?= t('sepet_kargo', 'Kargo') ?></span><span><?= $kargo > 0 ? para_formatla($kargo, $s->para_birimi) : t('sepet_ucretsiz', 'Ücretsiz') ?></span></div>
                        <div class="sepet-ozet-satr sepet-ozet-toplam"><span><?= t('sepet_toplam', 'Toplam') ?></span><span><?= para_formatla($s->toplam, $s->para_birimi) ?></span></div>
                    </div>
                </div>
