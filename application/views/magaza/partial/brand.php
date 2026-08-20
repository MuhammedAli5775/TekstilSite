<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Marka rozeti (XLVIII) — "Nesem Tesettür".
 * Amblem: örtü kubbesi (teal-deep) + altın tomurcuk ve nokta — tesettür
 * zarafeti; ölçeklenebilir, CSS değişkenli (tema rengi değişirse uyum sağlar).
 * Yazı markası ayarlar'dan (site_adi) gelir: ilk kelime koyu, kalanlar altın
 * tonlu serif. 6 yüzeyde paylaşılır: header, footer, bayi/kullanıcı giriş+kayıt.
 */
$_ad     = trim((string) ($site_adi ?? 'Nesem Tesettür'));
$_parca  = explode(' ', $_ad, 2);
?>
<svg class="brand__mark" viewBox="0 0 40 40" aria-hidden="true">
    <path d="M20 3.5C12.5 3.5 6.5 9.8 6.5 17.4V30c0 1.1.9 2 2 2h23c1.1 0 2-.9 2-2V17.4C33.5 9.8 27.5 3.5 20 3.5Z" fill="var(--teal-deep)"/>
    <path d="M20 12c-2.7 3-4 5.9-4 8.8 0 3.8 2.4 7.1 4 8.2 1.6-1.1 4-4.4 4-8.2 0-2.9-1.3-5.8-4-8.8Z" fill="var(--brand-gold)"/>
    <circle cx="20" cy="9.4" r="1.2" fill="var(--brand-gold)"/>
</svg>
<span class="brand__ad"><b><?= e($_parca[0]) ?></b><?= ! empty($_parca[1]) ? ' <i>' . e($_parca[1]) . '</i>' : '' ?></span>
