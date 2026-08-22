<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Eposta — sipariş onayı (graceful).
 * SMTP ayarları (Ayarlar) girilmezse e-posta gönderilmez; sipariş akışı bozulmaz.
 */
class Eposta
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('email');
    }

    /** SMTP ayarları yapılmış mı? */
    public function hazir()
    {
        $host = ayar('smtp_sunucu') ?: ayar('smtp_host');
        return $host && ayar('smtp_kullanici');
    }

    /** Sipariş onay e-postası (graceful). */
    public function siparis_onay($siparis_id)
    {
        $this->CI->load->model('siparis_model');
        $s = $this->CI->siparis_model->mg_getir($siparis_id);
        if (! $s || empty($s->email)) {
            log_message('error', 'Eposta: sipariş e-postası yok (id=' . $siparis_id . '), atlandı.');
            return FALSE;
        }

        $konu = ayar('site_adi', 'Nesem Tesettür') . ' — Siparişiniz alındı (' . $s->siparis_no . ')';
        $govde = $this->_govde($s);

        if (! $this->hazir()) {
            log_message('error', 'Eposta: SMTP yapılandırılmamış — sipariş ' . $s->siparis_no . ' e-postası atlandı (graceful).');
            return FALSE;
        }

        $this->CI->email->initialize($this->_smtp_ayarlari());
        $this->CI->email->from(ayar('gonderen_eposta', ayar('smtp_kullanici')), ayar('site_adi', 'Nesem Tesettür'));
        $this->CI->email->to($s->email);
        $this->CI->email->subject($konu);
        $this->CI->email->message($govde);

        if (! $this->CI->email->send(FALSE)) {
            log_message('error', 'Eposta gönderilemedi: ' . strip_tags($this->CI->email->print_debugger(array('headers'))));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Yeni sipariş yönetici bildirimi (graceful).
     * Ayarlar → "Yönetici Bildirim E-postası" (bildirim_eposta) doluysa her yeni
     * siparişte özet gider; boşsa sessizce atlanır.
     */
    public function yonetici_bildirim($siparis_id)
    {
        $hedef = trim((string) ayar('bildirim_eposta', ''));
        if ($hedef === '' || ! filter_var($hedef, FILTER_VALIDATE_EMAIL)) { return FALSE; }
        if (! $this->hazir()) { log_message('error', 'Eposta: SMTP yok — yönetici bildirimi atlandı (graceful).'); return FALSE; }

        $this->CI->load->model('siparis_model');
        $s = $this->CI->siparis_model->mg_getir($siparis_id);
        if (! $s) { log_message('error', 'Eposta: yönetici bildirimi — sipariş yok (id=' . $siparis_id . ').'); return FALSE; }

        $site    = htmlspecialchars(ayar('site_adi', 'Nesem Tesettür'));
        $musteri = trim((string) ($s->teslimat_ad ?: $s->email ?: '-'));
        $konu    = '[Yeni Sipariş] #' . $s->siparis_no . ' — ' . number_format((float) $s->toplam, 2, ',', '.') . ' ₺';
        $satir   = function($etiket, $deger) {
            return '<tr><td style="padding:4px 12px 4px 0;color:#5c6c7a;white-space:nowrap">' . $etiket
                . '</td><td><b>' . $deger . '</b></td></tr>';
        };
        $govde = '<div style="font-family:Helvetica,Arial,sans-serif;max-width:560px;margin:auto;color:#001e2b">'
            . '<div style="background:#001e2b;color:#00ed64;padding:16px 20px;font-size:18px;font-weight:600">' . $site . '</div>'
            . '<div style="padding:20px"><h1 style="font-size:20px;margin:0 0 12px">Yeni sipariş alındı</h1>'
            . '<table style="font-size:14px">'
            . $satir('Sipariş no', '#' . htmlspecialchars($s->siparis_no))
            . $satir('Müşteri', htmlspecialchars($musteri))
            . $satir('E-posta', htmlspecialchars($s->email ?: '-'))
            . $satir('Toplam', number_format((float) $s->toplam, 2, ',', '.') . ' ₺')
            . $satir('Ödeme', htmlspecialchars($s->odeme_yontemi ?: '-'))
            . '</table>'
            . '<p style="margin-top:16px"><a href="' . site_url('yonetim/siparisler/detay/' . (int) $s->id) . '" style="background:#001e2b;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-size:14px">Yönetim panelinde aç</a></p>'
            . '</div></div>';

        $this->CI->email->initialize($this->_smtp_ayarlari());
        $this->CI->email->from(ayar('gonderen_eposta', ayar('smtp_kullanici')), ayar('site_adi', 'Nesem Tesettür'));
        $this->CI->email->to($hedef);
        $this->CI->email->subject($konu);
        $this->CI->email->message($govde);
        if (! $this->CI->email->send(FALSE)) {
            log_message('error', 'Eposta yönetici bildirimi gönderilemedi: ' . strip_tags($this->CI->email->print_debugger(array('headers'))));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Şifre sıfırlama bağlantısı (graceful — LIX).
     * SMTP yoksa atlanır (log'a düşer) — hesap sahibi LVII yönetici paneliyle
     * şifre sıfırlatabilir; akış asla hata vermez.
     */
    public function sifre_sifirlama($eposta, $link)
    {
        $eposta = trim((string) $eposta);
        if (! filter_var($eposta, FILTER_VALIDATE_EMAIL)) { return FALSE; }
        if (! $this->hazir()) { log_message('error', 'Eposta: SMTP yok — şifre sıfırlama e-postası atlandı (graceful).'); return FALSE; }

        $site = htmlspecialchars(ayar('site_adi', 'Nesem Tesettür'));
        $konu = ayar('site_adi', 'Nesem Tesettür') . ' — Şifre Sıfırlama';
        $govde = '<div style="font-family:Helvetica,Arial,sans-serif;max-width:560px;margin:auto;color:#001e2b">'
            . '<div style="background:#001e2b;color:#00ed64;padding:16px 20px;font-size:18px;font-weight:600">' . $site . '</div>'
            . '<div style="padding:20px"><h1 style="font-size:20px;margin:0 0 8px">Şifre sıfırlama isteği</h1>'
            . '<p style="color:#5c6c7a;margin:0 0 16px">Hesabınız için yeni şifre belirlemek için aşağıdaki düğmeyi kullanın. Bağlantı 30 dakika geçerlidir ve tek kullanımlıktır.</p>'
            . '<p style="margin:0 0 16px"><a href="' . htmlspecialchars($link) . '" style="background:#001e2b;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;font-size:14px">Yeni şifre belirle</a></p>'
            . '<p style="color:#5c6c7a;font-size:13px">Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz; şifreniz değişmez.</p>'
            . '</div></div>';

        $this->CI->email->initialize($this->_smtp_ayarlari());
        $this->CI->email->from(ayar('gonderen_eposta', ayar('smtp_kullanici')), ayar('site_adi', 'Nesem Tesettür'));
        $this->CI->email->to($eposta);
        $this->CI->email->subject($konu);
        $this->CI->email->message($govde);
        if (! $this->CI->email->send(FALSE)) {
            log_message('error', 'Eposta şifre sıfırlama gönderilemedi.');
            return FALSE;
        }
        return TRUE;
    }

    public function durum_bildirim($siparis_id, $durum_etiket, $notu = '')
    {
        $this->CI->load->model('siparis_model');
        $s = $this->CI->siparis_model->mg_admin_getir($siparis_id);
        if (! $s || empty($s->email)) { log_message('error', 'Eposta: durum bildirimi — e-posta yok, atlandı.'); return FALSE; }
        if (! $this->hazir()) { log_message('error', 'Eposta: SMTP yok — durum bildirimi atlandı (graceful).'); return FALSE; }
        $site = htmlspecialchars(ayar('site_adi', 'Nesem Tesettür'));
        $konu = ayar('site_adi', 'Nesem Tesettür') . ' — Sipariş #' . $s->siparis_no . ' durumu: ' . $durum_etiket;
        $govde = '<div style="font-family:Helvetica,Arial,sans-serif;max-width:560px;margin:auto;color:#001e2b">'
            . '<div style="background:#001e2b;color:#00ed64;padding:16px 20px;font-size:18px;font-weight:600">' . $site . '</div>'
            . '<div style="padding:20px"><h1 style="font-size:20px;margin:0 0 8px">Sipariş durumu güncellendi</h1>'
            . '<p style="color:#5c6c7a;margin:0 0 8px">Sipariş <b>#' . htmlspecialchars($s->siparis_no) . '</b> durumu: <b>' . htmlspecialchars($durum_etiket) . '</b></p>'
            . ($notu ? '<p style="color:#5c6c7a">' . htmlspecialchars($notu) . '</p>' : '')
            . '</div></div>';
        $this->CI->email->initialize($this->_smtp_ayarlari());
        $this->CI->email->from(ayar('gonderen_eposta', ayar('smtp_kullanici')), ayar('site_adi', 'Nesem Tesettür'));
        $this->CI->email->to($s->email);
        $this->CI->email->subject($konu);
        $this->CI->email->message($govde);
        if (! $this->CI->email->send(FALSE)) { log_message('error', 'Eposta durum bildirimi başarısız.'); return FALSE; }
        return TRUE;
    }

    private function _smtp_ayarlari()
    {
        return array(
            'protocol'    => 'smtp',
            'smtp_host'   => ayar('smtp_sunucu') ?: ayar('smtp_host'),
            'smtp_user'   => ayar('smtp_kullanici'),
            'smtp_pass'   => ayar('smtp_sifre'),
            'smtp_port'   => (int) (ayar('smtp_port') ?: 587),
            'smtp_crypto' => ayar('smtp_sifrelem') ?: 'tls',
            'mailtype'    => 'html',
            'charset'     => 'utf-8',
            'wordwrap'    => FALSE,
            'newline'     => "\r\n",
            'crlf'        => "\r\n",
        );
    }

    private function _govde($s)
    {
        $site = ayar('site_adi', 'Nesem Tesettür');
        $satirlar = '';
        foreach ($s->detaylar as $d) {
            $satirlar .= '<tr>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee">' . htmlspecialchars($d->urun_adi) . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;color:#5c6c7a">' . htmlspecialchars($d->varyant_bilgi ?: '-') . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:center">' . (int) $d->adet . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">' . number_format((float) $d->ara_toplam, 2, ',', '.') . ' ₺</td>'
                . '</tr>';
        }
        $islem = (float) $s->islem_ucreti;
        $kargo = (float) $s->kargo_ucreti;
        $ek = '';
        if ($islem > 0) { $ek .= '<tr><td style="padding:4px 0;color:#5c6c7a">İşlem ücreti</td><td style="text-align:right">' . number_format($islem, 2, ',', '.') . ' ₺</td></tr>'; }
        if ($kargo > 0) { $ek .= '<tr><td style="padding:4px 0;color:#5c6c7a">Kargo</td><td style="text-align:right">' . number_format($kargo, 2, ',', '.') . ' ₺</td></tr>'; }

        return '<div style="font-family:Helvetica,Arial,sans-serif;max-width:560px;margin:auto;color:#001e2b">'
            . '<div style="background:#001e2b;color:#00ed64;padding:16px 20px;font-size:18px;font-weight:600">' . htmlspecialchars($site) . '</div>'
            . '<div style="padding:20px">'
            . '<h1 style="font-size:22px;margin:0 0 8px">Siparişiniz alındı 🌿</h1>'
            . '<p style="color:#5c6c7a;margin:0 0 16px">Sipariş no: <b>#' . htmlspecialchars($s->siparis_no) . '</b></p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px">' . $satirlar . '</table>'
            . '<table style="width:100%;font-size:14px;margin-top:12px">'
            . '<tr><td style="padding:4px 0">Ara toplam</td><td style="text-align:right">' . number_format((float) $s->ara_toplam, 2, ',', '.') . ' ₺</td></tr>'
            . $ek
            . '<tr><td style="padding:10px 0;font-size:16px;font-weight:600;border-top:2px solid #001e2b">Toplam</td><td style="text-align:right;font-size:16px;font-weight:600;border-top:2px solid #001e2b">' . number_format((float) $s->toplam, 2, ',', '.') . ' ₺</td></tr>'
            . '</table>'
            . '<p style="color:#5c6c7a;font-size:13px;margin-top:18px">Ödeme yöntemi: ' . htmlspecialchars($s->odeme_yontemi) . ' · Sipariş durumunuz hesabınızdan takip edilebilir.</p>'
            . '</div></div>';
    }
}
