<?php defined('BASEPATH') OR exit('No direct script access allowed');
$ilk_ad = explode(' ', trim((string) $b->yetkili_ad_soyad));
$ilk_ad = isset($ilk_ad[0]) ? $ilk_ad[0] : $b->yetkili_ad_soyad;
?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <span class="simdiki">Hesabım</span></nav>
        <h1 class="kat-baslik">Merhaba, <?= e($ilk_ad) ?></h1>
    </div>
</section>

<section class="section section--tight"><div class="container hesabim-grid">
    <?php $this->load->view('magaza/hesabim/_menu'); ?>
    <div class="hesabim-main">
        <?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="notice"><?= e($bilgi) ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
            <div class="sepet-ozet"><div class="sepet-ozet-satr"><span style="color:var(--steel);font-size:13px">Toplam Sipariş</span><b style="font-size:22px"><?= (int) $siparis_sayi ?></b></div></div>
            <div class="sepet-ozet"><div class="sepet-ozet-satr"><span style="color:var(--steel);font-size:13px">Aktif Sipariş</span><b style="font-size:22px"><?= (int) $aktif_sayi ?></b></div></div>
            <div class="sepet-ozet"><div class="sepet-ozet-satr"><span style="color:var(--steel);font-size:13px">Grup İndirimi</span><b style="font-size:22px"><?= $indirim ? '%' . number_format($indirim, 0) : '—' ?></b></div></div>
        </div>

        <div class="odeme-kart" style="margin:0">
            <h3 style="margin-bottom:12px">Son Siparişler</h3>
            <?php if (empty($son_siparisler)): ?>
                <p class="text-steel">Henüz siparişiniz yok.</p>
                <a class="btn btn-primary btn-sm" href="<?= site_url('katalog') ?>">Alışverişe Başla →</a>
            <?php else: ?>
                <table class="tablo-sepet">
                    <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Durum</th><th class="sag">Toplam</th></tr></thead>
                    <tbody>
                    <?php foreach ($son_siparisler as $s): $de = durum_etiket($s->durum); ?>
                        <tr>
                            <td><a class="b" href="<?= site_url('hesabim/siparis/' . $s->id) ?>"><?= e($s->siparis_no) ?></a></td>
                            <td><small><?= e(date('d.m.Y', strtotime($s->olusturma_zaman))) ?></small></td>
                            <td><span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></td>
                            <td class="sag"><?= para_formatla($s->toplam, $s->para_birimi) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a class="btn btn-ghost btn-sm" href="<?= site_url('hesabim/siparisler') ?>" style="margin-top:12px">Tüm Siparişler →</a>
            <?php endif; ?>
        </div>
    </div>
</div></section>
