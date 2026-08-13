<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kupon_model — kupon/kampanya kodları (Query Builder).
 * dogrula(): checkout tarafında geçerlilik + indirim hesabı (TRY).
 */
class Kupon_model extends CI_Model
{
    /** Liste (arama: kod). */
    public function liste($q = '')
    {
        if (! $this->db->table_exists('kuponlar')) { return array(); }
        $q = trim((string) $q);
        if ($q !== '') { $this->db->like('kod', $q); }
        return $this->db->order_by('id', 'DESC')->get('kuponlar')->result();
    }

    public function getir($id)
    {
        return $this->db->where('id', (int) $id)->limit(1)->get('kuponlar')->row();
    }

    /** Koda göre aktif kupon (durum=1). */
    public function getir_kod($kod)
    {
        return $this->db->where('kod', trim((string) $kod))->where('durum', 1)->limit(1)->get('kuponlar')->row();
    }

    /**
     * Kupon doğrula + TRY indirimi hesapla.
     * @return array {ok, kupon, indirim(TRY), mesaj}
     */
    public function dogrula($kod, $ara_toplam_try)
    {
        if (! $this->db->table_exists('kuponlar')) {
            return array('ok' => FALSE, 'kupon' => NULL, 'indirim' => 0.0, 'mesaj' => 'Kupon devre dışı.');
        }
        $k = $this->getir_kod($kod);
        if (! $k) { return array('ok' => FALSE, 'kupon' => NULL, 'indirim' => 0.0, 'mesaj' => 'Kupon bulunamadı.'); }
        $simdi = date('Y-m-d H:i:s');
        if ($k->baslangic_zaman && $simdi < $k->baslangic_zaman) { return array('ok' => FALSE, 'kupon' => $k, 'indirim' => 0.0, 'mesaj' => 'Kupon henüz başlamadı.'); }
        if ($k->bitis_zaman && $simdi > $k->bitis_zaman) { return array('ok' => FALSE, 'kupon' => $k, 'indirim' => 0.0, 'mesaj' => 'Kupon süresi doldu.'); }
        if ($k->kullanim_limiti > 0 && (int) $k->kullanim_sayisi >= (int) $k->kullanim_limiti) { return array('ok' => FALSE, 'kupon' => $k, 'indirim' => 0.0, 'mesaj' => 'Kupon kullanım limiti doldu.'); }
        if ((float) $k->min_sepet_tutar > 0 && (float) $ara_toplam_try < (float) $k->min_sepet_tutar) { return array('ok' => FALSE, 'kupon' => $k, 'indirim' => 0.0, 'mesaj' => 'Sepet tutarı yetersiz (min. ' . para_tr((float) $k->min_sepet_tutar) . ').'); }

        $ind = ($k->tip === 'sabit') ? (float) $k->deger : (float) $ara_toplam_try * (float) $k->deger / 100;
        if ((float) $k->max_indirim > 0 && $ind > (float) $k->max_indirim) { $ind = (float) $k->max_indirim; }
        $ind = max(0.0, round($ind, 2));
        // Güvenlik: indirim asla sepet ara toplamını aşamaz — yokla yuzde>100 veya
        // sabit>subtotal kuponu (ör. 200 TL indirim, 150 TL sepet) negatif sipariş toplamı üretir.
        $ind = min($ind, max(0.0, (float) $ara_toplam_try));
        return array('ok' => TRUE, 'kupon' => $k, 'indirim' => $ind, 'mesaj' => '');
    }

    public function kaydet($d)
    {
        $d['kod'] = $this->_kod_temizle($d['kod'] ?? '');
        $this->db->insert('kuponlar', $d);
        return $this->db->insert_id();
    }

    public function guncelle($id, $d)
    {
        if (isset($d['kod'])) { $d['kod'] = $this->_kod_temizle($d['kod']); }
        $this->db->where('id', (int) $id)->update('kuponlar', $d);
    }

    public function sil($id)
    {
        $this->db->where('id', (int) $id)->delete('kuponlar');
    }

    /** Kullanım sayacını artır (sipariş başarıyla oluşunca). */
    public function kullan_artir($kod)
    {
        $this->db->where('kod', trim((string) $kod))->set('kullanim_sayisi', 'kullanim_sayisi + 1', FALSE)->update('kuponlar');
    }

    private function _kod_temizle($kod)
    {
        $kod = strtoupper(trim((string) $kod));
        return preg_replace('/[^A-Z0-9_-]/', '', $kod);
    }
}
