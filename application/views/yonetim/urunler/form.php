<?php defined('BASEPATH') OR exit('No direct script access allowed');
$u = $u ?? NULL;
$veri = function ($alan, $def = '') use ($u) { return $u ? ($u->{$alan} ?? $def) : $def; };
?>
<?php if ($bilgi = $this->session->flashdata('bilgi')): ?><div class="adm-uyari adm-uyari--ok"><?= e($bilgi) ?></div><?php endif; ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="sayfa-baslik">
    <h2><?= $u ? 'Ürün Düzenle' : 'Yeni Ürün' ?></h2>
    <a class="btn btn-secondary btn-sm" href="<?= site_url('yonetim/urunler') ?>">← Listeye</a>
</div>

<form action="<?= site_url('yonetim/urunler/kaydet') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($u): ?><input type="hidden" name="id" value="<?= (int) $u->id ?>"><?php endif; ?>

    <div class="adm-detay-grid">
        <div>
            <div class="adm-card">
                <div class="adm-card-baslik"><h3>Temel Bilgiler</h3></div>
                <div class="fld"><label>Ürün Adı <span class="zor">*</span></label><input type="text" name="ad" value="<?= e($veri('ad')) ?>" required maxlength="190"></div>
                <div class="fld-row">
                    <div class="fld"><label>Stok Kodu (SKU)</label><input type="text" name="stok_kodu" value="<?= e($veri('stok_kodu')) ?>" placeholder="boşsa otomatik"></div>
                    <div class="fld"><label>Slug (boşsa ad'dan)</label><input type="text" name="slug" value="<?= e($veri('slug')) ?>"></div>
                </div>
                <div class="fld-row">
                    <div class="fld"><label>Kategori</label>
                        <select name="kategori_id"><option value="">— Yok —</option>
                            <?php foreach ($kategoriler as $k): ?>
                                <option value="<?= (int) $k->id ?>" <?= $u && (int) $u->kategori_id === (int) $k->id ? 'selected' : '' ?>><?= str_repeat('— ', $k->ust_id ? 1 : 0) . e($k->ad) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld"><label>Marka</label>
                        <select name="marka_id"><option value="">— Yok —</option>
                            <?php foreach ($markalar as $m): ?><option value="<?= (int) $m->id ?>" <?= $u && (int) $u->marka_id === (int) $m->id ? 'selected' : '' ?>><?= e($m->ad) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="fld"><label>Açıklama</label><textarea name="aciklama" rows="4"><?= e($veri('aciklama')) ?></textarea></div>
                <label class="checkbox"><input type="checkbox" name="durum" value="1" <?= (! $u || $u->durum) ? 'checked' : '' ?>> Aktif (mağazada görünsün)</label>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>Fiyat &amp; Stok (B2B)</h3></div>
                <div class="fld-row">
                    <div class="fld"><label>Alış Fiyatı (₺)</label><input type="number" step="0.01" name="alis_fiyat" value="<?= e($veri('alis_fiyat')) ?>"></div>
                    <div class="fld"><label>Satış Fiyatı (₺) <span class="zor">*</span></label><input type="number" step="0.01" name="fiyat" value="<?= e($veri('fiyat')) ?>" required></div>
                </div>
                <div class="fld-row">
                    <div class="fld"><label>Eski Fiyat (₺, üstü çizili)</label><input type="number" step="0.01" name="eski_fiyat" value="<?= e($veri('eski_fiyat')) ?>"></div>
                    <div class="fld"><label>KDV (%)</label><input type="number" name="kdv" value="<?= e($veri('kdv', 20)) ?>"></div>
                </div>
                <div class="fld-row">
                    <div class="fld"><label>Min. Sipariş (MOQ)</label><input type="number" name="moq" value="<?= e($veri('moq', 1)) ?>" min="1"></div>
                    <div class="fld"><label>Adet Basamağı</label><input type="number" name="birim_adim" value="<?= e($veri('birim_adim', 1)) ?>" min="1"></div>
                </div>
                <div class="fld-row" style="display:flex;gap:18px;align-items:center;margin-top:8px">
                    <label class="checkbox"><input type="checkbox" name="vitrin" value="1" <?= $u && $u->vitrin ? 'checked' : '' ?>> Vitrin</label>
                    <label class="checkbox"><input type="checkbox" name="cok_satan" value="1" <?= $u && $u->cok_satan ? 'checked' : '' ?>> Çok satan</label>
                </div>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>SEO</h3></div>
                <div class="fld"><label>Meta Başlık</label><input type="text" name="meta_title" value="<?= e($veri('meta_title')) ?>"></div>
                <div class="fld"><label>Meta Açıklama</label><textarea name="meta_description" rows="2"><?= e($veri('meta_description')) ?></textarea></div>
            </div>
        </div>

        <div>
            <div class="adm-card">
                <div class="adm-card-baslik"><h3>Görseller</h3></div>
                <div class="fld"><label>Görsel Yükle (çoklu, jpg/png/webp)</label><input type="file" name="gorseller[]" multiple accept="image/*"></div>
                <?php if ($u && ! empty($u->gorseller)): ?>
                    <div class="urun-gorseller">
                        <?php foreach ($u->gorseller as $g): ?>
                            <div class="urun-gorsel">
                                <img src="<?= e(gorsel_url($g->yol)) ?>" alt="">
                                <div class="urun-gorsel-alt">
                                    <?php if ($u->ana_gorsel === $g->yol): ?><span class="rozet rozet-yesil">ana</span>
                                    <?php else: ?>
                                        <form action="<?= site_url('yonetim/urunler/gorsel_ana/' . $u->id) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="gorsel_id" value="<?= (int) $g->id ?>"><button class="btn btn-ghost btn-sm" type="submit">Ana yap</button></form>
                                    <?php endif; ?>
                                    <form action="<?= site_url('yonetim/urunler/gorsel_sil/' . $g->id) ?>" method="post" style="display:inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit" style="color:var(--danger)" onclick="return confirm('Görsel silinsin mi?')">Sil</button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($u): ?><small style="color:var(--stone)">Görsel yok. Yükleyin veya <b>Ana Görsel</b> boş kalır.</small><?php endif; ?>
                <?php if ($u && $u->ana_gorsel && strpos($u->ana_gorsel, 'http') === 0): ?>
                    <div class="fld" style="margin-top:10px"><label>Ana Görsel (URL)</label><input type="text" value="<?= e($u->ana_gorsel) ?>" disabled></div>
                <?php endif; ?>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>Varyantlar (renk / beden / stok)</h3>
                    <button type="button" class="btn btn-ghost btn-sm" id="varyantEkle">+ Satır</button>
                </div>
                <div id="varyantListe">
                    <?php
                    $varyantlar = ($u && ! empty($u->varyantlar)) ? $u->varyantlar : array((object) array('renk' => '', 'beden' => '', 'stok' => '', 'sku' => ''));
                    foreach ($varyantlar as $i => $v): ?>
                        <div class="varyant-satir">
                            <input type="text" name="varyant[<?= $i ?>][renk]" value="<?= e($v->renk ?? '') ?>" placeholder="Renk">
                            <input type="text" name="varyant[<?= $i ?>][beden]" value="<?= e($v->beden ?? '') ?>" placeholder="Beden">
                            <input type="number" name="varyant[<?= $i ?>][stok]" value="<?= e($v->stok ?? '') ?>" placeholder="Stok" min="0">
                            <input type="text" name="varyant[<?= $i ?>][sku]" value="<?= e($v->sku ?? '') ?>" placeholder="SKU" class="mono">
                            <button type="button" class="btn btn-ghost btn-sm varyant-sil" style="color:var(--danger)">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small style="color:var(--stone)">Kaydederken varyant listesi değiştirilir (renk ve beden'in ikisi de boşsa satır atlanır).</small>
            </div>

            <div class="adm-card" style="margin-top:16px">
                <div class="adm-card-baslik"><h3>Fiyat Basamağı (adet indirimi)</h3>
                    <button type="button" class="btn btn-ghost btn-sm" id="basamakEkle">+ Basamak</button>
                </div>
                <small style="display:block;margin-bottom:10px;color:var(--stone)">Ürüne özel adet indirimi (B2B). Global kurallar (ör. 50+ %5, 100+ %10) tüm ürünlere zaten uygulanır; buraya ürün-özel basamak ekleyin. Min. adet &lt; 1 veya % ≤ 0 olan satırlar atlanır.</small>
                <div id="basamakListe">
                    <?php
                    $basamaklar = ($u && ! empty($u->basamaklar)) ? $u->basamaklar : array();
                    if (empty($basamaklar)) { $basamaklar = array((object) array('min_adet' => '', 'indirim_yuzde' => '')); }
                    foreach ($basamaklar as $i => $b): ?>
                        <div class="basamak-satir" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap">
                            <input type="number" name="basamak[<?= $i ?>][min_adet]" value="<?= e($b->min_adet ?? '') ?>" placeholder="Min. adet (örn. 20)" min="1" style="width:150px">
                            <span style="font-size:13px;color:var(--stone)">adet ve üstü</span>
                            <input type="number" step="0.01" name="basamak[<?= $i ?>][indirim_yuzde]" value="<?= e($b->indirim_yuzde ?? '') ?>" placeholder="indirim" min="0" max="100" style="width:100px">
                            <span style="font-size:13px;color:var(--stone)">% indirim</span>
                            <button type="button" class="btn btn-ghost btn-sm basamak-sil" style="color:var(--danger)">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary btn--block"><?= $u ? 'Güncelle' : 'Ürünü Ekle' ?></button>
            </div>
        </div>
    </div>
</form>

<script>
(function(){
    var liste = document.getElementById('varyantListe');
    var ekle = document.getElementById('varyantEkle');
    if (!liste || !ekle) return;
    function yeniSatir(){
        var n = liste.querySelectorAll('.varyant-satir').length;
        var d = document.createElement('div'); d.className = 'varyant-satir';
        d.innerHTML = '<input type="text" name="varyant['+n+'][renk]" placeholder="Renk">'
            + '<input type="text" name="varyant['+n+'][beden]" placeholder="Beden">'
            + '<input type="number" name="varyant['+n+'][stok]" placeholder="Stok" min="0">'
            + '<input type="text" name="varyant['+n+'][sku]" placeholder="SKU" class="mono">'
            + '<button type="button" class="btn btn-ghost btn-sm varyant-sil" style="color:var(--danger)">✕</button>';
        liste.appendChild(d);
    }
    ekle.addEventListener('click', yeniSatir);
    liste.addEventListener('click', function(e){
        if (e.target.classList.contains('varyant-sil')) { e.target.closest('.varyant-satir').remove(); }
    });
})();

// Fiyat basamağı satır ekle/sil
(function(){
    var bListe = document.getElementById('basamakListe');
    var bEkle = document.getElementById('basamakEkle');
    if (!bListe || !bEkle) return;
    bEkle.addEventListener('click', function(){
        var n = bListe.querySelectorAll('.basamak-satir').length;
        var d = document.createElement('div'); d.className = 'basamak-satir'; d.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap';
        d.innerHTML = '<input type="number" name="basamak[' + n + '][min_adet]" placeholder="Min. adet" min="1" style="width:150px"><span style="font-size:13px;color:var(--stone)">adet ve üstü</span><input type="number" step="0.01" name="basamak[' + n + '][indirim_yuzde]" placeholder="indirim" min="0" max="100" style="width:100px"><span style="font-size:13px;color:var(--stone)">% indirim</span><button type="button" class="btn btn-ghost btn-sm basamak-sil" style="color:var(--danger)">✕</button>';
        bListe.appendChild(d);
    });
    bListe.addEventListener('click', function(e){
        if (e.target.classList.contains('basamak-sil')) { e.target.closest('.basamak-satir').remove(); }
    });
})();
</script>
