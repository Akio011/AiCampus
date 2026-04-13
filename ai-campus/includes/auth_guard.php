<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user'])) {
    $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST']
        . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\')
        . '/auth/login.php';
    header('Location: ' . $loginUrl);
    exit;
}

$authUser = $_SESSION['user'];
$role     = $authUser['role'];
$page     = basename($_SERVER['PHP_SELF']);

// Pages each role can access
$allowedPages = [
    'admin'   => ['index.php','devices.php','lost-found.php','capstone.php','community.php'],
    'faculty' => ['index.php','devices.php','lost-found.php','capstone.php','community.php'],
    'staff'   => ['devices.php'],
    'student' => ['lost-found.php','capstone.php'],
];

// Default landing page per role
$defaultPage = [
    'admin'   => '/index.php',
    'faculty' => '/index.php',
    'staff'   => '/devices.php',
    'student' => '/lost-found.php',
];

$allowed = $allowedPages[$role] ?? ['lost-found.php'];

if (!in_array($page, $allowed)) {
    header('Location: ' . ($defaultPage[$role] ?? '/lost-found.php'));
    exit;
}

function isStaff(): bool {
    global $authUser;
    return in_array($authUser['role'], ['admin', 'faculty', 'staff']);
}
function isAdmin(): bool {
    global $authUser;
    return $authUser['role'] === 'admin';
}
function requireStaff(): void {
    if (!isStaff()) {
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}
