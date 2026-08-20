/* TekstilSite — mağaza etkileşimleri (Faz 1 + Faz 2) */
(function () {
    'use strict';

    /* ---------- Toast ---------- */
    function toast(msg) {
        var t = document.createElement('div');
        t.className = 'tk-toast';
        t.textContent = msg;
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('gor'); });
        setTimeout(function () {
            t.classList.remove('gor');
            setTimeout(function () { if (t.parentNode) t.remove(); }, 300);
        }, 2800);
    }
    window.tkToast = toast;

    /* ---------- Header sepet sayacı ---------- */
    window.tkUpdateCart = function (n) {
        var el = document.getElementById('cartCount');
        if (el) { el.textContent = n; el.style.display = n > 0 ? '' : 'none'; }
    };

    /* AJAX POST yardımcı (CSRF + urlencoded gövde).
       Gövde URLSearchParams ile application/x-www-form-urlencoded gider —
       tarayıcının FormData'sı multipart basar ve php -S altında $_POST'a
       boş düşebiliyordu (XXIV: tarayıcıda posted=YOK; curl multipart testi
       yanıltıcıydı). Token kaynağı: sayfaya gömülü tkCsrf, yoksa (bir sebeple
       eksikse) çerezden okunur — çerez HttpOnly değil, model double-submit.
       Yanıt JSON değilse (CSRF 403 sayfası, hata HTML'i…) tip'li hata fırlatır —
       ağ hatasıyla karışmasın (XXII). Not: dosya yükleyen çağrı gelirse
       multipart yeniden düşünülmeli (tek çağrı sepet — dosya yok). */
    function ajaxPost(url, fd) {
        var govde = new URLSearchParams();
        if (fd && typeof fd.forEach === 'function') {
            fd.forEach(function (v, k) { govde.append(k, v); });
        }
        var tk = window.tkCsrf;
        if (! tk || ! tk.hash) {
            var m = document.cookie.match(/(?:^|;\s*)teksil_csrf_cookie=([^;]+)/);
            tk = { name: 'teksil_csrf', hash: m ? decodeURIComponent(m[1]) : '' };
        }
        if (tk && tk.hash) { govde.append(tk.name, tk.hash); }
        return fetch(url, { method: 'POST', body: govde, credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (txt) {
                    var veri = null;
                    try { veri = JSON.parse(txt); } catch (e) {}
                    if (veri) { return veri; }
                    var hata = new Error('yanit');
                    hata.tip = r.status === 403 ? 'csrf' : 'yanit';
                    hata.status = r.status;
                    throw hata;
                });
            });
    }
    window.tkAjaxPost = ajaxPost;

    /* ---------- Katalog: filtre ---------- */
    function initFiltre() {
        /* Filtre/sıralama yeniden yüklemesinde kaydırma konumunu koru. */
        var sy = sessionStorage.getItem('katScrollY');
        if (sy !== null) { sessionStorage.removeItem('katScrollY'); window.scrollTo(0, parseInt(sy, 10) || 0); }

        var form = document.querySelector('.filtre-form');
        if (form) {
            /* Anında filtre: beden/renk checkbox'ı + fiyat girişi (blur/Enter) formu gönderir. */
            form.addEventListener('change', function (e) {
                if (e.target.matches('input[type=checkbox], input[type=number]')) {
                    sessionStorage.setItem('katScrollY', String(window.scrollY));
                    form.submit();
                }
            });
        }
        /* Sırala: konumu koru, sonra yönlendir (inline onchange yerine). */
        var sira = document.getElementById('siraSelect');
        if (sira) {
            sira.addEventListener('change', function () {
                sessionStorage.setItem('katScrollY', String(window.scrollY));
                window.location.href = sira.value;
            });
        }
        var toggle = document.getElementById('filtreToggle');
        var sarma = document.getElementById('filtreSarma');
        if (toggle && sarma) {
            toggle.addEventListener('click', function () {
                var acik = sarma.classList.toggle('is-open');   /* CSS: .filtre-sarma.is-open */
                toggle.setAttribute('aria-expanded', acik ? 'true' : 'false');
            });
        }
    }

    /* ---------- Ürün detay ---------- */
    function initUrunDetay() {
        var veriEl = document.getElementById('pdVeri');
        if (!veriEl) { return; }
        var V = {};
        try { V = JSON.parse(veriEl.textContent); } catch (e) { return; }

        var ana = document.getElementById('anaGorsel');
        Array.prototype.forEach.call(document.querySelectorAll('.pd-thumb'), function (th) {
            th.addEventListener('click', function () {
                Array.prototype.forEach.call(document.querySelectorAll('.pd-thumb'), function (x) { x.classList.remove('aktif'); });
                th.classList.add('aktif');
                if (ana) { ana.src = th.getAttribute('data-src'); }
            });
        });

        var secRenk = null, secBeden = null;
        var renkSecili = document.getElementById('renkSecili');
        var renkBtns = document.querySelectorAll('.renk-sw');
        var bedenBtns = document.querySelectorAll('.beden-btn');

        function bedenGuncelle() {
            if (!bedenBtns.length) { return; }
            Array.prototype.forEach.call(bedenBtns, function (b) {
                var beden = b.getAttribute('data-beden');
                var key = secRenk ? (secRenk + '|' + beden) : null;
                var varr = key ? V.varyant[key] : null;
                var uygun = varr && varr.stok > 0;
                b.classList.toggle('yok', !uygun);
                b.disabled = !uygun;
                if (!uygun && b.classList.contains('aktif')) { b.classList.remove('aktif'); secBeden = null; }
            });
            if (secRenk && !secBeden) {
                var ilk = document.querySelector('.beden-btn:not(.yok)');
                if (ilk) { ilk.classList.add('aktif'); secBeden = ilk.getAttribute('data-beden'); }
            }
        }

        Array.prototype.forEach.call(renkBtns, function (sw) {
            sw.addEventListener('click', function () {
                Array.prototype.forEach.call(renkBtns, function (x) { x.classList.remove('aktif'); });
                sw.classList.add('aktif');
                secRenk = sw.getAttribute('data-renk');
                secBeden = null;
                Array.prototype.forEach.call(bedenBtns, function (x) { x.classList.remove('aktif'); });
                if (renkSecili) { renkSecili.textContent = secRenk; }
                bedenGuncelle();
                stokTazele();   /* XLV: varyant değişti → yeni stok tavanı */
                guncelle();
            });
        });
        Array.prototype.forEach.call(bedenBtns, function (b) {
            b.addEventListener('click', function () {
                if (b.disabled || b.classList.contains('yok')) { return; }
                Array.prototype.forEach.call(bedenBtns, function (x) { x.classList.remove('aktif'); });
                b.classList.add('aktif');
                secBeden = b.getAttribute('data-beden');
                stokTazele();   /* XLV */
                guncelle();
            });
        });

        var input = document.getElementById('adetInput');
        var eksi = document.getElementById('adetEksi');
        var arti = document.getElementById('adetArti');
        var toplamEl = document.getElementById('toplamTutar');
        var birimEl = document.getElementById('toplamBirim');
        var moq = V.moq || 1, adim = V.adim || 1, fiyat = V.fiyat || 0;
        var kur = V.kur || 1, pbSembol = V.sembol || '₺';

        function snap(v) {
            v = parseInt(v, 10); if (isNaN(v)) { v = moq; }
            if (v < moq) { return moq; }
            var k = Math.round((v - moq) / adim);
            return moq + k * adim;
        }
        function birimFiyat(adet) {
            var yuzde = 0;
            (V.basamak || []).forEach(function (b) { if (adet >= b.min && b.yuzde > yuzde) { yuzde = b.yuzde; } });
            return fiyat * (1 - yuzde / 100);
        }
        function fmt(n) { return (n / kur).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + pbSembol; }

        /* XLV: seçili varyantın stoğu — sayaç tavanı + uyarı. Stok yalnız
           varyant düzeyinde var; varyant seçili değilse tavan yoktur. */
        var stokSiniri = null;
        var stokBilgiEl = document.getElementById('stokBilgi');
        var uyariEl = document.getElementById('adetUyari');
        function seciliVaryant() {
            if (!V.varyant || (!renkBtns.length && !bedenBtns.length)) { return null; }
            return V.varyant[(secRenk || '') + '|' + (secBeden || '')] || null;
        }
        function stokTazele() {
            var varr = seciliVaryant();
            stokSiniri = (varr && varr.stok > 0) ? varr.stok : null;
            if (stokBilgiEl) {
                stokBilgiEl.hidden = (stokSiniri === null);
                stokBilgiEl.textContent = String(V.metin && V.metin.stok ? V.metin.stok : 'Stok: %s adet').replace('%s', stokSiniri || 0);
            }
        }
        function guncelle() {
            var adet = snap(input.value);
            var asti = (stokSiniri !== null && adet > stokSiniri);
            if (asti) { adet = stokSiniri; }   /* sayaç stok üstüne çıkamaz — stokta kalır */
            input.value = adet;
            if (uyariEl) {
                uyariEl.hidden = !asti;
                if (asti) { uyariEl.textContent = String(V.metin && V.metin.ust ? V.metin.ust : 'En fazla %s adet alabilirsiniz (mevcut stok).').replace('%s', stokSiniri); }
            }
            var birim = birimFiyat(adet);
            if (toplamEl) { toplamEl.textContent = fmt(birim * adet); }
            if (birimEl) { birimEl.textContent = '(' + fmt(birim) + ' / adet)'; }
        }
        if (arti) { arti.addEventListener('click', function () { input.value = snap((parseInt(input.value, 10) || moq) + adim); guncelle(); }); }
        if (eksi) { eksi.addEventListener('click', function () { input.value = snap((parseInt(input.value, 10) || moq) - adim); guncelle(); }); }
        if (input) { input.addEventListener('change', guncelle); }

        Array.prototype.forEach.call(document.querySelectorAll('.pd-tab'), function (tab) {
            tab.addEventListener('click', function () {
                Array.prototype.forEach.call(document.querySelectorAll('.pd-tab'), function (x) { x.classList.remove('aktif'); });
                Array.prototype.forEach.call(document.querySelectorAll('.pd-tab-panel'), function (x) { x.classList.remove('aktif'); });
                tab.classList.add('aktif');
                var p = document.querySelector('.pd-tab-panel[data-panel="' + tab.getAttribute('data-tab') + '"]');
                if (p) { p.classList.add('aktif'); }
            });
        });

        /* Sepete ekle (AJAX) */
        var sepet = document.getElementById('pdSepet');
        if (sepet) {
            sepet.addEventListener('click', function () {
                var varyantId = 0;
                if (renkBtns.length || bedenBtns.length) {
                    if (renkBtns.length && !secRenk) { toast('Lütfen bir renk seçin.'); return; }
                    if (bedenBtns.length && !secBeden) { toast('Lütfen bir beden seçin.'); return; }
                    var key = (secRenk || '') + '|' + (secBeden || '');
                    var varr = V.varyant ? V.varyant[key] : null;
                    if (varr) { varyantId = varr.id; }
                }
                var adet = parseInt(input.value, 10) || moq;
                var fd = new FormData();
                fd.append('urun_id', V.id);
                fd.append('varyant_id', varyantId);
                fd.append('adet', adet);
                sepet.disabled = true; sepet.textContent = 'Ekleniyor…';
                ajaxPost((window.tkBase || '/') + 'sepet/ekle', fd)
                    .then(function (res) {
                        sepet.disabled = false; sepet.textContent = 'Sepete Ekle';
                        if (res && res.ok) {
                            toast(res.mesaj || 'Sepete eklendi.');
                            if (typeof res.adet !== 'undefined') { window.tkUpdateCart(res.adet); }
                        } else {
                            toast((res && res.mesaj) ? res.mesaj : 'Eklenemedi.');
                        }
                    })
                    .catch(function (hata) {
                        sepet.disabled = false; sepet.textContent = 'Sepete Ekle';
                        if (hata && hata.tip === 'csrf') {
                            /* Bayat güvenlik anahtarı: istek denetleyiciye hiç varmadı —
                               yenileme güvenli; taze hash'le kullanıcı tekrar tıklar.
                               Döngü-kilidi: otomatik yenileme sayfa ömründe bir kez. */
                            if (sessionStorage.getItem('tkCsrfYenile')) {
                                toast('Güvenlik anahtarı hâlâ eskimiş. Sayfayı elle yenileyin (F5) ve tekrar deneyin.');
                                return;
                            }
                            sessionStorage.setItem('tkCsrfYenile', '1');
                            toast('Güvenlik anahtarı eskimiş — sayfa yenileniyor…');
                            setTimeout(function () { window.location.reload(); }, 2000);
                            return;
                        }
                        toast((hata && hata.tip === 'yanit')
                            ? 'Sunucu beklenmeyen yanıt döndürdü. Sayfayı yenileyip tekrar deneyin.'
                            : 'Bağlantı hatası.');
                    });
            });
        }

        if (renkBtns.length) { secRenk = renkBtns[0].getAttribute('data-renk'); }
        bedenGuncelle();
        stokTazele();
        guncelle();
    }

    /* ---------- Checkout ---------- */
    function initCheckout() {
        var form = document.querySelector('.odeme-form');
        if (!form) { return; }

        // Fatura teslimatla aynı toggle
        var ayni = document.getElementById('faturaAyni');
        var farkli = document.getElementById('faturaFarkli');
        if (ayni && farkli) {
            function toggleFatura() { farkli.style.display = ayni.checked ? 'none' : 'block'; }
            ayni.addEventListener('change', toggleFatura); toggleFatura();
        }

        // Ödeme yöntemine göre tahmini tutar
        var araEl = form.querySelector('[data-ara]');
        var ara = parseFloat(form.getAttribute('data-ara')) || 0;
        var esik = parseFloat(form.getAttribute('data-esik')) || 0;
        var ozetIslem = document.getElementById('ozetIslem');
        var ozetIslemSatir = document.getElementById('ozetIslemSatir');
        var ozetIslemEtiket = document.getElementById('ozetIslemEtiket');
        var ozetKargo = document.getElementById('ozetKargo');
        var ozetToplam = document.getElementById('ozetToplam');

        function fmt(n) { return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺'; }

        function hesapla() {
            var secili = form.querySelector('input[name="odeme_yontemi"]:checked');
            var islem = 0, etiket = 'İşlem ücreti';
            if (secili) {
                var tip = secili.getAttribute('data-ek-tip');
                var ek = parseFloat(secili.getAttribute('data-ek')) || 0;
                if (ek > 0) {
                    if (tip === 'yuzde') { islem = Math.round(ara * ek) / 100; etiket = '%' + (ek % 1 === 0 ? ek : ek.toFixed(1)) + ' kart komisyonu'; }
                    else { islem = ek; etiket = (secili.value === 'kapida' ? 'Kapıda ödeme ücreti' : 'İşlem ücreti'); }
                }
            }
            if (ozetIslemSatir) { ozetIslemSatir.style.display = islem > 0 ? '' : 'none'; }
            if (ozetIslem) { ozetIslem.textContent = fmt(islem); }
            if (ozetIslemEtiket) { ozetIslemEtiket.textContent = etiket; }

            var kargo = (ara >= esik) ? 0 : null;
            if (ozetKargo) { ozetKargo.textContent = (kargo === 0) ? 'Ücretsiz' : 'Hesaplanacak'; }

            if (ozetToplam) { ozetToplam.textContent = fmt(ara + islem + (kargo || 0)); }
        }
        Array.prototype.forEach.call(form.querySelectorAll('input[name="odeme_yontemi"]'), function (r) { r.addEventListener('change', hesapla); });
        hesapla();
    }

    /* ---------- Navbar arama ---------- */
    /* Kutu boşsa form gönderilmez — büyüteç/Enter hiçbir şey yapmasın. */
    function initHeaderArama() {
        var form = document.querySelector('form.header-search');
        if (!form) { return; }
        form.addEventListener('submit', function (e) {
            var q = form.querySelector('input[name="q"]');
            if (!q || !String(q.value).trim()) { e.preventDefault(); }
        });
    }

    /* ---------- Yukarı çık butonu ---------- */
    /* Sayfa dibine gelince belirir; tıklamada yumuşak kaydırmayla en üste döner. */
    function initYukariBtn() {
        var btn = document.getElementById('yukariBtn');
        if (!btn) { return; }
        var guncelle = function () {
            var dibde = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 40;
            btn.classList.toggle('gorunur', dibde);
        };
        window.addEventListener('scroll', guncelle, { passive: true });
        window.addEventListener('resize', guncelle);
        guncelle();
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function init() { initFiltre(); initUrunDetay(); initCheckout(); initYukariBtn(); initHeaderArama(); }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
    else { init(); }
})();
