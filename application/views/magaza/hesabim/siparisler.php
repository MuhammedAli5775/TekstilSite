<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <a href="<?= site_url('hesabim') ?>">Hesabım</a> <span class="ayrac">/</span> <span class="simdiki">Siparişlerim</span></nav>
        <h1 class="kat-baslik">Siparişlerim</h1>
    </div>
</section>

<section class="section section--tight"><div class="container hesabim-grid">
    <?php $this->load->view('magaza/hesabim/_menu'); ?>
    <div class="hesabim-main">
        <?php if (empty($siparisler)): ?>
            <div class="odeme-kart" style="text-align:center;padding:40px">
                <p style="margin-bottom:12px">Henüz siparişiniz yok.</p>
                <a class="btn btn-primary" href="<?= site_url('katalog') ?>">Alışverişe Başla →</a>
            </div>
        <?php else: ?>
            <div class="odeme-kart" style="margin:0">
                <table class="tablo-sepet">
                    <thead><tr><th>Sipariş No</th><th>Tarih</th><th>Durum</th><th class="sag">Toplam</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($siparisler as $s): $de = durum_etiket($s->durum); ?>
                        <tr>
                            <td><b><?= e($s->siparis_no) ?></b></td>
                            <td><small><?= e(date('d.m.Y', strtotime($s->olusturma_zaman))) ?></small></td>
                            <td><span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></td>
                            <td class="sag"><?= para_formatla($s->toplam, $s->para_birimi) ?></td>
                            <td><a class="btn btn-ghost btn-sm" href="<?= site_url('hesabim/siparis/' . $s->id) ?>">Detay →</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div></section>
