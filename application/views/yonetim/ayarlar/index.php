<?php defined('BASEPATH') OR exit('No direct script access allowed');
$a = $ayarlar ?? array();
$g = function ($k, $def = '') use ($a) { return isset($a[$k]) ? $a[$k] : $def; };
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<form action="<?= site_url('yonetim/ayarlar/kaydet') ?>" method="post">
    <?= csrf_field() ?>

    <div class="adm-detay-grid">
        <div>
            <div class="adm-card">
                <div class="adm-card-baslik"><h3>Site &amp; Genel</h3></div>
                <div class="fld"><label>Site Adı</label><input type="text" name="site_adi" value="<?= e($g('site_adi', 'TekstilSite')) ?>"></div>
                <div class="fld-row">
                    <div class="fld"><label>İletişim Telefonu</label><input type="text" name="iletisim_telefon" value="<?= e($g('iletisim_telefon')) ?>"></div>
                    <div class="fld"><label>WhatsApp (uluslararası)</label><input type="text" name="whatsapp" value="<?= e($g('whatsapp')) ?>"></div>
                </div>
                <div class="fld"><label>İletişim E-postası</label><input type="email" name="iletisim_eposta" value="<?= e($g('iletisim_eposta')) ?>"></div>
                <div class="fld"><label>Adres</label><textarea name="iletisim_adres" rows="2"><?= e($g('iletisim_adres')) ?></textarea></div>
                <div class="fld"><label>Ücretsiz Kargo Eşiği (₺)</label><input type="number" name="ucretsiz_kargo_esik" value="<?= e($g('ucretsiz_kargo_esik', '2000')) ?>"></div>
                <div class="fld"><label>Üst Duyuru Şeridi</label><input type="text" name="duyuru_1" value="<?= e($g('duyuru_1')) ?>" placeholder="1. satır"></div>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>SEO &amp; Analytics</h3></div>
                <div class="fld"><label>Meta Açıklama</label><textarea name="meta_description" rows="2"><?= e($g('meta_description')) ?></textarea></div>
                <div class="fld"><label>Meta Anahtar Kelimeler</label><input type="text" name="meta_keywords" value="<?= e($g('meta_keywords')) ?>"></div>
                <label class="checkbox" style="margin:10px 0"><input type="checkbox" name="arama_index" value="1" <?= $g('arama_index', '0') === '1' ? 'checked' : '' ?>> Arama motoru indekslemesi açık (yayında)</label>
                <div class="fld-row">
                    <div class="fld"><label>Google Analytics ID</label><input type="text" name="ga_id" value="<?= e($g('ga_id')) ?>" placeholder="G-XXXX"></div>
                    <div class="fld"><label>Meta Pixel ID</label><input type="text" name="fb_pixel" value="<?= e($g('fb_pixel')) ?>"></div>
                </div>
                <div class="fld"><label>Google Site Doğrulama</label><input type="text" name="google_site_verification" value="<?= e($g('google_site_verification')) ?>"></div>
                <div class="fld"><label>Facebook Domain Doğrulama</label><input type="text" name="facebook_domain_verification" value="<?= e($g('facebook_domain_verification')) ?>"></div>
            </div>
        </div>

        <div>
            <div class="adm-card">
                <div class="adm-card-baslik"><h3>SMTP (E-posta)</h3></div>
                <div class="fld"><label>SMTP Sunucu</label><input type="text" name="smtp_sunucu" value="<?= e($g('smtp_sunucu')) ?>" placeholder="mail.site.com"></div>
                <div class="fld-row">
                    <div class="fld"><label>Port</label><input type="number" name="smtp_port" value="<?= e($g('smtp_port', '587')) ?>"></div>
                    <div class="fld"><label>Şifreleme</label><select name="smtp_sifrelem"><option value="tls" <?= $g('smtp_sifrelem', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= $g('smtp_sifrelem') === 'ssl' ? 'selected' : '' ?>>SSL</option><option value="" <?= $g('smtp_sifrelem') === '' ? 'selected' : '' ?>>Yok</option></select></div>
                </div>
                <div class="fld-row">
                    <div class="fld"><label>Kullanıcı (e-posta)</label><input type="text" name="smtp_kullanici" value="<?= e($g('smtp_kullanici')) ?>"></div>
                    <div class="fld"><label>Şifre</label><input type="password" name="smtp_sifre" value="<?= e($g('smtp_sifre')) ?>" autocomplete="new-password"></div>
                </div>
                <div class="fld"><label>Gönderen E-postası</label><input type="email" name="gonderen_eposta" value="<?= e($g('gonderen_eposta')) ?>"></div>
                <small style="color:var(--stone)">SMTP girilince sipariş onayı ve durum bildirimleri otomatik gönderilir.</small>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>SMS</h3></div>
                <label class="checkbox" style="margin-bottom:10px"><input type="checkbox" name="sms_aktif" value="1" <?= $g('sms_aktif', '0') === '1' ? 'checked' : '' ?>> SMS bildirimi aktif</label>
                <div class="fld"><label>SMS Kullanıcı</label><input type="text" name="sms_kullanici" value="<?= e($g('sms_kullanici')) ?>"></div>
                <div class="fld-row">
                    <div class="fld"><label>SMS Şifre</label><input type="password" name="sms_sifre" value="<?= e($g('sms_sifre')) ?>"></div>
                    <div class="fld"><label>Gönderen Adı</label><input type="text" name="sms_gonderen" value="<?= e($g('sms_gonderen')) ?>"></div>
                </div>
                <small style="color:var(--stone)">SMS sağlayıcı (Netgsm vb.) anahtarları girilince durum SMS'leri gönderilir.</small>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>E-Fatura / E-Arşiv</h3></div>
                <div class="fld-row">
                    <div class="fld"><label>Entegratör</label><input type="text" name="efatura_entegrator" value="<?= e($g('efatura_entegrator')) ?>" placeholder="parasut / uyumsoft / foriba"></div>
                    <div class="fld"><label>Test ortamı</label><label class="checkbox" style="margin-top:6px"><input type="checkbox" name="efatura_test" value="1" <?= $g('efatura_test', '0') === '1' ? 'checked' : '' ?>> Test modu</label></div>
                </div>
                <div class="fld"><label>API URL</label><input type="text" name="efatura_api_url" value="<?= e($g('efatura_api_url')) ?>" placeholder="https://.../e_invoices"></div>
                <div class="fld"><label>Token / Anahtar</label><input type="password" name="efatura_token" value="<?= e($g('efatura_token')) ?>" autocomplete="new-password"></div>
                <div class="fld-row">
                    <div class="fld"><label>Satıcı VKN</label><input type="text" name="efatura_firma_vkn" value="<?= e($g('efatura_firma_vkn')) ?>"></div>
                    <div class="fld"><label>Satıcı Ünvan</label><input type="text" name="efatura_firma_unvan" value="<?= e($g('efatura_firma_unvan')) ?>"></div>
                </div>
                <small style="color:var(--stone)">API URL + token + satıcı VKN girilince siparişten kesilen faturalar entegratöre gönderilir (asenkron durum takibi). Boşsa faturalar "bekliyor" kaydedilir.</small>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>PayTR (Kartlı Ödeme)</h3></div>
                <div class="fld-row">
                    <div class="fld"><label>Mağaza ID</label><input type="text" name="paytr_merchant_id" value="<?= e($g('paytr_merchant_id')) ?>" placeholder="123456"></div>
                    <div class="fld"><label>Test ortamı</label><label class="checkbox" style="margin-top:6px"><input type="checkbox" name="paytr_test" value="1" <?= $g('paytr_test', '0') === '1' ? 'checked' : '' ?>> Test modu</label></div>
                </div>
                <div class="fld"><label>Mağaza Anahtarı (Key)</label><input type="password" name="paytr_merchant_key" value="<?= e($g('paytr_merchant_key')) ?>" autocomplete="new-password"></div>
                <div class="fld"><label>Mağaza Tuzu (Salt)</label><input type="password" name="paytr_merchant_salt" value="<?= e($g('paytr_merchant_salt')) ?>" autocomplete="new-password"></div>
                <small style="color:var(--stone)">Mağaza ID + anahtar + tuz girilince kartlı ödeme aktifleşir. Test modu PayTR sandbox'una gider; canlıya geçince işareti kaldırın.</small>
            </div>
        </div>
    </div>

    <div style="margin-top:16px"><button type="submit" class="btn btn-primary">Tüm Ayarları Kaydet</button></div>
</form>
