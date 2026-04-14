<?php
session_start();
require_once '../config/db.php';
require_once '../config/google.php';

// CSRF state check
if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    header('Location: login.php?error=Invalid+state+parameter'); exit;
}
unset($_SESSION['oauth_state']);

if (!empty($_GET['error'])) {
    header('Location: login.php?error=' . urlencode($_GET['error'])); exit;
}

if (empty($_GET['code'])) {
    header('Location: login.php?error=No+authorization+code'); exit;
}

// Check cURL is available
if (!function_exists('curl_init')) {
    header('Location: login.php?error=cURL+is+not+enabled+in+PHP.+Enable+extension%3Dcurl+in+php.ini'); exit;
}

// Exchange code for access token
$tokenData = http_post(GOOGLE_TOKEN_URL, [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenData['access_token'])) {
    $errMsg = $tokenData['error_description'] ?? $tokenData['error'] ?? 'Failed to get access token';
    header('Location: login.php?error=' . urlencode($errMsg)); exit;
}

// Fetch user info from Google
$userInfo = http_get(GOOGLE_USER_URL, $tokenData['access_token']);

if (empty($userInfo['email'])) {
    header('Location: login.php?error=Failed+to+get+user+info'); exit;
}

// Restrict to school domain only
$domain = substr($userInfo['email'], strpos($userInfo['email'], '@') + 1);
if ($domain !== 'lorma.edu') {
    header('Location: login.php?error=Access+denied.+Only+%40lorma.edu+accounts+are+allowed.'); exit;
}

// Upsert user in database
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$userInfo['email']]);
$user = $stmt->fetch();

$freshAvatar = $userInfo['picture'] ?? null;

// Role is managed via DB — do not override here
$assignedRole = 'student';

if (!$user) {
    $pdo->prepare("INSERT INTO users (name, email, role, avatar) VALUES (?,?,?,?)")
        ->execute([$userInfo['name'], $userInfo['email'], $assignedRole, $freshAvatar]);
    $stmt->execute([$userInfo['email']]);
    $user = $stmt->fetch();
} else {
    // Only refresh name and avatar — never override role
    $pdo->prepare("UPDATE users SET name=?, avatar=? WHERE email=?")
        ->execute([$userInfo['name'], $freshAvatar, $userInfo['email']]);
}

// Re-fetch user to get updated role
$stmt->execute([$userInfo['email']]);
$user = $stmt->fetch();

// Store user in session — use fresh Google picture URL directly
$_SESSION['user'] = [
    'id'     => $user['id'],
    'name'   => $userInfo['name'],
    'email'  => $user['email'],
    'role'   => $user['role'],
    'avatar' => $freshAvatar,
];

header('Location: ../index.php');
exit;

// ── Helpers ──────────────────────────────────────────────

function http_post(string $url, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => false,  // disabled for local dev on Windows
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}

function http_get(string $url, string $accessToken): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_SSL_VERIFYPEER => false,  // disabled for local dev on Windows
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}
