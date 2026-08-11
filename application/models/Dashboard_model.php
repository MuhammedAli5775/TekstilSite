<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard_model — yönetim paneli istatistikleri (Query Builder).
 */
class Dashboard_model extends CI_Model
{
    public function ozet()
    {
        if (! $this->db->table_exists('siparisler')) {
            return array('siparis' => 0, 'bekleyen' => 0, 'bayi' => 0, 'bekleyen_bayi' => 0, 'urun' => 0, 'ciro' => 0.0);
        }
        $ciro = $this->db->select('COALESCE(SUM(toplam),0) AS c', FALSE)
                         ->where_in('durum', array('onaylandi', 'hazirlaniyor', 'kargolandi', 'teslim_edildi'))
                         ->get('siparisler')->row();
        return array(
            'siparis'        => $this->db->count_all('siparisler'),
            'bekleyen'       => $this->db->where('durum', 'onay_bekliyor')->count_all_results('siparisler'),
            'bayi'           => $this->db->where('durum', 1)->count_all_results('bayiler'),
            'bekleyen_bayi'  => $this->db->where('durum', 0)->count_all_results('bayiler'),
            'urun'           => $this->db->where('durum', 1)->count_all_results('urunler'),
            'ciro'           => $ciro ? (float) $ciro->c : 0.0,
        );
    }

    public function son_siparisler($n = 8)
    {
        return $this->db->select('s.id, s.siparis_no, s.durum, s.toplam, s.olusturma_zaman, b.firma_adi, b.yetkili_ad_soyad')
                        ->from('siparisler s')
                        ->join('bayiler b', 'b.id = s.bayi_id', 'left')
                        ->order_by('s.id', 'DESC')->limit((int) $n)
                        ->get()->result();
    }

    public function kritik_stok($esik = 15, $n = 10)
    {
        return $this->db->select('u.ad, v.renk, v.beden, v.stok')
                        ->from('urun_varyantlari v')
                        ->join('urunler u', 'u.id = v.urun_id', 'inner')
                        ->where('v.stok <=', (int) $esik)->where('v.durum', 1)
                        ->order_by('v.stok', 'ASC')->limit((int) $n)
                        ->get()->result();
    }

    public function bekleyen_bayiler($n = 5)
    {
        return $this->db->where('durum', 0)->order_by('id', 'DESC')->limit((int) $n)->get('bayiler')->result();
    }

    /**
     * Chart verileri (Query Builder).
     */
    /** Son N gunun siparis trendi (gun -> adet + tutar). */
    public function siparis_trendi($gun = 14)
    {
        if (! $this->db->table_exists('siparisler')) { return array(); }
        $basla = date('Y-m-d 00:00:00', strtotime('-' . ((int) $gun - 1) . ' days'));
        return $this->db->select("DATE(olusturma_zaman) AS gun, COUNT(*) AS adet, COALESCE(SUM(toplam),0) AS tutar")
                        ->where('olusturma_zaman >=', $basla)
                        ->group_by('gun')->order_by('gun', 'ASC')
                        ->get('siparisler')->result();
    }

    /** Siparis durumlarina gore dagilim (durum -> adet). */
    public function durum_dagilim()
    {
        if (! $this->db->table_exists('siparisler')) { return array(); }
        return $this->db->select('durum, COUNT(*) AS adet')
                        ->group_by('durum')->order_by('adet', 'DESC')
                        ->get('siparisler')->result();
    }

    /** En cok satan urunler (satis_adet'e gore, top N). */
    public function cok_satanlar($n = 6)
    {
        if (! $this->db->table_exists('urunler')) { return array(); }
        return $this->db->select('ad, satis_adet')
                        ->where('durum', 1)->where('deleted_at IS NULL', NULL, FALSE)
                        ->order_by('satis_adet', 'DESC')->limit((int) $n)
                        ->get('urunler')->result();
    }

    /** Kategori bazinda aktif urun adedi (kategori -> adet). */
    public function kategori_dagilim()
    {
        if (! $this->db->table_exists('urunler')) { return array(); }
        return $this->db->select('COALESCE(k.ad,"Kategorisiz") AS kategori, COUNT(u.id) AS adet')
                        ->from('urunler u')
                        ->join('kategoriler k', 'k.id = u.kategori_id', 'left')
                        ->where('u.durum', 1)->where('u.deleted_at IS NULL', NULL, FALSE)
                        ->group_by('k.id')->order_by('adet', 'DESC')
                        ->get()->result();
    }
}
