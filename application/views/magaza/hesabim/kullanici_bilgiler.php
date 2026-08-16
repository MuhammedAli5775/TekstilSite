<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>">Hesabım</a> <span class="ayrac">/</span> <span class="simdiki">Bilgilerim</span></nav>
        <h1 class="kat-baslik">Bilgilerim</h1>
    </div>
</section>

<section class="section section--tight">
    <div class="container hesabim-grid">
        <?php $this->load->view('magaza/hesabim/_menu'); ?>
        <div class="hesabim-main">
            <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>
            <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

            <form class="odeme-kart" action="<?= site_url('hesabim/bilgiler') ?>/kaydet" method="post" style="margin:0">
                <?= csrf_field() ?>
                <legend style="padding:0 0 12px;font-weight:600;font-size:16px">Kullanıcı Bilgileri</legend>
                <div class="odeme-alan"><label>Ad Soyad <span class="zor">*</span></label><input type="text" name="ad_soyad" value="<?= set_value('ad_soyad', $b->yetkili_ad_soyad) ?>" required maxlength="120"></div>
                <div class="odeme-alan"><label>Kullanıcı Adı <span class="zor">*</span></label><input type="text" name="kullanici_adi" value="<?= set_value('kullanici_adi', $b->kullanici_adi ?? '') ?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_-]+" title="Harf, rakam, tire (-) ve alt çizgi (_)"></div>
                <div class="odeme-alan-2">
                    <div class="odeme-alan"><label>Telefon <span class="zor">*</span></label><input type="tel" name="telefon" value="<?= set_value('telefon', $b->telefon) ?>" required maxlength="30"></div>
                    <div class="odeme-alan"><label>E-posta (değiştirilemez)</label><input type="email" value="<?= e($b->email) ?>" disabled><small class="text-steel">Siparişleriniz bu e-posta ile eşleşir.</small></div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:16px">Kaydet</button>
            </form>
        </div>
    </div>
</section>
