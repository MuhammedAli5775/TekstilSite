<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>">Anasayfa</a> <span class="ayrac">/</span> <span class="simdiki">Yardım</span></nav>
        <h1 class="kat-baslik">Yardım</h1>
        <p class="kat-alt">Sık sorulan sorular ve iletişim.</p>
    </div>
</section>

<section class="section section--tight">
    <div class="container" style="max-width:760px">
        <div class="card card--feature">
            <h3>Sık Sorulan Sorular</h3>
            <p><b>Toptan sipariş için minimum adet var mı?</b><br>Evet — her ürünün minimum sipariş adedi (MOQ) ürün sayfasında belirtilir; adet basamağına göre toptan fiyat uygulanır.</p>
            <p><b>Nasıl bayi olurum?</b><br><a href="<?= site_url('bayi/kayit') ?>">Bayi kayıt</a> formunu doldurun; onay sonrası toptan fiyatlarına erişirsiniz.</p>
            <p><b>Siparişimi nasıl takip ederim?</b><br><a href="<?= site_url('siparis-takip') ?>">Sipariş Takibi</a> sayfasından sipariş no + e-postanızla; bayiler <a href="<?= site_url('hesabim/siparisler') ?>">Hesabım</a>'dan da görebilir.</p>
            <p><b>Ödeme yöntemleri?</b><br>Havale/EFT, kapıda nakit veya kartla (PayTR) ödeyebilirsiniz.</p>
            <p><b>Sipariş biriminde para birimi?</b><br>Hesabım › Bilgiler'den para biriminizi seçin; sipariş anlık kur ile o para biriminde kaydedilir.</p>
        </div>
        <div class="card card--feature" style="margin-top:16px">
            <h3>İletişim</h3>
            <p>
                📞 +90 212 481 36 92<br>
                📧 <a href="mailto:<?= e(ayar('iletisim_eposta','info@teksilsite.test')) ?>"><?= e(ayar('iletisim_eposta','info@teksilsite.test')) ?></a>
            </p>
        </div>
    </div>
</section>
