<?php defined('BASEPATH') OR exit('No direct script access allowed');
$yil = date('Y');
// LI: iletişim satırları ayarlardan (yönetim → Ayarlar); boşsa kalıcı yer tutucular.
$ftr_telefon = trim((string) ayar('iletisim_telefon', '')) ?: '+90 212 481 36 92';
$ftr_eposta  = trim((string) ayar('iletisim_eposta', '')) ?: 'info@teksilsite.com';
$ftr_adres   = trim((string) ayar('iletisim_adres', '')) ?: t('ftr_adres_varsayilan', 'Merter, İstanbul');
// WhatsApp: uluslararası biçim beklenir; yerel biçim girilirse TR ülke kodu eklenir.
$ftr_wa_no = preg_replace('/\D+/', '', (string) ayar('whatsapp', ''));
if ($ftr_wa_no !== '' && strncmp($ftr_wa_no, '90', 2) !== 0) {
    $ftr_wa_no = '90' . ltrim($ftr_wa_no, '0');
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer__grid">
            <div>
                <div class="footer__brand">
                    <?php $this->load->view('magaza/partial/brand'); ?>
                </div>
                <p class="footer__tanim"><?= t('ftr_tanim', "Toptan kadın giyimde üretici fiyatı ve kaliteli kumaş. İstanbul Merter'den Türkiye ve dünya.") ?></p>
                <div class="footer__iletisim">
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= e($ftr_adres) ?>
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <?= e($ftr_telefon) ?>
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <a href="mailto:<?= e($ftr_eposta) ?>" style="display:inline;padding:0"><?= e($ftr_eposta) ?></a>
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= t('ftr_calisma', 'Pzt – Cmt · 09.00 – 18.00') ?>
                    </span>
                </div>
                <?php if ($ftr_wa_no !== ''): ?>
                <a class="footer__wa" href="https://wa.me/<?= e($ftr_wa_no) ?>" target="_blank" rel="noopener">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                    <?= t('ftr_whatsapp', "WhatsApp'tan Yazın") ?>
                </a>
                <?php endif; ?>
            </div>
            <div>
                <h4><?= t('ftr_kategoriler', 'Kategoriler') ?></h4>
                <?php /* XXXIII: DB menüsünden — kategori adları aktif dilde (mg_menu/kategori_ad) */ ?>
                <?php foreach ((array) ($menu ?? array()) as $ftr_k): ?>
                    <a href="<?= e($ftr_k['url']) ?>"><?= e($ftr_k['baslik']) ?></a>
                <?php endforeach; ?>
                <a href="<?= site_url('katalog') ?>"><?= t('ftr_tum_urunler', 'Tüm Ürünler') ?></a>
                <a href="<?= site_url('katalog/yeni') ?>"><?= t('ftr_yeni_gelenler', 'Yeni Gelenler') ?></a>
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
                <a href="<?= site_url('blog') ?>"><?= t('ftr_blog', 'Blog') ?></a>
                <a href="<?= site_url('sayfa/mesafeli-satis') ?>"><?= t('ftr_mesafeli', 'Mesafeli Satış') ?></a>
                <a href="<?= site_url('sayfa/iade-degisim') ?>"><?= t('ftr_iade', 'İade & Değişim') ?></a>
                <a href="<?= site_url('sayfa/gizlilik') ?>"><?= t('ftr_gizlilik', 'Gizlilik & KVKK') ?></a>
                <a href="<?= site_url('sayfa/cerez') ?>"><?= t('ftr_cerez', 'Çerez Politikası') ?></a>
                <a href="<?= site_url('iletisim') ?>"><?= t('ftr_iletisim', 'İletişim') ?></a>
            </div>
            <div>
                <h4><?= t('ftr_dil', 'Dil / Language') ?></h4>
                <?php /* XXXIX: dil seçimi oturum+çerez; dil/cevir referer-dönüşlü */ ?>
                <?php foreach (array('tr' => 'Türkçe', 'en' => 'English', 'ru' => 'Русский', 'ar' => 'العربية') as $d_kod => $d_ad): ?>
                    <a href="<?= site_url('dil/cevir/' . $d_kod) ?>" class="<?= ($dil ?? 'tr') === $d_kod ? 'aktif' : '' ?>"><?= $d_ad ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php /* LI: güven şeridi — aktif ödeme yöntemleri + kargo firmaları DB'den */ ?>
        <div class="footer__strip">
            <div>
                <h5><?= t('ftr_odeme', 'Ödeme Yöntemleri') ?></h5>
                <div class="footer__rozetler">
                    <?php foreach ((array) ($ftr_odemeler ?? array()) as $ftr_oy): ?>
                        <span class="footer__rozet"><?= e($ftr_oy['ad']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h5><?= t('ftr_kargo', 'Kargo') ?></h5>
                <div class="footer__rozetler">
                    <?php foreach ((array) ($ftr_kargolar ?? array()) as $ftr_kf): ?>
                        <span class="footer__rozet"><?= e($ftr_kf['ad']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="footer__bottom">
            <span><?= t('ftr_telif', '© %1$s %2$s. Tüm hakları saklıdır.', (int) $yil, e($site_adi ?? 'Nesem Tesettür')) ?></span>
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
