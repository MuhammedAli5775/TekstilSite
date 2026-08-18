<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="kat-hero">
    <div class="container">
        <nav class="kirinti"><a href="<?= site_url() ?>"><?= t('kat_anasayfa', 'Anasayfa') ?></a> <span class="ayrac">/</span> <span class="simdiki"><?= t('syf_yardim_b', 'Yardım') ?></span></nav>
        <h1 class="kat-baslik"><?= t('syf_yardim_b', 'Yardım') ?></h1>
        <p class="kat-alt"><?= t('syf_yardim_alt', 'Sık sorulan sorular ve iletişim.') ?></p>
    </div>
</section>

<section class="section section--tight">
    <div class="container" style="max-width:760px">
        <div class="card card--feature">
            <h3><?= t('syf_sss', 'Sık Sorulan Sorular') ?></h3>
            <p><b><?= t('syf_sss1_s', 'Toptan sipariş için minimum adet var mı?') ?></b><br><?= t('syf_sss1_c', 'Evet — her ürünün minimum sipariş adedi (MOQ) ürün sayfasında belirtilir; adet basamağına göre toptan fiyat uygulanır.') ?></p>
            <p><b><?= t('syf_sss2_s', 'Nasıl bayi olurum?') ?></b><br><?= sprintf(t('syf_sss2_c', '%s formunu doldurun; onay sonrası toptan fiyatlarına erişirsiniz.'), '<a href="' . site_url('bayi/kayit') . '">' . e(t('syf_bayi_kayit_link', 'Bayi kayıt')) . '</a>') ?></p>
            <p><b><?= t('syf_sss3_s', 'Siparişimi nasıl takip ederim?') ?></b><br><?= sprintf(t('syf_sss3_c', '%1$s sayfasından sipariş no + e-postanızla; bayiler %2$s bölümünden de görebilir.'), '<a href="' . site_url('siparis-takip') . '">' . e(t('syf_takip_b', 'Sipariş Takibi')) . '</a>', '<a href="' . site_url('hesabim/siparisler') . '">' . e(t('hesap_baslik', 'Hesabım')) . '</a>') ?></p>
            <p><b><?= t('syf_sss4_s', 'Ödeme yöntemleri?') ?></b><br><?= t('syf_sss4_c', 'Havale/EFT, kapıda nakit veya kartla (PayTR) ödeyebilirsiniz.') ?></p>
            <p><b><?= t('syf_sss5_s', 'Sipariş biriminde para birimi?') ?></b><br><?= t('syf_sss5_c', "Hesabım › Bilgiler'den para biriminizi seçin; sipariş anlık kur ile o para biriminde kaydedilir.") ?></p>
        </div>
        <div class="card card--feature" style="margin-top:16px">
            <h3><?= t('syf_iletisim_b', 'İletişim') ?></h3>
            <p>
                📞 +90 212 481 36 92<br>
                📧 <a href="mailto:<?= e(ayar('iletisim_eposta','info@teksilsite.test')) ?>"><?= e(ayar('iletisim_eposta','info@teksilsite.test')) ?></a>
            </p>
        </div>
    </div>
</section>
