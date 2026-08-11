<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pazaryeri_api — pazaryeri senkronu (graceful).
 *
 * Referans adapter: Trendyol Partner API (sapigw, Basic Auth, supplierId).
 *   - Stok/fiyat:  PUT /v1/sellers/{supplierId}/products/price-and-inventory
 *   - Sipariş:     GET /suppliers/{supplierId}/orders
 * Diğer platformlar (Hepsiburada/N11) adapter'ı ileride eklenir; o zamana kadar
 * "bekliyor" log ile graceful atlar. Anahtar yoksa/yanlışsa HTTP hatası loglanır,
 * uygulama akışı bozulmaz.
 *
 * Hesap kimlikleri model içinde CI Encryption ile çözülür (plaintext saklanmaz).
 */
class Pazaryeri_api
{
    protected $CI;
    const TRENDYOL = 'https://api.trendyol.com/sapigw';

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Hesap gönderime hazır mı (platform + kimlik + supplier)? */
    public function hazir($h)
    {
        return $h && ! empty($h->platform) && ! empty($h->api_key) && ! empty($h->api_secret) && ! empty($h->supplier_id);
    }

    /** Eşleşen ürünlerin stok+fiyatını pazaryerine gönder. */
    public function stok_fiyat_gonder($hesap_id)
    {
        $this->CI->load->model('pazaryeri_model');
        $h = $this->CI->pazaryeri_model->hesap_getir_acik($hesap_id);
        if (! $h) { return $this->_sonuc(FALSE, 'Hesap bulunamadı.'); }
        if (! $this->hazir($h)) {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'stok_fiyat', 'bekliyor', 'Kimlik/supplier eksik — atlandı.');
            return $this->_sonuc(FALSE, 'Hesap kimlikleri eksik (platform/API key/API secret/supplier).');
        }
        if ($h->platform !== 'trendyol') {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'stok_fiyat', 'bekliyor', $h->platform . ' adapter yok (yalnız trendyol).');
            return $this->_sonuc(FALSE, ucfirst($h->platform) . ' adapter henüz yok (şimdilik yalnız Trendyol).');
        }

        // Eşleşen ürünler + toplam varyant stok
        $eslesmeler = $this->CI->db->where('hesap_id', (int) $hesap_id)->where('durum', 1)->get('pazaryeri_urun_eslestirme')->result();
        if (! $eslesmeler) {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'stok_fiyat', 'bekliyor', 'Eşleşen ürün yok.');
            return $this->_sonuc(FALSE, 'Bu hesap için eşleştirilmiş ürün yok.');
        }

        $urun_idler = array();
        foreach ($eslesmeler as $e) { $urun_idler[$e->urun_id] = $e->pazaryeri_urun_id; }
        $urunler = $this->CI->db->where_in('id', array_keys($urun_idler))->where('durum', 1)->get('urunler')->result();

        // Varyant stoklarını tek seferde topla
        $stok_harita = array();
        $vrows = $this->CI->db->select('urun_id, COALESCE(SUM(stok),0) AS s')->where_in('urun_id', array_keys($urun_idler))->where('durum', 1)->group_by('urun_id')->get('urun_varyantlari')->result();
        foreach ($vrows as $v) { $stok_harita[(int) $v->urun_id] = (int) $v->s; }

        $items = array();
        foreach ($urunler as $u) {
            $items[] = array(
                'barcode'   => $urun_idler[(int) $u->id] ?: $u->stok_kodu,
                'quantity'  => $stok_harita[(int) $u->id] ?? 0,
                'listPrice' => (float) $u->fiyat,
                'salePrice' => (float) $u->fiyat,
            );
        }
        if (! $items) {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'stok_fiyat', 'bekliyor', 'Gönderilebilir aktif ürün yok.');
            return $this->_sonuc(FALSE, 'Gönderilebilir aktif ürün yok.');
        }

        $url = self::TRENDYOL . '/v1/sellers/' . rawurlencode($h->supplier_id) . '/products/price-and-inventory';
        $yanit = $this->_http('PUT', $url, $h, json_encode(array('items' => $items), JSON_UNESCAPED_UNICODE));
        if (! $yanit['ok']) {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'stok_fiyat', 'hata', count($items) . ' ürün gönderilemedi', $yanit['mesaj']);
            return $this->_sonuc(FALSE, $yanit['mesaj']);
        }
        $d = json_decode($yanit['govde'], TRUE);
        $batch = $d['batchRequestId'] ?? ($d['id'] ?? NULL);
        $this->CI->pazaryeri_model->son_sin_isaretle($hesap_id);
        $this->CI->pazaryeri_model->log_ekle($hesap_id, 'stok_fiyat', 'basarili', count($items) . ' ürün gönderildi' . ($batch ? ' (batch ' . $batch . ')' : ''));
        return $this->_sonuc(TRUE, count($items) . ' ürünün stok/fiyatı gönderildi.', array('batch_id' => $batch, 'adet' => count($items)));
    }

    /** Pazaryerinden siparişleri çek (sorgula + log). İçe aktarım platform şemasına göre genişletilebilir. */
    public function siparis_cek($hesap_id)
    {
        $this->CI->load->model('pazaryeri_model');
        $h = $this->CI->pazaryeri_model->hesap_getir_acik($hesap_id);
        if (! $h) { return $this->_sonuc(FALSE, 'Hesap bulunamadı.'); }
        if (! $this->hazir($h)) {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'siparis_cek', 'bekliyor', 'Kimlik/supplier eksik — atlandı.');
            return $this->_sonuc(FALSE, 'Hesap kimlikleri eksik.');
        }
        if ($h->platform !== 'trendyol') {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'siparis_cek', 'bekliyor', $h->platform . ' adapter yok.');
            return $this->_sonuc(FALSE, ucfirst($h->platform) . ' adapter henüz yok.');
        }

        $url = self::TRENDYOL . '/suppliers/' . rawurlencode($h->supplier_id) . '/orders?size=20';
        $yanit = $this->_http('GET', $url, $h, '');
        if (! $yanit['ok']) {
            $this->CI->pazaryeri_model->log_ekle($hesap_id, 'siparis_cek', 'hata', 'Sipariş sorgu hatası', $yanit['mesaj']);
            return $this->_sonuc(FALSE, $yanit['mesaj']);
        }
        $d = json_decode($yanit['govde'], TRUE);
        $n = isset($d['content']) && is_array($d['content']) ? count($d['content']) : (isset($d['totalElements']) ? (int) $d['totalElements'] : 0);
        $this->CI->pazaryeri_model->son_sin_isaretle($hesap_id);
        $this->CI->pazaryeri_model->log_ekle($hesap_id, 'siparis_cek', 'basarili', $n . ' sipariş sorgulandı (içe aktarım genişletilebilir).');
        return $this->_sonuc(TRUE, $n . ' sipariş sorgulandı.', array('adet' => $n));
    }

    /** Genel HTTP (Trendyol Basic Auth + User-Agent). */
    private function _http($metot, $url, $h, $govde)
    {
        if (! function_exists('curl_init')) { return array('ok' => FALSE, 'mesaj' => 'curl yok.'); }
        $auth = 'Basic ' . base64_encode($h->api_key . ':' . $h->api_secret);
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST   => $metot,
            CURLOPT_RETURNTRANSFER  => TRUE,
            CURLOPT_TIMEOUT         => 25,
            CURLOPT_SSL_VERIFYPEER  => TRUE,
            CURLOPT_HTTPHEADER      => array(
                'Authorization: ' . $auth,
                'User-Agent: TekstilSite',
                'Accept: application/json',
                'Content-Type: application/json',
            ),
            CURLOPT_POSTFIELDS      => $govde !== '' ? $govde : NULL,
        ));
        $cikti = curl_exec($ch);
        $hata  = curl_error($ch);
        $kod   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($cikti === FALSE) { return array('ok' => FALSE, 'mesaj' => 'Ağ/curl hatası: ' . $hata); }
        if ($kod >= 400) { return array('ok' => FALSE, 'mesaj' => 'Platform HTTP ' . $kod . ': ' . mb_substr((string) $cikti, 0, 500)); }
        return array('ok' => TRUE, 'govde' => $cikti);
    }

    private function _sonuc($ok, $mesaj, $ek = array())
    {
        return array_merge(array('ok' => $ok, 'mesaj' => $mesaj), $ek);
    }
}
