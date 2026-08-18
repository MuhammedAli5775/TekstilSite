<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = isset($duzenlenen) && $duzenlenen ? $duzenlenen : NULL;
$tipler = array('her_ikisi' => t('hesap_tip_her_ikisi', 'Teslimat & Fatura'), 'teslimat' => t('hesap_tip_teslimat', 'Yalnız Teslimat'), 'fatura' => t('hesap_tip_fatura', 'Yalnız Fatura'));
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>"><?= t('hesap_baslik', 'Hesabım') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('hesap_adresler_b', 'Adreslerim') ?></span></nav>
        <h1 class="kat-baslik"><?= t('hesap_adresler_b', 'Adreslerim') ?></h1>
    </div>
</section>

<section class="section section--tight">
    <div class="container hesabim-grid">
        <?php $this->load->view('magaza/hesabim/_menu'); ?>
        <div class="hesabim-main">
            <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>
            <?= validation_errors() ? '<div class="notice notice--warn">' . strip_tags(validation_errors()) . '</div>' : '' ?>

            <?php if ($adresler): ?>
                <?php foreach ($adresler as $a): ?>
                <div class="odeme-kart" style="margin-bottom:14px;padding:16px 18px">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                        <div>
                            <b><?= e($a->ad_soyad) ?></b> <?= $a->varsayilan ? '<span class="pill">' . t('hesap_varsayilan', 'Varsayılan') . '</span>' : '' ?><br>
                            <span class="text-steel"><?= e($a->adres) ?> · <?= e($a->ilce) ?> / <?= e($a->il) ?><?= $a->telefon ? ' · ' . e($a->telefon) : '' ?></span><br>
                            <small class="text-steel"><?= e($tipler[$a->tip] ?? $a->tip) ?></small>
                        </div>
                        <div style="display:flex;gap:8px;flex-shrink:0">
                            <a class="btn" href="<?= site_url('hesabim/adresler') ?>?duzenle=<?= (int) $a->id ?>"><?= t('hesap_duzenle', 'Düzenle') ?></a>
                            <form method="post" action="<?= site_url('hesabim/adresler/sil/' . (int) $a->id) ?>" onsubmit="return confirm('<?= t('hesap_adres_sil_onay', 'Bu adresi silmek istediğinizden emin misiniz?') ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn"><?= t('sepet_sil', 'Sil') ?></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notice"><?= t('hesap_adres_yok', 'Kayıtlı adresiniz yok. Sipariş verirken bilgileri elle girmemek için adres ekleyin.') ?></div>
            <?php endif; ?>

            <form class="odeme-kart" action="<?= site_url('hesabim/adresler') ?>/kaydet" method="post" style="margin-top:18px">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $d ? (int) $d->id : 0 ?>">
                <legend style="padding:0 0 12px;font-weight:600;font-size:16px"><?= $d ? t('hesap_adres_duzenle', 'Adresi Düzenle') : t('hesap_adres_yeni', 'Yeni Adres Ekle') ?></legend>
                <div class="odeme-alan"><label><?= t('odeme_ad_soyad', 'Ad Soyad') ?> <span class="zor">*</span></label><input type="text" name="ad_soyad" value="<?= set_value('ad_soyad', $d->ad_soyad ?? '') ?>" required maxlength="120"></div>
                <div class="odeme-alan"><label><?= t('odeme_adres', 'Adres') ?> <span class="zor">*</span></label><input type="text" name="adres" value="<?= set_value('adres', $d->adres ?? '') ?>" required maxlength="255" placeholder="<?= e(t('hesap_adres_ph', 'Mahalle, cadde, no, daire')) ?>"></div>
                <div class="odeme-alan-2">
                    <div class="odeme-alan"><label><?= t('odeme_il', 'İl') ?> <span class="zor">*</span></label><input type="text" name="il" value="<?= set_value('il', $d->il ?? '') ?>" required maxlength="60"></div>
                    <div class="odeme-alan"><label><?= t('odeme_ilce', 'İlçe') ?> <span class="zor">*</span></label><input type="text" name="ilce" value="<?= set_value('ilce', $d->ilce ?? '') ?>" required maxlength="90"></div>
                </div>
                <div class="odeme-alan-2">
                    <div class="odeme-alan"><label><?= t('odeme_telefon', 'Telefon') ?></label><input type="tel" name="telefon" value="<?= set_value('telefon', $d->telefon ?? '') ?>" maxlength="30"></div>
                    <div class="odeme-alan"><label><?= t('hesap_adres_tipi', 'Adres Tipi') ?></label>
                        <select name="tip">
                            <?php foreach ($tipler as $kv => $etiket): ?>
                            <option value="<?= $kv ?>" <?= set_select('tip', $kv, ($d && $d->tip === $kv) || (! $d && $kv === 'her_ikisi')) ?>><?= e($etiket) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label class="odeme-check"><input type="checkbox" name="varsayilan" value="1" <?= ($d && $d->varsayilan) ? 'checked' : '' ?>> <span><?= t('hesap_adres_varsayilan_yap', 'Varsayılan adresim olsun') ?></span></label>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <button type="submit" class="btn btn-primary"><?= $d ? t('sepet_guncelle', 'Güncelle') : t('hesap_adres_ekle', 'Adres Ekle') ?></button>
                    <?php if ($d): ?><a class="btn" href="<?= site_url('hesabim/adresler') ?>"><?= t('hesap_iptal', 'İptal') ?></a><?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</section>
