<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Kategori vitrini (kaktusmoda yapısı) — DB'den de beslenebilir, Faz 0 statik.
$kategoriler = array(
    array('ad' => 'Üst Giyim',    'url' => site_url('katalog/ust-giyim'),  'img' => 'https://picsum.photos/seed/ustgiyim/600/800'),
    array('ad' => 'Alt Giyim',    'url' => site_url('katalog/alt-giyim'),  'img' => 'https://picsum.photos/seed/altgiyim/600/800'),
    array('ad' => 'Elbise',       'url' => site_url('katalog/elbise'),     'img' => 'https://picsum.photos/seed/elbise/600/800'),
    array('ad' => 'Dış Giyim',    'url' => site_url('katalog/dis-giyim'),  'img' => 'https://picsum.photos/seed/disgiyim/600/800'),
);
// Yıldız SVG'si (ASCII-güvenli; mojibake riski yok)
$yildiz = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 2l3 6.3 6.9 1-5 4.8 1.2 6.9L12 17.8 5.9 21l1.2-6.9-5-4.8 6.9-1z"/></svg>';
// Müşteri / bayi yorumları
$yorumlar = array(
    array('metin' => 'Toptan fiyatlar ve kumaş kalitesi beklentimizin üzerinde. Siparişlerimiz her zaman zamanında ulaştı.', 'ad' => 'Ayşe K.',  'rol' => 'Mağaza Sahibi',    'sehir' => 'İstanbul'),
    array('metin' => 'XML entegrasyonu sayesinde ürünleri kendi sitemize anında çekebiliyoruz. Bayi kaydı çok kolay oldu.',  'ad' => 'Murat T.', 'rol' => 'E-Ticaret Müdürü', 'sehir' => 'İzmir'),
    array('metin' => 'Minimum adetler esnek, fiyat basamakları toptan alımda ciddi avantaj sağlıyor. Kesinlikle tavsiye ederim.', 'ad' => 'Zeynep A.', 'rol' => 'Boutique Sahibi',  'sehir' => 'Ankara'),
);
// Güven / istatistik seridi
$istatistikler = array(
    array('sayi' => '15+',    'etiket' => 'Yıllık üretim deneyimi'),
    array('sayi' => '5.000+', 'etiket' => 'Aktif toptancı bayi'),
    array('sayi' => '50+',    'etiket' => 'Üretici marka'),
    array('sayi' => '24s',    'etiket' => 'Hızlı sevkiyat'),
);
?>
<?php
// Slider bannerları (bannerlar tablosu)
$sliderler = $this->db->where('yer', 'anasayfa_slider')->where('durum', 1)->order_by('sira', 'ASC')->order_by('id', 'ASC')->get('bannerlar')->result();
?>
<?php if (! empty($sliderler)): ?>
<section class="slider" id="anasayfaSlider" aria-label="Vitrin">
    <div class="slider__viewport">
        <div class="slider__track" id="sliderTrack">
            <?php foreach ($sliderler as $i => $b): ?>
                <div class="slide slide--<?= e($b->yazi_konum ?: 'sol') ?>" style="background-image:url('<?= e(gorsel_url($b->gorsel)) ?>')">
                    <div class="slide__overlay"></div>
                    <div class="container slide__content">
                        <?php if ($b->baslik): ?><h2 class="slide__title"><?= e($b->baslik) ?></h2><?php endif; ?>
                        <?php if ($b->alt_baslik): ?><p class="slide__lead"><?= e($b->alt_baslik) ?></p><?php endif; ?>
                        <?php if ($b->buton_yazi && $b->link): ?>
                            <a class="btn btn-primary btn--lg slide__btn" href="<?= e(strpos((string) $b->link, 'http') === 0 ? $b->link : site_url($b->link)) ?>"><?= e($b->buton_yazi) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (count($sliderler) > 1): ?>
        <button class="slider__arrow slider__arrow--prev" type="button" aria-label="Önceki slayt">‹</button>
        <button class="slider__arrow slider__arrow--next" type="button" aria-label="Sonraki slayt">›</button>
        <div class="slider__dots">
            <?php foreach ($sliderler as $i => $b): ?>
                <button type="button" class="slider__dot<?= $i === 0 ? ' is-active' : '' ?>" data-i="<?= $i ?>" aria-label="Slayt <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<script>
