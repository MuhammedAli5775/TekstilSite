<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Yetkiler (yönetim) — rol bazlı yetki matrisi. SADECE süper admin (rol 1).
 * Süper (rol 1) matriste yer almaz (daima tam yetkili, kod içinde sabit).
 */
class Yetkiler extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('yetki_model');
        // Sadece süper yönetici erişebilir.
        if (! $this->auth_admin->logged_in() || $this->auth_admin->rol_id() !== 1) {
            show_error('Bu sayfa yalnızca süper yönetici içindir.', 403);
        }
    }

    /** Matris: rol seç (?rol=, varsayılan 2) + modül × {görüntüle/düzenle/sil}. */
    public function index()
    {
        $rol = (int) $this->input->get('rol');
        if ($rol < 2) { $rol = 2; }   // rol 1 (süper) matriste yok
        $data = array(
            'sayfa_basligi' => 'Yetki Matrisi',
            'menu_aktif'    => 'yetkiler',
            'rol'           => $rol,
            'roller'        => $this->db->where('id !=', 1)->order_by('id', 'ASC')->get('roller')->result(),
            'matris'        => $this->yetki_model->liste($rol),
        );
        $this->render('yonetim/yetkiler/index', $data);
    }

    /** Matris kaydet (POST: rol + grid). */
    public function kaydet()
    {
        $rol = (int) $this->input->post('rol');
        if ($rol < 2) { redirect('yonetim/yetkiler'); }
        $this->yetki_model->kaydet($rol, (array) $this->input->post('grid'));
        $this->auth_admin->audit('yetkiler', 'guncelle', 'rol#' . $rol, '');
        $this->session->set_flashdata('bilgi', 'Yetki matrisi kaydedildi (rol #' . $rol . ').');
        redirect('yonetim/yetkiler?rol=' . $rol);
    }
}
