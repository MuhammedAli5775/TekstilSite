<?php defined('BASEPATH') OR exit('No direct script access allowed');
$u = $u; // ürün nesnesi
$ilk_gorsel = ! empty($gorseller) ? $gorseller[0] : '';
$moq = (int) $u->moq;
$adim = max(1, (int) $u->birim_adim);
$renk_secili = ! empty($renkler) ? $renkler[0] : null;
?>
<section class="pd-hero">
    <div class="container">
        <nav class="kirinti" aria-label="Yol">
            <a href="<?= site_url() ?>">Anasayfa</a>
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
                    <span class="pd-fiyat-now"><?= para_tr($u->fiyat) ?></span>
                    <?php if (! empty($u->eski_fiyat) && $u->eski_fiyat > $u->fiyat): ?>
                        <span class="pd-fiyat-eski"><?= para_tr($u->eski_fiyat) ?></span>
                    <?php endif; ?>
                    <span class="badge badge--green-soft">Adet başı toptan</span>
                </div>

                <?php if (! empty($renkler)): ?>
                <div class="pd-opt">
                    <div class="pd-opt-label">Renk: <b id="renkSecili"><?= e($renk_secili) ?></b></div>
                    <div class="pd-renkler">
                        <?php foreach ($renkler as $r): ?>
                            <button type="button" class="renk-sw<?= $r === $renk_secili ? ' aktif' : '' ?>" data-renk="<?= e($r) ?>" style="background:<?= e(renk_hex($r)) ?>" title="<?= e($r) ?>" aria-label="<?= e($r) ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (! empty($bedenler)): ?>
                <div class="pd-opt">
                    <div class="pd-opt-label">Beden</div>
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
                        <button type="button" id="adetEksi" aria-label="Azalt">−</button>
                        <input type="number" id="adetInput" value="<?= $moq ?>" min="<?= $moq ?>" step="<?= $adim ?>" inputmode="numeric">
                        <button type="button" id="adetArti" aria-label="Artır">+</button>
                    </div>
                    <div class="pd-adet-bilgi">Min. <b><?= $moq ?></b> adet · <?= $adim ?>'li katlar</div>
                </div>

                <?php if (! empty($basamaklar)): ?>
                <div class="pd-basamak">
                    <div class="pd-basamak-baslik">Adet basamağı indirimi</div>
                    <div class="pd-basamak-liste">
                        <?php foreach ($basamaklar as $b): ?>
                            <span class="pd-basamak-item"><?= (int) $b->min_adet ?>+ adet <b>%<?= e(number_format($b->indirim_yuzde, 0)) ?></b></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="pd-toplam">
                    <span>Toplam:</span>
                    <b id="toplamTutar"><?= para_tr($u->fiyat * $moq) ?></b>
                    <span class="pd-toplam-birim" id="toplamBirim">(<?= para_tr($u->fiyat) ?> / adet)</span>
                </div>

                <div class="pd-sepet-satir">
                    <button type="button" class="btn btn-primary btn--lg pd-sepet" id="pdSepet">Sepete Ekle</button>
                    <a class="btn btn-secondary btn--lg" href="<?= e(site_url('bayi/kayit')) ?>">Bayi Fiyatları</a>
                    <a class="btn btn-secondary btn--lg" href="<?= site_url('favoriler/ekle/' . $u->id) ?>">♡ Favorilere Ekle</a>
                </div>

                <ul class="pd-deger">
                    <li><span>✓</span> Üretici garantisi · gerçek stok</li>
                    <li><span>✓</span> <?= ayar('ucretsiz_kargo_esik', '2000') ?> ₺ üzeri ücretsiz kargo</li>
                    <li><span>✓</span> Hızlı sevkiyat (Merter, İstanbul)</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="pd-tabs">
            <button class="pd-tab aktif" data-tab="aciklama">Açıklama</button>
            <button class="pd-tab" data-tab="kumas">Kumaş &amp; Bakım</button>
            <button class="pd-tab" data-tab="teslimat">Teslimat</button>
        </div>
        <div class="pd-tab-panel aktif" data-panel="aciklama">
            <div class="prose">
                <?= ! empty($u->aciklama) ? $u->aciklama : '<p>Bu ürün için detaylı açıklama hazırlanıyor.</p>' ?>
            </div>
        </div>
        <div class="pd-tab-panel" data-panel="kumas">
            <div class="prose">
                <p>Ürün kumaş ve bakım bilgileri ürün ekleme sırasında girilir. Genel bakım önerisi: 30°de yıkayın, ağartıcı kullanmayın, düşük ısıda ütüleyin.</p>
            </div>
        </div>
        <div class="pd-tab-panel" data-panel="teslimat">
            <div class="prose">
                <p>Siparişler aynı iş günü hazırlanır ve anlaşmalı kargo firmalarıyla gönderilir. <?= ayar('ucretsiz_kargo_esik', '2000') ?> ₺ üzeri siparişlerde kargo ücretsizdir.</p>
            </div>
        </div>
    </div>
</section>

<?php if (! empty($benzer)): ?>
<section class="section section--tight" style="background:var(--surface)">
    <div class="container">
        <div class="section__head">
            <span class="section__eyebrow">Benzer Ürünler</span>
            <h2 class="section__title">Bunları da beğenebilirsiniz</h2>
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
    'moq'      => $moq,
    'adim'     => $adim,
    'basamak'  => array_map(function ($x) { return array('min' => (int) $x->min_adet, 'yuzde' => (float) $x->indirim_yuzde); }, $basamaklar),
), JSON_UNESCAPED_UNICODE) ?></script>
