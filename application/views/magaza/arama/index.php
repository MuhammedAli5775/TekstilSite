<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <h1 class="kat-baslik"><?= t('arama_baslik', 'Arama') ?><?= $q !== '' ? ': <span class="arama-sorgu">' . e($q) . '</span>' : '' ?></h1>
        <p class="kat-alt"><?= $q !== '' ? t('arama_sonuc', '%s sonuç bulundu', (int) $toplam) : t('arama_yonlendirme', 'Aramak istediğiniz ürünü yazın') ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if (! empty($sonuc)): ?>
            <div class="prodgrid">
                <?php foreach ($sonuc as $u): ?>
                    <?php $this->load->view('magaza/partial/urun_karti', array('urun' => $u)); ?>
                <?php endforeach; ?>
            </div>
            <?php $this->load->view('magaza/partial/sayfalama', array('sayfa' => $sayfa, 'sayfa_sayisi' => $sayfa_sayisi)); ?>
        <?php elseif ($q !== ''): ?>
            <div class="notice">
                <?= e(t('arama_bos', '"%s" için sonuç bulunamadı. Farklı bir kelime ya da stok kodu deneyin.', $q)) ?>
            </div>
            <p style="margin-top:20px">
                <a class="btn btn-secondary" href="<?= site_url('katalog') ?>"><?= t('arama_tumu', 'Tüm ürünlere göz at →') ?></a>
            </p>
        <?php endif; ?>
    </div>
</section>
