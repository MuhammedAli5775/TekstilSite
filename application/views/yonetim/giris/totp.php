<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="adm-login-sarma">
    <div class="adm-login">
        <div class="adm-login-brand">
            <?php $this->load->view('magaza/partial/brand'); ?>
        </div>
        <h1>İki Adımlı Doğrulama</h1>
        <p class="alt">Kimlik doğrulayıcı uygulamanızdaki güncel 6 haneli kodu girin.</p>
        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>
        <form action="<?= site_url('yonetim/giris/totp') ?>" method="post">
            <?= csrf_field() ?>
            <div class="fld"><label>Doğrulama Kodu</label><input name="kod" required autofocus autocomplete="one-time-code" inputmode="numeric" style="letter-spacing:4px;font-size:18px;text-align:center"></div>
            <button type="submit" class="btn btn-primary btn--block">Doğrula</button>
        </form>
        <p class="adm-login-link"><small>Telefonunuzu kaybettiyseniz kurtarma kodunuzu girebilirsiniz.</small></p>
        <p class="adm-login-link"><a href="<?= site_url('yonetim/giris') ?>">← Girişe dön</a></p>
    </div>
</section>
</body>
</html>
