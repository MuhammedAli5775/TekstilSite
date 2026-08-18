<?php defined('BASEPATH') OR exit('No direct script access allowed');
$de = durum_etiket($s->durum);
$detaylar = isset($s->detaylar) ? $s->detaylar : array();
$fatura_durum = array(
    'bekliyor'   => array(t('fdurum_bekliyor', 'Bekliyor'), 'gri'),
    'isleniyor'  => array(t('fdurum_isleniyor', 'İşleniyor'), 'mavi'),
    'olustu'     => array(t('fdurum_olustu', 'Oluştu'), 'yesil'),
    'gonderildi' => array(t('fdurum_gonderildi', 'Gönderildi'), 'yesil'),
    'reddedildi' => array(t('fdurum_reddedildi', 'Reddedildi'), 'kirmizi'),
    'iptal'      => array(t('fdurum_iptal', 'İptal'), 'gri'),
);
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>"><?= t('hesap_baslik', 'Hesabım') ?></a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim/siparisler') ?>"><?= t('hesap_siparisler_link', 'Siparişler') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= e($s->siparis_no) ?></span></nav>
        <h1 class="kat-baslik"><?= t('hesap_siparis_b', 'Sipariş %s', e($s->siparis_no)) ?> <span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></h1>
    </div>
</section>

<section class="section section--tight"><div class="container hesabim-grid">
    <?php $this->load->view('magaza/hesabim/_menu'); ?>
    <div class="hesabim-main">
        <div class="odeme-kart" style="margin-bottom:16px">
            <table class="tablo-sepet">
                <thead><tr><th><?= t('sepet_th_urun', 'Ürün') ?></th><th><?= t('sepet_th_varyant', 'Varyant') ?></th><th class="sag"><?= t('sepet_th_adet', 'Adet') ?></th><th class="sag"><?= t('sepet_th_tutar', 'Tutar') ?></th></tr></thead>
                <tbody>
                <?php foreach ($detaylar as $d): ?>
                    <tr>
                        <td><?= e($d->urun_adi) ?><br><small class="mono text-steel"><?= e($d->stok_kodu) ?></small></td>
                        <td><?= e($d->varyant_bilgi ?: '-') ?></td>
                        <td class="sag"><?= (int) $d->adet ?></td>
                        <td class="sag"><?= para_formatla($d->birim_fiyat * $d->adet, $s->para_birimi) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="sepet-ozet">
            <div class="sepet-ozet-satr"><span><?= t('hesap_th_tarih', 'Tarih') ?></span><span><?= e(date('d.m.Y H:i', strtotime($s->olusturma_zaman))) ?></span></div>
            <div class="sepet-ozet-satr"><span><?= t('hesap_para_birimi', 'Para birimi') ?></span><span><?= e($s->para_birimi) ?> <?= t('hesap_kur', '(kur %s)', e($s->kur)) ?></span></div>
            <div class="sepet-ozet-satr"><span><?= t('sepet_ara_toplam', 'Ara toplam') ?></span><span><?= para_formatla($s->ara_toplam, $s->para_birimi) ?></span></div>
            <?php if ((float) $s->islem_ucreti > 0): ?><div class="sepet-ozet-satr"><span><?= t('hesap_islem_ucreti', 'İşlem ücreti') ?></span><span><?= para_formatla($s->islem_ucreti, $s->para_birimi) ?></span></div><?php endif; ?>
            <div class="sepet-ozet-satr"><span><?= t('sepet_kargo', 'Kargo') ?></span><span><?= (float) $s->kargo_ucreti > 0 ? para_formatla($s->kargo_ucreti, $s->para_birimi) : t('sepet_ucretsiz', 'Ücretsiz') ?></span></div>
            <div class="sepet-ozet-toplam"><span><?= t('sepet_toplam', 'Toplam') ?></span><span><?= para_formatla($s->toplam, $s->para_birimi) ?></span></div>
            <?php if (! empty($s->kargo_takip_no)): ?><div class="sepet-ozet-satr"><span><?= t('hesap_kargo_takip', 'Kargo Takip') ?></span><span><?= e($s->kargo_firma ?: '') ?> · <?= e($s->kargo_takip_no) ?></span></div><?php endif; ?>
        </div>

        <div class="odeme-kart" style="margin-top:16px">
            <h3 style="margin-bottom:8px"><?= t('hesap_teslimat_adresi', 'Teslimat Adresi') ?></h3>
            <p style="font-size:14px;color:var(--slate);line-height:1.6"><?= e($s->teslimat_ad) ?><br><?= nl2br(e($s->teslimat_adres)) ?><br><?= e(((string) ($s->teslimat_il ?? '')) . ' ' . ((string) ($s->teslimat_ilce ?? ''))) ?><br><?= e($s->teslimat_telefon) ?></p>
        </div>

        <?php if (! empty($faturalar)): ?>
        <div class="odeme-kart" style="margin-top:16px">
            <h3 style="margin-bottom:8px"><?= t('hesap_faturalar_l', 'Faturalar') ?></h3>
            <table class="tablo-sepet">
                <thead><tr><th><?= t('hesap_th_fatura_no', 'Fatura No') ?></th><th><?= t('hesap_th_tip', 'Tip') ?></th><th><?= t('hesap_th_durum', 'Durum') ?></th><th class="sag"><?= t('sepet_th_tutar', 'Tutar') ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($faturalar as $f): $dm = $fatura_durum[$f->durum] ?? array($f->durum, 'gri'); ?>
                    <tr>
                        <td><b><?= e($f->fatura_no ?: ('#' . $f->id)) ?></b></td>
                        <td><?= $f->tip === 'efatura' ? t('hesap_efatura', 'e-Fatura') : t('hesap_earsiv', 'e-Arşiv') ?></td>
                        <td><span class="rozet rozet-<?= e($dm[1]) ?>"><?= e($dm[0]) ?></span></td>
                        <td class="sag"><?= para_formatla($f->toplam, $f->para_birimi) ?></td>
                        <td><?php if (! empty($f->pdf_url)): ?><a class="btn btn-ghost btn-sm" href="<?= e($f->pdf_url) ?>" target="_blank" rel="noopener">PDF</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div></section>
