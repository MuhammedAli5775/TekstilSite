<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8">
<title><?= e($rapor_adi) ?> — <?= e($site_adi) ?></title>
<style>
  *{ box-sizing:border-box; }
  body{ font-family:'Figtree',-apple-system,'Segoe UI',Roboto,Arial,sans-serif; color:#001e2b; margin:0; padding:32px; font-size:13px; }
  .baslik{ display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #001e2b; padding-bottom:12px; margin-bottom:18px; }
  .baslik h1{ font-size:20px; margin:0; } .baslik .site{ color:#5c6c7a; font-size:12px; }
  .meta{ color:#5c6c7a; font-size:12px; margin-bottom:14px; }
  .ozet{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
  .ozet .k{ border:1px solid #e1e5e8; border-radius:8px; padding:10px 12px; } .ozet .e{ font-size:11px; color:#5c6c7a; text-transform:uppercase; } .ozet .v{ font-size:18px; font-weight:600; margin-top:2px; }
  table{ width:100%; border-collapse:collapse; } th,td{ text-align:left; padding:8px 10px; border-bottom:1px solid #e1e5e8; } th{ background:#f4f7f6; font-size:11px; text-transform:uppercase; color:#3d4f5b; } td.sag,th.sag{ text-align:right; }
  .yazdir{ position:fixed; top:16px; right:16px; background:#001e2b; color:#fff; border:0; border-radius:9999px; padding:9px 20px; font-size:13px; font-weight:600; cursor:pointer; }
  .bos{ text-align:center; color:#7c8c9a; padding:28px; }
  @media print{ .yazdir{ display:none; } body{ padding:0; } }
</style></head>
<body>
<button class="yazdir" onclick="window.print()">Yazdır / PDF</button>
<div class="baslik">
    <div><h1><?= e($rapor_adi) ?></h1><div class="site"><?= e($site_adi) ?></div></div>
    <div class="meta">Üretim: <?= e(date('d.m.Y H:i')) ?><br>Dönem: <?= e($bas) ?> – <?= e($son) ?></div>
</div>

<?php if ($rapor_adi === 'Satış Özeti' && isset($ozet)): $o = $ozet; $dm = array('onay_bekliyor'=>'Onay bekliyor','onaylandi'=>'Onaylandı','hazirlaniyor'=>'Hazırlanıyor','kargolandi'=>'Kargolandı','teslim_edildi'=>'Teslim edildi','iptal'=>'İptal','iade_talep'=>'İade talebi','iade_edildi'=>'İade edildi'); ?>
    <div class="ozet">
        <div class="k"><div class="e">Tüm Sipariş</div><div class="v"><?= (int) $o['toplam'] ?></div></div>
        <div class="k"><div class="e">Brüt Sipariş</div><div class="v"><?= (int) $o['brut_siparis'] ?></div></div>
        <div class="k"><div class="e">Ortalama Sepet</div><div class="v"><?= e(number_format((float) $o['aov'], 2, ',', '.')) ?> ₺</div></div>
        <div class="k"><div class="e">Brüt Ciro</div><div class="v"><?= e(number_format((float) $o['ciro'], 2, ',', '.')) ?> ₺</div></div>
        <div class="k"><div class="e">Kargo</div><div class="v"><?= e(number_format((float) $o['kargo'], 2, ',', '.')) ?> ₺</div></div>
        <div class="k"><div class="e">İndirim</div><div class="v"><?= e(number_format((float) $o['indirim'], 2, ',', '.')) ?> ₺</div></div>
    </div>
    <table><thead><tr><th>Durum</th><th class="sag">Sipariş</th></tr></thead><tbody>
    <?php foreach ($o['durumlar'] as $d => $n): ?><tr><td><?= e(isset($dm[$d]) ? $dm[$d] : $d) ?></td><td class="sag"><?= (int) $n ?></td></tr><?php endforeach; ?>
    <?php if (! $o['durumlar']): ?><tr><td colspan="2" class="bos">Bu aralıkta sipariş yok</td></tr><?php endif; ?>
    </tbody></table>

<?php elseif ($kolonlar): ?>
    <table><thead><tr>
        <?php foreach ($kolonlar as $label): ?><th class="<?= (strpos($label, 'Ciro') !== FALSE) ? 'sag' : '' ?>"><?= e($label) ?></th><?php endforeach; ?>
    </tr></thead><tbody>
    <?php if ($satirlar): foreach ($satirlar as $r): ?><tr>
        <?php foreach (array_keys($kolonlar) as $k): ?>
            <td class="<?= ($k === 'ciro') ? 'sag' : '' ?>"><?= ($k === 'ciro') ? e(number_format((float) $r->$k, 2, ',', '.')) . ' ₺' : e($r->$k ?? '') ?></td>
        <?php endforeach; ?>
    </tr><?php endforeach; else: ?>
        <tr><td colspan="<?= count($kolonlar) ?>" class="bos">Bu aralıkta veri yok</td></tr>
    <?php endif; ?>
    </tbody></table>
<?php endif; ?>

<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
</body></html>
