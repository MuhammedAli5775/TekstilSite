<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="adm-shell">
<aside class="adm-sidebar">
    <a class="adm-sidebar-brand" href="<?= site_url('yonetim/dashboard') ?>">
        <?php /* XLIX: mağaza markasıyla aynı amblem — paylaşım parçası */ ?>
        <?php $this->load->view('magaza/partial/brand'); ?>
    </a>
    <nav class="adm-menu">
        <?php foreach (($menu ?? array()) as $m): ?>
            <a href="<?= e($m['url']) ?>" class="<?= ($menu_aktif ?? '') === $m['key'] ? 'aktif' : '' ?>"><span class="adm-menu-ikon"><?= e($m['ikon']) ?></span><?= e($m['baslik']) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="adm-sidebar-alt">B2B toptan · yönetim</div>
</aside>
<div class="adm-main">
