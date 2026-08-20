<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="adm-shell">
<aside class="adm-sidebar">
    <a class="adm-sidebar-brand" href="<?= site_url('yonetim/dashboard') ?>">
        <svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 2C9 6 5 12 5 19a11 11 0 0 0 22 0c0-7-4-13-11-17Z" fill="#00ed64"/><path d="M16 8c-4 3-6 7-6 11a6 6 0 0 0 12 0c0-4-2-8-6-11Z" fill="#001e2b"/></svg>
        <span><?= e($site_adi ?? 'Nesem Tesettür') ?></span>
    </a>
    <nav class="adm-menu">
        <?php foreach (($menu ?? array()) as $m): ?>
            <a href="<?= e($m['url']) ?>" class="<?= ($menu_aktif ?? '') === $m['key'] ? 'aktif' : '' ?>"><span class="adm-menu-ikon"><?= e($m['ikon']) ?></span><?= e($m['baslik']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="adm-sidebar-alt">B2B toptan · yönetim</div>
</aside>
<div class="adm-main">
