<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin_Controller
{
    public function index()
    {
        $this->load->model('dashboard_model');
        $aralik    = $this->_donem_aralik($this->input->get('donem'));

        $data['sayfa_basligi']    = 'Dashboard';
        $data['menu_aktif']       = 'dashboard';
        $data['donem_kod']        = $aralik['kod'];
        $data['donem']            = $aralik['etiket'];
        $data['ozet']             = $this->dashboard_model->ozet($aralik['basla'], $aralik['bitir']);
        $data['son_siparisler']   = $this->dashboard_model->son_siparisler(8, $aralik['basla'], $aralik['bitir']);
        $data['kritik_stok']      = $this->dashboard_model->kritik_stok(15, 10);
        $data['bekleyen_bayiler'] = $this->dashboard_model->bekleyen_bayiler(5);
        // Chart verileri (siparis-tabanlilar doneme gore filtrelenir)
        $data['trend']            = $this->dashboard_model->siparis_trendi($aralik['basla'], $aralik['bitir'], $aralik['granul']);
        $data['durum']            = $this->dashboard_model->durum_dagilim($aralik['basla'], $aralik['bitir']);
        $data['cok_satan']        = $this->dashboard_model->cok_satanlar(6);
        $data['kategori']         = $this->dashboard_model->kategori_dagilim();
        $this->render('yonetim/dashboard/index', $data);
    }

    /**
     * Donem kodu (bugun|hafta|ay|yil|tumu) -> [kod, basla, bitir, granul, etiket].
     * basla/bitir olusturma_zaman araligi; tumu = filtre yok. granul trend grafigi icin
     * (bugun->saatlik, hafta/ay->gunluk, yil/tumu->aylik).
     */
    private function _donem_aralik($kod)
    {
        $kod = in_array($kod, array('bugun', 'hafta', 'ay', 'yil', 'tumu'), TRUE) ? $kod : 'ay';
        $bitir = date('Y-m-d 23:59:59');
        switch ($kod) {
            case 'bugun':
                $basla = date('Y-m-d 00:00:00');                                     $granul = 'hour';   $etiket = 'Bugün';     break;
            case 'hafta':
                $basla = date('Y-m-d 00:00:00', strtotime('monday this week'));      $granul = 'day';    $etiket = 'Bu Hafta';  break;
            case 'ay':
                $basla = date('Y-m-01 00:00:00');                                    $granul = 'day';    $etiket = 'Bu Ay';     break;
            case 'yil':
                $basla = date('Y-01-01 00:00:00');                                   $granul = 'month';  $etiket = 'Bu Yıl';    break;
            default: // tumu
                $basla = NULL; $bitir = NULL;                                        $granul = 'month';  $etiket = 'Tümü';      break;
        }
        return array('kod' => $kod, 'basla' => $basla, 'bitir' => $bitir, 'granul' => $granul, 'etiket' => $etiket);
    }
}
