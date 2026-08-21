<?php
/**
 * ikon_uret.php — marka ikonlarını üret (LII).
 *
 * Kullanım:  C:/xampp/php/php.exe scripts/ikon_uret.php
 * Çıktılar:  assets/magaza/img/favicon-32.png (tarayıcı sekmesi, PNG fallback)
 *            assets/magaza/img/apple-touch-icon.png (iOS ana ekran, 180×180)
 *            assets/magaza/img/og-default.png (WhatsApp/sosyal paylaşım kartı, 1200×630)
 *
 * GD üretim için bir kez gerekir (php.ini: extension=gd); çıktılar repoya
 * commitlenir — hostingte GD bulunmak zorunda değildir. Marka değişirse
 * partial/brand.php amblemiyle birlikte buradaki renkleri de güncelle.
 */

$kok    = dirname(__DIR__);
$cikti  = $kok . '/assets/magaza/img';
if (! is_dir($cikti)) { mkdir($cikti, 0777, TRUE); }

// CSS değişkenleriyle aynı tonlar (teksil.css / brand.php fallback'leri)
function rnk($im, array $rgb) { return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]); }
$TEAL = array(0, 30, 43);       // #001e2b --teal-deep
$TEAL2 = array(9, 43, 60);      // kubbe dolgusu (zeminden bir ton açık)
$GOLD = array(185, 141, 95);    // #b98d5f --brand-gold
$ACIK = array(200, 214, 223);   // #c8d6df açık metin
$BEYAZ = array(255, 255, 255);

/** Tomurcuk (ucu yukarıda damla): alt yarım daire + üste incelen üçgen. */
function tomurcuk($im, $cx, $cy, $h, $renk)
{
    $r = (int) max(2, round($h * 0.44));
    imagefilledellipse($im, (int) $cx, (int) $cy, $r * 2, $r * 2, $renk);
    imagefilledpolygon($im, array((int) $cx, (int) ($cy - $h), (int) ($cx - $r + 1), (int) $cy, (int) ($cx + $r - 1), (int) $cy), $renk);
}

/* ---------- favicon-32.png + apple-touch-icon.png ---------- */
foreach (array('favicon-32.png' => 32, 'apple-touch-icon.png' => 180) as $ad => $S) {
    $im = imagecreatetruecolor($S, $S);
    imagefill($im, 0, 0, rnk($im, $TEAL));
    tomurcuk($im, $S * 0.5, $S * 0.56, $S * 0.34, rnk($im, $GOLD));
    imagefilledellipse($im, (int) ($S * 0.5), (int) ($S * 0.235), (int) ($S * 0.085), (int) ($S * 0.085), rnk($im, $GOLD));
    imagepng($im, "$cikti/$ad", 9);
    imagedestroy($im);
    echo "$ad ({$S}x{$S}) üretildi\n";
}

/* ---------- og-default.png (1200×630) ---------- */
$W = 1200; $H = 630;
$im = imagecreatetruecolor($W, $H);
imagefill($im, 0, 0, rnk($im, $TEAL));

// Kubbe (örtü silueti) — sol tarafta, zeminden bir ton açık
$domeW = 340; $domeH = 400; $domeCx = 285; $domeCy = 355;
imagefilledarc($im, $domeCx, $domeCy, $domeW, $domeH * 2, 180, 360, rnk($im, $TEAL2), IMG_ARC_PIE);
imagefilledrectangle($im, $domeCx - $domeW / 2, $domeCy - 2, $domeCx + $domeW / 2, $domeCy + 40, rnk($im, $TEAL2));
// altın tomurcuk + nokta kubbenin içinde
tomurcuk($im, $domeCx, $domeCy - 45, 210, rnk($im, $GOLD));
imagefilledellipse($im, $domeCx, $domeCy - 285, 26, 26, rnk($im, $GOLD));

// Yazı markası — Georgia (Windows yazı tipi; sunucuda üretiyorsan eşdeğer serif ver)
$font = 'C:/Windows/Fonts/georgia.ttf';
if (! is_file($font)) { $font = 'C:/Windows/Fonts/times.ttf'; }
$ad1 = 'Nesem'; $ad2 = 'Tesettür';
$fs = 84;
$b1 = imagettfbbox($fs, 0, $font, $ad1);
$w1 = abs($b1[2] - $b1[0]);
$tx = 540; $ty = 330;
imagettftext($im, $fs, 0, $tx, $ty, rnk($im, $BEYAZ), $font, $ad1);
imagettftext($im, $fs, 0, $tx + $w1 + 22, $ty, rnk($im, $GOLD), $font, $ad2);

// Slogan + ince altın çizgi
imagettftext($im, 33, 0, $tx + 4, $ty + 78, rnk($im, $ACIK), $font, 'Toptan Kadın Giyim · Üretici Fiyatı, Kaliteli Kumaş');
$b2 = imagettfbbox(33, 0, $font, 'Toptan Kadın Giyim · Üretici Fiyatı, Kaliteli Kumaş');
$lineW = abs($b2[2] - $b2[0]);
imagefilledrectangle($im, $tx + 4, $ty + 108, $tx + 4 + (int) ($lineW * 0.32), $ty + 113, rnk($im, $GOLD));

imagepng($im, "$cikti/og-default.png", 9);
imagedestroy($im);
echo "og-default.png ({$W}×{$H}) üretildi\n";
