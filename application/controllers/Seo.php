<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Seo — arama motoru endpoint'leri: dinamik sitemap.xml + robots.txt.
 *
 * CI_Controller türevi (Magaza_Controller DEĞİL): session/layout/bakım YOK;
 * public makine erişimi. robots, indeksleme ayarına (arama_index) duyarlı.
 */
class Seo extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url', 'teksil'));
    }

    /** sitemap.xml — anasayfa + katalog + kategori + ürün URL'leri. */
    public function sitemap()
    {
        $base = rtrim((string) base_url(), '/');

        $urls = array();
        $urls[] = array('loc' => $base . '/', 'priority' => '1.0');
        $urls[] = array('loc' => $base . '/katalog', 'priority' => '0.9');

        if ($this->db->conn_id) {
            // Kategoriler
            foreach ($this->db->select('slug, olusturma_zaman')->where('durum', 1)->where('slug IS NOT NULL', NULL, FALSE)->order_by('id')->get('kategoriler')->result() as $k) {
                $urls[] = array('loc' => $base . '/katalog/' . $k->slug, 'priority' => '0.8', 'lastmod' => $k->olusturma_zaman);
            }
            // Ürünler
            foreach ($this->db->select('slug, olusturma_zaman')->where('durum', 1)->where('slug IS NOT NULL', NULL, FALSE)->order_by('id')->get('urunler')->result() as $u) {
                $urls[] = array('loc' => $base . '/urun/' . $u->slug, 'priority' => '0.7', 'lastmod' => $u->olusturma_zaman);
            }
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url>'
               . '<loc>' . htmlspecialchars($u['loc'], ENT_QUOTES) . '</loc>';
            if (! empty($u['lastmod'])) {
                echo '<lastmod>' . date('Y-m-d', strtotime($u['lastmod'])) . '</lastmod>';
            }
            echo '<priority>' . $u['priority'] . '</priority>'
               . '</url>' . "\n";
        }
        echo '</urlset>';
    }

    /** robots.txt — yönetim/özel alanları engelle; indeksleme ayarına göre. */
    public function robots()
    {
        $base = rtrim((string) base_url(), '/');
        header('Content-Type: text/plain; charset=utf-8');

        // İndeksleme kapalıysa (arama_index != 1) tüm site engelli.
        if ((string) ayar('arama_index') !== '1') {
            echo "User-agent: *\nDisallow: /\n";
        } else {
            echo "User-agent: *\n";
            echo "Disallow: /yonetim\n";
            echo "Disallow: /hesabim\n";
            echo "Disallow: /odeme\n";
            echo "Disallow: /sepet\n";
            echo "Disallow: /bayi\n";
            echo "Disallow: /api\n";
            echo "Allow: /\n";
        }
        echo "\nSitemap: " . $base . "/sitemap.xml\n";
    }
}
