<?php defined('BASEPATH') OR exit('No direct script access allowed');
$durum_map = array(
    'bekliyor'   => array('Bekliyor', 'gri'),
    'isleniyor'  => array('İşleniyor', 'mavi'),
    'olustu'     => array('Oluştu', 'yesil'),
    'gonderildi' => array('Gönderildi', 'yesil'),
    'reddedildi' => array('Reddedildi', 'kirmizi'),
    'iptal'      => array('İptal', 'gri'),
);
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>">Hesabım</a> <span class="ayrac">/</span> <span class="simdiki">Faturalarım</span></nav>
        <h1 class="kat-baslik">Faturalarım</h1>
    </div>
</section>

<section class="section section--tight"><div class="container hesabim-grid">
    <?php $this->load->view('magaza/hesabim/_menu'); ?>
    <div class="hesabim-main">
        <?php if (empty($faturalar)): ?>
            <div class="odeme-kart" style="text-align:center;padding:40px">
                <p style="margin-bottom:12px">Henüz faturanız yok. Siparişiniz için fatura kesildiğinde burada listelenir.</p>
                <a class="btn btn-primary" href="<?= site_url('hesabim/siparisler') ?>">Siparişlerim →</a>
            </div>
        <?php else: ?>
            <div class="odeme-kart" style="margin:0">
                <table class="tablo-sepet">
                    <thead><tr><th>Fatura No</th><th>Sipariş</th><th>Tip</th><th>Durum</th><th class="sag">Tutar</th><th>Tarih</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($faturalar as $f): $dm = $durum_map[$f->durum] ?? array($f->durum, 'gri'); ?>
                        <tr>
                            <td><b><?= e($f->fatura_no ?: ('#' . $f->id)) ?></b></td>
                            <td><a href="<?= site_url('hesabim/siparis/' . (int) $f->siparis_id) ?>"><?= e($f->siparis_no ?: ('#' . $f->siparis_id)) ?></a></td>
                            <td><?= $f->tip === 'efatura' ? 'e-Fatura' : 'e-Arşiv' ?></td>
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
