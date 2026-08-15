<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth-sarma">
    <div class="auth-kart auth-kart--dar">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <svg class="brand__leaf" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2C9 6 5 12 5 19a11 11 0 0 0 22 0c0-7-4-13-11-17Z" fill="#00ed64"/><path d="M16 8c-4 3-6 7-6 11a6 6 0 0 0 12 0c0-4-2-8-6-11Z" fill="#001e2b"/></svg>
            <span>TekstilSite</span>
        </a>
        <h1 class="auth-baslik">Kullanıcı Hesabı Oluştur</h1>
        <p class="auth-alt">Sipariş geçmişinizi takip etmek için kişisel hesap açın.</p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

        <form action="<?= site_url('kullanici/kayit_kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <div class="odeme-alan"><label>Ad Soyad <span class="zor">*</span></label><input type="text" name="ad_soyad" value="<?= set_value('ad_soyad') ?>" required maxlength="120"></div>
            <div class="odeme-alan"><label>E-posta <span class="zor">*</span></label><input type="email" name="email" value="<?= set_value('email') ?>" required maxlength="150"></div>
            <div class="odeme-alan"><label>Telefon</label><input type="tel" name="telefon" value="<?= set_value('telefon') ?>" maxlength="30"></div>
            <div class="odeme-alan-2">
                <div class="odeme-alan"><label>Şifre <span class="zor">*</span></label><input type="password" name="sifre" required minlength="6"></div>
                <div class="odeme-alan"><label>Şifre Tekrar <span class="zor">*</span></label><input type="password" name="sifre2" required></div>
            </div>
            <label class="odeme-check"><input type="checkbox" name="sozlesme" value="1" <?= set_checkbox('sozlesme', '1') ?>> <span><a href="<?= site_url('sayfa/mesafeli-satis') ?>">Mesafeli satış</a> ve <a href="<?= site_url('sayfa/gizlilik') ?>">gizlilik</a> koşullarını onaylıyorum. <span class="zor">*</span></span></label>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:14px">Kayıt Ol</button>
        </form>
        <p class="auth-link">Zaten hesabın var mı? <a href="<?= site_url('kullanici/giris') ?>">Giriş yap →</a></p>
        <p class="auth-link"><small>Toptan almak isteyen firma? <a href="<?= site_url('bayi/kayit') ?>">Bayi kaydı</a></small></p>
    </div>
</section>
