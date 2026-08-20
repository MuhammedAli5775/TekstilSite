<?php defined('BASEPATH') OR exit('No direct script access allowed');
$u = $u; // ürün nesnesi
$ilk_gorsel = ! empty($gorseller) ? $gorseller[0] : '';
$moq = (int) $u->moq;
$adim = max(1, (int) $u->birim_adim);
$renk_secili = ! empty($renkler) ? $renkler[0] : null;
$pb_kod = aktif_para_birimi();   // XXXIV: teslimat ülkesi → vitrin para birimi
?>
<section class="pd-hero">
    <div class="container">
        <nav class="kirinti" aria-label="<?= t('kat_yol', 'Yol') ?>">
            <a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a>
            <?php if (! empty($u->kategori_slug)): ?>
                <span class="ayrac">/</span>
                <a href="<?= e(site_url('katalog/' . $u->kategori_slug)) ?>"><?= e($u->kategori_adi) ?></a>
            <?php endif; ?>
            <span class="ayrac">/</span>
            <span class="simdiki"><?= e($u->ad) ?></span>
        </nav>

        <div class="pd-grid">
            <!-- GALERİ -->
            <div class="pd-gallery">
                <div class="pd-main">
                    <img id="anaGorsel" src="<?= e(gorsel_url($ilk_gorsel)) ?>" alt="<?= e($u->ad) ?>" decoding="async">
                </div>
                <?php if (count($gorseller) > 1): ?>
                <div class="pd-thumbs">
                    <?php foreach ($gorseller as $i => $g): ?>
                        <button type="button" class="pd-thumb<?= $i === 0 ? ' aktif' : '' ?>" data-src="<?= e(gorsel_url($g)) ?>">
                            <img src="<?= e(gorsel_url($g)) ?>" alt="" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- BİLGİ -->
            <div class="pd-info">
                <?php if (! empty($u->kategori_slug)): ?>
                    <a class="pd-kat-link" href="<?= e(site_url('katalog/' . $u->kategori_slug)) ?>"><?= e($u->kategori_adi) ?></a>
                <?php endif; ?>
                <h1 class="pd-baslik"><?= e($u->ad) ?></h1>
                <div class="pd-meta">
                    <span class="pd-sku mono">SKU: <?= e($u->stok_kodu) ?></span>
                </div>

                <div class="pd-fiyat">
                    <span class="pd-fiyat-now"><?= para_goster($u->fiyat) ?></span>
                    <?php if (! empty($u->eski_fiyat) && $u->eski_fiyat > $u->fiyat): ?>
                        <span class="pd-fiyat-eski"><?= para_goster($u->eski_fiyat) ?></span>
                    <?php endif; ?>
                    <span class="badge badge--green-soft"><?= t('detay_toptan', 'Adet başı toptan') ?></span>
                </div>

                <?php if (! empty($renkler)): ?>
                <div class="pd-opt">
                    <div class="pd-opt-label"><?= t('detay_renk', 'Renk') ?>: <b id="renkSecili"><?= e($renk_secili) ?></b></div>
                    <div class="pd-renkler">
                        <?php foreach ($renkler as $r): ?>
                            <button type="button" class="renk-sw<?= $r === $renk_secili ? ' aktif' : '' ?>" data-renk="<?= e($r) ?>" style="background:<?= e(renk_hex($r)) ?>" title="<?= e($r) ?>" aria-label="<?= e($r) ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (! empty($bedenler)): ?>
                <div class="pd-opt">
                    <div class="pd-opt-label"><?= t('detay_beden', 'Beden') ?></div>
                    <div class="pd-bedenler" id="bedenGrup">
                        <?php foreach ($bedenler as $b): ?>
                            <button type="button" class="beden-btn" data-beden="<?= e($b) ?>"><?= e($b) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="pd-beden-uyari" id="bedenUyari"></div>
                </div>
                <?php endif; ?>

                <!-- ADET + basamak -->
                <div class="pd-adet-satir">
                    <div class="pd-stepper">
                        <button type="button" id="adetEksi" aria-label="<?= t('detay_azalt', 'Azalt') ?>">−</button>
                        <input type="number" id="adetInput" value="<?= $moq ?>" min="<?= $moq ?>" step="<?= $adim ?>" inputmode="numeric">
                        <button type="button" id="adetArti" aria-label="<?= t('detay_artir', 'Artır') ?>">+</button>
                    </div>
                    <div class="pd-adet-bilgi"><?= t('detay_adet_min', "Min. %s adet · %s'li katlar", '<b>' . $moq . '</b>', $adim) ?></div>
                    <div class="pd-adet-bilgi" id="stokBilgi" hidden></div>
                    <div class="pd-beden-uyari" id="adetUyari" style="margin-top:4px" hidden></div>
                </div>

                <?php if (! empty($basamaklar)): ?>
                <div class="pd-basamak">
                    <div class="pd-basamak-baslik"><?= t('detay_basamak_baslik', 'Adet basamağı indirimi') ?></div>
                    <div class="pd-basamak-liste">
                        <?php foreach ($basamaklar as $b): ?>
                            <span class="pd-basamak-item"><?= t('detay_basamak_adet', '%s+ adet', (int) $b->min_adet) ?> <b>%<?= e(number_format($b->indirim_yuzde, 0)) ?></b></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="pd-toplam">
                    <span><?= t('detay_toplam', 'Toplam:') ?></span>
                    <b id="toplamTutar"><?= para_goster($u->fiyat * $moq) ?></b>
                    <span class="pd-toplam-birim" id="toplamBirim"><?= t('detay_toplam_birim', '(%s / adet)', para_goster($u->fiyat)) ?></span>
                </div>

                <div class="pd-sepet-satir">
                    <button type="button" class="btn btn-primary btn--lg pd-sepet" id="pdSepet"><?= t('detay_sepete', 'Sepete Ekle') ?></button>
                    <a class="btn btn-secondary btn--lg" href="<?= e(site_url('bayi/kayit')) ?>"><?= t('detay_bayi_fiyat', 'Bayi Fiyatları') ?></a>
                    <?php if (! empty($favorilerde)): ?>
                    <a class="btn btn-secondary btn--lg pd-favoride" href="<?= site_url('favoriler/sil/' . $u->id) ?>" title="<?= t('detay_favori_cikar', 'Favorilerden çıkar') ?>">♥ <?= t('detay_favorilerde', 'Favorilerde') ?></a>
                    <?php else: ?>
                    <a class="btn btn-secondary btn--lg" href="<?= site_url('favoriler/ekle/' . $u->id) ?>"><?= t('detay_favori_ekle', '♡ Favorilere Ekle') ?></a>
                    <?php endif; ?>
                </div>

                <ul class="pd-deger">
                    <li><span>✓</span> <?= t('detay_deger_1', 'Üretici garantisi · gerçek stok') ?></li>
                    <li><span>✓</span> <?= t('detay_deger_2', '%s üzeri ücretsiz kargo', para_goster((float) ayar('ucretsiz_kargo_esik', '2000'))) ?></li>
                    <li><span>✓</span> <?= t('detay_deger_3', 'Hızlı sevkiyat (Merter, İstanbul)') ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="pd-tabs">
            <button class="pd-tab aktif" data-tab="aciklama"><?= t('detay_tab_1', 'Açıklama') ?></button>
            <button class="pd-tab" data-tab="kumas"><?= t('detay_tab_2', 'Kumaş & Bakım') ?></button>
            <button class="pd-tab" data-tab="teslimat"><?= t('detay_tab_3', 'Teslimat') ?></button>
        </div>
        <div class="pd-tab-panel aktif" data-panel="aciklama">
            <div class="prose">
                <?= ! empty($u->aciklama) ? $u->aciklama : '<p>' . t('detay_aciklama_yok', 'Bu ürün için detaylı açıklama hazırlanıyor.') . '</p>' ?>
            </div>
        </div>
        <div class="pd-tab-panel" data-panel="kumas">
            <div class="prose">
                <p><?= t('detay_kumas_not', 'Ürün kumaş ve bakım bilgileri ürün ekleme sırasında girilir. Genel bakım önerisi: 30°de yıkayın, ağartıcı kullanmayın, düşük ısıda ütüleyin.') ?></p>
            </div>
        </div>
        <div class="pd-tab-panel" data-panel="teslimat">
            <div class="prose">
                <p><?= t('detay_teslimat_not', 'Siparişler aynı iş günü hazırlanır ve anlaşmalı kargo firmalarıyla gönderilir. %s üzeri siparişlerde kargo ücretsizdir.', para_goster((float) ayar('ucretsiz_kargo_esik', '2000'))) ?></p>
            </div>
        </div>
    </div>
