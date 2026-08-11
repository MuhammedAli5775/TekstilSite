<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stok (yönetim) — varyant stok listesi + hareket geçmişi + manuel düzeltme.
 * Ürün katalog tanımı Urunler'de; Stok envanter GÖRÜNÜRLÜĞÜ + operasyon (düzeltme/hareket).
 */
class Stok extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('stok_model');
    }

    /** Varyant stok listesi (arama + kritik/sıfır filtresi + sayfalama). */
    public function index()
    {
        $this->yetki_gerek('stok', 'goruntule');
        $limit  = 50;
        $sayfa  = max(1, (int) $this->input->get('sayfa'));
        $q      = (string) $this->input->get('q');
        $filtre = (string) $this->input->get('filtre');
        if (! in_array($filtre, array('all', 'kritik', 'sifir'), TRUE)) { $filtre = 'all'; }
        $toplam = $this->stok_model->liste_say($q, $filtre);

        $data['sayfa_basligi'] = 'Stok Yönetimi';
        $data['menu_aktif']    = 'stok';
        $data['varyantlar']    = $this->stok_model->liste($q, $filtre, $limit, ($sayfa - 1) * $limit);
        $data['q']             = $q;
        $data['filtre']        = $filtre;
        $data['sayfa']         = $sayfa;
        $data['sayfa_sayisi']  = max(1, (int) ceil($toplam / $limit));
        $this->render('yonetim/stok/index', $data);
    }

    /** Stok hareket geçmişi (tip + arama filtresi + sayfalama). */
    public function hareketler()
    {
        $this->yetki_gerek('stok', 'goruntule');
        $limit = 50;
        $sayfa = max(1, (int) $this->input->get('sayfa'));
        $tip   = (string) $this->input->get('tip');
        $q     = (string) $this->input->get('q');
        if (! in_array($tip, array('', 'giris', 'cikis', 'satis', 'iade', 'duzeltme'), TRUE)) { $tip = ''; }
        $toplam = $this->stok_model->hareketler_say($tip, $q);

        $data['sayfa_basligi'] = 'Stok Hareketleri';
        $data['menu_aktif']    = 'stok';
        $data['hareketler']    = $this->stok_model->hareketler($tip, $q, $limit, ($sayfa - 1) * $limit);
        $data['tip']           = $tip;
        $data['q']             = $q;
        $data['sayfa']         = $sayfa;
        $data['sayfa_sayisi']  = max(1, (int) ceil($toplam / $limit));
        $this->render('yonetim/stok/hareketler', $data);
    }

    /** Manuel stok düzeltme (POST). PRG: işle → listeye yönlendir. */
    public function duzeltle($varyant_id = NULL)
    {
        $this->yetki_gerek('stok', 'duzenle');
        $varyant_id = (int) $varyant_id;
        if (! $varyant_id) { redirect('yonetim/stok'); }
        $yeni  = (int) $this->input->post('yeni_stok');
        $sebep = (string) $this->input->post('sebep');
        if ($yeni < 0) { $yeni = 0; }
        $ok = $this->stok_model->duzelt($varyant_id, $yeni, $sebep);
        if ($ok) {
            $this->auth_admin->audit('stok', 'duzeltme', '#' . $varyant_id, 'stok=' . $yeni . ($sebep !== '' ? ' · ' . $sebep : ''));
            $this->session->set_flashdata('bilgi', 'Stok güncellendi.');
        } else {
            $this->session->set_flashdata('hata', 'Stok güncellenemedi (varyant bulunamadı).');
        }
        redirect('yonetim/stok' . ($this->input->get('q') || $this->input->get('filtre') ? '?' . http_build_query(array_filter(array('q' => $this->input->get('q'), 'filtre' => $this->input->get('filtre')))) : ''));
    }
}
