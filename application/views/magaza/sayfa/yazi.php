<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <a href="<?= site_url('blog') ?>"><?= t('syf_blog_b', 'Blog') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= e($yazi->baslik) ?></span></nav>
        <h1 class="kat-baslik"><?= e($yazi->baslik) ?></h1>
        <?php if ($yazi->yayin_tarihi): ?><p class="text-steel" style="font-size:13px;margin-top:6px"><?= t('yazi_yayin', 'Yayın: %s', date('d.m.Y', strtotime($yazi->yayin_tarihi))) ?></p><?php endif; ?>
    </div>
</section>

<section class="section section--tight">
    <div class="container" style="max-width:760px">
        <?php if ($yazi->gorsel !== ''): ?>
            <img src="<?= e(gorsel_url($yazi->gorsel)) ?>" alt="<?= e($yazi->baslik) ?>" style="width:100%;border-radius:12px;margin-bottom:22px" loading="lazy">
        <?php endif; ?>
        <div class="prose">
            <?= $yazi->icerik /* admin HTML — CMS deseni (güvenilir kaynak) */ ?>
        </div>
        <p style="margin-top:28px"><a class="btn btn-secondary" href="<?= site_url('blog') ?>"><?= t('yazi_bloga_don', '← Blog\'a Dön') ?></a></p>
    </div>
</section>
