<?php
// Include at the top of every protected page
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

// Role helpers
function isStaff(): bool {
    global $authUser;
    return in_array($authUser['role'], ['admin', 'faculty', 'staff']);
}
function isAdmin(): bool {
    global $authUser;
    return $authUser['role'] === 'admin';
}
// Block action and redirect if not staff
function requireStaff(): void {
    if (!isStaff()) {
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}
