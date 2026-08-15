<?php defined('BASEPATH') OR exit('No direct script access allowed');
$aktif = isset($menu_aktif) ? $menu_aktif : '';
$kullanici_modu = ! empty($kullanici);   // bayi değil kullanıcı oturumu
$ind   = (! $kullanici_modu && function_exists('bayi_indirim')) ? bayi_indirim() : 0.0;
$menuler = $kullanici_modu ? array(
    // Kullanıcı (B2C): firma/indirim/şifre bölümleri yok — sipariş takip odaklı
    'dashboard'  => array('📋 Hesap Özeti',   site_url('hesabim')),
    'siparisler' => array('📦 Siparişlerim',  site_url('hesabim/siparisler')),
) : array(
    'dashboard'  => array('📋 Hesap Özeti',   site_url('hesabim')),
    'siparisler' => array('📦 Siparişlerim',  site_url('hesabim/siparisler')),
    'bilgiler'   => array('👤 Bilgilerim',    site_url('hesabim/bilgiler')),
    'sifre'      => array('🔑 Şifre Değiştir', site_url('hesabim/sifre')),
);
?>
<aside class="hesabim-aside">
    <div class="hesabim-kullanici">
        <div class="hesabim-avatar"><?= e(bas_harfler($b->yetkili_ad_soyad)) ?></div>
        <div class="hesabim-kullanici-ad">
            <b><?= e($b->yetkili_ad_soyad) ?></b>
            <?php if (! $kullanici_modu): ?>
            <small><?= e($b->firma_adi) ?></small>
            <small class="hesabim-grup"><?= $ind ? 'Grup indirimi %' . number_format($ind, 0) : 'Standart toptan' ?></small>
            <?php else: ?>
            <small><?= e($b->email) ?></small>
            <small class="hesabim-grup">Kullanıcı hesabı</small>
            <?php endif; ?>
        </div>
    </div>
    <nav class="hesabim-menu">
        <?php foreach ($menuler as $key => $m): ?>
            <a href="<?= e($m[1]) ?>" class="<?= $aktif === $key ? 'aktif' : '' ?>"><?= e($m[0]) ?></a>
        <?php endforeach; ?>
        <a href="<?= site_url($kullanici_modu ? 'kullanici/cikis' : 'bayi/cikis') ?>" class="hesabim-cikis">🚪 Çıkış Yap</a>
    </nav>
</aside>
