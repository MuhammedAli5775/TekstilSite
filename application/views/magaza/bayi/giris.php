<?php defined('BASEPATH') OR exit('No direct script access allowed');
$donus = isset($donus) ? $donus : '';
?>
<section class="auth-sarma">
    <div class="auth-kart auth-kart--dar">
        <a class="brand auth-brand" href="<?= site_url() ?>">
            <svg class="brand__leaf" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2C9 6 5 12 5 19a11 11 0 0 0 22 0c0-7-4-13-11-17Z" fill="#00ed64"/><path d="M16 8c-4 3-6 7-6 11a6 6 0 0 0 12 0c0-4-2-8-6-11Z" fill="#001e2b"/></svg>
            <span>TekstilSite</span>
        </a>
        <h1 class="auth-baslik">Bayi Girişi</h1>
        <p class="auth-alt">Toptan hesabınıza giriş yapın.</p>

        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

        <form action="<?= site_url('bayi/giris_yap') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="donus" value="<?= e($donus) ?>">
            <div class="odeme-alan"><label>E-posta</label><input type="email" name="email" value="<?= set_value('email') ?>" required></div>
            <div class="odeme-alan"><label>Şifre</label><input type="password" name="sifre" required></div>
            <button type="submit" class="btn btn-primary btn--lg" style="width:100%;margin-top:8px">Giriş Yap</button>
        </form>
        <p class="auth-link">Hesabın yok mu? <a href="<?= site_url('bayi/kayit') ?>">Bayi ol →</a></p>
    </div>
</section>
