<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('syf_takip_b', 'Sipariş Takibi') ?></span></nav>
        <h1 class="kat-baslik"><?= t('syf_takip_b', 'Sipariş Takibi') ?></h1>
        <p class="kat-alt"><?= t('syf_takip_alt', 'Sipariş no ve e-postanızla siparişinizi bulun.') ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container" style="max-width:720px">
        <div class="card card--feature">
            <form method="post" action="<?= site_url('siparis-takip') ?>">
                <?= csrf_field() ?>
                <div class="odeme-alan"><label><?= t('syf_takip_no', 'Sipariş No') ?></label><input type="text" name="siparis_no" value="<?= e(set_value('siparis_no')) ?>" placeholder="<?= e(t('syf_takip_ph', 'örn. TS26080866AC97')) ?>" required></div>
                <div class="odeme-alan"><label><?= t('syf_takip_eposta', 'Siparişi Veren E-posta') ?></label><input type="email" name="email" value="<?= e(set_value('email')) ?>" required></div>
                <button class="btn btn-primary"><?= t('syf_takip_btn', 'Siparişimi Bul') ?></button>
            </form>
            <?php if (! empty($hata)): ?><div class="notice notice--warn" style="margin-top:12px"><?= e($hata) ?></div><?php endif; ?>
        </div>

        <?php if (! empty($siparis)): $s = $siparis; $de = durum_etiket($s->durum); ?>
            <div class="card card--feature" style="margin-top:16px">
                <h3><?= t('hesap_siparis_b', 'Sipariş %s', '#' . e($s->siparis_no)) ?></h3>
                <p>
                    <?= t('syf_takip_durum', 'Durum:') ?> <b><?= e($de[0]) ?></b> ·
                    <?= t('syf_takip_odeme', 'Ödeme:') ?> <b><?= e(t('odurum_' . $s->odeme_durumu, (string) $s->odeme_durumu)) ?></b> ·
                    <?= t('syf_takip_tarih', 'Tarih:') ?> <?= e(date('d.m.Y', strtotime($s->olusturma_zaman))) ?>
                </p>
                <table class="tablo-sepet" style="margin-top:12px">
                    <thead><tr><th><?= t('sepet_th_urun', 'Ürün') ?></th><th class="sag"><?= t('sepet_th_adet', 'Adet') ?></th><th class="sag"><?= t('sepet_th_tutar', 'Tutar') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($s->detaylar as $d): ?>
                        <tr>
                            <td><?= e($d->urun_adi) ?></td>
                            <td class="sag"><?= (int) $d->adet ?></td>
                            <td class="sag"><?= para_formatla($d->ara_toplam, $s->para_birimi) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><td colspan="2" class="sag"><b><?= t('sepet_toplam', 'Toplam') ?></b></td><td class="sag"><b><?= para_formatla($s->toplam, $s->para_birimi) ?></b></td></tr></tfoot>
                </table>
                <?php if (! empty($s->kargo_takip_no)): ?>
                    <p style="margin-top:12px">📦 <?= t('syf_takip_kargo', 'Kargo takip no: %s', e($s->kargo_takip_no)) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
