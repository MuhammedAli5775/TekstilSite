<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xml_ice_model — tedarikçi XML feed'i içe aktarımı (Faz 5).
 *
 * Akış: kaynak (xml_kaynaklari) → cek() → cozumle(esleme) → ice_aktar().
 * Eşleşme anahtarı urunler.stok_kodu: mevcut ürün güncellenir; yeni ürün
 * kaynak izin veriyorsa oluşturulur. Önizleme = aynı kodun transaction
 * ROLLBACK ile çalıştırılması (yazmadan kesin sonuç).
 *
 * Güvenlik (workflow.md §2): URL yalnız http/https (SSRF yüzeyi kısıtlı,
 * özellik yönetim panelinden tanımlanır); 20 sn zaman aşımı + 8 MB boyut
 * sınırı; eşleme etiketleri ^[A-Za-z0-9_.-]+$ doğrulanır (XPath enjeksiyonu
 * yok); SimpleXML entity yüklemesi PHP 8/libxml 2.9+'da öntanımlı kapalı.
 */
class Xml_ice_model extends CI_Model
{
    /** URL çekme sınırları. */
    const ZAMAN_ASIMI = 20;
    const BOYUT_SINIRI = 8388608; // 8 MB

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('teksil'); // slug_tr()
    }

    /* ---------------- Kaynak CRUD ---------------- */

    public function kaynak_liste()
    {
        return $this->db->order_by('id', 'DESC')->get('xml_kaynaklari')->result();
    }

    public function kaynak_getir($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('xml_kaynaklari')->row();
    }

    /** Ekle (id boş) / güncelle (id dolu). Eşleme JSON'ı burada doğrulanır. */
    public function kaynak_kaydet($id, $d)
    {
        $esleme = $this->_esleme_dogrula($d['esleme'] ?? NULL);
        if ($esleme === FALSE) { return FALSE; }

        $satir = array(
            'ad'                     => mb_substr(trim((string) $d['ad']), 0, 120),
            'url'                    => mb_substr(trim((string) $d['url']), 0, 500),
            'esleme'                 => $esleme, // NULL = varsayılan
            'varsayilan_kategori_id' => ((int) ($d['varsayilan_kategori_id'] ?? 0)) ?: NULL,
            'fiyat_carpani'          => (float) ($d['fiyat_carpani'] ?? 1) > 0 ? (float) $d['fiyat_carpani'] : 1.0,
            'yeni_urun_olustur'      => empty($d['yeni_urun_olustur']) ? 0 : 1,
        );
        if ($id) {
            $this->db->where('id', (int) $id)->update('xml_kaynaklari', $satir);
            return (int) $id;
        }
        $this->db->insert('xml_kaynaklari', $satir);
        return (int) $this->db->insert_id();
    }

    public function kaynak_durum($id, $durum)
    {
        $this->db->where('id', (int) $id)->update('xml_kaynaklari', array('durum' => $durum ? 1 : 0));
    }

    public function kaynak_sil($id)
    {
        $this->db->where('id', (int) $id)->delete('xml_kaynaklari'); // log CASCADE
    }

    public function log_liste($kaynak_id, $limit = 30)
    {
        return $this->db->where('kaynak_id', (int) $kaynak_id)
                        ->order_by('id', 'DESC')->limit((int) $limit)
                        ->get('xml_loglari')->result();
    }

    /* ---------------- Çekme ---------------- */

    /** URL'den XML metnini çeker. Dönüş: array(ok, icerik, hata). */
    public function cek($url)
    {
        $url = trim((string) $url);
        if (! preg_match('#^https?://#i', $url)) {
            return array(FALSE, NULL, 'URL yalnız http/ olabilir: ' . $url);
        }
        $cizgi = curl_init($url);
        curl_setopt_array($cizgi, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => self::ZAMAN_ASIMI,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_MAXFILESIZE    => self::BOYUT_SINIRI,
            CURLOPT_USERAGENT      => 'NesemTesettur-XmlIce/1.0',
            CURLOPT_SSL_VERIFYPEER => TRUE,
        ));
        $govde = curl_exec($cizgi);
        $hata  = curl_error($cizgi);
        $kod   = (int) curl_getinfo($cizgi, CURLINFO_RESPONSE_CODE);
        curl_close($cizgi);

        if ($govde === FALSE || $kod >= 400) {
            return array(FALSE, NULL, "Çekme hatası (HTTP $kod): " . mb_substr($hata, 0, 180));
        }
        if (strlen((string) $govde) > self::BOYUT_SINIRI) {
            return array(FALSE, NULL, 'XML 8 MB sınırını aşıyor.');
        }
        return array(TRUE, $govde, NULL);
    }

    /* ---------------- Çözümleme ---------------- */

    /**
     * XML metnini eşlemeye göre ürünlere çevirir. Dönüş: array(ok, urunler, hata).
     * Her ürün: stok_kodu, ad, aciklama(NULL=etiket yok), fiyat, eski_fiyat,
     * moq, birim_adim, kategori, gorsel, varyantlar[], gecerli, neden.
     */
    public function cozumle($xml_metin, $esleme_json)
    {
        $e = $this->_esleme_coz($esleme_json);

        libxml_use_internal_errors(TRUE); // uyarıları loga değil dönüşe yaz
        $xml = simplexml_load_string((string) $xml_metin);
        if ($xml === FALSE) {
            $ilksatirlar = array();
            foreach (array_slice(libxml_get_errors(), 0, 3) as $le) {
                $ilksatirlar[] = trim($le->message) . ' (satır ' . $le->line . ')';
            }
            libxml_clear_errors();
            return array(FALSE, NULL, 'XML çözümlenemedi: ' . implode('; ', $ilksatirlar));
        }
        libxml_clear_errors();

        $dugumler = $xml->xpath('//' . $e['kok']);
        if (! $dugumler) {
            return array(FALSE, NULL, "Ürün elemanı bulunamadı (kök etiketi '{$e['kok']}') — eşlemeyi kontrol edin.");
        }

        $urunler = array();
        foreach ($dugumler as $d) {
            $u = array(
                'stok_kodu'  => trim((string) $this->_deger($d, $e['stokKodu'])),
                'ad'         => trim((string) $this->_deger($d, $e['ad'])),
                'aciklama'   => $this->_deger($d, $e['aciklama']), // NULL = etiket yok
                'fiyat'      => $this->_fiyat_norm($this->_deger($d, $e['fiyat'])),
                'eski_fiyat' => $this->_fiyat_norm($this->_deger($d, $e['eskiFiyat'])),
                'moq'        => (int) $this->_deger($d, $e['moq']),
                'birim_adim' => (int) $this->_deger($d, $e['birimAdim']),
                'kategori'   => trim((string) $this->_deger($d, $e['kategori'])),
                'gorsel'     => trim((string) $this->_deger($d, $e['gorsel'])),
                'varyantlar' => array(),
                'gecerli'    => TRUE,
                'neden'      => '',
            );

            $vk = $d->{$e['varyantKok']} ?? NULL;
            if ($vk !== NULL) {
                foreach ($vk->children() as $c) {
                    if ($c->getName() !== $e['varyant']) { continue; }
                    $u['varyantlar'][] = array(
                        'renk'  => trim((string) $this->_deger($c, $e['vRenk'])),
                        'beden' => trim((string) $this->_deger($c, $e['vBeden'])),
                        'sku'   => trim((string) $this->_deger($c, $e['vSku'])),
                        'stok'  => (int) $this->_fiyat_norm($this->_deger($c, $e['vStok'])),
                    );
                }
            }

            if ($u['stok_kodu'] === '' || $u['ad'] === '') {
                $u['gecerli'] = FALSE; $u['neden'] = 'stok kodu / ad eksik';
            } elseif ($u['fiyat'] <= 0) {
                $u['gecerli'] = FALSE; $u['neden'] = 'geçersiz fiyat';
            }
            $urunler[] = $u;
        }
        return array(TRUE, $urunler, NULL);
    }

    /* ---------------- İçe aktarım ---------------- */

    /**
     * Kaynağı işler. $gercek=TRUE yazıp komit eder; FALSE aynı kodu rollback
     * ile çalıştırır (önizleme). $xml_metin verilirse URL yerine o çözümlenir
     * (yapıştır-itest / regresyon). Dönüş: array(ok, mesaj, sayaclar, satirlar).
     */
    public function ice_aktar($kaynak, $gercek = FALSE, $xml_metin = NULL)
    {
        $basla = microtime(TRUE);
        $o = array('urun_sayisi' => 0, 'yeni' => 0, 'guncellenen' => 0, 'atlanan' => 0,
                   'varyant_eklenen' => 0, 'varyant_guncellenen' => 0);
        $satirlar = array();   // önizleme tablosu (ilk 100)
        $notlar  = array();

        // 1) XML metnini edin
        if (trim((string) $xml_metin) === '') {
            list($ok, $icerik, $hata) = $this->cek($kaynak->url);
            if (! $ok) { return $this->_bitir($kaynak, $gercek, FALSE, $o, $satirlar, $hata, $basla); }
            $xml_metin = $icerik;
        }

        // 2) Çözümle
        list($ok, $urunler, $hata) = $this->cozumle($xml_metin, $kaynak->esleme);
        if (! $ok) { return $this->_bitir($kaynak, $gercek, FALSE, $o, $satirlar, $hata, $basla); }
        $o['urun_sayisi'] = count($urunler);

        // 3) Transaction (önizleme = rollback)
        $carpan = (float) $kaynak->fiyat_carpani ?: 1.0;
        $this->db->trans_begin();
        foreach ($urunler as $u) {

            if (! $u['gecerli']) {
                $o['atlanan']++;
                if (count($notlar) < 5) { $notlar[] = $u['stok_kodu'] . ': ' . $u['neden']; }
                $this->_satir($satirlar, $u, 'atla', $u['neden']);
                continue;
            }

            // Silinmiş ürünler dahil ara (stok_kodu UNIQUE — çakışmada ezme, atla)
            $mevcut = $this->db->where('stok_kodu', $u['stok_kodu'])->limit(1)->get('urunler')->row();
            if ($mevcut && $mevcut->deleted_at !== NULL) {
                $o['atlanan']++;
                if (count($notlar) < 5) { $notlar[] = $u['stok_kodu'] . ': silinmiş ürünle kod çakışması'; }
                $this->_satir($satirlar, $u, 'atla', 'silinmiş ürün çakışması');
                continue;
            }

            $fiyat     = round($u['fiyat'] * $carpan, 2);
            $eski_fiyat = $u['eski_fiyat'] > 0 ? round($u['eski_fiyat'] * $carpan, 2) : 0.0;

            if ($mevcut) {
                $upd = array('fiyat' => $fiyat);
                if ($eski_fiyat > 0)                          { $upd['eski_fiyat'] = $eski_fiyat; }
                if ($u['aciklama'] !== NULL && $u['aciklama'] !== '') { $upd['aciklama'] = $u['aciklama']; }
                if ($u['moq'] >= 1)                           { $upd['moq'] = $u['moq']; }
                if ($u['birim_adim'] >= 1)                    { $upd['birim_adim'] = $u['birim_adim']; }
                if (preg_match('#^https?://#i', $u['gorsel'])) { $upd['ana_gorsel'] = $u['gorsel']; }
                $this->db->where('id', (int) $mevcut->id)->update('urunler', $upd);
                $urun_id = (int) $mevcut->id;
                $o['guncellenen']++;
                $this->_satir($satirlar, $u, 'guncelle', '');
            } elseif (((int) $kaynak->yeni_urun_olustur) === 1) {
                $kategori_id = $this->_kategori_bul($u['kategori']);
                if (! $kategori_id) {
                    $kategori_id = ((int) $kaynak->varsayilan_kategori_id) ?: NULL;
                    if (! $kategori_id && count($notlar) < 5) { $notlar[] = $u['stok_kodu'] . ': kategori eşleşmedi, kategorisiz oluşturuldu'; }
                }
                $this->db->insert('urunler', array(
                    'kategori_id'  => $kategori_id,
                    'ad'           => $u['ad'],
                    'slug'         => $this->_slug_benzersiz($u['ad']),
                    'stok_kodu'    => $u['stok_kodu'],
                    'aciklama'     => ($u['aciklama'] !== NULL && $u['aciklama'] !== '') ? $u['aciklama'] : NULL,
                    'fiyat'        => $fiyat,
                    'eski_fiyat'   => $eski_fiyat,
                    'moq'          => $u['moq'] >= 1 ? $u['moq'] : 1,
                    'birim_adim'   => $u['birim_adim'] >= 1 ? $u['birim_adim'] : 1,
                    'ana_gorsel'   => preg_match('#^https?://#i', $u['gorsel']) ? $u['gorsel'] : NULL,
                    'durum'        => 1,
                ));
                $urun_id = (int) $this->db->insert_id();
                $o['yeni']++;
                $this->_satir($satirlar, $u, 'yeni', $kategori_id ? '' : 'kategorisiz');
            } else {
                $o['atlanan']++;
                $this->_satir($satirlar, $u, 'atla', 'yeni ürün kapalı');
                continue;
            }

            // Varyantlar: ürünün mevcut satırları bir kez çekilip PHP'de eşleştirilir
            // (Query Builder'da COALESCE/LOWER içeren alan ifadeleri bozuk SQL üretiyor).
            if ($u['varyantlar']) {
                $mevcut_v = $this->db->where('urun_id', $urun_id)->get('urun_varyantlari')->result();
                foreach ($u['varyantlar'] as $v) {
                    $mv = NULL;
                    foreach ($mevcut_v as $m) {
                        if ($v['sku'] !== '') {
                            if ($m->sku !== NULL && strcasecmp((string) $m->sku, $v['sku']) === 0) { $mv = $m; break; }
                        } elseif (strcasecmp((string) $m->renk, $v['renk']) === 0
                               && strcasecmp((string) $m->beden, $v['beden']) === 0) {
                            $mv = $m; break;
                        }
                    }
                    if ($mv) {
                        $this->db->where('id', (int) $mv->id)
                                 ->update('urun_varyantlari', array('stok' => max(0, $v['stok'])));
                        $o['varyant_guncellenen']++;
                    } else {
                        $this->db->insert('urun_varyantlari', array(
                            'urun_id' => $urun_id,
                            'renk'    => $v['renk']  !== '' ? $v['renk']  : NULL,
                            'beden'   => $v['beden'] !== '' ? $v['beden'] : NULL,
                            'sku'     => $v['sku']   !== '' ? $v['sku']   : NULL,
                            'stok'    => max(0, $v['stok']),
                            'durum'   => 1,
                        ));
                        $o['varyant_eklenen']++;
                    }
                }
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return $this->_bitir($kaynak, $gercek, FALSE, $o, $satirlar, 'İçe aktarım sırasında SQL hatası (loglara bakın).', $basla);
        }
        if ($gercek) { $this->db->trans_commit(); } else { $this->db->trans_rollback(); }

        $mesaj = "{$o['yeni']} yeni, {$o['guncellenen']} güncellenen, {$o['atlanan']} atlanan"
               . " (+{$o['varyant_eklenen']} varyant eklendi, {$o['varyant_guncellenen']} güncellendi)";
        $res = $this->_bitir($kaynak, $gercek, TRUE, $o, $satirlar, $mesaj, $basla);
        $res['notlar'] = $notlar;
        return $res;
    }

    /* ---------------- Yardımcılar ---------------- */

    /** Varsayılan eşleme — kendi Xml_export biçimimiz (api/Feed çıktısı). */
    private function _esleme_varsayilan()
    {
        return array(
            'kok' => 'urun',
            'stokKodu' => 'stokKodu', 'ad' => 'ad', 'aciklama' => 'aciklama',
            'fiyat' => 'fiyat', 'eskiFiyat' => 'eskiFiyat',
            'moq' => 'moq', 'birimAdim' => 'birimAdim',
            'kategori' => 'kategori', 'gorsel' => 'gorsel',
            'varyantKok' => 'varyantlar', 'varyant' => 'varyant',
            'vRenk' => 'renk', 'vBeden' => 'beden', 'vSku' => 'sku', 'vStok' => 'stok',
        );
    }

    /** Kayıttaki eşleme JSON'ını varsayılanla birleştirir; geçersizse FALSE. */
    private function _esleme_coz($json)
    {
        $e = $this->_esleme_varsayilan();
        $json = trim((string) $json);
        if ($json === '' || $json === NULL) { return $e; }
        $d = json_decode($json, TRUE);
        if (! is_array($d)) { return $e; }
        foreach ($e as $k => $bos) {
            if (isset($d[$k]) && is_string($d[$k]) && preg_match('/^[A-Za-z0-9_.-]+$/', trim($d[$k]))) {
                $e[$k] = trim($d[$k]);
            }
        }
        return $e;
    }

    /** Kaydetme formundan gelen eşleme JSON'ı: boş → NULL, bozuk → FALSE (form hatası). */
    private function _esleme_dogrula($json)
    {
        $json = trim((string) $json);
        if ($json === '') { return NULL; }
        $d = json_decode($json, TRUE);
        if (! is_array($d)) { return FALSE; }
        foreach ($d as $k => $v) {
            if (! is_string($k) || ! is_string($v) || ! preg_match('/^[A-Za-z0-9_.-]{1,60}$/', trim($v))) { return FALSE; }
        }
        return $json;
    }

    /** Alt eleman değeri; etiket yoksa NULL (var-ama-boş '' ile ayrım önemli). */
    private function _deger($dugum, $etiket)
    {
        if (! isset($dugum->{$etiket})) { return NULL; }
        return (string) $dugum->{$etiket};
    }

    /** '1234.56' / '1234,56' / '1.234,56' biçimlerini floata çevirir. */
    private function _fiyat_norm($v)
    {
        if ($v === NULL) { return 0.0; }
        $v = trim((string) $v);
        if ($v === '') { return 0.0; }
        if (strpos($v, ',') !== FALSE) {
            $v = str_replace(array('.', ' ', ','), array('', '', '.'), $v);
        }
        return (float) $v;
    }

    /** Kategori adını id'ye bağlar (slug veya ad eşleşmesi); bulamazsa NULL. */
    private function _kategori_bul($ad)
    {
        $ad = trim((string) $ad);
        if ($ad === '') { return NULL; }
        $k = $this->db->where('slug', slug_tr($ad))->limit(1)->get('kategoriler')->row();
        if ($k) { return (int) $k->id; }
        foreach ($this->db->get('kategoriler')->result() as $k) {   // ad eşleşmesi PHP'de (küçük tablo)
            if (strcasecmp((string) $k->ad, $ad) === 0) { return (int) $k->id; }
        }
        return NULL;
    }

    private function _slug_benzersiz($ad)
    {
        $slug = slug_tr($ad);
        if ($slug === '') { $slug = 'urun'; }
        $aday = $slug; $i = 1;
        while ($this->db->where('slug', $aday)->count_all_results('urunler') > 0) {
            $aday = $slug . '-' . (++$i);
        }
        return $aday;
    }

    /** Önizleme tablosu satırı (ilk 100). */
    private function _satir(&$satirlar, $u, $eylem, $not)
    {
        if (count($satirlar) >= 100) { return; }
        $satirlar[] = array(
            'stok_kodu' => $u['stok_kodu'], 'ad' => $u['ad'], 'eylem' => $eylem,
            'fiyat' => $u['fiyat'], 'kategori' => $u['kategori'], 'not' => $not,
        );
    }

    /** Log satırı + (gerçek koşuda) kaynak sonucu + standart dönüş paketi. */
    private function _bitir($kaynak, $gercek, $ok, $o, $satirlar, $mesaj, $basla)
    {
        $sure = round(microtime(TRUE) - $basla, 2);
        $this->db->insert('xml_loglari', array(
            'kaynak_id'          => (int) $kaynak->id,
            'kip'                => $gercek ? 'gercek' : 'onizleme',
            'durum'              => $ok ? 'basarili' : 'hata',
            'urun_sayisi'        => (int) $o['urun_sayisi'],
            'yeni'               => (int) $o['yeni'],
            'guncellenen'        => (int) $o['guncellenen'],
            'atlanan'            => (int) $o['atlanan'],
            'varyant_eklenen'    => (int) $o['varyant_eklenen'],
            'varyant_guncellenen'=> (int) $o['varyant_guncellenen'],
            'ozet'               => mb_substr((string) $mesaj, 0, 255),
            'hata_mesaji'        => $ok ? NULL : mb_substr((string) $mesaj, 0, 2000),
            'sure_sn'            => $sure,
        ));
        if ($gercek) {
            $this->db->where('id', (int) $kaynak->id)->update('xml_kaynaklari', array(
                'son_calisma' => date('Y-m-d H:i:s'),
                'son_sonuc'   => $ok ? 'basarili' : 'hata',
            ));
        }
        return array('ok' => $ok, 'mesaj' => (string) $mesaj, 'sayaclar' => $o, 'satirlar' => $satirlar);
    }
}
