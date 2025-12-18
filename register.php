<?php
require 'config/db.php';
require 'includes/header.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $existing_name, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Correct credentials - Auto Login
                $_SESSION['user_id'] = $id;
                $_SESSION['name'] = $existing_name;
                echo "<script>alert('Account already exists. Logging you in...'); window.location.href='index.php';</script>";
                exit();
            } else {
                $error = "Email already registered.";
            }
        } else {
            // New user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                echo "<script>alert('Registration Successful! Redirecting to Landing Page...'); window.location.href='index.php';</script>";
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<!-- Registration Section -->
<section id="register" class="section pt-24 min-h-screen bg-gradient-to-br from-gray-50 to-purple-50">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10">
            <h2 class="text-3xl font-bold text-center mb-8 gradient-text">Create Account</h2>
            <p class="text-center text-gray-600 mb-10">Sign up to start generating timetables</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"
                    role="alert">
                    <span class="block sm:inline"><?php echo $success; ?> <a href="login.php"
                            class="font-bold underline">Login here</a>.</span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="reg-name">Full Name</label>
                    <input type="text" id="reg-name" name="name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="John Doe" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="reg-email">Email Address</label>
                    <input type="email" id="reg-email" name="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="admin@example.com" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="••••••••" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="reg-confirm">Confirm Password</label>
                    <input type="password" id="reg-confirm" name="confirm"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="••••••••" required>
                </div>
                <div class="mb-8">
                    <label class="flex items-center">
                        <input type="checkbox" id="reg-terms" class="mr-2" required>
                        <span class="text-gray-600 text-sm">I agree to the <a href="#"
                                class="text-purple-600 hover:underline">Terms of Service</a> and <a href="#"
                                class="text-purple-600 hover:underline">Privacy Policy</a></span>
                    </label>
                </div>
                <button type="submit" class="w-full btn-gradient text-white py-4 rounded-lg font-semibold shadow-lg">
                    Register
                </button>
            </form>
            <p class="text-center mt-8 text-gray-600">
                Already have an account? <a href="login.php" class="text-purple-600 font-semibold hover:underline">Log
                    in here</a>
            </p>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>