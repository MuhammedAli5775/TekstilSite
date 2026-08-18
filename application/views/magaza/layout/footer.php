<?php defined('BASEPATH') OR exit('No direct script access allowed');
$yil = date('Y');
?>
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div>
                <div class="footer__brand">
                    <svg class="brand__leaf" viewBox="0 0 32 32" fill="none" aria-hidden="true" style="width:26px;height:26px">
                        <path d="M16 2C9 6 5 12 5 19a11 11 0 0 0 22 0c0-7-4-13-11-17Z" fill="#00ed64"/>
                        <path d="M16 8c-4 3-6 7-6 11a6 6 0 0 0 12 0c0-4-2-8-6-11Z" fill="#001e2b"/>
                    </svg>
                    TekstilSite
                </div>
                <p style="font-size:14px;max-width:34ch"><?= t('ftr_tanim', "Toptan kadın giyimde üretici fiyatı ve kaliteli kumaş. İstanbul Merter'den Türkiye ve dünya.") ?></p>
                <p style="margin-top:14px;font-size:13px">info@teksilsite.com<br>+90 212 481 36 92</p>
            </div>
            <div>
                <h4><?= t('ftr_kategoriler', 'Kategoriler') ?></h4>
                <?php /* XXXIII: DB menüsünden — kategori adları aktif dilde (mg_menu/kategori_ad) */ ?>
                <?php foreach ((array) ($menu ?? array()) as $ftr_k): ?>
                    <a href="<?= e($ftr_k['url']) ?>"><?= e($ftr_k['baslik']) ?></a>
                <?php endforeach; ?>
            </div>
            <div>
                <h4><?= t('ftr_toptanci', 'Toptancı') ?></h4>
                <a href="<?= site_url('bayi/kayit') ?>"><?= t('ftr_bayi_kayit', 'Bayi Kaydı') ?></a>
                <a href="<?= site_url('bayi/giris') ?>"><?= t('ftr_bayi_giris', 'Bayi Girişi') ?></a>
                <a href="<?= site_url('xml-feed') ?>">XML / API Feed</a>
                <a href="<?= site_url('toptan-sartlari') ?>"><?= t('ftr_toptan_sartlar', 'Toptan Şartlar (MOQ)') ?></a>
                <a href="<?= site_url('siparis-takip') ?>"><?= t('util_siparis_takibi', 'Sipariş Takibi') ?></a>
            </div>
            <div>
                <h4><?= t('ftr_yardim_kurumsal', 'Yardım & Kurumsal') ?></h4>
                <a href="<?= site_url('sayfa/hakkimizda') ?>"><?= t('ftr_hakkinda', 'Hakkımızda') ?></a>
                <a href="<?= site_url('sayfa/mesafeli-satis') ?>"><?= t('ftr_mesafeli', 'Mesafeli Satış') ?></a>
                <a href="<?= site_url('sayfa/iade-degisim') ?>"><?= t('ftr_iade', 'İade & Değişim') ?></a>
                <a href="<?= site_url('sayfa/gizlilik') ?>"><?= t('ftr_gizlilik', 'Gizlilik & KVKK') ?></a>
                <a href="<?= site_url('iletisim') ?>"><?= t('ftr_iletisim', 'İletişim') ?></a>
            </div>
        </div>
        <div class="footer__bottom">
            <span><?= t('ftr_telif', '© %s TekstilSite. Tüm hakları saklıdır.', (int) $yil) ?></span>
            <span><?= t('ftr_guvenlik', 'SSL ile korumalı · 3D Secure ödeme') ?></span>
        </div>
    </div>
</footer>
<button type="button" id="yukariBtn" class="yukari-btn" aria-label="<?= t('ftr_yukari_aria', 'Sayfanın başına dön') ?>" title="<?= t('ftr_yukari', 'Yukarı çık') ?>">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
</button>
<?php /* tkBase: origin-göreli uygulama kökü (php -S kökünde '/', Apache alt-dizinde '/TekstilSite/').
       DİKKAT: Windows PHP dirname('/index.php') '\' döndürür (platform ayraç normalizasyonu) —
       dirname KULLANMA; strrpos+substr ile saf dizgi hesapla. Mutlak base_url kullanılsaydı
       sayfa başka origin'den açılınca AJAX CORS'a takılıp "Bağlantı hatası" üretiyordu
       (DEGISIKLIK XVII). */
$tk_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$tk_kes = (int) strrpos($tk_script, '/');
$tk_base = ($tk_kes > 0 ? rtrim(substr($tk_script, 0, $tk_kes), '/') : '') . '/'; ?>
<script>window.tkBase = <?= json_encode($tk_base, JSON_UNESCAPED_SLASHES) ?>;</script>
<script>window.tkCsrf = {name: <?= json_encode($this->security->get_csrf_token_name()) ?>, hash: <?= json_encode($this->security->get_csrf_hash()) ?>}};</script>
<script src="<?= asset('magaza/js/teksil.js') ?>"></script>
</body>
</html>
