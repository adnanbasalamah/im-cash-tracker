<?php
require_once 'db.php';
requireLogin();

$success = '';
$error = '';

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cashier = $_POST['cashier'] ?? '';
    $rp100k = intval($_POST['rp100k'] ?? 0);
    $rp50k = intval($_POST['rp50k'] ?? 0);
    $rp20k = intval($_POST['rp20k'] ?? 0);
    $rp10k = intval($_POST['rp10k'] ?? 0);
    $rp5k = intval($_POST['rp5k'] ?? 0);
    $rp2k = intval($_POST['rp2k'] ?? 0);
    $rp1k = intval($_POST['rp1k'] ?? 0);
    $coin_total = intval($_POST['coin_total'] ?? 0);

    $total_kutipan = ($rp100k * 100000) + ($rp50k * 50000);
    $total_di_kasir = ($rp20k * 20000) + ($rp10k * 10000) + ($rp5k * 5000) + ($rp2k * 2000) + ($rp1k * 1000) + $coin_total;

    if ($cashier === '') {
        $error = 'Pilih kasir terlebih dahulu.';
    } else {
        $now = new DateTime();
        $record_date = $now->format('Y-m-d');
        $record_time = $now->format('H:i:s');

        $stmt = $pdo->prepare('INSERT INTO ospos_cash_records (person_id, cashier, record_date, record_time, rp100k, rp50k, rp20k, rp10k, rp5k, rp2k, rp1k, coin_total, total_kutipan, total_di_kasir) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $cashier,
            $record_date,
            $record_time,
            $rp100k,
            $rp50k,
            $rp20k,
            $rp10k,
            $rp5k,
            $rp2k,
            $rp1k,
            $coin_total,
            $total_kutipan,
            $total_di_kasir,
        ]);

        if ($result) {
            $_SESSION['flash_success'] = 'Pencatatan kas berhasil disimpan!';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Gagal menyimpan data. Silakan coba lagi.';
        }
    }
}

$userName = $_SESSION['user_name'] ?? 'User';
$userInitial = strtoupper(mb_substr($userName, 0, 1));

$months_id = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$dateDisplay = $now->format('j') . '-' . $months_id[intval($now->format('n'))] . ', ' . $now->format('H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | IM Cek Kasir</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .toast {
            animation: slideIn 0.3s ease-out;
        }
        .toast.fade-out {
            animation: fadeOut 0.4s ease-in forwards;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; display: none; }
        }
    </style>
