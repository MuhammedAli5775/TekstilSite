<?php defined('BASEPATH') OR exit('No direct script access allowed');
// DB yoksa statik fallback menü (kaktusmoda B2B yapısı) — XXXI: dizgiler t() ile
if (empty($menu)) {
    $menu = array(
        array('baslik' => t('kat_sira_yeni', 'Yeni Gelenler'), 'url' => site_url('katalog/yeni'), 'altlar' => array()),
        array('baslik' => t('anasayfa_kat_ust', 'Üst Giyim'),     'url' => site_url('katalog/ust-giyim'), 'altlar' => array(
            array('baslik' => t('menu_tisort', 'Tişört & Body'), 'url' => site_url('katalog/ust-giyim/tisort')),
            array('baslik' => t('menu_bluz', 'Bluz & Gömlek'), 'url' => site_url('katalog/ust-giyim/bluz')),
            array('baslik' => t('menu_sweatshirt', 'Sweatshirt'), 'url' => site_url('katalog/ust-giyim/sweatshirt')),
            array('baslik' => t('menu_triko', 'Triko & Hırka'), 'url' => site_url('katalog/ust-giyim/triko')),
        )),
        array('baslik' => t('anasayfa_kat_alt', 'Alt Giyim'), 'url' => site_url('katalog/alt-giyim'), 'altlar' => array(
            array('baslik' => t('menu_etek', 'Etek'),       'url' => site_url('katalog/alt-giyim/etek')),
            array('baslik' => t('menu_pantolon', 'Pantolon'),   'url' => site_url('katalog/alt-giyim/pantolon')),
            array('baslik' => t('menu_esofman', 'Eşofman'),    'url' => site_url('katalog/alt-giyim/esofman')),
        )),
        array('baslik' => t('menu_elbise_tulum', 'Elbise & Tulum'), 'url' => site_url('katalog/elbise'), 'altlar' => array()),
        array('baslik' => t('anasayfa_kat_dis', 'Dış Giyim'),      'url' => site_url('katalog/dis-giyim'), 'altlar' => array()),
    );
}
?>
<header>
    <div class="utility-bar">
        <div class="container">
            <div class="utility-bar__left">
                <span>📞 +90 212 481 36 92</span>
                <span class="pill"><?= t('util_toptan', 'Toptan / B2B') ?></span>
            </div>
            <div class="utility-bar__right">
                <details class="dil-sec">
                    <summary title="Dil / Language"><?= e($dil_adi ?? 'Türkçe') ?> <span class="dil-sec__ok">▾</span></summary>
                    <div class="dil-sec__menu">
                        <a href="<?= site_url('dil/cevir/tr') ?>" class="<?= ($dil ?? 'tr') === 'tr' ? 'aktif' : '' ?>">Türkçe</a>
                        <a href="<?= site_url('dil/cevir/en') ?>" class="<?= ($dil ?? '') === 'en' ? 'aktif' : '' ?>">English</a>
                        <a href="<?= site_url('dil/cevir/ru') ?>" class="<?= ($dil ?? '') === 'ru' ? 'aktif' : '' ?>">Русский</a>
                        <a href="<?= site_url('dil/cevir/ar') ?>" class="<?= ($dil ?? '') === 'ar' ? 'aktif' : '' ?>">العربية</a>
                        <div class="dil-sec__ayrac"></div>
                        <span class="dil-sec__ulke-baslik"><?= t('ulke_baslik', 'Teslimat Ülkesi') ?></span>
                        <?php foreach (ulke_listesi() as $u_kod => $u_bilgi): ?>
                            <a class="dil-sec__ulke<?= aktif_ulke() === $u_kod ? ' aktif' : '' ?>" href="<?= site_url('ulke/sec/' . $u_kod) ?>"><?= $u_bilgi['bayrak'] ?> <?= t('ulke_' . $u_kod, $u_bilgi['ad']) ?> <small><?= e(para_sembol($u_bilgi['pb'])) ?></small></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <a href="<?= site_url('siparis-takip') ?>"><?= t('util_siparis_takibi', 'Sipariş Takibi') ?></a>
                <a href="<?= site_url('yardim') ?>"><?= t('util_yardim', 'Yardım') ?></a>
                <a href="<?= site_url('blog') ?>">Blog</a>
            </div>
        </div>
    </div>

    <div class="header-main">
        <div class="site-header">
            <div class="container site-header__row">
                <a class="brand" href="<?= site_url() ?>">
                    <?php $this->load->view('magaza/partial/brand'); ?>
                </a>

                <form class="header-search" action="<?= site_url('arama') ?>" method="get" role="search">
                    <input type="text" name="q" placeholder="<?= t('hdr_ara', 'Ürün ara…') ?>" aria-label="<?= t('hdr_arama', 'Arama') ?>">
                    <button type="submit" aria-label="<?= t('hdr_ara_dugme', 'Ara') ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                </form>

                <div class="header-actions">
                    <a href="<?= site_url('favorilerim') ?>">♡ <?= t('hdr_favoriler', 'Favoriler') ?></a>
                    <?php if (! empty($bayi)): ?>
                        <a href="<?= site_url('hesabim') ?>">👤 <?= e($bayi->yetkili_ad_soyad ? explode(' ', $bayi->yetkili_ad_soyad)[0] : t('hdr_hesabim', 'Hesabım')) ?></a>
                        <a href="<?= site_url('bayi/cikis') ?>" onclick="return confirm('<?= t('hdr_cikis_onay', 'Çıkış yapmak istediğinizden emin misiniz?') ?>')"><?= t('hdr_cikis', 'Çıkış') ?></a>
                    <?php elseif (! empty($kullanici)): ?>
                        <a href="<?= site_url('hesabim') ?>">👤 <?= e($kullanici->ad_soyad ? explode(' ', $kullanici->ad_soyad)[0] : t('hdr_hesabim', 'Hesabım')) ?></a>
                        <a href="<?= site_url('kullanici/cikis') ?>" onclick="return confirm('<?= t('hdr_cikis_onay', 'Çıkış yapmak istediğinizden emin misiniz?') ?>')"><?= t('hdr_cikis', 'Çıkış') ?></a>
                    <?php else: ?>
                        <a href="<?= site_url('kullanici/giris') ?>"><?= t('hdr_giris', 'Kullanıcı Girişi') ?></a>
                    <?php endif; ?>
                    <a href="<?= site_url('sepet') ?>"><?= t('hdr_sepet', 'Sepet') ?> <span class="cart-count" id="cartCount"><?= (int) ($sepet_adet ?? 0) ?></span></a>
                </div>
            </div>

        <div class="catbar">
            <div class="container">
                <nav>
                    <ul class="mega">
                        <?php foreach ($menu as $m): ?>
                            <li>
                                <a href="<?= e($m['url']) ?>"><?= e($m['baslik']) ?></a>
                                <?php if (!empty($m['altlar'])): ?>
                                <div class="mega__sub">
                                    <?php foreach ($m['altlar'] as $a): ?>
                                        <a href="<?= e($a['url']) ?>"><?= e($a['baslik']) ?></a>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </div>
        </div>
    </div>
</header>
