<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('syf_blog_b', 'Blog') ?></span></nav>
        <h1 class="kat-baslik"><?= t('syf_blog_b', 'Blog') ?></h1>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if (empty($yazilar)): ?>
            <div class="card card--feature">
                <p class="text-steel"><?= t('yazi_yok', 'Henüz blog yazısı yok.') ?></p>
                <a class="btn btn-primary" href="<?= site_url('katalog') ?>"><?= t('syf_kataloga_gozat_ok', 'Kataloğa Göz At →') ?></a>
            </div>
        <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($yazilar as $y): ?>
                    <article class="card blog-kart">
                        <?php if ($y->gorsel !== ''): ?>
                            <a class="blog-kart__gorsel" href="<?= site_url('blog/' . $y->slug) ?>">
                                <img src="<?= e(gorsel_url($y->gorsel)) ?>" alt="<?= e($y->baslik) ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <div class="blog-kart__govde">
                            <?php if ($y->yayin_tarihi): ?><small class="text-steel"><?= t('yazi_yayin', 'Yayın: %s', date('d.m.Y', strtotime($y->yayin_tarihi))) ?></small><?php endif; ?>
                            <h3><a href="<?= site_url('blog/' . $y->slug) ?>"><?= e($y->baslik) ?></a></h3>
                            <p class="text-steel"><?= e($y->ozet) ?></p>
                            <a class="btn btn-secondary" href="<?= site_url('blog/' . $y->slug) ?>"><?= t('yazi_oku', 'Devamını Oku →') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
