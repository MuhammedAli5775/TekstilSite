<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fatura_model — e-fatura/e-arşiv kayıtları (sağlayıcı-bağımsız kayıt katmanı).
 */
class Fatura_model extends CI_Model
{
    /** Yönetim: faturalar + sipariş/bayi join, filtre (durum, arama). */
    public function liste($f = array())
    {
        $this->db->select('f.*, s.siparis_no, b.firma_adi')
                 ->from('faturalar f')
                 ->join('siparisler s', 's.id = f.siparis_id', 'left')
                 ->join('bayiler b', 'b.id = f.bayi_id', 'left');
        if (! empty($f['durum'])) { $this->db->where('f.durum', $f['durum']); }
        if (! empty($f['q'])) {
            $this->db->group_start()
                     ->like('f.fatura_no', $f['q'])->or_like('f.etn', $f['q'])
                     ->or_like('s.siparis_no', $f['q'])->or_like('f.alici_unvan', $f['q'])
                     ->group_end();
        }
        return $this->db->order_by('f.id', 'DESC')->get()->result();
    }

    /** Bir siparişe ait faturalar (sipariş detayında gösterim). */
    public function siparis_faturalari($siparis_id)
    {
        return $this->db->where('siparis_id', (int) $siparis_id)
                        ->order_by('id', 'DESC')->get('faturalar')->result();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('faturalar')->row();
    }

    public function ekle($d)
    {
        $this->db->insert('faturalar', $d);
        return $this->db->insert_id();
    }

    public function guncelle($id, $d)
    {
        $this->db->where('id', (int) $id)->update('faturalar', $d);
    }

    public function sil($id)
    {
        $this->db->where('id', (int) $id)->delete('faturalar');
    }
}
