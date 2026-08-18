<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * teksil_lang — mağaza dizgileri, ARAPÇA (eksik anahtar Türkçeye düşer;
 * AR seçildiğinde <html dir="rtl"> — DEGISIKLIK XXIX).
 *
 * RTL notu: ileri-yönlü eylem dizgilerinde ok karakteri → yerine ←
 * kullanılır (sağdan sola okunuşta "ileri" sol taraftadır).
 */

$lang = array(
    // ---- kabuk: utility bar / header / meta / footer (XXIX) ----
    'util_siparis_takibi' => 'تتبع الطلب',
    'util_yardim'         => 'المساعدة',
    'util_toptan'         => 'بالجملة / B2B',

    'hdr_ara'        => 'ابحث عن منتجات…',
    'hdr_arama'      => 'بحث',
    'hdr_ara_dugme'  => 'بحث',
    'hdr_favoriler'  => 'المفضلة',
    'hdr_hesabim'    => 'حسابي',
    'hdr_cikis'      => 'خروج',
    'hdr_cikis_onay' => 'هل أنت متأكد أنك تريد تسجيل الخروج؟',
    'hdr_giris'      => 'تسجيل الدخول',
    'hdr_sepet'      => 'السلة',

    'meta_desc_default' => 'ملابس نسائية بالجملة — أسعار المصنع، أقمشة عالية الجودة، شحن سريع.',

    'ftr_tanim'          => 'ملابس نسائية بالجملة بأسعار المصنع وأقمشة عالية الجودة. من مرتر إسطنبول إلى تركيا والعالم.',
    'ftr_kategoriler'    => 'الفئات',
    'ftr_toptanci'       => 'لتجار الجملة',
    'ftr_yardim_kurumsal' => 'المساعدة والشركة',
    'ftr_bayi_kayit'     => 'تسجيل الموزع',
    'ftr_bayi_giris'     => 'دخول الموزعين',
    'ftr_hakkinda'       => 'من نحن',
    'ftr_mesafeli'       => 'البيع عن بعد',
    'ftr_iade'           => 'الإرجاع والاستبدال',
    'ftr_gizlilik'       => 'الخصوصية',
    'ftr_iletisim'       => 'اتصل بنا',
    'ftr_telif'          => '© %s TekstilSite. جميع الحقوق محفوظة.',
    'ftr_guvenlik'       => 'محمي بـ SSL · دفع 3D Secure',

    // ---- anasayfa (XXX) ----
    'anasayfa_kat_ust'    => 'ملابس علوية',
    'anasayfa_kat_alt'    => 'ملابس سفلية',
    'anasayfa_kat_elbise' => 'فساتين',
    'anasayfa_kat_dis'    => 'ملابس خارجية',

    'anasayfa_yorum1' => 'أسعار الجملة وجودة الأقمشة تجاوزت توقعاتنا. طلباتنا تصل دائماً في الوقت المحدد.',
    'anasayfa_yorum2' => 'بفضل تكامل XML نسحب المنتجات إلى موقعنا فوراً. كان تسجيل الموزع سهلاً جداً.',
    'anasayfa_yorum3' => 'الكميات الدنيا المرنة ودرجات الأسعار ميزة حقيقية في الشراء بالجملة. نوصي به بالتأكيد.',
    'anasayfa_rol1'   => 'صاحبة متجر',
    'anasayfa_rol2'   => 'مدير التجارة الإلكترونية',
    'anasayfa_rol3'   => 'صاحبة بوتيك',

    'anasayfa_stat_deneyim'  => 'سنوات خبرة في الإنتاج',
    'anasayfa_stat_bayi'     => 'موزعو جملة نشطون',
    'anasayfa_stat_marka'    => 'علامات مصنّعة',
    'anasayfa_stat_sevkiyat' => 'شحن سريع',

    'anasayfa_slider_aria' => 'الواجهة',
    'anasayfa_onceki'      => 'الشريحة السابقة',
    'anasayfa_sonraki'     => 'الشريحة التالية',
    'anasayfa_slayt'       => 'شريحة',

    'anasayfa_uretici_fiyat'   => 'سعر المصنع',
    'anasayfa_uretici_fiyat_d' => 'بدون وسطاء، مباشرة من المصنع.',
    'anasayfa_moq'             => 'الحد الأدنى للطلب (MOQ)',
    'anasayfa_moq_d'           => 'درجات كميات مرنة.',
    'anasayfa_kargo'           => 'شحن سريع',
    'anasayfa_kargo_d'         => 'شحن في نفس اليوم وإلى كل العالم.',
    'anasayfa_xml'             => 'تغذية XML / API',
    'anasayfa_xml_d'           => 'تكامل مع المتاجر والبرمجيات.',

    'anasayfa_koleksiyon' => 'المجموعة',
    'anasayfa_kat_baslik' => 'تصفح الفئات',
    'anasayfa_kat_lead'   => 'كل فئة: إنتاج طازج ومخزون حقيقي ودرجات أسعار بالجملة.',
    'anasayfa_incele'     => 'عرض ←',

    'anasayfa_yorumlar'     => 'المراجعات',
    'anasayfa_yorum_baslik' => 'ماذا يقول موزعونا؟',
    'anasayfa_yorum_lead'   => 'آلاف تجار الجملة يعملون معنا لأسعار المصنع والشحن السريع.',
    'anasayfa_yildiz'       => '5 من 5 نجوم',

    'anasayfa_cta_baslik' => 'تاجر جملة؟ ابدأ الآن.',
    'anasayfa_cta_metin'  => 'افتح حساب موزع — سنفعّل أسعار الجملة وتغذية XML/API.',
    'anasayfa_cta_buton'  => 'إنشاء حساب موزع',

    // ---- katalog + filtre/sayfalama partial (XXX) ----
    'kat_sira_yeni'    => 'وصل حديثاً',
    'kat_sira_cok'     => 'الأكثر مبيعاً',
    'kat_sira_artan'   => 'السعر (تصاعدي)',
    'kat_sira_azalan'  => 'السعر (تنازلي)',
    'kat_sira_alfa'    => 'أبجدي (أ→ي)',
    'kat_yol'          => 'مسار التنقل',
    'kat_anasayfa'     => 'الرئيسية',
    'kat_urun_sayisi'  => '%s منتج معروض · أسعار الجملة تظهر بعد دخول الموزع',
    'kat_filtreler'    => 'الفلاتر',
    'kat_urun'         => 'منتج',
    'kat_sirala'       => 'ترتيب',
    'kat_bos'          => 'لا توجد منتجات مطابقة للفلاتر المختارة.',
    'kat_filtre_temizle' => 'مسح الفلاتر ←',

    'kat_filtre_beden' => 'المقاس',
    'kat_filtre_renk'  => 'اللون',
    'kat_filtre_fiyat' => 'نطاق السعر (₺)',
    'kat_filtre_min'   => 'الأدنى',
    'kat_filtre_maks'  => 'الأقصى',

    'kat_sayfalama'     => 'ترقيم الصفحات',
    'kat_sayfa_onceki'  => 'السابق',
    'kat_sayfa_sonraki' => 'التالي',

    // ---- sepet (XXX) ----
    'sepet_baslik'     => 'سلتي',
    'sepet_bos'        => 'سلتك فارغة.',
    'sepet_basla'      => 'ابدأ التسوق ←',
    'sepet_th_urun'    => 'المنتج',
    'sepet_th_varyant' => 'الخيار',
    'sepet_th_adet'    => 'الكمية',
    'sepet_th_birim'   => 'للوحدة',
    'sepet_th_tutar'   => 'المبلغ',
    'sepet_guncelle'   => 'تحديث',
    'sepet_moq_not'    => 'MOQ %s · خطوة %s',
    'sepet_sil_onay'   => 'إزالة هذا المنتج؟',
    'sepet_sil'        => 'حذف',
    'sepet_ozet'       => 'الملخص',
    'sepet_ara_toplam' => 'المجموع الفرعي',
    'sepet_kargo'      => 'الشحن',
    'sepet_ucretsiz'   => 'مجاني',
    'sepet_odemede'    => 'يُحسب عند الدفع',
    'sepet_kargo_kalan' => '%s أخرى ← شحن مجاني',
    'sepet_toplam'     => 'الإجمالي',
    'sepet_odemeye'    => 'إتمام الشراء ←',
    'sepet_devam'      => 'مواصلة التسوق',

    // ---- odeme (XXX) ----
    'odeme_baslik'        => 'الدفع',
    'odeme_kirinti_sepet' => 'السلة',
    'odeme_teslimat'      => 'بيانات التوصيل',
    'odeme_ad_soyad'      => 'الاسم الكامل',
    'odeme_adres'         => 'العنوان',
    'odeme_il'            => 'المحافظة',
    'odeme_ilce'          => 'المنطقة',
    'odeme_secin'         => 'اختر',
    'odeme_telefon'       => 'الهاتف',
    'odeme_eposta'        => 'البريد الإلكتروني',
    'odeme_fatura'        => 'بيانات الفاتورة',
    'odeme_fatura_ayni'   => 'بيانات الفاتورة مطابقة للتوصيل',
    'odeme_fatura_ad'     => 'اسم / عنوان الفاتورة',
    'odeme_fatura_adres'  => 'عنوان الفاتورة',
    'odeme_firma_unvan'   => 'اسم الشركة',
    'odeme_vergi_no'      => 'الرقم الضريبي / الهوية',
    'odeme_kargo_odeme'   => 'الشحن والدفع',
    'odeme_kargo_firma'   => 'شركة الشحن',
    'odeme_yontem'        => 'طريقة الدفع',
    'odeme_ek_ucret'      => '(+%s)',
    'odeme_banka_hesap'   => 'الحسابات البنكية (للتحويل/EFT):',
    'odeme_sozlesme'      => 'قرأت اتفاقية البيع عن بعد وأوافق عليها.',
    'odeme_tamamla'       => 'إتمام الطلب',
    'odeme_ozet'          => 'ملخص الطلب',
    'odeme_kupon'         => 'كوبون (%s)',
    'odeme_kupon_kaldir'  => 'إزالة الكوبون',
    'odeme_kupon_ph'      => 'رمز الكوبون',
    'odeme_uygula'        => 'تطبيق',
    'odeme_kargo_islem'   => 'الشحن / رسوم المعالجة',
    'odeme_kargo_odemede' => 'يُحسب عند الدفع',
);