</section>

<?php if (! empty($benzer)): ?>
<section class="section section--tight" style="background:var(--surface)">
    <div class="container">
        <div class="section__head">
            <span class="section__eyebrow"><?= t('detay_benzer', 'Benzer Ürünler') ?></span>
            <h2 class="section__title"><?= t('detay_benzer_alt', 'Bunları da beğenebilirsiniz') ?></h2>
        </div>
        <div class="prodgrid">
            <?php foreach ($benzer as $b): ?>
                <?php $this->load->view('magaza/partial/urun_karti', array('urun' => $b)); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script id="pdVeri" type="application/json"><?= json_encode(array(
    'id'       => (int) $u->id,
    'varyant'  => $varyant_map,
    'fiyat'    => (float) $u->fiyat,
    'kur'      => kur_getir($pb_kod),        // XXXIV: JS toplamı bu kurla böler
    'sembol'   => para_sembol($pb_kod),
    'moq'      => $moq,
    'adim'     => $adim,
    'basamak'  => array_map(function ($x) { return array('min' => (int) $x->min_adet, 'yuzde' => (float) $x->indirim_yuzde); }, $basamaklar),
    // XLV: stok tavanı uyarı/bilgi metinleri JS tarafında aktif dille verilir.
    'metin'    => array(
        'stok' => t('detay_stok', 'Stok: %s adet'),
        'ust'  => t('detay_stok_ust', 'En fazla %s adet alabilirsiniz (mevcut stok).'),
    ),
), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<?php
// Yapısal veri (XXXVII): schema.org Product — arama motoru zengin sonuçları.
// Fiyat daima TRY bazlıdır (vitrin para birimi oturum bazlı görüntü dönüşümüdür).
$_ld_gorsel = (string) $ilk_gorsel;
if ($_ld_gorsel !== '' && strpos($_ld_gorsel, 'http') !== 0) {
    $_ld_gorsel = rtrim((string) base_url(), '/') . '/' . ltrim($_ld_gorsel, '/');
}
$_ld = array(
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => (string) $u->ad,
    'image'       => $_ld_gorsel !== '' ? array($_ld_gorsel) : NULL,
    'sku'         => 'U' . (int) $u->id,
    'offers'      => array(
        '@type'         => 'Offer',
        'url'           => site_url('urun/' . $u->slug),
        'priceCurrency' => 'TRY',
        'price'         => number_format((float) $u->fiyat, 2, '.', ''),
        'availability'  => (int) ($u->stok ?? 1) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ),
);
if (trim(strip_tags((string) ($u->aciklama ?? ''))) !== '') {
    $_ld['description'] = mb_substr(trim(strip_tags((string) $u->aciklama)), 0, 500);
}
?>
<script type="application/ld+json"><?= json_encode($_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
