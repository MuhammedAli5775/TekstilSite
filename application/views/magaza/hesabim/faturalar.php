<?php defined('BASEPATH') OR exit('No direct script access allowed');
$durum_map = array(
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
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>"><?= t('hesap_baslik', 'Hesabım') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('hesap_faturalar_b', 'Faturalarım') ?></span></nav>
        <h1 class="kat-baslik"><?= t('hesap_faturalar_b', 'Faturalarım') ?></h1>
    </div>
</section>

<section class="section section--tight"><div class="container hesabim-grid">
    <?php $this->load->view('magaza/hesabim/_menu'); ?>
    <div class="hesabim-main">
        <?php if (empty($faturalar)): ?>
            <div class="odeme-kart" style="text-align:center;padding:40px">
                <p style="margin-bottom:12px"><?= t('hesap_fatura_yok', 'Henüz faturanız yok. Siparişiniz için fatura kesildiğinde burada listelenir.') ?></p>
                <a class="btn btn-primary" href="<?= site_url('hesabim/siparisler') ?>"><?= t('hesap_siparislerim_link', 'Siparişlerim →') ?></a>
            </div>
        <?php else: ?>
            <div class="odeme-kart" style="margin:0">
                <table class="tablo-sepet">
                    <thead><tr><th><?= t('hesap_th_fatura_no', 'Fatura No') ?></th><th><?= t('hesap_th_siparis', 'Sipariş') ?></th><th><?= t('hesap_th_tip', 'Tip') ?></th><th><?= t('hesap_th_durum', 'Durum') ?></th><th class="sag"><?= t('sepet_th_tutar', 'Tutar') ?></th><th><?= t('hesap_th_tarih', 'Tarih') ?></th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($faturalar as $f): $dm = $durum_map[$f->durum] ?? array($f->durum, 'gri'); ?>
                        <tr>
                            <td><b><?= e($f->fatura_no ?: ('#' . $f->id)) ?></b></td>
                            <td><a href="<?= site_url('hesabim/siparis/' . (int) $f->siparis_id) ?>"><?= e($f->siparis_no ?: ('#' . $f->siparis_id)) ?></a></td>
                            <td><?= $f->tip === 'efatura' ? t('hesap_efatura', 'e-Fatura') : t('hesap_earsiv', 'e-Arşiv') ?></td>
                            <td><span class="rozet rozet-<?= e($dm[1]) ?>"><?= e($dm[0]) ?></span></td>
                            <td class="sag"><?= para_formatla($f->toplam, $f->para_birimi) ?></td>
                            <td><small><?= e(date('d.m.Y', strtotime($f->olusturma_zaman))) ?></small></td>
                            <td><?php if (! empty($f->pdf_url)): ?><a class="btn btn-ghost btn-sm" href="<?= e($f->pdf_url) ?>" target="_blank" rel="noopener">PDF</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div></section>
