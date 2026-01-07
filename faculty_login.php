<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config/db.php';

$error = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'Faculty') === 0) {
    header("Location: faculty_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ? AND role = 'Faculty'");
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
                echo "<script>alert('Faculty login successful'); window.location.href='faculty_dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Invalid email or password');</script>";
                $error = "Invalid email or password";
            }
        } else {
            echo "<script>alert('Invalid email or password');</script>";
            $error = "Invalid email or password";
        }
        $stmt->close();
    }
}

require 'includes/header.php';
?>

<!-- Faculty Login Section -->
<section id="faculty-login" class="section pt-24 min-h-screen"
    style="background: linear-gradient(120deg, #84fab0 0%, #8fd3f4 100%);">
    <div class="max-w-md mx-auto mt-12">
        <div class="card p-10 bg-white shadow-2xl">
            <h2 class="text-3xl font-bold text-center mb-4 gradient-text">Faculty Portal</h2>
            <p class="text-center text-gray-600 mb-10">Access your timetable and management tools</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form action="faculty_login.php" method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="faculty-email">Faculty Email</label>
                    <input type="email" id="faculty-email" name="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600 transition"
                        placeholder="faculty@college.edu" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="faculty-password">Password</label>
                    <input type="password" id="faculty-password" name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600 transition"
                        placeholder="••••••••" required>
                </div>
                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-4 rounded-lg font-semibold shadow-lg hover:bg-indigo-700 transition mb-6">
                    Log In as Faculty
                </button>
            </form>

            <p class="text-center mt-8 text-gray-600 text-sm">
                Need to register? <a href="register.php" class="text-indigo-600 font-semibold hover:underline">Go to
                    Registration</a>
            </p>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>