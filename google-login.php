<?php
session_start();
if (!file_exists('config/google_auth.php')) {
    die("Google Auth configuration missing. Please create config/google_auth.php with your credentials.");
}
require 'config/google_auth.php';

// Generate a random state for security
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

$params = [
    'response_type' => 'code',
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'scope' => 'email profile openid',
    'state' => $_SESSION['oauth_state'],
    'access_type' => 'offline',
    'prompt' => 'select_account'
];

$authUrl = GOOGLE_OAUTH_URL . '?' . http_build_query($params);

header('Location: ' . $authUrl);
exit();
?>