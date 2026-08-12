<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard_model — yönetim paneli istatistikleri (Query Builder).
 */
class Dashboard_model extends CI_Model
{
    /** Ozet KPI'lar. Siparis/bekleyen/ciro donem araligina gore; bayi/urun anlik durum. */
    public function ozet($basla = NULL, $bitir = NULL)
    {
        if (! $this->db->table_exists('siparisler')) {
            return array('siparis' => 0, 'bekleyen' => 0, 'bayi' => 0, 'bekleyen_bayi' => 0, 'urun' => 0, 'ciro' => 0.0);
        }
        $this->db->select('COALESCE(SUM(toplam * kur),0) AS c', FALSE)
                 ->where_in('durum', array('onaylandi', 'hazirlaniyor', 'kargolandi', 'teslim_edildi'));
        $this->_aralik($basla, $bitir);
        $ciro = $this->db->get('siparisler')->row();

        $this->_aralik($basla, $bitir);
        $siparis = $this->db->count_all_results('siparisler');

        $this->db->where('durum', 'onay_bekliyor');
        $this->_aralik($basla, $bitir);
        $bekleyen = $this->db->count_all_results('siparisler');

        return array(
            'siparis'        => $siparis,
            'bekleyen'       => $bekleyen,
            'bayi'           => $this->db->where('durum', 1)->count_all_results('bayiler'),
            'bekleyen_bayi'  => $this->db->where('durum', 0)->count_all_results('bayiler'),
            'urun'           => $this->db->where('durum', 1)->count_all_results('urunler'),
            'ciro'           => $ciro ? (float) $ciro->c : 0.0,
        );
    }

    /** Tarih araligi where kousulu. $alan kolon adi (join'li sorguda 's.olusturma_zaman'). */
    private function _aralik($basla, $bitir, $alan = 'olusturma_zaman')
    {
        if ($basla) { $this->db->where($alan . ' >=', $basla); }
        if ($bitir) { $this->db->where($alan . ' <=', $bitir); }
    }

    public function son_siparisler($n = 8, $basla = NULL, $bitir = NULL)
    {
        $this->_aralik($basla, $bitir, 's.olusturma_zaman');
        return $this->db->select('s.id, s.siparis_no, s.durum, s.toplam, s.para_birimi, s.olusturma_zaman, b.firma_adi, b.yetkili_ad_soyad')
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
    /**
     * Siparis trendi (etiket + adet + tutar). Granul: hour/day/month.
     * Bugun->saatlik, hafta/ay->gunluk, yil/tumu->aylik. 'etiket' grafikte kullanilir.
     */
    public function siparis_trendi($basla = NULL, $bitir = NULL, $granul = 'day')
    {
        if (! $this->db->table_exists('siparisler')) { return array(); }
        if ($basla) { $this->db->where('olusturma_zaman >=', $basla); }
        if ($bitir) { $this->db->where('olusturma_zaman <=', $bitir); }
        if ($granul === 'hour') {
            $sel = "HOUR(olusturma_zaman) AS birim";
        } elseif ($granul === 'month') {
            $sel = "DATE_FORMAT(olusturma_zaman,'%Y-%m') AS birim";
        } else {
            $sel = "DATE(olusturma_zaman) AS birim";
        }
        $rows = $this->db->select($sel . ", COUNT(*) AS adet, COALESCE(SUM(toplam * kur),0) AS tutar", FALSE)
                         ->group_by('birim')->order_by('birim', 'ASC')
                         ->get('siparisler')->result();
        foreach ($rows as $r) {
            if ($granul === 'hour') {
                $r->etiket = sprintf('%02d:00', (int) $r->birim);
            } elseif ($granul === 'month') {
                $r->etiket = date('M Y', strtotime((string) $r->birim . '-01'));
            } else {
                $r->etiket = date('d.m', strtotime((string) $r->birim));
            }
        }
        return $rows;
    }

    /** Siparis durumlarina gore dagilim (durum -> adet). */
    public function durum_dagilim($basla = NULL, $bitir = NULL)
    {
        if (! $this->db->table_exists('siparisler')) { return array(); }
        $this->_aralik($basla, $bitir);
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
