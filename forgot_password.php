<?php
require 'config/db.php';
require 'includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    // No validation or actual logic required beyond showing the message for this task
    $message = "the password can be reset successfully........";
}
?>

<section id="forgot-password" class="section pt-24 min-h-screen bg-gradient-to-br from-gray-50 to-purple-50">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10">
            <h2 class="text-3xl font-bold text-center mb-8 gradient-text">Reset Password</h2>
            <p class="text-center text-gray-600 mb-10">Enter your email to reset your password</p>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"
                    role="alert">
                    <span class="block sm:inline"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="reset-email">Email Address</label>
                    <input type="email" id="reset-email" name="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="admin@example.com" required>
                </div>

                <button type="submit"
                    class="w-full btn-gradient text-white py-4 rounded-lg font-semibold shadow-lg mb-6">
                    Reset Password
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