<?php defined('BASEPATH') OR exit('No direct script access allowed');
$satirlar = isset($satirlar) ? $satirlar : array();
$havale_var = false;
foreach ($odeme_yontemleri as $oy) { if ($oy->tip === 'havale') { $havale_var = true; } }
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <a href="<?= site_url('sepet') ?>">Sepet</a> <span class="ayrac">/</span> <span class="simdiki">Ödeme</span></nav>
        <h1 class="kat-baslik">Ödeme</h1>
    </div>
</section>

<section class="section section--tight"><div class="container">
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice notice--warn"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="notice notice--warn"><?= e($hata) ?></div><?php endif; ?>
<?php $_ve = validation_errors(); echo $_ve ? '<div class="notice notice--warn">' . strip_tags($_ve) . '</div>' : ''; ?>

<div class="odeme-grid">
    <form class="odeme-kart" action="<?= site_url('odeme/tamamla') ?>" method="post" style="margin:0">
        <?= csrf_field() ?>

        <h3 style="margin-bottom:12px">Teslimat Bilgileri</h3>
        <div class="odeme-alan"><label>Ad Soyad <span class="zor">*</span></label><input type="text" name="teslimat_ad" value="<?= set_value('teslimat_ad', $bayi ? $bayi->yetkili_ad_soyad : '') ?>" required maxlength="150"></div>
        <div class="odeme-alan"><label>Adres <span class="zor">*</span></label><textarea name="teslimat_adres" rows="3" required maxlength="500"><?= set_value('teslimat_adres') ?></textarea></div>
        <div class="odeme-row">
            <div class="odeme-alan"><label>İl <span class="zor">*</span></label>
                <select name="teslimat_il" required><option value="">Seçin</option>
                <?php foreach ($iller as $il): ?><option value="<?= e($il->ad) ?>" <?= set_select('teslimat_il', $il->ad) ?>><?= e($il->ad) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="odeme-alan"><label>İlçe</label><input type="text" name="teslimat_ilce" value="<?= set_value('teslimat_ilce') ?>"></div>
        </div>
        <div class="odeme-row">
            <div class="odeme-alan"><label>Telefon <span class="zor">*</span></label><input type="tel" name="teslimat_telefon" value="<?= set_value('teslimat_telefon', $bayi ? $bayi->telefon : '') ?>" required maxlength="30"></div>
            <div class="odeme-alan"><label>E-posta <span class="zor">*</span></label><input type="email" name="email" value="<?= set_value('email', $bayi ? $bayi->email : ($kullanici ? $kullanici->email : '')) ?>" <?= $kullanici ? 'readonly' : '' ?> required maxlength="150"></div>
        </div>

        <h3 style="margin:20px 0 12px">Fatura Bilgileri</h3>
        <label class="checkbox"><input type="checkbox" name="fatura_ayni" value="1" checked onchange="var f=document.getElementById('faturaAlan');if(this.checked){f.style.display='none'}else{f.style.display='block'}"> Fatura bilgileri teslimat ile aynı</label>
        <div id="faturaAlan" style="display:none">
            <div class="odeme-alan"><label>Fatura Ad / Ünvan</label><input type="text" name="fatura_ad" value="<?= set_value('fatura_ad') ?>"></div>
            <div class="odeme-alan"><label>Fatura Adresi</label><textarea name="fatura_adres" rows="2"><?= set_value('fatura_adres') ?></textarea></div>
        </div>
        <div class="odeme-row">
            <div class="odeme-alan"><label>Firma Ünvanı</label><input type="text" name="firma_adi" value="<?= set_value('firma_adi', $bayi ? $bayi->firma_adi : '') ?>"></div>
            <div class="odeme-alan"><label>Vergi / TC No</label><input type="text" name="vergi_no" value="<?= set_value('vergi_no', $bayi ? $bayi->vergi_no : '') ?>"></div>
        </div>

        <h3 style="margin:20px 0 12px">Kargo & Ödeme</h3>
        <div class="odeme-alan"><label>Kargo Firması <span class="zor">*</span></label>
            <select name="kargo_firma_id" required><option value="">Seçin</option>
            <?php foreach ($kargo_firmalari as $kf): ?><option value="<?= (int) $kf->id ?>" <?= set_select('kargo_firma_id', (string) $kf->id) ?>><?= e($kf->ad) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div style="font-weight:600;margin:12px 0 6px">Ödeme Yöntemi <span class="zor">*</span></div>
        <?php foreach ($odeme_yontemleri as $i => $oy): ?>
            <label class="odeme-yontem"><input type="radio" name="odeme_yontemi" value="<?= e($oy->kod) ?>" <?= set_radio('odeme_yontemi', $oy->kod, $i === 0) ?> required> <b><?= e($oy->ad) ?></b><?php if ((float) $oy->ek_ucret > 0): ?> <small class="text-steel">(<?= $oy->ek_ucret_tip === 'yuzde' ? '%' . $oy->ek_ucret : para_goster($oy->ek_ucret, $pb) ?> ek)</small><?php endif; ?></label>
        <?php endforeach; ?>
        <?php if ($havale_var && ! empty($banka_hesaplari)): ?>
            <div style="margin-top:10px;padding:12px;background:var(--surface);border-radius:8px;font-size:13px">
                <b>Banka Hesapları (Havale/EFT için):</b><br>
                <?php foreach ($banka_hesaplari as $bh): ?><?= e($bh->banka_adi) ?> — <?= e($bh->iban ?: $bh->hesap_no ?: '') ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label class="checkbox" style="margin-top:16px"><input type="checkbox" name="sozlesme" value="1" <?= set_checkbox('sozlesme') ?> required> Mesafeli satış sözleşmesini okudum, onaylıyorum. <span class="zor">*</span></label>
        <button type="submit" class="btn btn-primary btn--lg btn--block" style="margin-top:16px">Siparişi Tamamla</button>
    </form>

    <div>
        <div class="sepet-ozet">
            <h3 style="margin-bottom:12px">Sipariş Özeti</h3>
            <?php foreach ($satirlar as $r): ?>
                <div class="sepet-ozet-satr"><span><?= e($r->ad) ?> <small>×<?= (int) $r->adet ?></small></span><span><?= para_formatla($r->ara_pb, $r->pb) ?></span></div>
            <?php endforeach; ?>
            <div class="sepet-ozet-toplam"><span>Ara toplam</span><span><?= para_formatla($pb_ara_toplam, $pb) ?></span></div>

            <?php if (! empty($kupon_kod) && $kupon_indirim > 0): ?>
                <div class="sepet-ozet-satr" style="color:var(--teal-mid)"><span>Kupon (<?= e($kupon_kod) ?>)</span><span>-<?= para_goster($kupon_indirim, $pb) ?></span></div>
                <form method="post" action="<?= site_url('odeme/kupon_kaldir') ?>" style="margin:6px 0 0"><?= csrf_field() ?><button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);padding:2px 10px">Kuponu kaldır</button></form>
            <?php else: ?>
                <form method="post" action="<?= site_url('odeme/kupon_uygula') ?>" style="margin:10px 0"><?= csrf_field() ?>
                    <div style="display:flex;gap:6px"><input type="text" name="kod" placeholder="Kupon kodu" style="flex:1;height:38px;padding:0 10px;border:1px solid var(--hairline-strong);border-radius:6px;text-transform:uppercase;font-size:13px"><button type="submit" class="btn btn-secondary btn-sm">Uygula</button></div>
                </form>
                <?php if (! empty($kupon_mesaj)): ?><div style="font-size:12px;color:var(--danger);margin-top:-4px"><?= e($kupon_mesaj) ?></div><?php endif; ?>
            <?php endif; ?>

            <div class="sepet-ozet-satr" style="font-size:12px;color:var(--steel)"><span>Kargo / işlem ücreti</span><span>ödemede hesaplanır</span></div>
        </div>
    </div>
</div>
</div></section>
