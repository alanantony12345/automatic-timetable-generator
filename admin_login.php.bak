<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config/db.php';

$error = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'Admin') === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND role = 'Admin'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password) || $password === $hashed_password) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;
                echo "<script>alert('Admin login successful'); window.location.href='admin_dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Login failed');</script>";
                $error = "Login failed";
            }
        } else {
            echo "<script>alert('Login failed');</script>";
            $error = "Login failed";
        }
        $stmt->close();
    }
}

require 'includes/header.php';
?>

<!-- Admin Login Section -->
<section id="admin-login" class="section pt-24 min-h-screen"
    style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10 bg-white shadow-2xl">
            <h2 class="text-3xl font-bold text-center mb-4 gradient-text">Admin Portal</h2>
            <p class="text-center text-gray-600 mb-10">Access the administrative dashboard</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form action="admin_login.php" method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="admin-email">Admin Email</label>
                    <input type="email" id="admin-email" name="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="admin@college.edu" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="admin-password">Password</label>
                    <input type="password" id="admin-password" name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="••••••••" required>
                </div>
                <button type="submit"
                    class="w-full bg-gray-900 text-white py-4 rounded-lg font-semibold shadow-lg hover:bg-black transition mb-6">
                    Log In as Admin
                </button>
            </form>

            <p class="text-center mt-8 text-gray-600 text-sm">
                Need to register? <a href="register.php" class="text-purple-600 font-semibold hover:underline">Go to
                    Registration</a>
            </p>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailInput = document.getElementById('admin-email');
        const passwordInput = document.getElementById('admin-password');

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
                errorSpan.style.color = '#10b981';
            } else {
                input.classList.remove('border-green-500');
                input.classList.add('border-red-500');
                errorSpan.textContent = errorMessage;
                errorSpan.style.color = '#ef4444';
            }
        }

        emailInput.addEventListener('input', function () {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            validateField(emailInput, emailRegex.test(emailInput.value), 'Please enter a valid admin email.');
        });

        passwordInput.addEventListener('input', function () {
            validateField(passwordInput, passwordInput.value.length >= 1, 'Admin password is required.');
        });
    });
</script>

<?php require 'includes/footer.php'; ?>