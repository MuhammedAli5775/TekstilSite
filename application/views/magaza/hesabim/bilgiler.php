<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>"><?= t('hesap_baslik', 'Hesabım') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('hesap_bilgiler_b', 'Bilgilerim') ?></span></nav>
        <h1 class="kat-baslik"><?= t('hesap_bilgiler_b', 'Bilgilerim') ?></h1>
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
                <legend style="padding:0 0 12px;font-weight:600;font-size:16px"><?= t('hesap_firma_iletisim', 'Firma & İletişim') ?></legend>
                <div class="odeme-alan"><label><?= t('bayi_yetkili', 'Ad Soyad / Yetkili') ?> <span class="zor">*</span></label><input type="text" name="yetkili_ad_soyad" value="<?= set_value('yetkili_ad_soyad', $b->yetkili_ad_soyad) ?>" required maxlength="120"></div>
                <div class="odeme-alan-2">
                    <div class="odeme-alan"><label><?= t('odeme_telefon', 'Telefon') ?> <span class="zor">*</span></label><input type="tel" name="telefon" value="<?= set_value('telefon', $b->telefon) ?>" required maxlength="30"></div>
                    <div class="odeme-alan"><label><?= t('hesap_eposta_sabit', 'E-posta (değiştirilemez)') ?></label><input type="email" value="<?= e($b->email) ?>" disabled></div>
                </div>
                <div class="odeme-alan"><label><?= t('odeme_firma_unvan', 'Firma Ünvanı') ?></label><input type="text" name="firma_adi" value="<?= set_value('firma_adi', $b->firma_adi) ?>" maxlength="160"></div>
                <div class="odeme-alan-2">
                    <div class="odeme-alan"><label><?= t('odeme_vergi_no', 'Vergi / TC No') ?></label><input type="text" name="vergi_no" value="<?= set_value('vergi_no', $b->vergi_no) ?>" maxlength="30"></div>
                    <div class="odeme-alan"><label><?= t('bayi_vergi_dairesi', 'Vergi Dairesi') ?></label><input type="text" name="vergi_dairesi" value="<?= set_value('vergi_dairesi', $b->vergi_dairesi) ?>" maxlength="120"></div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:16px"><?= t('hesap_kaydet', 'Kaydet') ?></button>
            </form>
        </div>
    </div>
</section>
