<?php defined('BASEPATH') OR exit('No direct script access allowed');
$donus = isset($donus) ? $donus : '';
?>
<section class="auth-sarma">
    <div class="auth-kart auth-kart--dar">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <?php $this->load->view('magaza/partial/brand'); ?>
        </a>
        <h1 class="auth-baslik"><?= t('kul_giris_baslik', 'Kullanıcı Girişi') ?></h1>
        <p class="auth-alt"><?= t('kul_giris_alt', 'Siparişlerinizi takip etmek için giriş yapın.') ?></p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>
        <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

        <form action="<?= site_url('kullanici/giris_yap') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="donus" value="<?= e($donus) ?>">
            <div class="odeme-alan"><label><?= t('odeme_eposta', 'E-posta') ?></label><input type="email" name="email" value="<?= set_value('email') ?>" required></div>
            <div class="odeme-alan"><label><?= t('auth_sifre', 'Şifre') ?></label><input type="password" name="sifre" required></div>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:8px"><?= t('auth_giris_btn', 'Giriş Yap') ?></button>
        </form>
        <p class="auth-link"><small><a href="<?= site_url('sifremi-unuttum/kullanici') ?>"><?= t('sifre_unuttum_link', 'Şifremi unuttum?') ?></a></small></p>
        <p class="auth-link"><?= t('auth_hesap_yok', 'Hesabın yok mu?') ?> <a href="<?= site_url('kullanici/kayit') ?>"><?= t('kul_kayit_ol', 'Kayıt ol →') ?></a></p>
        <p class="auth-link"><small><?= t('kul_bayi_misiniz', 'Bayi misiniz?') ?> <a href="<?= site_url('bayi/giris') ?>"><?= t('kul_bayi_girisi', 'Bayi girişi') ?></a></small></p>
    </div>
</section>
