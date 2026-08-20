<?php defined('BASEPATH') OR exit('No direct script access allowed');
$durum_map = array(
    'bekliyor'   => array('Bekliyor', 'gri'),
    'isleniyor'  => array('İşleniyor', 'mavi'),
    'olustu'     => array('Oluştu', 'yesil'),
    'gonderildi' => array('Gönderildi', 'yesil'),
    'reddedildi' => array('Reddedildi', 'kirmizi'),
    'iptal'      => array('İptal', 'gri'),
);
$dm = $durum_map[$f->durum] ?? array($f->durum, 'gri');
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Fatura #<?= (int) $f->id ?> <span class="rozet rozet-<?= e($dm[1]) ?>"><?= e($dm[0]) ?></span></h2>
    <span>
        <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/faturalar') ?>">← Listeye</a>
        <?php if ($f->process_id): ?><a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/faturalar/yenile/' . $f->id) ?>">Durum Yenile</a><?php endif; ?>
        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/faturalar/sil/' . $f->id) ?>" onclick="return confirm('Fatura kaydı silinsin mi?')">Sil</a>
    </span>
</div>

<?php if (! empty($f->hata_mesaji)): ?>
<div class="adm-uyari adm-uyari--hata"><b>Hata:</b> <?= nl2br(e($f->hata_mesaji)) ?></div>
<?php endif; ?>

<div class="adm-detay-grid">
    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Fatura Bilgisi</h3></div>
            <div class="adm-kv"><span>Fatura no</span><b><?= e($f->fatura_no ?: '-') ?></b></div>
            <div class="adm-kv"><span>Tip</span><b><?= $f->tip === 'efatura' ? 'e-Fatura' : 'e-Arşiv' ?></b></div>
            <div class="adm-kv"><span>ETN (e-fatura no)</span><b><?= e($f->etn ?: '—') ?></b></div>
            <div class="adm-kv"><span>UUID</span><b class="mono" style="font-size:12px"><?= e($f->uuid ?: '—') ?></b></div>
            <div class="adm-kv"><span>Entegratör</span><b><?= e($f->entegrator ?: '— (manuel / bekliyor)') ?></b></div>
            <?php if ($f->process_id): ?><div class="adm-kv"><span>İşlem ID</span><b class="mono" style="font-size:12px"><?= e($f->process_id) ?></b></div><?php endif; ?>
            <div class="adm-kv"><span>Tarih</span><b><?= e(date('d.m.Y H:i', strtotime($f->olusturma_zaman))) ?></b></div>
            <?php if (! empty($f->pdf_url)): ?><div class="adm-kv"><span>PDF</span><b><a href="<?= e($f->pdf_url) ?>" target="_blank" rel="noopener">İndir / Görüntüle ↗</a></b></div><?php endif; ?>
        </div>

        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Alıcı</h3></div>
            <div class="adm-kv"><span>Ünvan / Ad</span><b><?= e($f->alici_unvan ?: '-') ?></b></div>
            <div class="adm-kv"><span>VKN / T.C.</span><b><?= e($f->alici_vkn ?: '-') ?></b></div>
            <div class="adm-kv"><span>E-posta</span><b><?= e($f->alici_eposta ?: '-') ?></b></div>
        </div>
    </div>

    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Finansal</h3></div>
            <div class="adm-kv"><span>Matrah (KDV hariç)</span><b><?= para_tr($f->matrah) ?></b></div>
            <div class="adm-kv"><span>KDV (%20 varsayım)</span><b><?= para_tr($f->kdv) ?></b></div>
            <div class="adm-kv"><span><b>Genel toplam</b></span><b><?= para_tr($f->toplam) ?></b></div>
            <small style="color:var(--stone)">Matrah/KDV, ürün ara toplamından %20 KDV ayrışımıyla hesaplanır (KDV-dahil fiyatlandırma varsayımı). Gerçek entegratör gönderiminde satıcının KDV şeması esas alınır.</small>
        </div>

        <?php if ($s): ?>
        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Bağlı Sipariş</h3></div>
            <div class="adm-kv"><span>Sipariş no</span><b><a href="<?= site_url('yonetim/siparisler/detay/' . $s->id) ?>"><?= e($s->siparis_no) ?></a></b></div>
            <div class="adm-kv"><span>Ödeme</span><b><?= e($s->odeme_yontemi) ?></b></div>
            <div class="adm-kv"><span>Sipariş toplamı</span><b><?= para_formatla($s->toplam, $s->para_birimi) ?></b></div>
        </div>
        <?php endif; ?>

        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Satıcı</h3></div>
            <div class="adm-kv"><span>Ünvan</span><b><?= e(ayar('efatura_firma_unvan') ?: ayar('site_adi', 'Nesem Tesettür')) ?></b></div>
            <div class="adm-kv"><span>VKN</span><b><?= e(ayar('efatura_firma_vkn') ?: '— (Ayarlar’dan girin)') ?></b></div>
            <?php if (! $this->efatura->hazir()): ?>
            <small style="color:var(--stone)">Entegratör yapılandırılmamış — fatura “bekliyor”. Ayarlar → E-Fatura’dan API URL + token + satıcı VKN girilince otomatik gönderilir.</small>
            <?php endif; ?>
        </div>
    </div>
</div>
