<?php defined('BASEPATH') OR exit('No direct script access allowed');
$de = durum_etiket($s->durum);
$detaylar = isset($s->detaylar) ? $s->detaylar : array();
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>">Hesabım</a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim/siparisler') ?>">Siparişler</a> <span class="ayrac">/</span> <span class="simdiki"><?= e($s->siparis_no) ?></span></nav>
        <h1 class="kat-baslik">Sipariş <?= e($s->siparis_no) ?> <span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></h1>
    </div>
</section>

<section class="section section--tight"><div class="container hesabim-grid">
    <?php $this->load->view('magaza/hesabim/_menu'); ?>
    <div class="hesabim-main">
        <div class="odeme-kart" style="margin-bottom:16px">
            <table class="tablo-sepet">
                <thead><tr><th>Ürün</th><th>Varyant</th><th class="sag">Adet</th><th class="sag">Tutar</th></tr></thead>
                <tbody>
                <?php foreach ($detaylar as $d): ?>
                    <tr>
                        <td><?= e($d->urun_adi) ?><br><small class="mono text-steel"><?= e($d->stok_kodu) ?></small></td>
                        <td><?= e($d->varyant_bilgi ?: '-') ?></td>
                        <td class="sag"><?= (int) $d->adet ?></td>
                        <td class="sag"><?= para_formatla($d->birim_fiyat * $d->adet, $s->para_birimi) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="sepet-ozet">
            <div class="sepet-ozet-satr"><span>Tarih</span><span><?= e(date('d.m.Y H:i', strtotime($s->olusturma_zaman))) ?></span></div>
            <div class="sepet-ozet-satr"><span>Para birimi</span><span><?= e($s->para_birimi) ?> (kur <?= e($s->kur) ?>)</span></div>
            <div class="sepet-ozet-satr"><span>Ara toplam</span><span><?= para_formatla($s->ara_toplam, $s->para_birimi) ?></span></div>
            <?php if ((float) $s->islem_ucreti > 0): ?><div class="sepet-ozet-satr"><span>İşlem ücreti</span><span><?= para_formatla($s->islem_ucreti, $s->para_birimi) ?></span></div><?php endif; ?>
            <div class="sepet-ozet-satr"><span>Kargo</span><span><?= (float) $s->kargo_ucreti > 0 ? para_formatla($s->kargo_ucreti, $s->para_birimi) : 'Ücretsiz' ?></span></div>
            <div class="sepet-ozet-toplam"><span>Toplam</span><span><?= para_formatla($s->toplam, $s->para_birimi) ?></span></div>
            <?php if (! empty($s->kargo_takip_no)): ?><div class="sepet-ozet-satr"><span>Kargo Takip</span><span><?= e($s->kargo_firma ?: '') ?> · <?= e($s->kargo_takip_no) ?></span></div><?php endif; ?>
        </div>

        <div class="odeme-kart" style="margin-top:16px">
            <h3 style="margin-bottom:8px">Teslimat Adresi</h3>
            <p style="font-size:14px;color:var(--slate);line-height:1.6"><?= e($s->teslimat_ad) ?><br><?= nl2br(e($s->teslimat_adres)) ?><br><?= e(((string) ($s->teslimat_il ?? '')) . ' ' . ((string) ($s->teslimat_ilce ?? ''))) ?><br><?= e($s->teslimat_telefon) ?></p>
        </div>
    </div>
</div></section>
