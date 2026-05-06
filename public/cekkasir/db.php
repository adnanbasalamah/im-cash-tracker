<?php
$DB_HOST = 'localhost';
$DB_NAME = 'ospos';
$DB_USER = 'ospos';
$DB_PASS = 'ospos';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

session_start();

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function verifyOposPassword($password, $hash, $hashVersion)
{
    if ($hashVersion == 2) {
        return password_verify($password, $hash);
    } else {
        return md5($password) === $hash;
    }
}

function loginUser($personId, $name, $remember = false)
{
    global $pdo;
    $_SESSION['user_id'] = $personId;
    $_SESSION['user_name'] = $name;

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        try {
            $stmt = $pdo->prepare('UPDATE ospos_employees SET remember_token = ?, remember_expires = ? WHERE person_id = ?');
            $stmt->execute([$tokenHash, $expires, $personId]);
        } catch (PDOException $e) {
        }

        setcookie('remember_token', $token, [
            'expires' => time() + 7 * 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function checkRememberMe()
{
    global $pdo;

    if (isset($_SESSION['user_id'])) {
        return;
    }

    if (!isset($_COOKIE['remember_token'])) {
        return;
    }

    $token = $_COOKIE['remember_token'];
    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare('SELECT e.person_id, p.first_name, p.last_name FROM ospos_employees e JOIN ospos_people p ON e.person_id = p.person_id WHERE e.remember_token = ? AND e.remember_expires > NOW() AND e.deleted = 0');
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        $user = false;
    }

    if ($user) {
        $_SESSION['user_id'] = $user['person_id'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
    } else {
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function logoutUser()
{
    global $pdo;

    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare('UPDATE ospos_employees SET remember_token = NULL, remember_expires = NULL WHERE person_id = ?');
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
        }
    }

    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_destroy();
    header('Location: index.php');
    exit;
}

function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

checkRememberMe();