<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_admin — yönetici girişi (bcrypt + brute-force kilidi) + rol/yetki + audit log.
 */
class Auth_admin
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Giriş denemesi. */
    public function giris_yap($email, $sifre)
    {
        // brute-force kilidi
        $kilit = (int) $this->CI->session->userdata('adm_kilit');
        if ($kilit && time() < $kilit) {
            $kalan = max(1, (int) ceil(($kilit - time()) / 60));
            return array('ok' => FALSE, 'mesaj' => 'Çok fazla başarısız deneme. Lütfen ' . $kalan . ' dk sonra tekrar deneyin.');
        }

        $y = $this->CI->yonetici_model->by_email($email);
        if (! $y || ! password_verify($sifre, $y->sifre)) {
            $deneme = (int) $this->CI->session->userdata('adm_deneme') + 1;
            $this->CI->session->set_userdata('adm_deneme', $deneme);
            if ($deneme >= 5) {
                $this->CI->session->set_userdata('adm_kilit', time() + 900);
                $this->CI->session->unset_userdata('adm_deneme');
            }
            return array('ok' => FALSE, 'mesaj' => 'E-posta veya şifre hatalı.');
        }
        if ((int) $y->durum !== 1) {
            return array('ok' => FALSE, 'mesaj' => 'Hesabınız pasif. Yöneticinize başvurun.');
        }

        $this->CI->session->set_userdata(array('yonetici_id' => (int) $y->id, 'rol_id' => (int) $y->rol_id));
        $this->CI->session->unset_userdata(array('adm_deneme', 'adm_kilit'));
        $this->CI->yonetici_model->son_giris($y->id);
        $this->audit('auth', 'giris', '', $y->email . ' giriş yaptı');
        return array('ok' => TRUE, 'yonetici' => $y);
    }

    public function cikis()
    {
        $this->audit('auth', 'cikis');
        $this->CI->session->unset_userdata(array('yonetici_id', 'rol_id'));
    }

    public function logged_in()
    {
        return (bool) $this->CI->session->userdata('yonetici_id');
    }

    public function yonetici()
    {
        $id = (int) $this->CI->session->userdata('yonetici_id');
        return $id ? $this->CI->yonetici_model->get($id) : NULL;
    }

    public function rol_id()
    {
        return (int) $this->CI->session->userdata('rol_id');
    }

    /** Yetki kontrolü. Süper admin (rol 1) her şeyi yapar; diğerleri için şimdilik genel erişim (Faz 5'te matris). */
    public function yetki($modul, $islem = 'goruntule')
    {
        if (! $this->logged_in()) { return FALSE; }
        if ($this->rol_id() === 1) { return TRUE; }
        return TRUE; // rol 2 (yönetici): şimdilik genel erişim
    }

    public function audit($modul, $islem, $hedef = '', $aciklama = '')
    {
        $y = $this->yonetici();
        $this->CI->yonetici_model->audit_log(array(
            'yonetici_id' => $y ? $y->id : NULL,
            'modul'       => $modul,
            'islem'       => $islem,
            'hedef'       => $hedef,
            'aciklama'    => $aciklama,
            'ip'          => $this->CI->input->ip_address(),
        ));
    }
}
