<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sayfa — storefront yardımcı sayfalar (utility-bar 404 fix).
 *   yardim        — SSS + iletişim
 *   siparis_takip — misafir sipariş takibi (no + e-posta)
 *   favorilerim   — session tabanlı wishlist
 *   blog          — stub
 */
class Sayfa extends Magaza_Controller
{
    /** Yardım / SSS + iletişim. */
    public function yardim()
    {
        $this->v['meta_title']     = 'Yardım — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/sayfa/yardim');
    }

    /** Blog (stub — ileride içerik). */
    public function blog()
    {
        $this->v['meta_title']     = 'Blog — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/sayfa/blog');
    }

    /** Favorilerim (session tabanlı wishlist). */
    public function favorilerim()
    {
        $ids = array_filter(array_map('intval', (array) $this->session->userdata('favoriler')));
        $urunler = array();
        if ($ids) {
            $urunler = $this->db->where_in('id', $ids)->where('durum', 1)->order_by('FIELD(id,' . implode(',', $ids) . ')', '', FALSE)->get('urunler')->result();
        }
        $data = array('urunler' => $urunler);
        $this->v['meta_title']     = 'Favorilerim — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/sayfa/favorilerim', $data);
    }

    /** Favori ekle (session). */
    public function favoriler_ekle($id = NULL)
    {
        $id = (int) $id;
        if ($id > 0) {
            $f = (array) $this->session->userdata('favoriler');
            if (! in_array($id, $f, TRUE)) { $f[] = $id; }
            $this->session->set_userdata('favoriler', $f);
            $this->session->set_flashdata('bilgi', 'Favorilere eklendi.');
        }
        $ref = $this->input->server('HTTP_REFERER');
        redirect($ref ?: 'favorilerim');
    }

    /** Favoriden çıkar (session). */
    public function favoriler_sil($id = NULL)
    {
        $id = (int) $id;
        $f = array_values(array_diff((array) $this->session->userdata('favoriler'), array($id)));
        $this->session->set_userdata('favoriler', $f);
        $this->session->set_flashdata('bilgi', 'Favorilerden çıkarıldı.');
        redirect('favorilerim');
    }

    /** Sipariş takibi (misafir — sipariş no + e-posta). */
    public function siparis_takip()
    {
        $data = array('siparis' => NULL, 'hata' => NULL);
        if ($this->input->method() === 'post') {
            $no    = trim((string) $this->input->post('siparis_no'));
            $email = trim((string) $this->input->post('email'));
            $s = $this->db->where('siparis_no', $no)->where('email', $email)->limit(1)->get('siparisler')->row();
            if ($s) {
                $s->detaylar = $this->db->where('siparis_id', $s->id)->get('siparis_detaylari')->result();
                $data['siparis'] = $s;
            } else {
                $data['hata'] = 'Sipariş bulunamadı. Sipariş no ve e-postayı kontrol edin.';
            }
        }
        $this->v['meta_title']     = 'Sipariş Takibi — ' . ayar('site_adi', 'TekstilSite');
        $this->v['indexlenebilir'] = FALSE;
        $this->render('magaza/sayfa/siparis_takip', $data);
    }

    /** CMS sayfasi (sayfalar tablosundan slug ile) — footer/kurumsal sayfalar. */
    public function goster($slug)
    {
        $slug = trim((string) $slug);
        $sayfa = $this->db->where('slug', $slug)->where('durum', 1)->limit(1)->get('sayfalar')->row();
        if (! $sayfa) { show_404(); }
        $this->v['meta_title'] = ! empty($sayfa->seo_title)
            ? $sayfa->seo_title
            : ($sayfa->baslik . ' — ' . ayar('site_adi', 'TekstilSite'));
        $this->v['meta_desc']  = ! empty($sayfa->seo_description) ? $sayfa->seo_description : '';
        $this->render('magaza/sayfa/sayfa', array('sayfa' => $sayfa));
    }
}
