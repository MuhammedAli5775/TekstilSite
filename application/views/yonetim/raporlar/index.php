<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($hata = $this->session->flashdata('hata')): ?><div class="adm-uyari adm-uyari--hata"><?= e($hata) ?></div><?php endif; ?>

<div class="adm-sayfa-baslik"><h2>Raporlar</h2>
    <div class="adm-rapor-disa">
        <a class="btn btn-secondary btn-sm" target="_blank" href="<?= site_url('yonetim/raporlar/disa_aktar/' . $rapor . '/csv') ?>?bas=<?= e($bas) ?>&son=<?= e($son) ?><?= $rapor === 'bolge' ? '&alan=' . e($this->input->get('alan') === 'ilce' ? 'ilce' : 'il') : '' ?>">CSV indir</a>
        <a class="btn btn-secondary btn-sm" target="_blank" href="<?= site_url('yonetim/raporlar/disa_aktar/' . $rapor . '/pdf') ?>?bas=<?= e($bas) ?>&son=<?= e($son) ?><?= $rapor === 'bolge' ? '&alan=' . e($this->input->get('alan') === 'ilce' ? 'ilce' : 'il') : '' ?>">PDF (yazdır)</a>
    </div>
</div>

<div class="adm-sekmeler">
    <?php foreach ($raporlar as $key => $ad): ?>
        <a class="adm-sekme <?= $key === $rapor ? 'is-aktif' : '' ?>" href="<?= site_url('yonetim/raporlar/index/' . $key) ?>?bas=<?= e($bas) ?>&son=<?= e($son) ?>"><?= e($ad) ?></a>
    <?php endforeach; ?>
</div>

<form class="adm-filtre" method="get" action="<?= site_url('yonetim/raporlar/index/' . $rapor) ?>">
    <div class="adm-filtre-alan"><label>Başlangıç</label><input type="date" name="bas" value="<?= e($bas) ?>"></div>
    <div class="adm-filtre-alan"><label>Bitiş</label><input type="date" name="son" value="<?= e($son) ?>"></div>
    <?php if ($rapor === 'bolge'): ?>
    <div class="adm-filtre-alan"><label>Bölge tipi</label>
        <select name="alan"><option value="il" <?= $this->input->get('alan') !== 'ilce' ? 'selected' : '' ?>>İl</option><option value="ilce" <?= $this->input->get('alan') === 'ilce' ? 'selected' : '' ?>>İlçe</option></select>
    </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary btn-sm">Uygula</button>
    <div class="adm-filtre-alan"><label>Hızlı aralık</label>
        <span style="display:inline-flex;gap:6px;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" href="?bas=<?= date('Y-m-d') ?>&son=<?= date('Y-m-d') ?>">Bugün</a>
            <a class="btn btn-ghost btn-sm" href="?bas=<?= date('Y-m-d', strtotime('monday this week')) ?>&son=<?= date('Y-m-d') ?>">Bu hafta</a>
            <a class="btn btn-ghost btn-sm" href="?bas=<?= date('Y-m-01') ?>&son=<?= date('Y-m-d') ?>">Bu ay</a>
            <a class="btn btn-ghost btn-sm" href="?bas=<?= date('Y-m-01', strtotime('first day of last month')) ?>&son=<?= date('Y-m-t', strtotime('first day of last month')) ?>">Geçen ay</a>
        </span>
    </div>
</form>

<?php if ($rapor === 'satis' && isset($ozet)): $o = $ozet; $durum_etiket_map = array('onay_bekliyor'=>'Onay bekliyor','onaylandi'=>'Onaylandı','hazirlaniyor'=>'Hazırlanıyor','kargolandi'=>'Kargolandı','teslim_edildi'=>'Teslim edildi','iptal'=>'İptal','iade_talep'=>'İade talebi','iade_edildi'=>'İade edildi'); ?>
    <div class="adm-stats">
        <div class="adm-stat"><div class="adm-stat-etiket">Tüm Sipariş</div><div class="adm-stat-sayi"><?= (int) $o['toplam'] ?></div><div class="adm-stat-alt"><?= (int) $o['brut_siparis'] ?> brüt (iptal/iade hariç)</div></div>
        <div class="adm-stat"><div class="adm-stat-etiket">Brüt Ciro</div><div class="adm-stat-sayi"><?= para_tr($o['ciro']) ?></div><div class="adm-stat-alt"><?= e($bas) ?> – <?= e($son) ?></div></div>
        <div class="adm-stat"><div class="adm-stat-etiket">Ortalama Sepet</div><div class="adm-stat-sayi"><?= para_tr($o['aov']) ?></div><div class="adm-stat-alt">brüt sipariş başına</div></div>
        <div class="adm-stat"><div class="adm-stat-etiket">Kargo / İndirim</div><div class="adm-stat-sayi" style="font-size:20px"><?= para_tr($o['kargo']) ?> <span style="color:var(--stone);font-weight:400">/ <?= para_tr($o['indirim']) ?></span></div><div class="adm-stat-alt">toplam kargo / indirim</div></div>
    </div>
    <div class="adm-card adm-card--p0">
        <div class="adm-card-baslik"><h3>Durum Dağılımı</h3></div>
        <div class="adm-tbl-sar"><table class="adm-tbl">
            <thead><tr><th>Durum</th><th class="sag">Sipariş</th></tr></thead>
            <tbody>
            <?php if ($o['durumlar']): foreach ($o['durumlar'] as $d => $n): ?>
                <tr><td><?= e(isset($durum_etiket_map[$d]) ? $durum_etiket_map[$d] : $d) ?></td><td class="sag"><?= (int) $n ?></td></tr>
            <?php endforeach; else: ?>
                <tr><td colspan="2"><div class="adm-bosluk">Bu aralıkta sipariş yok</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>

<?php elseif ($kolonlar): ?>
    <div class="adm-card adm-card--p0">
        <div class="adm-tbl-sar"><table class="adm-tbl">
            <thead><tr>
                <?php foreach ($kolonlar as $label): ?><th class="<?= (strpos($label, 'Ciro') !== FALSE || strpos($label, 'İndirim') !== FALSE) ? 'sag' : '' ?>"><?= e($label) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php if ($satirlar): foreach ($satirlar as $r): ?>
                <tr>
                <?php foreach (array_keys($kolonlar) as $k): ?>
                    <td class="<?= in_array($k, array('ciro', 'indirim'), TRUE) ? 'sag' : '' ?>"><?= in_array($k, array('ciro', 'indirim'), TRUE) ? para_tr($r->$k) : e($r->$k ?? '') ?></td>
                <?php endforeach; ?>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="<?= count($kolonlar) ?>"><div class="adm-bosluk">Bu aralıkta veri yok</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
    </div>
<?php endif; ?>
