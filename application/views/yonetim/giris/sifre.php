<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="adm-bosluk">
    <div class="adm-card" style="max-width:480px">
        <div class="adm-card-baslik">Yönetici Parolası</div>
        <?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
        <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>
        <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>
        <form method="post" action="<?= site_url('yonetim/giris/sifre_kaydet') ?>" style="display:grid;gap:10px">
            <?= csrf_field() ?>
            <div class="fld"><label>Mevcut Parola</label><input type="password" name="eski" required></div>
            <div class="fld"><label>Yeni Parola</label><input type="password" name="yeni" minlength="6" required></div>
            <div class="fld"><label>Yeni Parola (tekrar)</label><input type="password" name="yeni2" minlength="6" required></div>
            <button type="submit" class="btn btn-primary">Parolayı Güncelle</button>
        </form>
    </div>
</div>
