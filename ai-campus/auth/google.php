<?php
session_start();
require_once '../config/google.php';

// Generate a random state token to prevent CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'access_type'   => 'online',
    'state'         => $state,
    'prompt'        => 'select_account',
    'device_id'     => session_id(),
    'device_name'   => 'AI Campus Server',
]);

header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
exit;
