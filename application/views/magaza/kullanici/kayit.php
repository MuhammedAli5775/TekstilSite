<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth-sarma">
    <div class="auth-kart auth-kart--dar">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <svg class="brand__leaf" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2C9 6 5 12 5 19a11 11 0 0 0 22 0c0-7-4-13-11-17Z" fill="#00ed64"/><path d="M16 8c-4 3-6 7-6 11a6 6 0 0 0 12 0c0-4-2-8-6-11Z" fill="#001e2b"/></svg>
            <span>TekstilSite</span>
        </a>
        <h1 class="auth-baslik"><?= t('kul_kayit_baslik', 'Kullanıcı Hesabı Oluştur') ?></h1>
        <p class="auth-alt"><?= t('kul_kayit_alt', 'Sipariş geçmişinizi takip etmek için kişisel hesap açın.') ?></p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

        <form action="<?= site_url('kullanici/kayit_kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <div class="odeme-alan"><label><?= t('odeme_ad_soyad', 'Ad Soyad') ?> <span class="zor">*</span></label><input type="text" name="ad_soyad" value="<?= set_value('ad_soyad') ?>" required maxlength="120"></div>
            <div class="odeme-alan"><label><?= t('kul_kullanici_adi', 'Kullanıcı Adı') ?> <span class="zor">*</span></label><input type="text" name="kullanici_adi" value="<?= set_value('kullanici_adi') ?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_-]+" title="<?= t('kul_kullanici_adi_title', 'Harf, rakam, tire (-) ve alt çizgi (_)') ?>" placeholder="<?= e(t('kul_kullanici_adi_ph', 'ör. ayse_yilmaz')) ?>"></div>
            <div class="odeme-alan"><label><?= t('odeme_eposta', 'E-posta') ?> <span class="zor">*</span></label><input type="email" name="email" value="<?= set_value('email') ?>" required maxlength="150"></div>
            <div class="odeme-alan"><label><?= t('odeme_telefon', 'Telefon') ?></label><input type="tel" name="telefon" value="<?= set_value('telefon') ?>" maxlength="30"></div>
            <div class="odeme-alan-2">
                <div class="odeme-alan"><label><?= t('auth_sifre', 'Şifre') ?> <span class="zor">*</span></label><input type="password" name="sifre" required minlength="6"></div>
                <div class="odeme-alan"><label><?= t('auth_sifre_tekrar', 'Şifre Tekrar') ?> <span class="zor">*</span></label><input type="password" name="sifre2" required></div>
            </div>
            <label class="odeme-check"><input type="checkbox" name="sozlesme" value="1" <?= set_checkbox('sozlesme', '1') ?>> <span><?= sprintf(t('bayi_sozlesme', '%1$s ve %2$s koşullarını onaylıyorum.'), '<a href="' . site_url('sayfa/mesafeli-satis') . '">' . e(t('bayi_sozlesme_1', 'Mesafeli satış')) . '</a>', '<a href="' . site_url('sayfa/gizlilik') . '">' . e(t('bayi_sozlesme_2', 'gizlilik')) . '</a>') ?> <span class="zor">*</span></span></label>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:14px"><?= t('auth_kayit_btn', 'Kayıt Ol') ?></button>
        </form>
        <p class="auth-link"><?= t('auth_hesap_var', 'Zaten hesabın var mı?') ?> <a href="<?= site_url('kullanici/giris') ?>"><?= t('auth_giris_yap', 'Giriş yap →') ?></a></p>
        <p class="auth-link"><small><?= t('kul_firma_misin', 'Toptan almak isteyen firma?') ?> <a href="<?= site_url('bayi/kayit') ?>"><?= t('kul_bayi_kaydi', 'Bayi kaydı') ?></a></small></p>
    </div>
</section>
