<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ebulten — e-bülten aboneleri (LV). Liste + CSV dışa aktarım.
 * Erişim: raporlar izni (pazarlama raporu yüzeyi — ayrı yetki modülü seed'i gerekmez;
 * menüde de ebulten anahtarı raporlar iznine eşlenir, MY_Controller).
 */
class Ebulten extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('raporlar', 'goruntule');
    }

    public function index()
    {
        $data['sayfa_basligi'] = 'E-Bülten Aboneleri';
        $data['menu_aktif']    = 'ebulten';
        $data['toplam']        = (int) $this->db->where('durum', 1)->count_all_results('ebulten_aboneler');
        $data['aboneler']      = $this->db->order_by('id', 'DESC')->limit(500)->get('ebulten_aboneler')->result();
        $this->render('yonetim/ebulten/index', $data);
    }

    /** CSV dışa aktarım (UTF-8 BOM + ';' — Raporlar deseni). */
    public function csv()
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ebulten_aboneler.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF");   // UTF-8 BOM (Excel)
        fputcsv($out, array('E-posta', 'Dil', 'Durum', 'IP', 'Kayit Tarihi'), ';');
        foreach ($this->db->order_by('id', 'ASC')->get('ebulten_aboneler')->result() as $a) {
            fputcsv($out, array($a->eposta, $a->dil, ((int) $a->durum === 1 ? 'aktif' : 'pasif'), (string) $a->kayit_ip, (string) $a->created_at), ';');
        }
        fclose($out);
    }
}
