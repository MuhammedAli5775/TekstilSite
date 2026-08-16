<?php defined('BASEPATH') OR exit('No direct script access allowed');
$aktif = isset($menu_aktif) ? $menu_aktif : '';
$kullanici_modu = ! empty($kullanici);   // bayi değil kullanıcı oturumu
$ind   = (! $kullanici_modu && function_exists('bayi_indirim')) ? bayi_indirim() : 0.0;
$menuler = $kullanici_modu ? array(
    // Kullanıcı (B2C): sipariş takip + hesap yönetimi (firma alanları yok)
    'dashboard'  => array('📋 Hesap Özeti',   site_url('hesabim')),
    'siparisler' => array('📦 Siparişlerim',  site_url('hesabim/siparisler')),
    'faturalar'  => array('🧾 Faturalarım',   site_url('hesabim/faturalar')),
    'bilgiler'   => array('👤 Bilgilerim',    site_url('hesabim/bilgiler')),
    'adresler'   => array('📍 Adreslerim',    site_url('hesabim/adresler')),
    'sifre'      => array('🔑 Şifre Değiştir', site_url('hesabim/sifre')),
) : array(
    'dashboard'  => array('📋 Hesap Özeti',   site_url('hesabim')),
    'siparisler' => array('📦 Siparişlerim',  site_url('hesabim/siparisler')),
    'faturalar'  => array('🧾 Faturalarım',   site_url('hesabim/faturalar')),
    'bilgiler'   => array('👤 Bilgilerim',    site_url('hesabim/bilgiler')),
    'sifre'      => array('🔑 Şifre Değiştir', site_url('hesabim/sifre')),
);
?>
<aside class="hesabim-aside">
    <div class="hesabim-kullanici">
        <div class="hesabim-avatar"><?= e($b->yetkili_ad_soyad) ?></div>
        <div class="hesabim-kullanici-ad">
            <b><?= e($b->email) ?></b>
            <?php if (! $kullanici_modu): ?>
            <small><?= e($b->firma_adi) ?></small>
            <small class="hesabim-grup"><?= $ind ? 'Grup indirimi %' . number_format($ind, 0) : 'Standart toptan' ?></small>
            <?php else: ?>
            <?php endif; ?>
        </div>
    </div>
    <nav class="hesabim-menu">
        <?php foreach ($menuler as $key => $m): ?>
            <a href="<?= e($m[1]) ?>" class="<?= $aktif === $key ? 'aktif' : '' ?>"><?= e($m[0]) ?></a>
        <?php endforeach; ?>
        <a href="<?= site_url($kullanici_modu ? 'kullanici/cikis' : 'bayi/cikis') ?>" class="hesabim-cikis" onclick="return confirm('Çıkış yapmak istediğinizden emin misiniz?')">🚪 Çıkış Yap</a>
    </nav>
</aside>
