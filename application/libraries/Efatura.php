<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Efatura — e-fatura/e-arşiv oluşturma (graceful, sağlayıcı-bağımsız kayıt + yapılandırılabilir çağrı).
 *
 * Akış: sipariş → fatura kaydı (matrah/KDV/toplam, alıcı/satıcı) → varsa entegratöre
 * JSON POST (asenkron process_id + durum takibi). Entegratör yapılandırılmamışsa fatura
 * "bekliyor" olarak kaydedilir; sipariş/admin akışı bozulmaz.
 *
 * Ayarlar: efatura_entegrator / efatura_api_url / efatura_token /
 *          efatura_firma_vkn / efatura_firma_unvan / efatura_test
 *
 * NOT: e-fatura oluşturma GİB uyumlu UBL-TR imzasını ENTEGRATÖR üretir; biz fatura
 * VERİSİNİ (satıcı/alıcı/kalemler/matrah/KDV) entegratörün API'sine göndeririz.
 * Yanıt sözleşmesi (genel): {process_id, etn, pdf_url, durum, hata}.
 */
class Efatura
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Entegratör yapılandırılmış mı (API URL + token + satıcı VKN)? */
    public function hazir()
    {
        return ayar('efatura_api_url') && ayar('efatura_token') && ayar('efatura_firma_vkn');
    }

    /** Siparişten fatura veri/payload'ı (satıcı + alıcı + kalemler + matrah/KDV/toplam). */
    public function payload($siparis_id)
    {
        $this->CI->load->model('siparis_model');
        $s = $this->CI->siparis_model->mg_admin_getir($siparis_id);
        if (! $s) { return NULL; }

        $ara = (float) $s->ara_toplam;          // ürünler ara toplamı (KDV dahil fiyatlandırma varsayımı)
        $matrah = round($ara / 1.20, 2);         // %20 KDV ayrışımı
        $kdv    = round($ara - $matrah, 2);

        $kalemler = array();
        foreach ($s->detaylar as $d) {
            $satir_ara = (float) $d->ara_toplam;
            $satir_matrah = round($satir_ara / 1.20, 2);
            $kalemler[] = array(
                'ad'          => $d->urun_adi,
                'stok_kodu'   => $d->stok_kodu ?: '',
                'varyant'     => $d->varyant_bilgi ?: '',
                'adet'        => (int) $d->adet,
                'birim_fiyat' => (float) $d->birim_fiyat,
                'kdv_orani'   => (int) $d->kdv,
                'matrah'      => $satir_matrah,
                'kdv'         => round($satir_ara - $satir_matrah, 2),
                'tutar'       => $satir_ara,
            );
        }

        return array(
            'siparis_no' => $s->siparis_no,
            'para_birimi'=> 'TRY',
            'test'       => ((string) ayar('efatura_test') === '1'),
            'satıcı' => array(
                'vkn'   => ayar('efatura_firma_vkn'),
                'unvan' => ayar('efatura_firma_unvan') ?: ayar('site_adi', 'TekstilSite'),
            ),
            'alıcı' => array(
                'unvan'  => $s->firma_adi ?: $s->teslimat_ad,
                'vkn'    => $s->vergi_no ?: '',
                'eposta' => $s->email ?: ($s->bayi_email ?: ''),
                'adres'  => $s->teslimat_il ? trim($s->teslimat_il . ' ' . $s->teslimat_ilce) : '',
            ),
            'matrah' => $matrah,
            'kdv'    => $kdv,
            'toplam' => (float) $s->toplam,      // ödenen (kargo/işlem dahil)
            'kalemler' => $kalemler,
        );
    }

    /**
     * Sipariş için fatura oluştur (kayıt + opsiyonel entegratör çağrısı).
     * @return array {ok, fatura_id, mesaj}
     */
    public function olustur($siparis_id, $tip = 'earsiv')
    {
        $this->CI->load->model('siparis_model');
        $this->CI->load->model('fatura_model');

        $s = $this->CI->siparis_model->mg_admin_getir($siparis_id);
        if (! $s) { return array('ok' => FALSE, 'mesaj' => 'Sipariş bulunamadı.'); }

        // Mükerrer koruma: aynı siparişe iptal dışı aktif fatura varsa engelle
        $mevcut = $this->CI->fatura_model->siparis_faturalari($siparis_id);
        foreach ($mevcut as $m) {
            if (! in_array($m->durum, array('iptal'), TRUE)) {
                return array('ok' => FALSE, 'mesaj' => 'Bu siparişe zaten fatura kesilmiş (#' . $m->id . ').');
            }
        }

        $tip      = ($tip === 'efatura') ? 'efatura' : 'earsiv';
        $payload  = $this->payload($siparis_id);
        $ara      = (float) $s->ara_toplam;
        $matrah   = round($ara / 1.20, 2);
        $fatura_no = 'FT-' . $s->siparis_no;

        $fatura_id = $this->CI->fatura_model->ekle(array(
            'siparis_id'   => (int) $siparis_id,
            'bayi_id'       => $s->bayi_id ? (int) $s->bayi_id : NULL,
            'fatura_no'     => $fatura_no,
            'uuid'          => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                  mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                                  mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                                  mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
            'tip'           => $tip,
            'durum'         => 'bekliyor',
            'entegrator'    => ayar('efatura_entegrator') ?: NULL,
            'alici_unvan'   => $s->firma_adi ?: $s->teslimat_ad,
            'alici_vkn'     => $s->vergi_no ?: NULL,
            'alici_eposta'  => $s->email ?: ($s->bayi_email ?: NULL),
            'matrah'        => $matrah,
            'kdv'           => round($ara - $matrah, 2),
            'toplam'        => (float) $s->toplam,
        ));

        if (! $fatura_id) {
            return array('ok' => FALSE, 'mesaj' => 'Fatura kaydı oluşturulamadı.');
        }

        // Entegratör yapılandırılmışsa gönder; değilse "bekliyor" kalır (graceful).
        if ($this->hazir()) {
            $payload['tip'] = $tip;
            $payload['fatura_no'] = $fatura_no;
            $this->_provider_gonder($fatura_id, $payload);
            $f = $this->CI->fatura_model->get($fatura_id);
            if ($f && $f->durum === 'olustu') {
                return array('ok' => TRUE, 'fatura_id' => $fatura_id, 'mesaj' => 'Fatura entegratöre gönderildi (ETN: ' . $f->etn . ').');
            }
            return array('ok' => TRUE, 'fatura_id' => $fatura_id, 'mesaj' => 'Fatura entegratöre iletildi — durum bekleniyor.');
        }

        log_message('error', 'Efatura: entegratör yapılandırılmamış — fatura ' . $fatura_no . ' bekliyor olarak kaydedildi (graceful).');
        return array('ok' => TRUE, 'fatura_id' => $fatura_id, 'mesaj' => 'Fatura kaydedildi (bekliyor). Entegratör Ayarlar’dan yapılandırılınca otomatik gönderilir.');
    }

    /** Asenkron işlem durumunu entegratörden sorgula (process_id ile). */
    public function durum_sorgula($fatura_id)
    {
        $this->CI->load->model('fatura_model');
        $f = $this->CI->fatura_model->get($fatura_id);
        if (! $f) { return array('ok' => FALSE, 'mesaj' => 'Fatura bulunamadı.'); }
        if (! $this->hazir()) { return array('ok' => FALSE, 'mesaj' => 'Entegratör yapılandırılmamış.'); }
        if (! $f->process_id) { return array('ok' => FALSE, 'mesaj' => 'İşlem ID yok — durum sorgulanamaz.'); }

        $url = rtrim((string) ayar('efatura_api_url'), '/') . '/' . rawurlencode($f->process_id);
        $token = ayar('efatura_token');

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $token, 'Accept: application/json'),
        ));
        $yanit = curl_exec($ch);
        $hata  = curl_error($ch);
        curl_close($ch);

        if ($yanit === FALSE) {
            return array('ok' => FALSE, 'mesaj' => 'Sorgu hatası: ' . $hata);
        }
        $d = json_decode($yanit, TRUE);
        $upd = array();
        if (! empty($d['etn'])) { $upd['etn'] = substr((string) $d['etn'], 0, 60); }
        if (! empty($d['pdf_url'])) { $upd['pdf_url'] = substr((string) $d['pdf_url'], 0, 255); }
        $durum = $this->_durum_coz((string) ($d['durum'] ?? ''));
        if ($durum) { $upd['durum'] = $durum; }
        if (! empty($d['hata'])) { $upd['hata_mesaji'] = mb_substr((string) $d['hata'], 0, 1000); }
        if ($upd) { $this->CI->fatura_model->guncelle($fatura_id, $upd); }
        return array('ok' => TRUE, 'mesaj' => 'Durum güncellendi.');
    }

    /** Fatura verisini entegratöre POST (genel JSON sözleşmesi). */
    private function _provider_gonder($fatura_id, $payload)
    {
        if (! function_exists('curl_init')) {
            $this->CI->fatura_model->guncelle($fatura_id, array('durum' => 'reddedildi', 'hata_mesaji' => 'curl yok.'));
            return;
        }
        $url   = rtrim((string) ayar('efatura_api_url'), '/');
        $token = ayar('efatura_token');
        $json  = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Authorization: Bearer ' . $token),
            CURLOPT_POSTFIELDS     => $json,
        ));
        $yanit = curl_exec($ch);
        $hata  = curl_error($ch);
        $kod   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($yanit === FALSE) {
            $this->CI->fatura_model->guncelle($fatura_id, array('durum' => 'reddedildi', 'hata_mesaji' => 'Ağ/curl hatası: ' . $hata));
            return;
        }
        $d = json_decode($yanit, TRUE);
        $upd = array();
        if (! empty($d['process_id'])) { $upd['process_id'] = substr((string) $d['process_id'], 0, 80); }
        if (! empty($d['etn']))         { $upd['etn'] = substr((string) $d['etn'], 0, 60); }
        if (! empty($d['pdf_url']))     { $upd['pdf_url'] = substr((string) $d['pdf_url'], 0, 255); }

        if ($kod >= 400 || (! empty($d['hata']))) {
            $upd['durum'] = 'reddedildi';
            $upd['hata_mesaji'] = mb_substr((string) ($d['hata'] ?? $yanit), 0, 1000);
        } elseif (! empty($upd['etn'])) {
            $upd['durum'] = 'olustu';
        } elseif (! empty($upd['process_id'])) {
            $upd['durum'] = 'isleniyor';
        } else {
            $upd['durum'] = 'isleniyor';
            $upd['hata_mesaji'] = 'Entegratör yanıtı tanımlanamadı (HTTP ' . $kod . '): ' . mb_substr((string) $yanit, 0, 300);
        }
        $this->CI->fatura_model->guncelle($fatura_id, $upd);
    }

    /** Entegratör durum metnini bizim durum enum'una çevir. */
    private function _durum_coz($d)
    {
        $d = strtolower(trim($d));
        $map = array(
            'success' => 'olustu', 'completed' => 'olustu', 'done' => 'olustu', 'olustu' => 'olustu',
            'pending' => 'isleniyor', 'processing' => 'isleniyor', 'isleniyor' => 'isleniyor',
            'sent' => 'gonderildi', 'gonderildi' => 'gonderildi',
            'rejected' => 'reddedildi', 'error' => 'reddedildi', 'reddedildi' => 'reddedildi',
            'canceled' => 'iptal', 'iptal' => 'iptal',
        );
        return $map[$d] ?? '';
    }
}
