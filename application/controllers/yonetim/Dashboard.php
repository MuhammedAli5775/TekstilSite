<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin_Controller
{
    public function index()
    {
        $this->load->model('dashboard_model');
        $data['sayfa_basligi']    = 'Dashboard';
        $data['menu_aktif']       = 'dashboard';
        $data['ozet']             = $this->dashboard_model->ozet();
        $data['son_siparisler']   = $this->dashboard_model->son_siparisler(8);
        $data['kritik_stok']      = $this->dashboard_model->kritik_stok(15, 10);
        $data['bekleyen_bayiler'] = $this->dashboard_model->bekleyen_bayiler(5);
        // Chart verileri
        $data['trend']            = $this->dashboard_model->siparis_trendi(14);
        $data['durum']            = $this->dashboard_model->durum_dagilim();
        $data['cok_satan']        = $this->dashboard_model->cok_satanlar(6);
        $data['kategori']         = $this->dashboard_model->kategori_dagilim();
        $this->render('yonetim/dashboard/index', $data);
    }
}
