<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config/db.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    $dashboard_url = 'dashboard.php';
    if (isset($_SESSION['role'])) {
        if (strcasecmp($_SESSION['role'], 'Admin') === 0) {
            $dashboard_url = 'admin_dashboard.php';
        } elseif (strcasecmp($_SESSION['role'], 'Faculty') === 0) {
            $dashboard_url = 'faculty_dashboard.php';
        }
    }
    header("Location: " . $dashboard_url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Prepare statement to prevent SQL injection - Restrict to Student
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND role = 'Student'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Password correct
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;

                // Redirect to Student Dashboard (or general dashboard if same)
                $redirect_url = 'dashboard.php';

                echo "<script>alert('Login Successful! Redirecting...'); window.location.href='" . $redirect_url . "';</script>";
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No student account found with that email.";
        }
        $stmt->close();
    }
}

require 'includes/header.php';
?>

<!-- Login Section -->
<section id="login" class="section pt-24 min-h-screen"
    style="background: linear-gradient(135deg, #E0EAFC 0%, #CFDEF3 100%);">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10">
            <h2 class="text-3xl font-bold text-center mb-8 gradient-text">Student Login Portal</h2>
            <p class="text-center text-gray-600 mb-10">Log in to your AutoTime account</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline">
                        <?php echo $error; ?>
                    </span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="login-email">Email Address</label>
                    <input type="email" id="login-email" name="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="student@example.com" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="login-password">Password</label>
                    <input type="password" id="login-password" name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="••••••••" required>
                </div>
                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center">
                        <input type="checkbox" class="mr-2">
                        <span class="text-gray-600">Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="text-purple-600 hover:underline">Forgot
                        password?</a>
                </div>
                <button type="submit"
                    class="w-full btn-gradient text-white py-4 rounded-lg font-semibold shadow-lg mb-6">
                    Log In
                </button>
            </form>

            <div class="my-6 text-center text-gray-500">or</div>
            <a href="google-login.php" class="google-btn">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"
                    alt="Google logo">
                <span>Sign in with Google</span>
            </a>

            <p class="text-center mt-8 text-gray-600">
                Don't have an account? <a href="register.php"
                    class="text-purple-600 font-semibold hover:underline">Register here</a>
            </p>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('login-email');
        const passwordInput = document.getElementById('login-password');

        function validateField(input, condition, errorMessage) {
            let errorSpan = input.parentNode.querySelector('.validation-msg');
            if (!errorSpan) {
                errorSpan = document.createElement('span');
                errorSpan.className = 'validation-msg text-xs mt-1 block';
                input.parentNode.appendChild(errorSpan);
            }

            if (condition) {
                input.classList.remove('border-red-500');
                input.classList.add('border-green-500');
                errorSpan.textContent = '';
                return true;
            } else {
                input.classList.remove('border-green-500');
                input.classList.add('border-red-500');
                errorSpan.textContent = errorMessage;
                errorSpan.style.color = '#ef4444';
                return false;
            }
        }

        emailInput.addEventListener('input', function () {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            validateField(emailInput, emailRegex.test(emailInput.value), 'Please enter a valid email address.');
        });

        passwordInput.addEventListener('input', function () {
            validateField(passwordInput, passwordInput.value.length >= 1, 'Password is required.');
        });
    });
</script>

<?php require 'includes/footer.php'; ?>