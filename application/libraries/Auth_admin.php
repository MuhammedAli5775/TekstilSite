<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_admin — yönetici girişi (bcrypt + brute-force kilidi) + rol/yetki + audit log.
 */
class Auth_admin
{
    protected $CI;
    protected $_yetki_cache = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** Giriş denemesi (dogrula + oturum aç — TOTP'siz hesaplar için kısayol). */
    public function giris_yap($email, $sifre)
    {
        $res = $this->dogrula($email, $sifre);
        if (! $res['ok']) { return $res; }
        $this->oturum_ac($res['yonetici']);
        return array('ok' => TRUE, 'yonetici' => $res['yonetici']);
    }

    /**
     * Kimlik doğrula — oturum AÇMAZ (LXIII).
     * TOTP'li hesaplarda paroladan sonra kod adımı gelir; yonetici_id yalnız
     * o adımın sonunda (oturum_ac) yazılır — ara durumda hesaba erişim yok.
     */
    public function dogrula($email, $sifre)
    {
        // brute-force kilidi
        $kilit = (int) $this->CI->session->userdata('adm_kilit');
        if ($kilit && time() < $kilt) {
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
        return array('ok' => TRUE, 'yonetici' => $y);
    }

    /** Doğrulanmış yönetici için oturumu aç (dogrula veya TOTP adımı sonrası). */
    public function oturum_ac($y)
    {
        $this->CI->session->sess_regenerate(); // oturum sabitleme koruması: yetki değişince ID döner
        $this->CI->session->set_userdata(array('yonetici_id' => (int) $y->id, 'rol_id' => (int) $y->rol_id));
        $this->CI->session->unset_userdata(array('adm_deneme', 'adm_kilit', 'totp_bekliyor', 'totp_deneme', 'totp_donus', 'totp_aday'));
        $this->CI->yonetici_model->son_giris($y->id);
        $this->audit('auth', 'giris', '', $y->email . ' giriş yaptı');
    }

    public function cikis()
    {
        $this->audit('auth', 'cikis');
        $this->CI->session->unset_userdata(array('yonetici_id', 'rol_id', 'totp_bekliyor', 'totp_deneme', 'totp_donus', 'totp_aday'));
        $this->CI->session->sess_regenerate();
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

    /**
     * Yetki kontrolü. Süper admin (rol 1) her şeyi yapar. Diğer roller yetkiler
     * tablosundan (rol_id × modul × {goruntule,duzenle,sil}) kontrol edilir.
     * İstek başına tek sorgu, önbellekten.
     */
    public function yetki($modul, $islem = 'goruntule')
    {
        if (! $this->logged_in()) { return FALSE; }
        if ($this->rol_id() === 1) { return TRUE; }            // süper = daima tam
        if (! in_array($islem, array('goruntule', 'duzenle', 'sil'), TRUE)) { return FALSE; }
        if ($this->_yetki_cache === NULL) { $this->_yetki_cache = $this->_yukle(); }
        $satir = $this->_yetki_cache[$modul] ?? NULL;
        if (! $satir) { return FALSE; }
        return (int) ($satir[$islem] ?? 0) === 1;
    }

    /** Mevcut rolün yetkilerini yükle: modul => [goruntule,duzenle,sil]. */
    private function _yukle()
    {
        $rol = $this->rol_id();
        if (! $rol) { return array(); }
        if (! $this->CI->db->table_exists('yetkiler')) { return array(); }
        $rows = $this->CI->db->where('rol_id', $rol)->get('yetkiler')->result();
        $out = array();
        foreach ($rows as $r) {
            $out[$r->modul] = array('goruntule' => $r->goruntule, 'duzenle' => $r->duzenle, 'sil' => $r->sil);
        }
        return $out;
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
