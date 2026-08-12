<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cron — periyodik bakım/senkron işleri (CLI ONLY).
 *
 * Çalıştırma:
 *   php index.php cron calis              # tüm işler (ana cron girişi)
 *   php index.php cron terk_sepet         # tek tek iş
 *   php index.php cron pazaryeri_senkron
 *   php index.php cron efatura_durum
 *
 * Web erişimi is_cli() guard'ı ile engellenir. Çıktı stdout'a (cron yakalar/loglar).
 * Tüm işler graceful: tablo/veri/entegratör yoksa atlar, hata vermez.
 */
class Cron extends CI_Controller
{
    public function __construct()
    {
        if (! is_cli()) {
            http_response_code(403);
            echo "Bu komut yalnizca CLI'dan calisir.\n";
            exit(1);
        }
        parent::__construct();
        $this->load->database();
        $this->load->helper('teksil');
    }

    /** Tüm periyodik işleri çalıştır (ana cron girişi). */
    public function calis()
    {
        $this->_out('=== Cron basladi ' . date('Y-m-d H:i:s') . ' ===');
        $this->terk_sepet();
        $this->pazaryeri_senkron();
        $this->efatura_durum();
        $this->_out('=== Cron bitti ===');
    }

    /** Eski misafir sepetlerini temizle (bayi_id NULL, N günden eski). */
    public function terk_sepet($gun = 7)
    {
        if (! $this->db->table_exists('sepet')) { $this->_out('terk_sepet: sepet tablosu yok, atlandi.'); return; }
        $gun  = max(1, (int) $gun);
        $esik = date('Y-m-d H:i:s', strtotime('-' . $gun . ' days'));
        $this->db->where('bayi_id IS NULL', NULL, FALSE)->where('eklenme <', $esik)->delete('sepet');
        $this->_out('terk_sepet: ' . $this->db->affected_rows() . ' satir silindi (>' . $gun . ' gun, misafir).');
    }

    /** Aktif pazaryeri hesaplarına stok/fiyat senkronu (graceful — kimlik yoksa atlar). */
    public function pazaryeri_senkron()
    {
        if (! $this->db->table_exists('pazaryeri_hesaplari')) { $this->_out('pazaryeri_senkron: tablo yok, atlandi.'); return; }
        $this->load->library('pazaryeri_api');
        $hesaplar = $this->db->where('durum', 1)->get('pazaryeri_hesaplari')->result();
        if (! $hesaplar) { $this->_out('pazaryeri_senkron: aktif hesap yok.'); return; }
        foreach ($hesaplar as $h) {
            $res = $this->pazaryeri_api->stok_fiyat_gonder($h->id);
            $this->_out('pazaryeri #' . $h->id . ' (' . $h->ad . '): ' . ($res['ok'] ? 'OK ' : 'SKIP ') . $res['mesaj']);
        }
    }

    /** İşlenen (process_id'li) faturaların durumunu entegratörden sorgula. */
    public function efatura_durum()
    {
        if (! $this->db->table_exists('faturalar')) { $this->_out('efatura_durum: tablo yok, atlandi.'); return; }
        $this->load->library('efatura');
        $bekleyen = $this->db->where('durum', 'isleniyor')->where('process_id IS NOT NULL', NULL, FALSE)->get('faturalar')->result();
        if (! $bekleyen) { $this->_out('efatura_durum: sorgulanacak fatura yok.'); return; }
        foreach ($bekleyen as $f) {
            $res = $this->efatura->durum_sorgula($f->id);
            $this->_out('efatura #' . $f->id . ': ' . ($res['ok'] ? 'OK ' : 'SKIP ') . $res['mesaj']);
        }
    }

    private function _out($mesaj)
    {
        echo $mesaj . "\n";
    }
}
