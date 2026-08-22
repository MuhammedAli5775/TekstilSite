<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<header class="adm-topbar">
    <h1><?= e($sayfa_basligi ?? 'Dashboard') ?></h1>
    <div class="adm-topbar-sag">
        <a class="btn btn-ghost btn-sm" href="<?= site_url() ?>" target="_blank" rel="noopener">Mağaza ↗</a>
        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/giris/sifre') ?>">Parola</a>
        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/giris/totp_kurulum') ?>">2FA</a>
        <div class="adm-kullanici">
            <div class="adm-avatar"><?= e(bas_harfler($admin->ad_soyad ?? 'Y')) ?></div>
            <div><b><?= e($admin->ad_soyad ?? 'Yönetici') ?></b><small><?= e($admin->email ?? '') ?></small></div>
        </div>
        <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/giris/cikis') ?>">Çıkış</a>
    </div>
</header>
<main class="adm-content">
