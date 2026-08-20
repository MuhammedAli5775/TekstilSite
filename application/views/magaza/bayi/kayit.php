<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth-sarma">
    <div class="auth-kart">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <?php $this->load->view('magaza/partial/brand'); ?>
        </a>
        <h1 class="auth-baslik"><?= t('bayi_kayit_baslik', 'Bayi Hesabı Oluştur') ?></h1>
        <p class="auth-alt"><?= t('bayi_kayit_alt', 'Toptan fiyatlar, minimum sipariş ve XML/API erişimi için kaydolun.') ?></p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

        <form action="<?= site_url('bayi/kayit_kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <div class="odeme-alan"><label><?= t('bayi_yetkili', 'Ad Soyad / Yetkili') ?> <span class="zor">*</span></label><input type="text" name="yetkili_ad_soyad" value="<?= set_value('yetkili_ad_soyad') ?>" required maxlength="120"></div>
            <div class="odeme-alan"><label><?= t('odeme_firma_unvan', 'Firma Ünvanı') ?> <span class="zor">*</span></label><input type="text" name="firma_adi" value="<?= set_value('firma_adi') ?>" required maxlength="160"></div>
            <div class="odeme-alan-2">
                <div class="odeme-alan"><label><?= t('odeme_eposta', 'E-posta') ?> <span class="zor">*</span></label><input type="email" name="email" value="<?= set_value('email') ?>" required maxlength="150"></div>
                <div class="odeme-alan"><label><?= t('odeme_telefon', 'Telefon') ?> <span class="zor">*</span></label><input type="tel" name="telefon" value="<?= set_value('telefon') ?>" required maxlength="30"></div>
            </div>
            <div class="odeme-alan-2">
                <div class="odeme-alan"><label><?= t('odeme_vergi_no', 'Vergi / TC No') ?></label><input type="text" name="vergi_no" value="<?= set_value('vergi_no') ?>" maxlength="30"></div>
                <div class="odeme-alan"><label><?= t('bayi_vergi_dairesi', 'Vergi Dairesi') ?></label><input type="text" name="vergi_dairesi" value="<?= set_value('vergi_dairesi') ?>" maxlength="120"></div>
            </div>
            <div class="odeme-alan-2">
                <div class="odeme-alan"><label><?= t('auth_sifre', 'Şifre') ?> <span class="zor">*</span></label><input type="password" name="sifre" required minlength="6"></div>
                <div class="odeme-alan"><label><?= t('auth_sifre_tekrar', 'Şifre Tekrar') ?> <span class="zor">*</span></label><input type="password" name="sifre2" required></div>
            </div>
            <label class="odeme-check"><input type="checkbox" name="sozlesme" value="1" <?= set_checkbox('sozlesme', '1') ?>> <span><?= sprintf(t('bayi_sozlesme', '%1$s ve %2$s koşullarını onaylıyorum.'), '<a href="' . site_url('sayfa/mesafeli-satis') . '">' . e(t('bayi_sozlesme_1', 'Mesafeli satış')) . '</a>', '<a href="' . site_url('sayfa/gizlilik') . '">' . e(t('bayi_sozlesme_2', 'gizlilik')) . '</a>') ?> <span class="zor">*</span></span></label>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:14px"><?= t('auth_kayit_btn', 'Kayıt Ol') ?></button>
        </form>
        <p class="auth-link"><?= t('auth_hesap_var', 'Zaten hesabın var mı?') ?> <a href="<?= site_url('bayi/giris') ?>"><?= t('auth_giris_yap', 'Giriş yap →') ?></a></p>
    </div>
</section>
