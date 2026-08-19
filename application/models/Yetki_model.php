<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Yetki_model — rol bazlı yetki matrisi (yetkiler tablosu).
 * MODULLER: yönetim modüllerinin kelime dağarcığı (yetki_gerek çağrılarıyla uyumlu).
 * Süper (rol 1) tabloda yer almaz — kod içinde daima tam yetkili (sabit).
 */
class Yetki_model extends CI_Model
{
    /** Yönetim modülleri: key => insan-okur etiket (matris satırları + menü eşlemesi). */
    public static $MODULLER = array(
        'siparisler' => 'Siparişler',
        'urunler'    => 'Ürünler',
        'kategoriler'=> 'Kategoriler',
        'markalar'   => 'Markalar',
        'stok'       => 'Stok',
        'bayiler'    => 'Bayiler',
        'faturalar'  => 'Faturalar',
        'pazaryeri'  => 'Pazaryeri',
        'feed'       => 'API / Feed',
        'xml_ice'    => 'XML İçe Aktarım',
        'raporlar'   => 'Raporlar',
        'bannerlar'  => 'Bannerlar',
        'yazilar'    => 'Blog Yazıları',
        'sayfalar'   => 'Sayfalar',
        'kuponlar'   => 'Kuponlar',
        'ayarlar'    => 'Ayarlar & Para Birimi',
    );

    /** Bir rolün yetkilerini döndür: modul => [etiket,goruntule,duzenle,sil] (tüm modüller; eksik=0). */
    public function liste($rol_id)
    {
        $rol_id = (int) $rol_id;
        $rows = $this->db->where('rol_id', $rol_id)->get('yetkiler')->result();
        $map = array();
        foreach ($rows as $r) { $map[$r->modul] = $r; }

        $out = array();
        foreach (self::$MODULLER as $key => $etiket) {
            $r = $map[$key] ?? NULL;
            $out[$key] = array(
                'etiket'    => $etiket,
                'goruntule' => $r ? (int) $r->goruntule : 0,
                'duzenle'   => $r ? (int) $r->duzenle : 0,
                'sil'       => $r ? (int) $r->sil : 0,
            );
        }
        return $out;
    }

    /** Rolün yetkilerini toplu kaydet. $grid: modul => [goruntule,duzenle,sil] (checkbox varlığı=1). */
    public function kaydet($rol_id, $grid)
    {
        $rol_id = (int) $rol_id;
        if ($rol_id < 1 || ! is_array($grid)) { return; }
        foreach (self::$MODULLER as $key => $_) {
            $g = isset($grid[$key]['goruntule']) ? 1 : 0;
            $d = isset($grid[$key]['duzenle']) ? 1 : 0;
            $s = isset($grid[$key]['sil']) ? 1 : 0;
            $exists = $this->db->where('rol_id', $rol_id)->where('modul', $key)->count_all_results('yetkiler');
            if ($exists) {
                $this->db->where('rol_id', $rol_id)->where('modul', $key)
                         ->update('yetkiler', array('goruntule' => $g, 'duzenle' => $d, 'sil' => $s));
            } else {
                $this->db->insert('yetkiler', array(
                    'rol_id' => $rol_id, 'modul' => $key,
                    'goruntule' => $g, 'duzenle' => $d, 'sil' => $s,
                ));
            }
        }
    }
}
