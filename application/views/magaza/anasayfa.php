<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Kategori vitrini (kaktusmoda yapısı) — DB'den de beslenebilir, Faz 0 statik.
$kategoriler = array(
    array('ad' => 'Üst Giyim',    'url' => site_url('katalog/ust-giyim'),  'img' => 'https://picsum.photos/seed/ustgiyim/600/800'),
    array('ad' => 'Alt Giyim',    'url' => site_url('katalog/alt-giyim'),  'img' => 'https://picsum.photos/seed/altgiyim/600/800'),
    array('ad' => 'Elbise',       'url' => site_url('katalog/elbise'),     'img' => 'https://picsum.photos/seed/elbise/600/800'),
    array('ad' => 'Dış Giyim',    'url' => site_url('katalog/dis-giyim'),  'img' => 'https://picsum.photos/seed/disgiyim/600/800'),
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

<section class="section section--tight" style="background:var(--surface)">
    <div class="container">
        <div class="section__head">
            <span class="section__eyebrow">Vitrin</span>
            <h2 class="section__title">Öne çıkan parçalar</h2>
            <p class="section__lead">Toptan fiyatlar bayi hesabına giriş yapınca görünür. MOQ bilgisi her üründe.</p>
        </div>

        <?php if (!empty($db_hazir) && !empty($vitrin)): ?>
            <div class="prodgrid">
                <?php foreach ($vitrin as $u): ?>
                    <?php $this->load->view('magaza/partial/urun_karti', array('urun' => $u)); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if (empty($db_hazir)): ?>
                <p class="notice notice--warn" style="margin-bottom:24px">
                    ⚠ Veritabanı henüz bağlı değil — bu kartlar tasarım önizlemesidir.
                    DB şifresi girilip şema içe aktarılınca gerçek ürünler gelecek (workflow.md §8).
                </p>
            <?php endif; ?>
            <div class="prodgrid">
                <?php foreach ($vitrin as $u): ?>
                    <?php $this->load->view('magaza/partial/urun_karti', array('urun' => $u)); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="ctaband">
            <div class="container--inner container" style="max-width:var(--container)">
                <div>
                    <h2>Toptancı mısiniz? Hemen başlayın.</h2>
                    <p>Bayi hesabınızı açın, toptan fiyatları ve XML/API feed'i açalım.</p>
                </div>
                <a class="btn btn-primary btn--lg" href="<?= site_url('bayi/kayit') ?>">Bayi Kaydı Oluştur</a>
            </div>
        </div>
    </div>
</section>
