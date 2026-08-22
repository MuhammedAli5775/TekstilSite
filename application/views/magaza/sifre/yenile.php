<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s_tip   = isset($s_tip) ? $s_tip : 'kullanici';
$s_token = isset($s_token) ? $s_token : '';
?>
<section class="auth-sarma">
    <div class="auth-kart auth-kart--dar">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <?php $this->load->view('magaza/partial/brand'); ?>
        </a>
        <h1 class="auth-baslik"><?= t('sifre_yenile_baslik', 'Yeni Şifre Belirle') ?></h1>
        <p class="auth-alt"><?= t('sifre_yenile_alt', 'Yeni şifrenizi girin; belirledikten sonra giriş yapabilirsiniz.') ?></p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>

        <form action="<?= site_url('sifre-yenile/' . $s_tip . '/' . $s_token) ?>" method="post">
            <?= csrf_field() ?>
            <div class="odeme-alan"><label><?= t('sifre_yeni', 'Yeni Şifre') ?></label><input type="password" name="sifre" minlength="6" required></div>
            <div class="odeme-alan"><label><?= t('sifre_yeni2', 'Yeni Şifre (tekrar)') ?></label><input type="password" name="sifre2" minlength="6" required></div>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:8px"><?= t('sifre_yenile_btn', 'Şifreyi Güncelle') ?></button>
        </form>
    </div>
</section>
