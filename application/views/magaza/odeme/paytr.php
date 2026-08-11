<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero" style="background:var(--surface-feature);border-bottom:1px solid #b7e8cc">
    <div class="container center">
        <?php if (! empty($bekle)): ?>
            <div class="basari-daire">&#10003;</div>
            <h1 class="kat-baslik">Ödemeniz Alınıyor</h1>
            <p class="kat-alt">Sipariş no: <b>#<?= e($s->siparis_no) ?></b> · Kartlı ödemeniz onaylanıyor.</p>
        <?php else: ?>
            <h1 class="kat-baslik">Kartla Ödeme</h1>
            <p class="kat-alt">Sipariş no: <b>#<?= e($s->siparis_no) ?></b> · Toplam: <b><?= para_tr($s->toplam) ?></b></p>
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
                <h3>Kartlı ödeme kullanılamıyor</h3>
                <p class="text-steel"><?= e($hata) ?></p>
                <p>Siparişiniz <b>#<?= e($s->siparis_no) ?></b> alındı (beklemede). Havale/EFT ile ödeyebilir veya bizimle iletişime geçebilirsiniz.</p>
                <a class="btn btn-primary" href="<?= site_url('katalog') ?>">Alışverişe Devam Et</a>
            </div>
        <?php else: ?>
            <div class="card card--feature" style="max-width:520px;margin:auto">
                <p class="text-steel">Siparişiniz <b>#<?= e($s->siparis_no) ?></b> için ödeme işleniyor. Onaylandığında e-posta/SMS ile bilgilendirileceksiniz.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
