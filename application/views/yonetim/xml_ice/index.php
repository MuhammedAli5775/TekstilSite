<?php defined('BASEPATH') OR exit('No direct script access allowed');
$d = $duzenle ?? NULL;
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik"><h2>XML İçe Aktarım</h2></div>

<div class="adm-detay-grid">
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Kaynaklar (<?= count($kaynaklar) ?>)</h3></div>
        <div style="padding:8px">
            <?php if (empty($kaynaklar)): ?><div class="adm-bosluk">Henüz kaynak yok.</div><?php endif; ?>
            <?php foreach ($kaynaklar as $k): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px;border-bottom:1px solid var(--hairline);flex-wrap:wrap">
                    <span style="max-width:55%">
                        <b><?= e($k->ad) ?></b>
                        <?php if ((int) $k->durum === 1): ?><small class="rozet rozet-yesil" style="margin-left:6px">Aktif</small><?php else: ?><small class="rozet rozet-gri" style="margin-left:6px">Pasif</small><?php endif; ?>
                        <?php if ($k->son_sonuc): ?><small class="rozet <?= $k->son_sonuc === 'basarili' ? 'rozet-yesil' : 'rozet-gri' ?>" style="margin-left:4px">son: <?= e($k->son_sonuc) ?></small><?php endif; ?>
                        <?php if ((int) $k->yeni_urun_olustur === 0): ?><small class="rozet rozet-gri" style="margin-left:4px">yalnız güncelle</small><?php endif; ?>
                        <?php if ((float) $k->fiyat_carpani != 1.0): ?><small class="rozet rozet-gri" style="margin-left:4px">×<?= e(rtrim(rtrim(number_format((float) $k->fiyat_carpani, 4, '.', ''), '0'), '.')) ?></small><?php endif; ?>
                        <br><small style="color:var(--muted)"><?= e($k->url) ?></small>
                        <?php if ($k->son_calisma): ?><br><small style="color:var(--muted)">son koşu: <?= e(date('d.m.Y H:i', strtotime($k->son_calisma))) ?></small><?php endif; ?>
                    </span>
                    <span>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/xml_ice/onizleme/' . $k->id) ?>">Önizle</a>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/xml_ice/log/' . $k->id) ?>">Log</a>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/xml_ice?duzenle=' . $k->id) ?>">Düzenle</a>
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('yonetim/xml_ice/durum/' . $k->id) ?>"><?= (int) $k->durum === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></a>
                        <a class="btn btn-ghost btn-sm" style="color:var(--danger)" href="<?= site_url('yonetim/xml_ice/sil/' . $k->id) ?>" onclick="return confirm('Kaynak silinsin mi? Logları da silinir.')">Sil</a>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-baslik"><h3><?= $d ? 'Kaynağı Düzenle' : 'Yeni Kaynak' ?></h3></div>
        <form action="<?= site_url('yonetim/xml_ice/kaydet') ?>" method="post">
            <?= csrf_field() ?>
            <?php if ($d): ?><input type="hidden" name="id" value="<?= (int) $d->id ?>"><?php endif; ?>
            <div class="fld"><label>Ad <span class="zor">*</span></label><input type="text" name="ad" value="<?= e($d->ad ?? '') ?>" placeholder="örn. Tedarikçi A katalog feed'i"></div>
            <div class="fld"><label>XML URL <span class="zor">*</span></label><input type="text" name="url" value="<?= e($d->url ?? '') ?>" placeholder="https://tedarikci.com/feed.xml"></div>
            <div class="fld-row">
                <div class="fld"><label>Varsayılan Kategori (yeni ürünlerde)</label>
                    <select name="varsayilan_kategori_id">
                        <option value="0">— kategori eşleşmezse boş bırakma —</option>
                        <?php foreach ($kategoriler as $kat): ?>
                            <option value="<?= (int) $kat->id ?>" <?= $d && (int) $d->varsayilan_kategori_id === (int) $kat->id ? 'selected' : '' ?>><?= e($kat->ad) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label>Fiyat Çarpanı</label><input type="text" name="fiyat_carpani" value="<?= e($d->fiyat_carpani ?? '1') ?>" placeholder="1 (kurusuz)"></div>
            </div>
            <div class="fld"><label><input type="checkbox" name="yeni_urun_olustur" value="1" <?= (! isset($d) || (int) $d->yeni_urun_olustur === 1) ? 'checked' : '' ?> style="width:auto"> Bilinmeyen stok kodunda yeni ürün oluştur</label></div>
            <div class="fld"><label>Alan Eşlemesi (JSON — boş = varsayılan)</label>
                <textarea name="esleme" rows="4" style="font-family:monospace;font-size:12px" placeholder='{"kok":"urun","stokKodu":"StokKodu","ad":"UrunAdi","fiyat":"SatisFiyati","kategori":"Kategori","vStok":"Adet"}'><?= e($d->esleme ?? '') ?></textarea>
            </div>
            <button class="btn btn-primary"><?= $d ? 'Güncelle' : 'Ekle' ?></button>
            <?php if ($d): ?><a class="btn btn-ghost" href="<?= site_url('yonetim/xml_ice') ?>">İptal</a><?php endif; ?>
        </form>
        <div style="margin-top:14px;padding:10px;background:var(--surface-soft);border-radius:8px;font-size:12px;color:var(--slate)">
            <b>Nasıl çalışır:</b> eşleşme anahtarı <b>stok kodu</b> — mevcut ürün güncellenir, bilinmeyen kod yeni ürün olur (izin verildiyse). Varsayılan eşleme sitemizin kendi feed biçimidir (<code>/feed/urunler</code> çıktısı aynen geri alınabilir). Fiyatlar çarpanla çarpılır. <b>Önizle</b> kuru koşudur (yazmaz); gerçek aktarım önizlemedeki butonla, periyodik aktarım cron ile yapılır.
        </div>
    </div>
</div>
