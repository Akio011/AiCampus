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
    'super_admin' => ['index.php','devices.php','lost-found.php','capstone.php','community.php','users.php','post-item.php'],
    'admin'       => ['index.php','devices.php','lost-found.php','capstone.php','community.php','post-item.php'],
    'faculty'     => ['index.php','devices.php','lost-found.php','capstone.php','community.php','post-item.php'],
    'staff'       => ['devices.php','post-item.php'],
    'student'     => ['lost-found.php','capstone.php'],
];

// Default landing page per role
$defaultPage = [
    'super_admin' => '/index.php',
    'admin'       => '/index.php',
    'faculty'     => '/index.php',
    'staff'       => '/devices.php',
    'student'     => '/lost-found.php',
];

$allowed = $allowedPages[$role] ?? ['lost-found.php'];

if (!in_array($page, $allowed)) {
    header('Location: ' . ($defaultPage[$role] ?? '/lost-found.php'));
    exit;
}

function isSuperAdmin(): bool {
    global $authUser;
    return $authUser['role'] === 'super_admin';
}
function isAdmin(): bool {
    global $authUser;
    return in_array($authUser['role'], ['super_admin', 'admin']);
}
function isStaff(): bool {
    global $authUser;
    return in_array($authUser['role'], ['super_admin', 'admin', 'faculty', 'staff']);
}
function requireAdmin(): void {
    global $authUser;
    if (!isAdmin()) {
        header('Location: /index.php'); exit;
    }
}
function requireSuperAdmin(): void {
    if (!isSuperAdmin()) {
        header('Location: /index.php'); exit;
    }
}
function requireStaff(): void {
    if (!isStaff()) {
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}
