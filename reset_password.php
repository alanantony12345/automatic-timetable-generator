<?php
require __DIR__ . '/config/db.php';
require 'includes/header.php';

$message = '';
$error = '';
// Get token from URL (GET) or Form (POST)
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the token to compare
        $token_hash = hash("sha256", $token);

        // Verify Token and Expiry from password_reset_tokens table
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token_hash = ? AND used = 0");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $expiry);
            $stmt->fetch();

            if (new DateTime() > new DateTime($expiry)) {
                $error = "Token has expired.";
            } else {
                // Success: Update Password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update User Password
                $updateUser = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateUser->bind_param("si", $hashed_password, $user_id);

                if ($updateUser->execute()) {
                    // Mark token as used
                    $markUsed = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token_hash = ?");
                    $markUsed->bind_param("s", $token_hash);
                    $markUsed->execute();

                    $message = "Password reset successfully! Redirecting to login...";
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                } else {
                    $error = "Failed to update password.";
                }
            }
        } else {
            $error = "Invalid or used token.";
        }
    }
}
?>

<section id="reset-password" class="section pt-24 min-h-screen bg-gradient-to-br from-gray-50 to-purple-50">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10">
            <h2 class="text-3xl font-bold text-center mb-8 gradient-text">Reset Password</h2>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="New Password" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="Confirm Password" required>
                </div>

                <button type="submit"
                    class="w-full btn-gradient text-white py-4 rounded-lg font-semibold shadow-lg mb-6">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>