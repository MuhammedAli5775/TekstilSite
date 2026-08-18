<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Yazilar — blog yazısı yönetimi (yazilar tablosu, D3/XXXV).
 *   index  — liste (sol) + ekle/düzenle formu (sağ)
 *   kaydet — ekle/güncelle (slug boşsa başlıktan üretilir; benzersiz değilse -2, -3…)
 *   sil    — satırı kaldır (kapak görseli yalnız URL olduğundan dosya temizliği yok)
 * İçerik admin HTML'idir — CMS sayfaları deseni (vitrinde raw basılır, güvenilir kaynak).
 */
class Yazilar extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->yetki_gerek('yazilar', 'goruntule');
    }

    public function index()
    {
        $duzenle_id = (int) $this->input->get('duzenle');
        $data = array(
            'sayfa_basligi' => 'Blog Yazıları',
            'menu_aktif'    => 'yazilar',
            'yazilar'       => $this->db->order_by('yayin_tarihi', 'DESC')->order_by('id', 'DESC')->get('yazilar')->result(),
            'duzenle'       => $duzenle_id ? $this->db->where('id', $duzenle_id)->get('yazilar')->row() : NULL,
        );
        $this->render('yonetim/yazilar/index', $data);
    }

    public function kaydet()
    {
        $this->yetki_gerek('yazilar', 'duzenle');
        $id     = (int) $this->input->post('id');
        $baslik = trim((string) $this->input->post('baslik'));
        $icerik = trim((string) $this->input->post('icerik'));

        if ($baslik === '' || $icerik === '') {
            $this->session->set_flashdata('hata', 'Başlık ve içerik zorunludur.');
            redirect('yonetim/yazilar' . ($id ? '?duzenle=' . $id : ''));
        }

        $ozet = trim((string) $this->input->post('ozet'));
        if ($ozet === '') { $ozet = mb_substr(trim(strip_tags($icerik)), 0, 500); }

        // Kapak görseli yalnız dış https URL — banner XXVIII kuralı (şemasız/relative yol DB'ye girmez).
        $gorsel = trim((string) $this->input->post('gorsel'));
        if ($gorsel !== '' && ! preg_match('#^https://#i', $gorsel)) { $gorsel = ''; }

        // Slug: elle girilebilir; boşsa başlıktan (slug_tr). Benzersiz değilse -2, -3… (kendisi hariç).
        $slug = slug_tr(trim((string) $this->input->post('slug')));
        if ($slug === '') { $slug = slug_tr($baslik); }
        if ($slug === '') { $slug = 'yazi-' . date('YmdHis'); }
        $deneme = $slug;
        $n = 1;
        while ($this->db->where('slug', $deneme)->where('id !=', $id)->limit(1)->get('yazilar')->row()) {
            $deneme = $slug . '-' . (++$n);
        }

        $tarih = trim((string) $this->input->post('yayin_tarihi'));
        $d = array(
            'slug'         => $deneme,
            'baslik'       => $baslik,
            'ozet'         => $ozet,
            'icerik'       => $icerik,
            'gorsel'       => $gorsel,
            'durum'        => $this->input->post('durum') ? 1 : 0,
            'yayin_tarihi' => preg_match('#^\d{4}-\d{2}-\d{2}$#', $tarih) ? $tarih : NULL,
        );

        if ($id && $this->db->where('id', $id)->get('yazilar')->row()) {
            $this->db->where('id', $id)->update('yazilar', $d);
            $this->session->set_flashdata('bilgi', 'Yazı güncellendi.');
        } else {
            $this->db->insert('yazilar', $d);
            $this->session->set_flashdata('bilgi', 'Yazı eklendi.');
        }
        redirect('yonetim/yazilar');
    }

    public function sil($id = 0)
    {
        $this->yetki_gerek('yazilar', 'sil');
        $id = (int) $id;
        if ($id > 0) { $this->db->where('id', $id)->delete('yazilar'); }
        $this->session->set_flashdata('bilgi', 'Yazı silindi.');
        redirect('yonetim/yazilar');
    }
}
