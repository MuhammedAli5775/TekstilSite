<?php defined('BASEPATH') OR exit('No direct script access allowed');
$aday = isset($aday) ? $aday : '';
$uri  = isset($uri) ? $uri : '';
?>
<div class="adm-bosluk">
    <?php if ($kurtarma = $this->session->flashdata('kurtarma')): ?>
        <div class="adm-card" style="max-width:560px;border:2px solid #b3261e">
            <div class="adm-card-baslik">Kurtarma Kodlarınız — ŞİMDİ KAYDEDİN</div>
            <p class="alt">Telefonunuzu kaybederseniz giriş için tek yol bu kodlardır. Her kod BİR KEZ kullanılır; bu liste bir daha gösterilmez.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px;font-family:monospace;font-size:16px;letter-spacing:1px">
                <?php foreach (explode(' ', $kurtarma) as $k): ?><code><?= e($k) ?></code><?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="adm-card" style="max-width:560px">
        <div class="adm-card-baslik">İki Adımlı Doğrulama (TOTP)</div>
        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>

        <?php if (! empty($mevcut)): ?>
            <p class="alt">Durum: <b style="color:#0a7d33">ETKİN</b> — girişte paroladan sonra kimlik doğrulayıcı kodu istenir.</p>
            <p class="alt">Kapatmak için şifrenizi ve güncel bir doğrulama kodu girin:</p>
            <form method="post" style="display:grid;gap:10px">
                <?= csrf_field() ?>
                <input type="hidden" name="islem" value="kapat">
                <div class="fld"><label>Şifre</label><input type="password" name="sifre" required></div>
                <div class="fld"><label>Güncel Kod</label><input name="kod" required autocomplete="one-time-code"></div>
                <button type="submit" class="btn btn-secondary">2FA'yı Kapat</button>
            </form>
        <?php elseif ($aday !== ''): ?>
            <p class="alt">1. Kimlik doğrulayıcı uygulamanıza (Google Authenticator, Aegis, 1Password…) aşağıdaki anahtarı girin ya da bağlantıyı açın:</p>
            <p style="font-family:monospace;font-size:16px;letter-spacing:2px;word-break:break-all"><span class="totp-secret"><?= e(trim(chunk_split($aday, 4, ' '))) ?></span></p>
            <p style="word-break:break-all"><a href="<?= e($uri) ?>"><?= e($uri) ?></a></p>
            <p class="alt">2. Uygulamanın ürettiği güncel 6 haneli kodu girip doğrulayın:</p>
            <form method="post" style="display:grid;gap:10px">
                <?= csrf_field() ?>
                <input type="hidden" name="islem" value="ac">
                <div class="fld"><label>Doğrulama Kodu</label><input name="kod" required autocomplete="one-time-code" inputmode="numeric"></div>
                <button type="submit" class="btn btn-primary">Doğrula ve Etkinleştir</button>
            </form>
        <?php endif; ?>
    </div>
</div>
