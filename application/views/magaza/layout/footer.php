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
                <p style="font-size:14px;max-width:34ch">Toptan kadın giyimde üretici fiyatı ve kaliteli kumaş. İstanbul Merter'den Türkiye ve dünya.</p>
                <p style="margin-top:14px;font-size:13px">info@teksilsite.com<br>+90 212 481 36 92</p>
            </div>
            <div>
                <h4>Kategoriler</h4>
                <a href="<?= site_url('katalog/ust-giyim') ?>">Üst Giyim</a>
                <a href="<?= site_url('katalog/alt-giyim') ?>">Alt Giyim</a>
                <a href="<?= site_url('katalog/elbise') ?>">Elbise &amp; Tulum</a>
                <a href="<?= site_url('katalog/dis-giyim') ?>">Dış Giyim</a>
                <a href="<?= site_url('katalog/yeni') ?>">Yeni Gelenler</a>
            </div>
            <div>
                <h4>Toptancı</h4>
                <a href="<?= site_url('bayi/kayit') ?>">Bayi Kaydı</a>
                <a href="<?= site_url('bayi/giris') ?>">Bayi Girişi</a>
                <a href="<?= site_url('xml-feed') ?>">XML / API Feed</a>
                <a href="<?= site_url('toptan-sartlari') ?>">Toptan Şartlar (MOQ)</a>
                <a href="<?= site_url('siparis-takip') ?>">Sipariş Takibi</a>
            </div>
            <div>
                <h4>Yardım &amp; Kurumsal</h4>
                <a href="<?= site_url('sayfa/hakkimizda') ?>">Hakkımızda</a>
                <a href="<?= site_url('sayfa/mesafeli-satis') ?>">Mesafeli Satış</a>
                <a href="<?= site_url('sayfa/iade-degisim') ?>">İade &amp; Değişim</a>
                <a href="<?= site_url('sayfa/gizlilik') ?>">Gizlilik &amp; KVKK</a>
                <a href="<?= site_url('iletisim') ?>">İletişim</a>
            </div>
        </div>
        <div class="footer__bottom">
            <span>© <?= e($yil) ?> TekstilSite. Tüm hakları saklıdır.</span>
            <span>SSL ile korumalı · 3D Secure ödeme</span>
        </div>
    </div>
</footer>
<script>window.tkCsrf = {name: <?= json_encode($this->security->get_csrf_token_name()) ?>, hash: <?= json_encode($this->security->get_csrf_hash()) ?>}};</script>
<script src="<?= asset('magaza/js/teksil.js') ?>"></script>
</body>
</html>
