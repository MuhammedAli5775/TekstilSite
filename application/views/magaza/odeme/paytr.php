<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero" style="background:var(--surface-feature);border-bottom:1px solid #b7e8cc">
    <div class="container center">
        <?php if (! empty($bekle)): ?>
            <div class="basari-daire">&#10003;</div>
            <h1 class="kat-baslik"><?= t('paytr_aliniyor', 'Ödemeniz Alınıyor') ?></h1>
            <p class="kat-alt"><?= t('sonuc_siparis_no', 'Sipariş no:') ?> <b>#<?= e($s->siparis_no) ?></b> · <?= t('paytr_onaylaniyor', 'Kartlı ödemeniz onaylanıyor.') ?></p>
        <?php else: ?>
            <h1 class="kat-baslik"><?= t('paytr_kartla', 'Kartla Ödeme') ?></h1>
            <p class="kat-alt"><?= t('sonuc_siparis_no', 'Sipariş no:') ?> <b>#<?= e($s->siparis_no) ?></b> · <?= t('detay_toplam', 'Toplam:') ?> <b><?= para_formatla($s->toplam, $s->para_birimi) ?></b></p>
        <?php endif; ?>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if (! empty($token)): ?>
            <div class="card card--feature" style="max-width:520px;margin:auto">
                <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                <iframe src="https://www.paytr.com/odeme/guvenli/<?= e($token) ?>" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;min-height:680px;border:0"></iframe>
                <script>iFrameResize({}, '#paytriframe');</script>
            </div>
        <?php elseif (! empty($hata)): ?>
            <div class="card card--feature" style="max-width:520px;margin:auto">
                <h3><?= t('paytr_kullanilamaz', 'Kartlı ödeme kullanılamıyor') ?></h3>
                <p class="text-steel"><?= e($hata) ?></p>
                <p><?= t('paytr_hata_not', 'Siparişiniz #%s alındı (beklemede). Havale/EFT ile ödeyebilir veya bizimle iletişime geçebilirsiniz.', e($s->siparis_no)) ?></p>
                <a class="btn btn-primary" href="<?= site_url('katalog') ?>"><?= t('paytr_devam', 'Alışverişe Devam Et') ?></a>
            </div>
        <?php else: ?>
            <div class="card card--feature" style="max-width:520px;margin:auto">
                <p class="text-steel"><?= t('paytr_isleniyor', 'Siparişiniz #%s için ödeme işleniyor. Onaylandığında e-posta/SMS ile bilgilendirileceksiniz.', e($s->siparis_no)) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>
