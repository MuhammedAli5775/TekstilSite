<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Raporlar (yönetim) — satış/ürün/kategori/bayi/bölge/ödeme raporları + CSV/PDF dışa aktarma.
 * Tüm raporlar tarih aralığına ve brüt satış kuralına (iptal/iade hariç) göredir.
 */
class Raporlar extends Admin_Controller
{
    private $RAPORLAR = array(
        'satis'    => 'Satış Özeti',
        'urun'     => 'Ürün Satışı',
        'kategori' => 'Kategori Satışı',
        'bayi'     => 'Bayi Satışı',
        'bolge'    => 'Bölge (İl/İlçe)',
        'odeme'    => 'Ödeme Yöntemi',
    );

    /** Tablo kolon haritası (anahtar => başlık) — 'satis' hariç (özet kartı). */
    private $KOLONLAR = array(
        'urun'     => array('urun_adi' => 'Ürün', 'adet' => 'Adet', 'ciro' => 'Ciro', 'siparis' => 'Sipariş'),
        'kategori' => array('ad' => 'Kategori', 'adet' => 'Adet', 'ciro' => 'Ciro'),
        'bayi'     => array('bayi' => 'Bayi', 'email' => 'E-posta', 'siparis' => 'Sipariş', 'ciro' => 'Ciro'),
        'bolge'    => array('bolge' => 'Bölge', 'siparis' => 'Sipariş', 'ciro' => 'Ciro'),
        'odeme'    => array('yontem' => 'Yöntem', 'siparis' => 'Sipariş', 'ciro' => 'Ciro'),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rapor_model');
    }

    /** Tarih aralığı oku + doğrula (varsayılan: son 30 gün). */
    private function _aralik_oku()
    {
        $bas = (string) $this->input->get('bas');
        $son = (string) $this->input->get('son');
        $vBas = date('Y-m-d', strtotime('-29 days'));
        $vSon = date('Y-m-d');
        $bas = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bas)) ? $bas : $vBas;
        $son = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $son)) ? $son : $vSon;
        if ($bas > $son) { $tmp = $bas; $bas = $son; $son = $tmp; }
        return array($bas, $son);
    }

    public function index($rapor = 'satis')
    {
        $this->yetki_gerek('raporlar', 'goruntule');
        if (! isset($this->RAPORLAR[$rapor])) { $rapor = 'satis'; }
        list($bas, $son) = $this->_aralik_oku();

        $data['rapor']        = $rapor;
        $data['raporlar']     = $this->RAPORLAR;
        $data['kolonlar']     = isset($this->KOLONLAR[$rapor]) ? $this->KOLONLAR[$rapor] : array();
        $data['bas']          = $bas;
        $data['son']          = $son;
        $data['satirlar']     = $this->_yukle($rapor, $bas, $son);
        $data['ozet']         = ($rapor === 'satis') ? $this->rapor_model->satis_ozet($bas, $son) : NULL;
        $data['sayfa_basligi']= 'Raporlar — ' . $this->RAPORLAR[$rapor];
        $data['menu_aktif']   = 'raporlar';
        $this->render('yonetim/raporlar/index', $data);
    }

    /** Rapor verisini yükle (satis → boş, özet ayrı). */
    private function _yukle($rapor, $bas, $son)
    {
        switch ($rapor) {
            case 'urun':     return $this->rapor_model->urun_satis($bas, $son);
            case 'kategori': return $this->rapor_model->kategori_satis($bas, $son);
            case 'bayi':     return $this->rapor_model->bayi_satis($bas, $son);
            case 'bolge':    return $this->rapor_model->bolge_satis($bas, $son, $this->input->get('alan') === 'ilce' ? 'teslimat_ilce' : 'teslimat_il');
            case 'odeme':    return $this->rapor_model->odeme_satis($bas, $son);
            default:         return array();
        }
    }

    /** Dışa aktarma: csv | pdf (yazdırılabilir HTML). */
    public function disa_aktar($rapor = 'satis', $format = 'csv')
    {
        $this->yetki_gerek('raporlar', 'goruntule');
        if (! isset($this->RAPORLAR[$rapor])) { $rapor = 'satis'; }
        $format = ($format === 'pdf') ? 'pdf' : 'csv';
        list($bas, $son) = $this->_aralik_oku();
        $kolonlar = isset($this->KOLONLAR[$rapor]) ? $this->KOLONLAR[$rapor] : array();

        if ($format === 'pdf') {
            $data = array(
                'rapor_adi' => $this->RAPORLAR[$rapor], 'bas' => $bas, 'son' => $son,
                'kolonlar' => $kolonlar, 'satirlar' => $this->_yukle($rapor, $bas, $son),
                'ozet' => ($rapor === 'satis') ? $this->rapor_model->satis_ozet($bas, $son) : NULL,
                'site_adi' => ayar('site_adi', 'TekstilSite'),
            );
            $this->load->view('yonetim/raporlar/disa_pdf', $data);  // bağımsız yazdırılabilir HTML (admin layout yok)
            return;
        }

        // --- CSV (UTF-8 BOM + ';') ---
        $dosya = 'rapor_' . $rapor . '_' . $bas . '_' . $son . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $dosya . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF");  // UTF-8 BOM (Excel)

        if ($rapor === 'satis') {
            $o = $this->rapor_model->satis_ozet($bas, $son);
            fputcsv($out, array('Metrik', 'Değer'), ';');
            fputcsv($out, array('Tüm Sipariş', $o['toplam']), ';');
            fputcsv($out, array('Brüt Sipariş (iptal/iade hariç)', $o['brut_siparis']), ';');
            fputcsv($out, array('Brüt Ciro', $o['ciro']), ';');
            fputcsv($out, array('Kargo', $o['kargo']), ';');
            fputcsv($out, array('İndirim', $o['indirim']), ';');
            fputcsv($out, array('Ortalama Sepet (AOV)', $o['aov']), ';');
            fputcsv($out, array(''), ';');
            fputcsv($out, array('Durum', 'Sipariş'), ';');
            foreach ($o['durumlar'] as $d => $n) { fputcsv($out, array($d, $n), ';'); }
            fputcsv($out, array('İade/İptal Oranı (%)', $o['iptal_oran']), ';');
            fputcsv($out, array(''), ';');
            fputcsv($out, array('Para Birimi', 'Brüt Ciro', 'Sipariş'), ';');
            foreach ($o['pb_dagilim'] as $pb => $d) { fputcsv($out, array($pb, $d['ciro'], $d['siparis']), ';'); }
        } else {
            fputcsv($out, array_values($kolonlar), ';');
            foreach ($this->_yukle($rapor, $bas, $son) as $r) {
                $row = array();
                foreach (array_keys($kolonlar) as $k) { $row[] = isset($r->$k) ? $r->$k : ''; }
                fputcsv($out, $row, ';');
            }
        }
        fclose($out);
    }
}