(function () {
    var s = document.getElementById('anasayfaSlider');
    if (!s) return;
    var track = document.getElementById('sliderTrack');
    var slides = track ? track.children : [];
    var dots = s.querySelectorAll('.slider__dot');
    var n = slides.length;
    if (n < 2) return;
    var cur = 0, timer = null;
    function go(i) {
        cur = (i + n) % n;
        if (track) track.style.transform = 'translateX(' + (-cur * 100) + '%)';
        [].forEach.call(dots, function (d, k) { d.classList.toggle('is-active', k === cur); });
    }
    function reset() { clearInterval(timer); timer = setInterval(function () { go(cur + 1); }, 5000); }
    s.querySelector('.slider__arrow--next').addEventListener('click', function () { go(cur + 1); reset(); });
    s.querySelector('.slider__arrow--prev').addEventListener('click', function () { go(cur - 1); reset(); });
    [].forEach.call(dots, function (d) { d.addEventListener('click', function () { go(parseInt(d.dataset.i, 10)); reset(); }); });
    reset();
})();
</script>
<?php endif; ?>

<section class="valuestrip">
    <div class="container">
        <div class="valuestrip__grid">
            <div class="valuestrip__item">
                <svg class="valuestrip__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2v20M5 9l7-7 7 7"/></svg>
                <div><div class="valuestrip__t">Üretici Fiyatı</div><div class="valuestrip__d">Aracısız, doğrudan fabrika.</div></div>
            </div>
            <div class="valuestrip__item">
                <svg class="valuestrip__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <div><div class="valuestrip__t">Min. Sipariş (MOQ)</div><div class="valuestrip__d">Esnek adet basamakları.</div></div>
            </div>
            <div class="valuestrip__item">
                <svg class="valuestrip__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <div><div class="valuestrip__t">Hızlı Kargo</div><div class="valuestrip__d">Aynı gün sevkiyat & dünya.</div></div>
            </div>
            <div class="valuestrip__item">
                <svg class="valuestrip__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m8 3-6 6 6 6M3 9h13a5 5 0 0 1 0 10h-2"/></svg>
                <div><div class="valuestrip__t">XML / API Feed</div><div class="valuestrip__d">Pazaryeri & yazılım entegrasyonu.</div></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <span class="section__eyebrow">Koleksiyon</span>
            <h2 class="section__title">Kategorilere göz atın</h2>
            <p class="section__lead">Her kategori taze üretim, gerçek stok ve toptan fiyat basamağı ile.</p>
        </div>
        <div class="catgrid">
            <?php foreach ($kategoriler as $k): ?>
                <a class="cattile" href="<?= e($k['url']) ?>">
                    <img src="<?= e($k['img']) ?>" alt="<?= e($k['ad']) ?>" loading="lazy">
                    <div class="cattile__label"><div class="t"><?= e($k['ad']) ?></div><div class="c">İncele →</div></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="stats">
    <div class="container">
        <div class="stats__grid">
            <?php foreach ($istatistikler as $i): ?>
                <div class="stat">
                    <div class="stat__num"><?= e($i['sayi']) ?></div>
                    <div class="stat__lbl"><?= e($i['etiket']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <span class="section__eyebrow">Yorumlar</span>
            <h2 class="section__title">Bayilerimiz ne diyor?</h2>
            <p class="section__lead">Binlerce toptancı üretici fiyatı ve hızlı sevkiyatla bizimle çalışıyor.</p>
        </div>
        <div class="reviews__grid">
            <?php foreach ($yorumlar as $y): ?>
                <figure class="review-card">
                    <div class="review-card__stars" role="img" aria-label="5 üzerinden 5 yıldız"><?= str_repeat($yildiz, 5) ?></div>
                    <blockquote class="review-card__text">&ldquo;<?= e($y['metin']) ?>&rdquo;</blockquote>
                    <figcaption class="review-card__who">
                        <span class="review-card__ava"><?= e(mb_substr($y['ad'], 0, 1)) ?></span>
                        <span>
                            <span class="review-card__name"><?= e($y['ad']) ?></span><br>
                            <span class="review-card__role"><?= e($y['rol']) ?> · <?= e($y['sehir']) ?></span>
                        </span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="ctaband">
            <div class="container--inner container" style="max-width:var(--container)">
                <div>
                    <h2>Toptancı mısınız? Hemen başlayın.</h2>
                    <p>Bayi hesabınızı açın, toptan fiyatları ve XML/API feed'i açalım.</p>
                </div>
                <a class="btn btn-primary btn--lg" href="<?= site_url('bayi/kayit') ?>">Bayi Kaydı Oluştur</a>
            </div>
        </div>
    </div>
</section>
