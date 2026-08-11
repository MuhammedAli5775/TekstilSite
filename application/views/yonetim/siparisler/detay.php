<?php defined('BASEPATH') OR exit('No direct script access allowed');
$de = durum_etiket($s->durum);
$islem = (float) $s->islem_ucreti;
$kargo = (float) $s->kargo_ucreti;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2>Sipariş <?= e($s->siparis_no) ?> <span class="rozet rozet-<?= e($de[1]) ?>" style="vertical-align:middle"><?= e($de[0]) ?></span></h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/siparisler') ?>">← Listeye</a>
</div>

<div class="adm-detay-grid">
    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Durum Güncelle</h3></div>
            <form action="<?= site_url('yonetim/siparisler/durum_guncelle/' . $s->id) ?>" method="post">
                <?= csrf_field() ?>
                <div class="fld-row">
                    <div class="fld"><label>Yeni Durum</label>
                        <select name="durum" id="durumSelect">
                            <?php foreach ($durumlar as $k => $ad): ?><option value="<?= e($k) ?>" <?= $s->durum === $k ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld" id="kargoAlan" style="display:none"><label>Kargo Firması</label>
                        <select name="kargo_firma_id">
                            <?php foreach ($kargo_firmalari as $kf): ?><option value="<?= (int) $kf->id ?>" <?= (int) $s->kargo_firma_id === (int) $kf->id ? 'selected' : '' ?>><?= e($kf->ad) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="fld" id="takipAlan" style="display:none"><label>Kargo Takip No</label><input type="text" name="kargo_takip_no" value="<?= e($s->kargo_takip_no ?? '') ?>"></div>
                <div class="fld"><label>Not (bayiye görünür)</label><textarea name="notu" rows="2" placeholder="Opsiyonel açıklama…"></textarea></div>
                <button type="submit" class="btn btn-primary">Güncelle</button>
            </form>
        </div>

        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Durum Geçmişi</h3></div>
            <?php foreach ($s->gecmis as $g): $ge = durum_etiket($g->durum); ?>
                <div class="adm-kv"><span><b><?= e($ge[0]) ?></b><br><small><?= e(date('d.m.Y H:i', strtotime($g->zaman))) ?> · <?= e($g->taraf) ?><?= $g->notu ? ' · ' . e($g->notu) : '' ?></small></span><span></span></div>
            <?php endforeach; ?>
        </div>

        <div class="adm-card adm-card--p0" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Kalemler</h3></div>
            <div class="adm-tbl-sar">
                <table class="adm-tbl">
                    <thead><tr><th>Ürün</th><th>Varyant</th><th class="sag">Adet</th><th class="sag">Tutar</th></tr></thead>
                    <tbody>
                    <?php foreach ($s->detaylar as $d): ?>
                        <tr><td><?= e($d->urun_adi) ?><br><small class="mono"><?= e($d->stok_kodu) ?></small></td><td><?= e($d->varyant_bilgi ?: '-') ?></td><td class="sag"><?= (int) $d->adet ?></td><td class="sag"><?= para_tr($d->birim_fiyat * $d->adet) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="adm-card">
            <div class="adm-card-baslik"><h3>Özet</h3></div>
            <div class="adm-kv"><span>Sipariş no</span><b><?= e($s->siparis_no) ?></b></div>
            <div class="adm-kv"><span>Tarih</span><b><?= e(date('d.m.Y H:i', strtotime($s->olusturma_zaman))) ?></b></div>
            <div class="adm-kv"><span>Ödeme</span><b><?= e($s->odeme_yontemi) ?> · <?= e($s->odeme_durumu) ?></b></div>
            <div class="adm-kv"><span>Ara toplam</span><b><?= para_tr($s->ara_toplam) ?></b></div>
            <?php if ($islem > 0): ?><div class="adm-kv"><span>İşlem ücreti</span><b><?= para_tr($islem) ?></b></div><?php endif; ?>
            <div class="adm-kv"><span>Kargo</span><b><?= $kargo > 0 ? para_tr($kargo) : 'Ücretsiz' ?></b></div>
            <div class="adm-kv"><span><b>Toplam</b></span><b><?= para_tr($s->toplam) ?></b></div>
            <?php if (! empty($s->kargo_takip_no)): ?><div class="adm-kv"><span>Kargo</span><b><?= e($s->kargo_firma ?: '') ?> · <?= e($s->kargo_takip_no) ?></b></div><?php endif; ?>
        </div>
        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Bayi</h3></div>
            <div class="adm-kv"><span>Firma</span><b><?= e($s->firma_adi ?: '-') ?></b></div>
            <div class="adm-kv"><span>Yetkili</span><b><?= e($s->yetkili_ad_soyad ?: '-') ?></b></div>
            <div class="adm-kv"><span>E-posta</span><b><?= e($s->bayi_email ?: '-') ?></b></div>
            <div class="adm-kv"><span>Telefon</span><b><?= e($s->bayi_telefon ?: '-') ?></b></div>
        </div>
        <div class="adm-card" style="margin-top:16px">
            <div class="adm-card-baslik"><h3>Teslimat</h3></div>
            <p style="font-size:13px;color:var(--slate);line-height:1.6"><?= e($s->teslimat_ad) ?><br><?= nl2br(e($s->teslimat_adres)) ?><br><?= e($s->teslimat_il) ?> <?= e($s->teslimat_ilce) ?><br><?= e($s->teslimat_telefon) ?></p>
        </div>
    </div>
</div>
<script>(function(){var sel=document.getElementById('durumSelect');if(!sel)return;var ka=document.getElementById('kargoAlan'),ta=document.getElementById('takipAlan');function t(){var v=sel.value;ka.style.display=v==='kargolandi'?'':'none';ta.style.display=v==='kargolandi'?'':'none';}sel.addEventListener('change',t);t();})();</script>
