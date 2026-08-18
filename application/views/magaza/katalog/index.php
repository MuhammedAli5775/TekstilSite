<?php defined('BASEPATH') OR exit('No direct script access allowed');
$sira   = isset($filtre['sira']) ? $filtre['sira'] : 'yeni';
$siralar = array(
    'yeni'       => t('kat_sira_yeni', 'Yeni Gelenler'),
    'cok_santan' => t('kat_sira_cok', 'Çok Satanlar'),
    'fiyat_asc'  => t('kat_sira_artan', 'Fiyat (Artan)'),
    'fiyat_desc' => t('kat_sira_azalan', 'Fiyat (Azalan)'),
    'ad'         => t('kat_sira_alfa', 'Alfabetik (A→Z)'),
);
$filtre_data = compact('liste_url', 'alt_kategoriler', 'kategori', 'facet_beden', 'facet_renk', 'secili_beden', 'secili_renk', 'filtre');
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti" aria-label="<?= t('kat_yol', 'Yol') ?>">
            <a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a>
            <?php if (! empty($ust_yol)): ?>
                <?php foreach ($ust_yol as $i => $k): ?>
                    <span class="ayrac">/</span>
                    <?php if ($i === count($ust_yol) - 1): ?>
                        <span class="simdiki"><?= e($k->ad) ?></span>
                    <?php else: ?>
                        <a href="<?= e(site_url('katalog/' . $k->slug)) ?>"><?= e($k->ad) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="ayrac">/</span><span class="simdiki"><?= e($baslik) ?></span>
            <?php endif; ?>
        </nav>
        <h1 class="kat-baslik"><?= e($baslik) ?></h1>
        <p class="kat-alt"><?= t('kat_urun_sayisi', '%s ürün listeleniyor · toptan fiyatlar bayi girişinde görünür', (int) $toplam) ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container katalog-layout">
        <aside class="katalog-sidebar">
            <button class="filtre-mobil-toggle" id="filtreToggle" type="button" aria-expanded="false">⚙ <?= t('kat_filtreler', 'Filtreler') ?></button>
            <div class="filtre-sarma" id="filtreSarma" style="margin-top: 18px">
                <?php $this->load->view('magaza/partial/filtre', $filtre_data); ?>
            </div>
        </aside>

        <div class="katalog-main">
            <div class="urun-topbar">
                <div class="urun-sayi"><b><?= (int) $toplam ?></b> <?= t('kat_urun', 'ürün') ?></div>
                <div class="urun-sira">
                    <label for="siraSelect"><?= t('kat_sirala', 'Sırala') ?></label>
                    <select id="siraSelect">
                        <?php foreach ($siralar as $kod => $ad): ?>
                            <option value="<?= e(qs_url(array('sira' => $kod))) ?>" <?= $sira === $kod ? 'selected' : '' ?>><?= e($ad) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (! empty($urunler)): ?>
                <div class="prodgrid">
                    <?php foreach ($urunler as $u): ?>
                        <?php $this->load->view('magaza/partial/urun_karti', array('urun' => $u)); ?>
                    <?php endforeach; ?>
                </div>

                <?php $this->load->view('magaza/partial/sayfalama', array('sayfa' => $sayfa, 'sayfa_sayisi' => $sayfa_sayisi)); ?>
            <?php else: ?>
                <div class="notice">
                    <?= t('kat_bos', 'Seçtiğiniz filtrelere uygun ürün bulunamadı.') ?>
                    <a href="<?= e(site_url($liste_url)) ?>"><?= t('kat_filtre_temizle', 'Filtreleri temizle →') ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
