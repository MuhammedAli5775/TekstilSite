<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero" style="background:#fff3f3;border-bottom:1px solid #f0b8b8">
    <div class="container center">
        <div class="basari-daire" style="background:#f0b8b8;color:#fff">!</div>
        <h1 class="kat-baslik">Ödeme Başarısız</h1>
        <p class="kat-alt"><?php if (! empty($s)): ?>Sipariş no: <b>#<?= e($s->siparis_no) ?></b> · <?php endif; ?>Kartlı ödeme tamamlanamadı.</p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="card card--feature" style="max-width:520px;margin:auto">
            <p class="text-steel">Ödemeniz alınmadı. Kart bilgilerinizi kontrol edip tekrar deneyebilir veya havale/EFT ile ödeyebilirsiniz.</p>
            <?php if (! empty($s)): ?>
                <a class="btn btn-primary" href="<?= site_url('paytr/ode/' . $s->id) ?>">Tekrar Dene</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= site_url('') ?>">Ana Sayfa</a>
        </div>
    </div>
</section>
