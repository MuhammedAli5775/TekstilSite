<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="adm-login-sarma">
    <div class="adm-login">
        <div class="adm-login-brand">
            <?php $this->load->view('magaza/partial/brand'); ?>
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
