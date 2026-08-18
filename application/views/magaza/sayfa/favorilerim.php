<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('syf_favoriler_b', 'Favorilerim') ?></span></nav>
        <h1 class="kat-baslik"><?= t('syf_favoriler_b', 'Favorilerim') ?></h1>
        <p class="kat-alt"><?= t('syf_favori_sayi', '%s ürün', (int) count($urunler)) ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>

        <?php if (empty($urunler)): ?>
            <div class="card card--feature" style="max-width:560px;margin:auto;text-align:center">
                <p class="text-steel"><?= sprintf(t('syf_favori_yok', 'Henüz favori yok. Ürün detayında %s ile ekleyin.'), '<b>' . e(t('detay_favori_ekle', '♡ Favorilere Ekle')) . '</b>') ?></p>
                <a class="btn btn-primary" href="<?= site_url('katalog') ?>"><?= t('syf_kataloga_gozat', 'Kataloğa Göz At') ?></a>
            </div>
        <?php else: ?>
            <div class="prodgrid">
                <?php foreach ($urunler as $u):
                    $fiyat = (float) $u->fiyat; $eski = (float) ($u->eski_fiyat ?: 0); ?>
                    <article class="prodcard">
                        <a class="prodcard__media" href="<?= e(site_url('urun/' . $u->slug)) ?>" aria-label="<?= e($u->ad) ?>">
                            <?php if ($u->ana_gorsel): ?><img src="<?= e(gorsel_url($u->ana_gorsel)) ?>" alt="<?= e($u->ad) ?>" loading="lazy"><?php else: ?><div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);font-size:13px"><?= t('kart_gorsel_yok', 'görsel yok') ?></div><?php endif; ?>
                        </a>
                        <div class="prodcard__body">
                            <a class="prodcard__name" href="<?= e(site_url('urun/' . $u->slug)) ?>"><?= e($u->ad) ?></a>
                            <?php if ($u->stok_kodu): ?><span class="prodcard__sku"><?= e($u->stok_kodu) ?></span><?php endif; ?>
                            <div class="prodcard__price">
                                <span class="now"><?= para_goster($fiyat) ?></span>
                                <?php if ($eski > $fiyat): ?><span style="text-decoration:line-through;color:var(--muted);font-size:13px"><?= para_goster($eski) ?></span><?php endif; ?>
                                <span class="prodcard__adet-etiket"><?= t('kart_adet', '/ adet') ?></span>
                            </div>
                            <?php $seri = (isset($u->seri_yuzde) && $u->seri_yuzde > 0) ? round($fiyat * (1 - $u->seri_yuzde / 100), 2) : 0; $sAdet = (int) ($u->seri_adet ?? 0); ?>
                            <?php if ($seri > 0 && $seri < $fiyat): ?><div class="prodcard__seri"><?= t('kart_seri', 'Seri') ?> <b><?= para_goster($seri) ?></b> <small><?= t('kart_seri_adet', '%s+ adette', $sAdet) ?></small></div><?php endif; ?>
                        </div>
                        <div class="prodcard__foot" style="padding:0 var(--s-lg) var(--s-lg)">
                            <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('favoriler/sil/' . $u->id) ?>"><?= t('syf_favori_cikar', '♡ Favoriden Çıkar') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
