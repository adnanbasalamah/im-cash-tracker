<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $error = 'Username dan password harus diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT e.person_id, e.password, e.hash_version, p.first_name, p.last_name FROM ospos_employees e JOIN ospos_people p ON e.person_id = p.person_id WHERE e.username = ? AND e.deleted = 0');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && verifyOposPassword($password, $user['password'], $user['hash_version'])) {
            $name = trim($user['first_name'] . ' ' . $user['last_name']);
            loginUser($user['person_id'], $name, $remember);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IM Cek Kasir</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen">
    <header class="flex justify-between items-center px-container-padding h-input-height w-full bg-surface border-b border-outline-variant shadow-sm sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">storefront</span>
            <h1 class="font-display text-display text-primary">IM Cek Kasir</h1>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-container-padding py-8">
        <div class="w-full max-w-md bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm p-6">
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 bg-primary-fixed rounded-full flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings: 'FILL' 1;">account_circle</span>
                </div>
                <h2 class="font-header-section text-header-section text-on-surface">Login Petugas</h2>
                <p class="font-caption text-caption text-on-surface-variant">Aplikasi pencatatan kas kasir minimarket</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-4 p-3 bg-error-container rounded-lg text-on-error-container font-data-entry text-data-entry flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="space-y-1">
                    <label class="font-label-caps text-label-caps text-on-surface-variant block ml-1" for="username">Staff ID / Username</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">person</span>
                        <input class="w-full h-input-height pl-12 pr-4 bg-surface border-outline-variant border-2 rounded-lg font-data-entry text-data-entry focus:border-primary-container focus:ring-0 transition-all outline-none" id="username" name="username" placeholder="Enter your ID" type="text" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="font-label-caps text-label-caps text-on-surface-variant block ml-1" for="password">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                        <input class="w-full h-input-height pl-12 pr-12 bg-surface border-outline-variant border-2 rounded-lg font-data-entry text-data-entry focus:border-primary-container focus:ring-0 transition-all outline-none" id="password" name="password" placeholder="••••••••" type="password" required>
                        <button class="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-primary" type="button" onclick="togglePassword()">
                            <span class="material-symbols-outlined" id="toggle-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input class="w-5 h-5 rounded border-outline-variant text-primary-container focus:ring-primary" id="remember" name="remember" type="checkbox" value="1">
                    <label class="font-caption text-caption text-on-surface" for="remember">Keep me logged in</label>
                </div>

                <button class="w-full h-input-height bg-primary-container text-on-primary font-header-section text-header-section rounded-lg shadow-sm hover:brightness-110 active:scale-[0.98] transition-all flex items-center justify-center gap-2" type="submit">
                    <span>Login</span>
                    <span class="material-symbols-outlined">login</span>
                </button>
            </form>

            <div class="mt-10 pt-6 border-t border-outline-variant flex flex-col items-center gap-4">
                <p class="font-caption text-caption text-on-surface-variant">Trusted retail management system</p>
                <div class="flex gap-4">
                    <div class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px] text-tertiary" style="font-variation-settings: 'FILL' 1;">verified_user</span>
                        <span class="font-caption text-[10px] text-tertiary uppercase tracking-widest font-bold">Secure Access</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var icon = document.getElementById('toggle-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
</body>
</html>