<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stok_model — varyant stok yönetimi + hareket geçmişi (Query Builder).
 * Stok listesi (aktif ürün varyantları), hareketler (stok_hareketleri), manuel düzeltme
 * (transaction: onceki_stok kaydı + varyant stok güncelle + hareket yaz).
 */
class Stok_model extends CI_Model
{
    /** Varyant stok listesi (aktif ürünler, soft-delete hariç). */
    public function liste($q = '', $filtre = 'all', $limit = 50, $offset = 0)
    {
        $this->_liste_filtre($q, $filtre);
        return $this->db->select('v.id, v.urun_id, u.ad, v.renk, v.beden, v.stok, v.kritik_stok, v.sku, v.durum')
                        ->from('urun_varyantlari v')->join('urunler u', 'u.id = v.urun_id', 'inner')
                        ->where('u.durum', 1)->where('u.deleted_at IS NULL', NULL, FALSE)
                        ->order_by('v.stok', 'ASC')->limit((int) $limit)->offset((int) $offset)
                        ->get()->result();
    }

    public function liste_say($q = '', $filtre = 'all')
    {
        $this->_liste_filtre($q, $filtre);
        return $this->db->from('urun_varyantlari v')->join('urunler u', 'u.id = v.urun_id', 'inner')
                        ->where('u.durum', 1)->where('u.deleted_at IS NULL', NULL, FALSE)
                        ->count_all_results();
    }

    private function _liste_filtre($q, $filtre)
    {
        $q = trim((string) $q);
        if ($q !== '') {
            $this->db->group_start()
                     ->like('u.ad', $q)->or_like('v.sku', $q)
                     ->or_like('v.renk', $q)->or_like('v.beden', $q)
                     ->group_end();
        }
        if ($filtre === 'kritik') {
            $this->db->where('v.kritik_stok >', 0)->where('v.stok <= v.kritik_stok', NULL, FALSE);
        } elseif ($filtre === 'sifir') {
            $this->db->where('v.stok <=', 0);
        }
    }

    /** Tek varyant (düzenleme formu için). */
    public function varyant_getir($id)
    {
        return $this->db->select('v.id, v.urun_id, u.ad, v.renk, v.beden, v.stok, v.kritik_stok, v.sku')
                        ->from('urun_varyantlari v')->join('urunler u', 'u.id = v.urun_id', 'inner')
                        ->where('v.id', (int) $id)->limit(1)->get()->row();
    }

    /** Stok hareketleri (filtre: tip + urun adı/aranan). */
    public function hareketler($tip = '', $q = '', $limit = 50, $offset = 0)
    {
        $this->_hareket_filtre($tip, $q);
        return $this->db->select('h.id, h.tip, h.adet, h.onceki_stok, h.aciklama, h.olusturma_zaman,
                                  h.siparis_id, u.ad AS urun_adi, v.renk, v.beden')
                        ->from('stok_hareketleri h')
                        ->join('urunler u', 'u.id = h.urun_id', 'left')
                        ->join('urun_varyantlari v', 'v.id = h.varyant_id', 'left')
                        ->order_by('h.id', 'DESC')->limit((int) $limit)->offset((int) $offset)
                        ->get()->result();
    }

    public function hareketler_say($tip = '', $q = '')
    {
        $this->_hareket_filtre($tip, $q);
        return $this->db->from('stok_hareketleri h')
                        ->join('urunler u', 'u.id = h.urun_id', 'left')
                        ->count_all_results();
    }

    private function _hareket_filtre($tip, $q)
    {
        $tipler = array('giris', 'cikis', 'satis', 'iade', 'duzeltme');
        if ($tip && in_array($tip, $tipler, TRUE)) { $this->db->where('h.tip', $tip); }
        $q = trim((string) $q);
        if ($q !== '') {
            $this->db->group_start()
                     ->like('u.ad', $q)->or_like('h.aciklama', $q)
                     ->group_end();
        }
    }

    /** Manuel stok düzeltme (transaction). Yeni mutlak stok + sebep → hareket tip=duzeltme. */
    public function duzelt($varyant_id, $yeni_stok, $sebep)
    {
        $varyant_id = (int) $varyant_id;
        $yeni_stok = (int) $yeni_stok;
        $v = $this->db->select('stok, urun_id')->where('id', $varyant_id)->limit(1)->get('urun_varyantlari')->row();
        if (! $v) { return FALSE; }

        $this->db->trans_begin();
        $this->db->where('id', $varyant_id)->update('urun_varyantlari', array('stok' => $yeni_stok));
        $this->db->insert('stok_hareketleri', array(
            'urun_id'      => (int) $v->urun_id,
            'varyant_id'   => $varyant_id,
            'tip'          => 'duzeltme',
            'adet'         => $yeni_stok - (int) $v->stok,   // imzalı fark (negatif olabilir)
            'onceki_stok'  => (int) $v->stok,
            'aciklama'     => mb_substr(trim((string) $sebep), 0, 255),
            'siparis_id'   => NULL,
        ));
        if ($this->db->trans_status() === FALSE) { $this->db->trans_rollback(); return FALSE; }
        $this->db->trans_commit();
        return TRUE;
    }
}
