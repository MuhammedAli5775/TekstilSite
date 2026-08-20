<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="adm-login-sarma">
    <div class="adm-login">
        <div class="adm-login-brand">
            <svg width="26" height="26" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2C9 6 5 12 5 19a11 11 0 0 0 22 0c0-7-4-13-11-17Z" fill="#00ed64"/><path d="M16 8c-4 3-6 7-6 11a6 6 0 0 0 12 0c0-4-2-8-6-11Z" fill="#001e2b"/></svg>
            <span><?= e($site_adi ?? 'Nesem Tesettür') ?></span>
        </div>
        <h1>Yönetim Girişi</h1>
        <p class="alt">Devam etmek için yönetici hesabınızla giriş yapın.</p>
        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>
        <form action="<?= site_url('yonetim/giris/giris_yap') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="donus" value="<?= e($this->input->get('donus') ?? '') ?>">
            <div class="fld"><label>E-posta</label><input type="email" name="email" required autofocus></div>
            <div class="fld"><label>Şifre</label><input type="password" name="sifre" required></div>
            <button type="submit" class="btn btn-primary btn--block">Giriş Yap</button>
        </form>
        <p class="adm-login-link"><?= e($site_adi ?? 'Nesem Tesettür') ?> yönetim paneli</p>
    </div>
</section>
</body>
</html>
