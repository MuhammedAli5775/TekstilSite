<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>">Hesabım</a> <span class="ayrac">/</span> <span class="simdiki">Şifre Değiştir</span></nav>
        <h1 class="kat-baslik">Şifre Değiştir</h1>
    </div>
</section>

<section class="section section--tight">
    <div class="container hesabim-grid">
        <?php $this->load->view('magaza/hesabim/_menu'); ?>
        <div class="hesabim-main">
            <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
            <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

            <form class="odeme-kart" action="<?= site_url('hesabim/sifre/kaydet') ?>" method="post" style="margin:0;max-width:460px">
                <?= csrf_field() ?>
                <legend style="padding:0 0 12px;font-weight:600;font-size:16px">Yeni Şifre Belirle</legend>
                <div class="odeme-alan"><label>Mevcut Şifre <span class="zor">*</span></label><input type="password" name="eski" required></div>
                <div class="odeme-alan"><label>Yeni Şifre <span class="zor">*</span></label><input type="password" name="yeni" required minlength="6"><small class="text-steel">En az 6 karakter.</small></div>
                <div class="odeme-alan"><label>Yeni Şifre (tekrar) <span class="zor">*</span></label><input type="password" name="yeni2" required></div>
                <button type="submit" class="btn btn-primary" style="margin-top:16px">Şifreyi Güncelle</button>
            </form>
        </div>
    </div>
</section>
