<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s_tip = isset($s_tip) ? $s_tip : 'kullanici';
?>
<section class="auth-sarma">
    <div class="auth-kart auth-kart--dar">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <?php $this->load->view('magaza/partial/brand'); ?>
        </a>
        <h1 class="auth-baslik"><?= t('sifre_unuttum_baslik', 'Şifre Sıfırlama') ?></h1>
        <p class="auth-alt"><?= t('sifre_unuttum_alt', 'Hesabınıza kayıtlı e-postayı girin; sıfırlama bağlantısı gönderilsin.') ?></p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>

        <form action="<?= site_url('sifremi-unuttum/' . $s_tip) ?>" method="post">
            <?= csrf_field() ?>
            <div class="odeme-alan"><label><?= t('odeme_eposta', 'E-posta') ?></label><input type="email" name="eposta" required></div>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:8px"><?= t('sifre_unuttum_btn', 'Sıfırlama Bağlantısı Gönder') ?></button>
        </form>
        <p class="auth-link"><small><a href="<?= site_url(($s_tip === 'bayi') ? 'bayi/giris' : 'kullanici/giris') ?>"><?= t('sifre_girise_don', '← Girişe dön') ?></a></small></p>
    </div>
</section>
