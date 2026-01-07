<?php
require 'config/db.php';
require 'includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close(); // Close previous statement

        // Generate Secure Token
        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        // Save to password_reset_tokens table
        $insertStmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())");
        $insertStmt->bind_param("iss", $user_id, $token_hash, $expiry);
        $insertStmt->execute();
        $insertStmt->close();

        // Create Token Link
        $resetLink = "http://localhost/autotimetable/reset_password.php?token=" . $token;
        $emailContent = "Hello,\n\nRequest for Password Reset received.\n\nClick here to reset your password:\n$resetLink\n\nThis link expires in 30 minutes.";

        // Log to file is disabled, now sending real email
        // file_put_contents("email_log.txt", "To: $email\nSubject: Password Reset\n$emailContent\n\n", FILE_APPEND);

        // Send real email using SimpleSMTP
        require_once 'includes/SimpleSMTP.php';
        require_once 'config/mail_config.php';

        $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, str_replace(' ', '', SMTP_PASS));

        if ($smtp->send($email, "Password Reset", $emailContent, "AutoTimetable Support")) {
            $message = "<b>Email Sent!</b><br>Please check your inbox (and spam folder) for the reset link.";
        } else {
            // Fallback for development/SMTP failure
            file_put_contents("email_log.txt", "To: $email\nSubject: Password Reset\n$emailContent\n\n", FILE_APPEND);
            $message = "<b>Simulation Successful!</b><br>Email failed: " . htmlspecialchars($smtp->error) . "<br>Since SMTP failed, I saved the reset link to <code>email_log.txt</code>.<br>Open that file to reset your password.";
        }
    } else {
        $error = "Email address not found.";
    }
}
?>

<section id="forgot-password" class="section pt-24 min-h-screen bg-gradient-to-br from-gray-50 to-purple-50">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10">
            <h2 class="text-3xl font-bold text-center mb-8 gradient-text">Reset Password</h2>
            <p class="text-center text-gray-600 mb-10">Enter your registered email address</p>

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

            <form action="forgot_password.php" method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="reset-email">Enter your registered email
                        address</label>
                    <input type="email" id="reset-email" name="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="alanantony2028@mca.ajce.in" required>
                </div>

                <button type="submit"
                    class="w-full btn-gradient text-white py-4 rounded-lg font-semibold shadow-lg mb-6">
                    Send Reset Link
                </button>
            </form>

            <p class="text-center mt-4 text-gray-600">
                Remember your password? <a href="login.php" class="text-purple-600 font-semibold hover:underline">Log in
                    here</a>
            </p>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>