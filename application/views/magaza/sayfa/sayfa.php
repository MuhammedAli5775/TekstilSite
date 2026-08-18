<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= e($sayfa->baslik) ?></span></nav>
        <h1 class="kat-baslik"><?= e($sayfa->baslik) ?></h1>
    </div>
</section>

<section class="section section--tight">
    <div class="container" style="max-width:760px">
        <div class="prose">
            <?= $sayfa->icerik /* CMS içeriği — admin HTML, güvenilir */ ?>
        </div>
    </div>
</section>
