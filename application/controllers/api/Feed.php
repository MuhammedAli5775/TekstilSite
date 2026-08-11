<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Feed — B2B toptancı katalog feed'i (XML / JSON).
 *
 * CI_Controller türevi (Magaza_Controller DEĞİL): session / layout / bakım modu
 * YOK — makine-makine erişim. Kimlik doğrulama API anahtarıyla (?key=... veya
 * X-Api-Key başlığı); anahtar sha256 hash ile doğrulanır (plaintext saklanmaz).
 *
 * URL: /feed/urunler?key=ANAHTAR            → XML (varsayılan)
 *      /feed/urunler?key=ANAHTAR&format=json → JSON
 */
class Feed extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // autoload DB'yi yüklemez (Faz 0 kararı) → burada lazy yüklüyoruz.
        $this->load->database();
        $this->load->helper(array('url', 'teksil'));
    }

    /** Ürün kataloğu feed'i. */
    public function urunler()
    {
        $fmt = (strtolower((string) $this->input->get('format')) === 'json') ? 'json' : 'xml';

        $anahtar = $this->_anahtar();
        if (! $anahtar) { return; } // _anahtar() hata çıktısını çoktan bastı

        $this->load->model('urun_model');
        $this->load->library('xml_export');
        $urunler = $this->urun_model->feed_liste();

        $this->load->model('api_anahtar_model');
        $this->api_anahtar_model->kullanildi($anahtar->id);

        if ($fmt === 'json') {
            $this->output->set_content_type('application/json')
                         ->set_output($this->xml_export->json($urunler));
        } else {
            $this->output->set_content_type('application/xml')
                         ->set_output($this->xml_export->xml($urunler));
        }
    }

    /** API anahtarını doğrula; geçersizse hatayı basıp NULL döner. */
    private function _anahtar()
    {
        $ham = (string) $this->input->get('key');
        if ($ham === '') { $ham = (string) $this->input->get_request_header('X-Api-Key', TRUE); }
        $ham = trim($ham);

        $this->load->model('api_anahtar_model');
        if ($ham === '') { $this->_hata(401, 'API anahtarı gerekli (?key=... veya X-Api-Key başlığı).'); return NULL; }
        $row = $this->api_anahtar_model->dogrula($ham);
        if (! $row) { $this->_hata(403, 'Geçersiz veya pasif API anahtarı.'); return NULL; }
        return $row;
    }

    private function _hata($kod, $mesaj)
    {
        $fmt = (strtolower((string) $this->input->get('format')) === 'json') ? 'json' : 'xml';
        set_status_header($kod);
        if ($fmt === 'json') {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(array('hata' => TRUE, 'kod' => $kod, 'mesaj' => $mesaj), JSON_UNESCAPED_UNICODE));
        } else {
            $this->output->set_content_type('application/xml')
                         ->set_output('<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                            . '<hata kod="' . (int) $kod . '"><mesaj>'
                            . htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8')
                            . '</mesaj></hata>');
        }
    }
}
