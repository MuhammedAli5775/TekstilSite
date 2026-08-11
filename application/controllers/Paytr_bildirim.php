<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Paytr_bildirim — PayTR sunucudan-sunucuya ödeme bildirimi (callback).
 *
 * CI_Controller türevi: session/layout/CSRF YOK (csrf_exclude_uris). Raw $_POST.
 * Hash geçersizse "bad hash"; geçerliyse siparişi ödendi işaretle + "OK" döndür.
 * PayTR her durumda tam "OK" bekler.
 */
class Paytr_bildirim extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('teksil');
        $this->load->library(array('paytr_api', 'eposta', 'sms'));
        $this->load->model('siparis_model');
    }

    public function index()
    {
        $post = $this->input->post();   // CSRF muaf (config)

        if (! $this->paytr_api->callback_dogrula($post)) {
            log_message('error', 'PayTR bildirim: geçersiz hash (oid=' . ($post['merchant_oid'] ?? '?') . ').');
            echo 'bad hash';
            return;
        }

        $oid = (string) $post['merchant_oid'];
        $s = $this->db->where('siparis_no', $oid)->limit(1)->get('siparisler')->row();
        if (! $s) {
            log_message('error', 'PayTR bildirim: sipariş yok (oid=' . $oid . ').');
            echo 'siparis yok';
            return;
        }

        if (($post['status'] ?? '') === 'success') {
            // Idempotent: zaten ödendiyse tekrar işleme
            if ($s->odeme_durumu !== 'odendi') {
                $this->db->trans_start();
                $this->db->where('id', $s->id)->update('siparisler', array(
                    'odeme_durumu' => 'odendi',
                    'durum'        => 'onaylandi',
                ));
                $this->db->insert('siparis_durum_gecmisi', array(
                    'siparis_id' => $s->id,
                    'durum'      => 'onaylandi',
                    'taraf'      => 'sistem',
                    'notu'       => 'PayTR ödemesi alındı (' . $oid . ')',
                ));
                $this->db->trans_complete();

                // Bayiye bildirim (graceful)
                @$this->eposta->durum_bildirim($s->id, 'Onaylandı', 'Ödemeniz alındı, siparişiniz hazırlanıyor.');
                @$this->sms->durum_bildirim($s->id, 'Onaylandı', '');
            }
            log_message('error', 'PayTR bildirim: ödeme ödendi işaretlendi (oid=' . $oid . ').');
        } else {
            log_message('error', 'PayTR bildirim: ödeme başarısız (oid=' . $oid . ', status=' . ($post['status'] ?? '?') . ').');
        }

        echo 'OK';
    }
}
