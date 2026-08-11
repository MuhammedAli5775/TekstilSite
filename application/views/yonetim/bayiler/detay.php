<?php defined('BASEPATH') OR exit('No direct script access allowed');
$durum_rozet = array('0' => 'rozet-turuncu', '1' => 'rozet-yesil', '2' => 'rozet-gri');
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2><?= e($b->firma_adi ?: $b->yetkili_ad_soyad) ?> <span class="rozet <?= e($durum_rozet[(string) $b->durum] ?? 'rozet-gri') ?>"><?= e($durumlar[(string) $b->durum] ?? '?') ?></span></h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/bayiler') ?>">← Listeye</a>
</div>

<div class="adm-detay-grid">
    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Durum &amp; Grup</h3></div>
            <form action="<?= site_url('yonetim/bayiler/durum_guncelle/' . $b->id) ?>" method="post" style="margin-bottom:14px">
                <?= csrf_field() ?>
                <div class="fld-row">
                    <div class="fld"><label>Durum</label>
                        <select name="durum"><?php foreach ($durumlar as $k => $ad): ?><option value="<?= e($k) ?>" <?= (string) $b->durum === (string) $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm">Durumu Güncelle</button>
            </form>
            <form action="<?= site_url('yonetim/bayiler/grup_guncelle/' . $b->id) ?>" method="post">
                <?= csrf_field() ?>
                <div class="fld-row">
                    <div class="fld"><label>Fiyat Grubu</label>
                        <select name="grup_id"><?php foreach ($gruplar as $g): ?><option value="<?= (int) $g->id ?>" <?= (int) $b->grup_id === (int) $g->id ? 'selected' : '' ?>><?= e($g->ad) ?> <?= $g->indirim_yuzde ? '(%' . number_format($g->indirim_yuzde, 0) . ')' : '' ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <button class="btn btn-secondary btn-sm">Grubu Güncelle</button>
            </form>
        </div>

        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Sipariş Özeti</h3></div>
            <div class="adm-kv"><span>Toplam sipariş</span><b><?= (int) $ozet->adet ?></b></div>
            <div class="adm-kv"><span>Toplam ciro</span><b><?= para_tr($ozet->ciro) ?></b></div>
        </div>

        <?php if (! empty($siparisler)): ?>
        <div class="adm-card adm-card--p0" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Son Siparişler</h3></div>
            <div class="adm-tbl-sar">
                <table class="adm-tbl"><thead><tr><th>Sipariş</th><th>Durum</th><th class="sag">Toplam</th></tr></thead><tbody>
                <?php foreach ($siparisler as $s): $de = durum_etiket($s->durum); ?>
                    <tr><td><a class="b" href="<?= site_url('yonetim/siparisler/detay/' . $s->id) ?>"><?= e($s->siparis_no) ?></a><br><small><?= e(date('d.m.Y', strtotime($s->olusturma_zaman))) ?></small></td><td><span class="rozet rozet-<?= e($de[1]) ?>"><?= e($de[0]) ?></span></td><td class="sag"><?= para_tr($s->toplam) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Bayi Bilgileri</h3></div>
            <div class="adm-kv"><span>Firma</span><b><?= e($b->firma_adi ?: '-') ?></b></div>
            <div class="adm-kv"><span>Yetkili</span><b><?= e($b->yetkili_ad_soyad ?: '-') ?></b></div>
            <div class="adm-kv"><span>E-posta</span><b><?= e($b->email) ?></b></div>
            <div class="adm-kv"><span>Telefon</span><b><?= e($b->telefon ?: '-') ?></b></div>
            <div class="adm-kv"><span>Vergi no</span><b><?= e($b->vergi_no ?: '-') ?></b></div>
            <div class="adm-kv"><span>Vergi dairesi</span><b><?= e($b->vergi_dairesi ?: '-') ?></b></div>
            <div class="adm-kv"><span>Kayıt</span><b><?= e(date('d.m.Y', strtotime($b->olusturma_zaman))) ?></b></div>
            <div class="adm-kv"><span>Son giriş</span><b><?= $b->son_giris ? e(date('d.m.Y H:i', strtotime($b->son_giris))) : '-' ?></b></div>
        </div>
    </div>
</div>
