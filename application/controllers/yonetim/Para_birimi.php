<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Para_birimi (yönetim) — para birimi + kur (kur_try: 1 birim = N TRY) yönetimi.
 * TRY daima kur_try=1 (temel). Sipariş anlık kopyası bu kuru kullanır.
 */
class Para_birimi extends Admin_Controller
{
    public function index()
    {
        $data = array(
            'sayfa_basligi' => 'Para Birimleri & Kurlar',
            'menu_aktif'    => 'para_birimi',
            'birimler'      => $this->db->order_by('sira', 'ASC')->get('para_birimleri')->result(),
        );
        $this->render('yonetim/para_birimi/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('ayarlar', 'duzenle');
        $kodlar = (array) $this->input->post('kod');
        $adlar  = (array) $this->input->post('ad');
        $sembol = (array) $this->input->post('sembol');
        $kur    = (array) $this->input->post('kur_try');
        $durum  = (array) $this->input->post('durum');
        $sira   = (array) $this->input->post('sira');

        foreach ($kodlar as $i => $kod) {
            $k = strtoupper(trim((string) $kod));
            if ($k === '') { continue; }
            $kurval = ($k === 'TRY') ? 1.0 : max(0.0001, (float) ($kur[$i] ?? 1));
            $row = array(
                'ad'      => trim((string) ($adlar[$i] ?? $k)),
                'sembol'  => trim((string) ($sembol[$i] ?? $k)),
                'kur_try' => $kurval,
                'durum'   => isset($durum[$i]) ? 1 : 0,
                'sira'    => (int) ($sira[$i] ?? 0),
            );
            $exists = $this->db->where('kod', $k)->count_all_results('para_birimleri');
            if ($exists) { $this->db->where('kod', $k)->update('para_birimleri', $row); }
            else { $row['kod'] = $k; $this->db->insert('para_birimleri', $row); }
        }
        $this->auth_admin->audit('ayarlar', 'para_birimi', '', 'Kurlar güncellendi');
        $this->session->set_flashdata('bilgi', 'Para birimleri güncellendi.');
        redirect('yonetim/para_birimi');
    }

    public function sil($kod = NULL)
    {
        $this->yetki_gerek('ayarlar', 'sil');
        $kod = strtoupper(trim((string) $kod));
        if ($kod !== 'TRY') { $this->db->where('kod', $kod)->delete('para_birimleri'); }
        redirect('yonetim/para_birimi');
    }
}
