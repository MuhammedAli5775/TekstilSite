<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rapor_model — yönetim raporları (Query Builder).
 * Tüm raporlar tarih aralığına (bas/son: Y-m-d) ve "brüt satış" kuralına göredir:
 * iptal/iade_edildi DIŞLANIR (onay_bekliyor dahil — bekleyen satışlar sayılır).
 * Not: CI3 get()/count_all_results() Query Builder where'lerini sıfırlar →
 * _aralik() her sorgudan ÖNCE yeniden çağrılır.
 */
class Rapor_model extends CI_Model
{
    private function _aralik($bas, $son)
    {
        if ($bas) { $this->db->where('s.olusturma_zaman >=', $bas . ' 00:00:00'); }
        if ($son) { $this->db->where('s.olusturma_zaman <=', $son . ' 23:59:59'); }
    }

    /** Satış özeti: toplam sipariş + durum dağılımı + brüt ciro + kargo + indirim + AOV. */
    public function satis_ozet($bas, $son)
    {
        $out = array('toplam' => 0, 'brut_siparis' => 0, 'durumlar' => array(), 'ciro' => 0.0, 'kargo' => 0.0, 'indirim' => 0.0, 'aov' => 0.0);
        if (! $this->db->table_exists('siparisler')) { return $out; }

        $this->_aralik($bas, $son);
        $out['toplam'] = $this->db->count_all_results('siparisler s');

        $this->_aralik($bas, $son);
        foreach ($this->db->select('s.durum, COUNT(*) AS n')->group_by('s.durum')->get('siparisler s')->result() as $r) {
            $out['durumlar'][$r->durum] = (int) $r->n;
        }

        $this->_aralik($bas, $son);
        $row = $this->db->select('COALESCE(SUM(s.toplam * s.kur),0) AS ciro, COALESCE(SUM(s.kargo_ucreti * s.kur),0) AS kargo, COALESCE(SUM(s.indirim * s.kur),0) AS indirim, COUNT(*) AS n', FALSE)
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->get('siparisler s')->row();
        if ($row) {
            $out['ciro']   = (float) $row->ciro;
            $out['kargo']  = (float) $row->kargo;
            $out['indirim']= (float) $row->indirim;
            $out['brut_siparis'] = (int) $row->n;
            $out['aov']    = $row->n > 0 ? round($out['ciro'] / $row->n, 2) : 0.0;
        }
        return $out;
    }

    /** Ürün satışı (detaylar üzerinden): adet + ciro + kaç siparişte. */
    public function urun_satis($bas, $son)
    {
        $this->_aralik($bas, $son);
        return $this->db->select('d.urun_adi, SUM(d.adet) AS adet, SUM(d.ara_toplam * s.kur) AS ciro, COUNT(DISTINCT s.id) AS siparis', FALSE)
                        ->from('siparis_detaylari d')->join('siparisler s', 's.id = d.siparis_id')
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by(array('d.urun_id', 'd.urun_adi'))->order_by('ciro', 'DESC')
                        ->get()->result();
    }

    /** Kategori satışı: adet + ciro. */
    public function kategori_satis($bas, $son)
    {
        $this->_aralik($bas, $son);
        return $this->db->select('COALESCE(k.ad, "Kategorisiz") AS ad, SUM(d.adet) AS adet, SUM(d.ara_toplam * s.kur) AS ciro', FALSE)
                        ->from('siparis_detaylari d')
                        ->join('siparisler s', 's.id = d.siparis_id')
                        ->join('urunler u', 'u.id = d.urun_id', 'left')
                        ->join('kategoriler k', 'k.id = u.kategori_id', 'left')
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by('k.id')->order_by('ciro', 'DESC')
                        ->get()->result();
    }

    /** Bayi satışı: sipariş sayısı + ciro. */
    public function bayi_satis($bas, $son)
    {
        $this->_aralik($bas, $son);
        return $this->db->select('COALESCE(b.firma_adi, "Misafir") AS bayi, b.email, COUNT(s.id) AS siparis, SUM(s.toplam * s.kur) AS ciro', FALSE)
                        ->from('siparisler s')->join('bayiler b', 'b.id = s.bayi_id', 'left')
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by('s.bayi_id')->order_by('ciro', 'DESC')
                        ->get()->result();
    }

    /** Bölge satışı: il veya ilçe bazında. */
    public function bolge_satis($bas, $son, $alan = 'teslimat_il')
    {
        $alan = ($alan === 'teslimat_ilce') ? 's.teslimat_ilce' : 's.teslimat_il';
        $this->_aralik($bas, $son);
        return $this->db->select('COALESCE(' . $alan . ', "Belirtilmemiş") AS bolge, COUNT(*) AS siparis, SUM(s.toplam * s.kur) AS ciro', FALSE)
                        ->from('siparisler s')
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by($alan)->order_by('ciro', 'DESC')
                        ->get()->result();
    }

    /** Ödeme yöntemi satışı: count + ciro. */
    public function odeme_satis($bas, $son)
    {
        $this->_aralik($bas, $son);
        return $this->db->select('COALESCE(s.odeme_yontemi, "Belirtilmemiş") AS yontem, COUNT(*) AS siparis, SUM(s.toplam * s.kur) AS ciro', FALSE)
                        ->from('siparisler s')
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by('s.odeme_yontemi')->order_by('ciro', 'DESC')
                        ->get()->result();
    }

    /** Günlük trend (XXXVIII): gün bazında sipariş/adet/ciro. */
    public function gunluk_satis($bas, $son)
    {
        $this->_aralik($bas, $son);
        return $this->db->select('DATE(s.olusturma_zaman) AS gun, COUNT(DISTINCT s.id) AS siparis, COALESCE(SUM(d.adet),0) AS adet, COALESCE(SUM(s.toplam * s.kur),0) AS ciro', FALSE)
                        ->from('siparisler s')
                        ->join('siparis_detaylari d', 'd.siparis_id = s.id', 'left')
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by('DATE(s.olusturma_zaman)')->order_by('gun', 'ASC')
                        ->get()->result();
    }

    /** Kupon kullanımı (XXXVIII): kod bazında sipariş/indirim/ciro (atıflama siparisler.kupon_kod). */
    public function kupon_kullanim($bas, $son)
    {
        $this->_aralik($bas, $son);
        return $this->db->select('s.kupon_kod AS kod, COUNT(*) AS siparis, COALESCE(SUM(s.indirim * s.kur),0) AS indirim, COALESCE(SUM(s.toplam * s.kur),0) AS ciro', FALSE)
                        ->from('siparisler s')
                        ->where('s.kupon_kod IS NOT NULL', NULL, FALSE)
                        ->where_not_in('s.durum', array('iptal', 'iade_edildi'))
                        ->group_by('s.kupon_kod')->order_by('ciro', 'DESC')
                        ->get()->result();
    }
}
