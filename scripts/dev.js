/**
 * npm run dev — CI3 yerleşik sunucu: http://localhost:8000
 *
 * PHP önce PATH'te aranır, yoksa sabit XAMPP yolu denenir (bu makinede php PATH'te
 * değil — workflow.md §8: C:\xampp\php\php.exe). router.php statik dosyaları
 * (assets/, uploads/) doğrudan servis eder, gerisi CI front controller'a gider.
 */
const { spawn, execSync } = require('child_process');
const path = require('path');

const ROOT = path.join(__dirname, '..');
// PORT yalnız sayısal kabul edilir (komut shell'e gidiyor — enjeksiyon kapısı olmasın)
const PORT = /^\d+$/.test(process.env.PORT || '') ? process.env.PORT : '8000';

let php = 'php';
try {
    execSync('php -v', { stdio: 'ignore' });
} catch (e) {
    php = 'C:\\xampp\\php\\php.exe';
}

// Komut tek dizi olarak verilir: shell:true + args DİZİSİ Node'da DEP0190 uyarısı üretir.
const cmd = `"${php}" -S localhost:${PORT} router.php`;

console.log(`> ${cmd}`);
console.log(`> Hazir: http://localhost:${PORT}  (durdurmak: Ctrl+C)`);

const child = spawn(cmd, {
    cwd: ROOT,
    stdio: 'inherit',
    shell: true,
});

child.on('error', (err) => {
    console.error('Sunucu baslatilamadi (php bulundu mu?):', err.message);
    process.exit(1);
});
child.on('close', (code) => {
    process.exit(code == null ? 0 : code);
});
