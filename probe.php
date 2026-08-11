<?php
/* Probe: PHP+mysqli doğrudan (CI katmanı yok). Türkçe LIKE davranışını izole eder. */
$mysqli = new mysqli('127.0.0.1', 'root', 'mysql1234', 'teksilsite');
if ($mysqli->connect_errno) { die("bağlantı hatası: " . $mysqli->connect_error); }
$mysqli->set_charset('utf8mb4');

echo "conn charset: " . $mysqli->character_set_name() . "\n";
echo "----- heredoc-style (sabit) -----\n";
$tests = array('ş', 'şifon', 'tişört', 'süprem', 'hırka', 'gömlek');
foreach ($tests as $q) {
    $like = '%' . $q . '%';
    $stmt = $mysqli->prepare("SELECT id, ad FROM urunler WHERE durum=1 AND (ad LIKE ? OR stok_kodu LIKE ?)");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = array();
    while ($r = $res->fetch_assoc()) { $rows[] = $r['ad']; }
    echo "q=[" . $q . "] hex=" . bin2hex($q) . "  eşleşme=" . count($rows) . "  " . implode(' | ', $rows) . "\n";
    $stmt->close();
}
echo "----- CI xss_clean taklidi (Security benzetimi) -----\n";
echo "sabit test tamam\n";
$mysqli->close();
