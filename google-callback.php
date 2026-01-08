<?php
session_start();
require __DIR__ . '/config/db.php';
require 'config/google_auth.php';

if (!isset($_GET['code'])) {
    header('Location: login.php?error=Access Denied');
    exit();
}

// Exchange authorization code for access token
$code = $_GET['code'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Disable SSL verification for localhost dev environment if needed (Try to avoid in production)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$tokenData = json_decode($response, true);
curl_close($ch);

if (isset($tokenData['error'])) {
    header('Location: login.php?error=Google Login Failed: ' . $tokenData['error_description']);
    exit();
}

$accessToken = $tokenData['access_token'];

// Get User Info
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, GOOGLE_USERINFO_URL . '?access_token=' . $accessToken);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$googleUser = json_decode($response, true);
curl_close($ch);

if (isset($googleUser['error'])) {
    header('Location: login.php?error=Failed to get user info');
    exit();
}

$google_id = $googleUser['id'];
$email = $googleUser['email'];
$name = $googleUser['name'];
$verified_email = $googleUser['verified_email'];

// Check if user exists
$stmt = $conn->prepare("SELECT id, name, email, google_id FROM users WHERE google_id = ? OR email = ?");
$stmt->bind_param("ss", $google_id, $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // User exists
    $user_id = $user['id'];

    // Update google_id if it was mapped by email only (first time google login)
    if (empty($user['google_id'])) {
        $updateStmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $updateStmt->bind_param("si", $google_id, $user_id);
        $updateStmt->execute();
        $updateStmt->close();
    }

    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $user['name']; // Use DB name or Google Name? DB name preferred.
} else {
    // New User - Register them
    // Create random password
    $random_password = bin2hex(random_bytes(10));
    $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

    $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, google_id) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $name, $email, $hashed_password, $google_id);

    if ($insertStmt->execute()) {
        $_SESSION['user_id'] = $insertStmt->insert_id;
        $_SESSION['user_name'] = $name;
    } else {
        header('Location: login.php?error= Registration Failed');
        exit();
    }
    $insertStmt->close();
}

header("Location: index.php");
exit();
?>