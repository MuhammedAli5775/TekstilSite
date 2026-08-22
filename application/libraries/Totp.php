<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Totp — RFC 6238 zaman-tabanlı tek kullanımlık parola (LXIII).
 * Bağımlılıksız: hash_hmac + base32. Kimlik doğrulayıcı uygulamalarla
 * (Google Authenticator, Aegis, 1Password...) uyumlu; SHA-1 / 30 sn / 6 hane.
 */
class Totp
{
    const ADIM    = 30;   // saniye
    const UZUNLUK = 6;    // hane

    /** Yeni base32 anahtar üret (20 bayt = 160 bit — standart). */
    public function anahtar_uret($bayt = 20)
    {
        return self::b32_kodla(random_bytes($bayt));
    }

    /** Belirli dilimdeki kodu hesapla. */
    public function kod($b32, $dilim = NULL)
    {
        $anahtar = self::b32_coz($b32);
        if ($anahtar === '' ) { return NULL; }
        if ($dilim === NULL) { $dilim = (int) floor(time() / self::ADIM); }
        $hmac = hash_hmac('sha1', pack('N*', 0, $dilim), $anahtar, TRUE);
        $ofset = ord($hmac[19]) & 0x0F;
        $deger = ((ord($hmac[$ofset]) & 0x7F) << 24)
               | (ord($hmac[$ofset + 1]) << 16)
               | (ord($hmac[$ofset + 2]) << 8)
               |  ord($hmac[$ofset + 3]);
        return str_pad((string) ($deger % (10 ** self::UZUNLUK)), self::UZUNLUK, '0', STR_PAD_LEFT);
    }

    /** Kod bu anahtar için şu an geçerli mi? (±1 dilim saat kayması toleransı) */
    public function gecerli($b32, $kod, $pencere = 1)
    {
        $kod = preg_replace('/\D/', '', (string) $kod);
        if (strlen($kod) !== self::UZUNLUK) { return FALSE; }
        $simdi = (int) floor(time() / self::ADIM);
        for ($i = -$pencere; $i <= $pencere; $i++) {
            $beklenen = $this->kod($b32, $simdi + $i);
            if ($beklenen !== NULL && hash_equals($beklenen, $kod)) { return TRUE; }
        }
        return FALSE;
    }

    /** Kimlik doğrulayıcıya girilecek URI (elle ekleme / tıklanabilir). */
    public function uri($b32, $etiket, $veren)
    {
        return 'otpauth://totp/' . rawurlencode($veren . ':' . $etiket)
             . '?secret=' . $b32 . '&issuer=' . rawurlencode($veren)
             . '&period=' . self::ADIM . '&digits=' . self::UZUNLUK;
    }

    /** RFC 4648 base32 kodla (A-Z2-7, dolgu yok). */
    public static function b32_kodla($ham)
    {
        $alfabe = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $cikti = ''; $tut = 0; $bit = 0;
        for ($i = 0, $n = strlen($ham); $i < $n; $i++) {
            $tut = ($tut << 8) | ord($ham[$i]);
            $bit += 8;
            while ($bit >= 5) {
                $bit -= 5;
                $cikti .= $alfabe[($tut >> $bit) & 31];
            }
        }
        if ($bit > 0) { $cikti .= $alfabe[($tut << (5 - $bit)) & 31]; }
        return $cikti;
    }

    /** base32 çöz (boşluk/tire/dolgu toleranslı). Hatalı girdide ''. */
    public static function b32_coz($b32)
    {
        $alfabe = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', (string) $b32));
        $cikti = ''; $tut = 0; $bit = 0;
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $v = strpos($alfabe, $b32[$i]);
            if ($v === FALSE) { return ''; }
            $tut = ($tut << 5) | $v;
            $bit += 5;
            if ($bit >= 8) {
                $bit -= 8;
                $cikti .= chr(($tut >> $bit) & 255);
            }
        }
        return $cikti;
    }
}
