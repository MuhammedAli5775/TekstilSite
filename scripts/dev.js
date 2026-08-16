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

// Ortam seçimi: süreç env'i (CI_ENV=...) YA DA argüman (npm run dev:testing).
// Zincirde (npm/cmd/sarmalayıcı) env yutulabilir — 2026-08-16 provasında
// CI_ENV=testing uygulamaya ulaşmadı (dev DB'sine yazdı); argüman + açık
// child env belirleyicidir.
const argEnv = process.argv.slice(2).filter((a) => a !== '--')[0] || '';
const ciEnv = process.env.CI_ENV || argEnv || '';
if (ciEnv) { console.log(`> CI_ENV=${ciEnv} → config/${ciEnv}/ yüklenecek`); }

// PHP yolu MUTLAK olsun ve shell KULLANMA: 2026-08-16 provasında shell:true
// zinciri CI_ENV'yi yuttu (php getenv=false gördü, uygulama dev DB'sine yazdı).
// Direkt spawn + args dizisi: env kesin iletilir, DEP0190 da kalkar.
if (php === 'php') {
    try {
        php = execSync(process.platform === 'win32' ? 'where php' : 'which php')
            .toString().trim().split(/\r?\n/)[0];
    } catch (e) {
        php = 'C:\\xampp\\php\\php.exe';
    }
}

// PORT DOLU MU? Sert öldürmede (terminate) php çocuğu Windows'ta yetim kalıp
// portu tutabilir (2026-08-16 provasında :8000'de 5 yetim bulundu — istekler
// rastgele eski süreçlere düşüyordu). Sessiz "istek ruleti" yerine gürültülü red.
if (process.platform === 'win32') {
    try {
        const satir = execSync('netstat -ano').toString()
            .split(/\r?\n/)
            .find((l) => new RegExp(`:${PORT}\\s+.*LISTENING`).test(l));
        if (satir) {
            const pid = satir.trim().split(/\s+/).pop();
            console.error(`HATA: localhost:${PORT} hâlihazırda dinleniyor (PID ${pid}).`);
            console.error(`Eski (yetim?) sunucu olabilir — kapat:  taskkill /F /PID ${pid}`);
            process.exit(1);
        }
    } catch (e) { /* netstat yoksa atla — php -S bind hatasını zaten basar */ }
}

const args = ['-S', `localhost:${PORT}`, 'router.php'];
console.log(`> ${php} ${args.join(' ')}`);
console.log(`> Hazir: http://localhost:${PORT}  (durdurmak: Ctrl+C)`);

const child = spawn(php, args, {
    cwd: ROOT,
    stdio: 'inherit',
    env: Object.assign({}, process.env, ciEnv ? { CI_ENV: ciEnv } : {}),
});

child.on('error', (err) => {
    console.error('Sunucu baslatilamadi (php bulundu mu?):', err.message);
    process.exit(1);
});
child.on('close', (code) => {
    process.exit(code == null ? 0 : code);
});

// Sarmalayıcı (npm/terminal) ölünce php çocuğu Windows'ta yetim kalabilir —
// 2026-08-16 provasında :8000'de 5 yetim dinler bulundu, istekler eski süreçlere
// düşüyordu. Çıkış ve sinyallerde çocuğu kesin öldür.
process.on('exit', () => { try { child.kill(); } catch (e) { /* zaten ölmüş */ } });
['SIGINT', 'SIGTERM', 'SIGHUP'].forEach((sig) => {
    process.on(sig, () => {
        try { child.kill(); } catch (e) { /* zaten ölmüş */ }
        process.exit(0);
    });
});
