<?php defined('BASEPATH') OR exit('No direct script access allowed');
$sira   = isset($filtre['sira']) ? $filtre['sira'] : 'yeni';
$siralar = array(
    'yeni'       => 'Yeni Gelenler',
    'cok_santan' => 'Çok Satanlar',
    'fiyat_asc'  => 'Fiyat (Artan)',
    'fiyat_desc' => 'Fiyat (Azalan)',
    'ad'         => 'Alfabetik (A→Z)',
);
$filtre_data = compact('liste_url', 'alt_kategoriler', 'kategori', 'facet_beden', 'facet_renk', 'secili_beden', 'secili_renk', 'filtre');
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti" aria-label="Yol">
            <a href="<?= site_url() ?>">Anasayfa</a>
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
        <p class="kat-alt"><?= (int) $toplam ?> ürün listeleniyor · toptan fiyatlar bayi girişinde görünür</p>
    </div>
</section>

<section class="section section--tight">
    <div class="container katalog-layout">
        <aside class="katalog-sidebar">
            <button class="filtre-mobil-toggle" id="filtreToggle" type="button" aria-expanded="false">⚙ Filtrele</button>
            <div class="filtre-sarma" id="filtreSarma">
                <?php $this->load->view('magaza/partial/filtre', $filtre_data); ?>
            </div>
        </aside>

        <div class="katalog-main">
            <div class="urun-topbar">
                <div class="urun-sayi"><b><?= (int) $toplam ?></b> ürün</div>
                <div class="urun-sira">
                    <label for="siraSelect">Sırala</label>
                    <select id="siraSelect" onchange="window.location.href=this.value">
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
                    Seçtiğiniz filtrelere uygun ürün bulunamadı.
                    <a href="<?= e(site_url($liste_url)) ?>">Filtreleri temizle →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
