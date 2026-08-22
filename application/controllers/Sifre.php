<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sifre — şifremi unuttum akışı (LIX; SMTP öncesi iskelet, graceful).
 *
 * İki uç: sifremi-unuttum/{kullanici|bayi} → token üret (+ SMTP varsa posta);
 * sifre-yenile/{tip}/{token} → yeni şifre. Anti-enumeration: e-posta var
 * olsun olmasın AYNI mesaj dönülür (hesap keşfi engeli). Token tek kullanımlık,
 * 30 dk geçerli. SMTP yoksa e-posta atlanır (log'a düşer) — akış bozulmaz;
 * kullanıcı yönetici şifre sıfılatana kadar LVII panelleriyle çare bulur.
 */
class Sifre extends Magaza_Controller
{
    const GECERLILIK = 1800;   // saniye (30 dk)

    public function unuttum($tip = 'kullanici')
    {
        $tip = $this->_tip($tip);
        $giris = ($tip === 'bayi') ? 'bayi/giris' : 'kullanici/giris';

        if (strtolower((string) $this->input->method()) !== 'post') {
            $this->v['meta_title']     = t('sifre_unuttum_baslik', 'Şifre Sıfırlama') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
            $this->v['indexlenebilir'] = FALSE;
            $this->v['s_tip']          = $tip;
            $this->render('magaza/sifre/unuttum');
            return;
        }

        $eposta = strtolower(trim((string) $this->input->post('eposta')));
        if (! filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('hata', t('flash_ebulten_gecersiz', 'Geçerli bir e-posta adresi girin.'));
            redirect('sifremi-unuttum/' . $tip);
        }

        $tablo = ($tip === 'bayi') ? 'bayiler' : 'kullanicilar';
        $hesap = $this->db->select('id')->where('email', $eposta)->limit(1)->get($tablo)->row();
        if ($hesap) {
            $token = bin2hex(random_bytes(32));
            // Yeni istek önceki tokenları iptal eder: hesap-başına tek aktif link
            // (spam'le tablo şişmez + eski linkler anında ölür — LX tur bulgusu).
            $this->db->where(array('tip' => $tip, 'eposta' => $eposta))->delete('sifre_sifirlama');
            $this->db->insert('sifre_sifirlama', array(
                'tip' => $tip, 'eposta' => $eposta, 'token' => $token,
                'uretildi' => date('Y-m-d H:i:s'), 'kullanildi' => 0,
            ));
            $this->load->library('eposta');
            @$this->eposta->sifre_sifirlama($eposta, site_url('sifre-yenile/' . $tip . '/' . $token));
        }

        // Anti-enumeration: hesap var/yok AYNI mesaj (sayfa yenileme ile de sızdırma yok).
        $this->session->set_flashdata('bilgi', t('flash_sifre_gonderildi', 'Şifre sıfırlama bağlantısı hesabınızın e-postasına gönderildi (varsa). Bağlantı 30 dakika geçerlidir.'));
        redirect($giris);
    }

    public function yenile($tip = 'kullanici', $token = '')
    {
        $tip = $this->_tip($tip);
        $token = trim((string) $token);
        $row = $this->_token_getir($tip, $token);

        if (! $row) {
            $this->session->set_flashdata('hata', t('flash_sifre_token_gecersiz', 'Bağlantı geçersiz veya süresi dolmuş. Yeniden isteyin.'));
            redirect('sifremi-unuttum/' . $tip);
        }

        if (strtolower((string) $this->input->method()) !== 'post') {
            $this->v['meta_title']     = t('sifre_yenile_baslik', 'Yeni Şifre Belirle') . ' — ' . ayar('site_adi', 'Nesem Tesettür');
            $this->v['indexlenebilir'] = FALSE;
            $this->v['s_tip']          = $tip;
            $this->v['s_token']        = $token;
            $this->render('magaza/sifre/yenile');
            return;
        }

        $sifre  = (string) $this->input->post('sifre');
        $sifre2 = (string) $this->input->post('sifre2');
        if (strlen($sifre) < 6 || $sifre !== $sifre2) {
            $this->session->set_flashdata('hata', t('flash_sifre_uyusmaz', 'Şifre en az 6 karakter olmalı ve iki alan aynı olmalı.'));
            redirect('sifre-yenile/' . $tip . '/' . $token);
        }

        $model  = ($tip === 'bayi') ? 'bayi_model' : 'kullanici_model';
        $this->load->model($model);
        $this->{$model}->sifre_guncelle((int) $row->hesap_id, $sifre);

        $this->db->where('id', (int) $row->id)->update('sifre_sifirlama', array('kullanildi' => 1));
        $this->session->set_flashdata('bilgi', t('flash_sifre_ok', 'Şifreniz güncellendi; yeni şifrenizle giriş yapabilirsiniz.'));
        redirect(($tip === 'bayi') ? 'bayi/giris' : 'kullanici/giris');
    }

    /** Geçerli token satırı (+ hesap id) ya da NULL. Tek kullanımlık, 30 dk. */
    private function _token_getir($tip, $token)
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) { return NULL; }
        $row = $this->db->where(array('tip' => $tip, 'token' => $token, 'kullanildi' => 0))
                        ->limit(1)->get('sifre_sifirlama')->row();
        if (! $row || strtotime((string) $row->uretildi) < time() - self::GECERLILIK) { return NULL; }
        $tablo = ($tip === 'bayi') ? 'bayiler' : 'kullanicilar';
        $hesap = $this->db->select('id')->where('email', $row->eposta)->limit(1)->get($tablo)->row();
        if (! $hesap) { return NULL; }
        $row->hesap_id = (int) $hesap->id;
        return $row;
    }

    private function _tip($t)
    {
        return in_array($t, array('kullanici', 'bayi'), TRUE) ? $t : 'kullanici';
    }
}