</head>
<body class="bg-background text-on-surface font-body-main">
    <?php if ($success): ?>
    <div class="toast fixed top-4 left-4 right-4 z-[100] bg-success text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 font-data-entry text-data-entry">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="fixed top-4 left-4 right-4 z-[100] bg-error text-on-error px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 font-data-entry text-data-entry">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">error</span>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <header class="bg-surface sticky top-0 z-50 shadow-sm flex justify-between items-center px-container-padding h-input-height w-full border-b border-outline-variant">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">storefront</span>
            <h1 class="font-display text-display text-primary">IM Cek Kasir</h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="font-label-caps text-label-caps text-on-surface-variant"><?php echo htmlspecialchars($userName); ?></p>
                <p class="font-caption text-caption text-outline">Petugas</p>
            </div>
            <a href="logout.php" class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-sm relative group" title="Logout">
                <?php echo $userInitial; ?>
            </a>
        </div>
    </header>

    <main class="max-w-md mx-auto p-container-padding pb-24">
        <form method="POST" id="cashForm">
            <div class="bg-surface-container-lowest rounded-xl p-4 shadow-sm border border-outline-variant mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="font-label-caps text-label-caps text-on-surface-variant block mb-1">Tanggal &amp; Jam</label>
                        <div class="flex items-center gap-2 text-on-surface">
                            <span class="material-symbols-outlined text-outline text-[20px]">calendar_today</span>
                            <span class="font-header-section text-header-section"><?php echo $dateDisplay; ?></span>
                        </div>
                    </div>
                    <div>
                        <label class="font-label-caps text-label-caps text-on-surface-variant block mb-1">Kasir</label>
                        <div class="flex items-center gap-2 text-on-surface">
                            <span class="material-symbols-outlined text-outline text-[20px]">person</span>
                            <select name="cashier" class="bg-transparent border-none p-0 font-header-section text-header-section focus:ring-0 cursor-pointer outline-none w-full min-h-[24px]" required>
                                <option value="Kasir 1" selected>Kasir 1</option>
                                <option value="Kasir 2">Kasir 2</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-header-section text-header-section flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
                        Bagian 1: Kutipan Tunai
                    </h2>
                    <span class="bg-primary-container text-white px-3 py-1 rounded-full font-label-caps text-label-caps">BESAR</span>
                </div>
                <div class="space-y-4">
                    <div class="bg-surface-container rounded-xl p-4 border border-outline-variant">
                        <div class="flex justify-between items-center mb-3">
                            <label class="font-data-entry text-data-entry">Pecahan Rp 100.000</label>
                            <span class="text-on-surface-variant font-caption text-caption">Jumlah Lembar</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-grow relative">
                                <input class="w-full h-input-height bg-surface border-2 border-outline-variant focus:border-primary rounded-lg px-4 font-display text-display text-right outline-none transition-all" id="rp100k" name="rp100k" placeholder="0" type="number" min="0" value="" oninput="calculate()">
                            </div>
                        </div>
                    </div>
                    <div class="bg-surface-container rounded-xl p-4 border border-outline-variant">
                        <div class="flex justify-between items-center mb-3">
                            <label class="font-data-entry text-data-entry">Pecahan Rp 50.000</label>
                            <span class="text-on-surface-variant font-caption text-caption">Jumlah Lembar</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-grow relative">
                                <input class="w-full h-input-height bg-surface border-2 border-outline-variant focus:border-primary rounded-lg px-4 font-display text-display text-right outline-none transition-all" id="rp50k" name="rp50k" placeholder="0" type="number" min="0" value="" oninput="calculate()">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-primary text-on-primary rounded-xl shadow-md">
                        <span class="font-label-caps text-label-caps uppercase tracking-widest">Total Kutipan</span>
                        <span class="font-display text-display" id="totalKutipan">Rp 0</span>
                    </div>
                </div>
            </section>

            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-header-section text-header-section flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">point_of_sale</span>
                        Bagian 2: Uang Di Kasir
                    </h2>
                    <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-caps text-label-caps">KECIL / KOIN</span>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="bg-white border border-outline-variant rounded-lg p-3 flex items-center justify-between">
                            <span class="font-data-entry text-data-entry">Rp 20.000</span>
                            <input class="w-24 h-touch-target-min border-b-2 border-outline-variant focus:border-primary text-right font-header-section outline-none" id="rp20k" name="rp20k" placeholder="0" type="number" min="0" oninput="calculate()">
                        </div>
                        <div class="bg-white border border-outline-variant rounded-lg p-3 flex items-center justify-between">
                            <span class="font-data-entry text-data-entry">Rp 10.000</span>
                            <input class="w-24 h-touch-target-min border-b-2 border-outline-variant focus:border-primary text-right font-header-section outline-none" id="rp10k" name="rp10k" placeholder="0" type="number" min="0" oninput="calculate()">
                        </div>
                        <div class="bg-white border border-outline-variant rounded-lg p-3 flex items-center justify-between">
                            <span class="font-data-entry text-data-entry">Rp 5.000</span>
                            <input class="w-24 h-touch-target-min border-b-2 border-outline-variant focus:border-primary text-right font-header-section outline-none" id="rp5k" name="rp5k" placeholder="0" type="number" min="0" oninput="calculate()">
                        </div>
                        <div class="bg-white border border-outline-variant rounded-lg p-3 flex items-center justify-between">
                            <span class="font-data-entry text-data-entry">Rp 2.000</span>
                            <input class="w-24 h-touch-target-min border-b-2 border-outline-variant focus:border-primary text-right font-header-section outline-none" id="rp2k" name="rp2k" placeholder="0" type="number" min="0" oninput="calculate()">
                        </div>
                        <div class="bg-white border border-outline-variant rounded-lg p-3 flex items-center justify-between">
                            <span class="font-data-entry text-data-entry">Rp 1.000</span>
                            <input class="w-24 h-touch-target-min border-b-2 border-outline-variant focus:border-primary text-right font-header-section outline-none" id="rp1k" name="rp1k" placeholder="0" type="number" min="0" oninput="calculate()">
                        </div>
                    </div>
                    <div class="bg-surface-container-high rounded-xl p-4 border-2 border-dashed border-outline">
                        <div class="flex justify-between items-center mb-2">
                            <label class="font-header-section text-header-section flex items-center gap-2">
                                <span class="material-symbols-outlined">savings</span>
                                Total Koin
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-display text-display text-outline">Rp</span>
                            <input class="w-full h-input-height bg-white border-2 border-outline-variant focus:border-primary rounded-lg pl-14 pr-4 font-display text-display text-right outline-none" id="coin_total" name="coin_total" placeholder="0" type="number" min="0" oninput="calculate()">
                        </div>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-secondary text-on-secondary rounded-xl shadow-md mt-4">
                        <span class="font-label-caps text-label-caps uppercase tracking-widest">Total Di Kasir</span>
                        <span class="font-display text-display" id="totalDiKasir">Rp 0</span>
                    </div>
                </div>
            </section>

            <button class="w-full bg-primary text-on-primary h-touch-target-min rounded-xl font-header-section shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2" type="submit">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                Submit Pencatatan Kas
            </button>
        </form>
    </main>

    <script>
        function getVal(id) {
            var el = document.getElementById(id);
            return el ? parseInt(el.value) || 0 : 0;
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function calculate() {
            var rp100k = getVal('rp100k');
            var rp50k = getVal('rp50k');
            var rp20k = getVal('rp20k');
            var rp10k = getVal('rp10k');
            var rp5k = getVal('rp5k');
            var rp2k = getVal('rp2k');
            var rp1k = getVal('rp1k');
            var coinTotal = getVal('coin_total');

            var totalKutipan = (rp100k * 100000) + (rp50k * 50000);
            var totalDiKasir = (rp20k * 20000) + (rp10k * 10000) + (rp5k * 5000) + (rp2k * 2000) + (rp1k * 1000) + coinTotal;

            document.getElementById('totalKutipan').textContent = formatRupiah(totalKutipan);
            document.getElementById('totalDiKasir').textContent = formatRupiah(totalDiKasir);
        }

        calculate();

        var toast = document.querySelector('.toast');
        if (toast) {
            setTimeout(function() {
                toast.classList.add('fade-out');
                toast.addEventListener('animationend', function() {
                    toast.remove();
                });
            }, 3000);
        }
    </script>
</body>
</html>