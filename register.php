<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config/db.php';

$error = '';
$success = '';

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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $register_number = $_POST['register_number'] ?? '';
    $department = $_POST['department'] ?? '';
    $course = $_POST['course'] ?? '';
    $year = $_POST['year'] ?? '';
    $semester = $_POST['semester'] ?? '';
    $section = $_POST['section'] ?? '';
    $batch = $_POST['batch'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $admission_type = $_POST['admission_type'] ?? '';
    $role = 'Student';

    if (empty($name) || empty($email) || empty($password) || empty($confirm) || empty($student_id) || empty($register_number) || empty($gender) || empty($mobile) || empty($dob) || empty($admission_type)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $existing_name, $hashed_password, $existing_role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Correct credentials - Auto Login (Smart Registration)
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $existing_name;
                $_SESSION['role'] = $existing_role;

                $redirect_url = 'dashboard.php';
                if (strcasecmp($existing_role, 'Admin') === 0)
                    $redirect_url = 'admin_dashboard.php';
                elseif (strcasecmp($existing_role, 'Faculty') === 0)
                    $redirect_url = 'faculty_dashboard.php';

                echo "<script>alert('Account already exists. Logging you in...'); window.location.href='" . $redirect_url . "';</script>";
                exit();
            } else {
                $error = "Email already registered.";
            }
        } else {
            // New user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, student_id, register_number, department, course, year, semester, section, batch, gender, mobile, dob, admission_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssiissssss", $name, $email, $hashed_password, $role, $student_id, $register_number, $department, $course, $year, $semester, $section, $batch, $gender, $mobile, $dob, $admission_type);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;

                echo "<script>alert('Registration Successful! Welcome.'); window.location.href='dashboard.php';</script>";
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

require 'includes/header.php';
?>

<!-- Registration Section -->
<section id="register" class="section pt-24 min-h-screen"
    style="background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);">
    <div class="max-w-2xl mx-auto mt-12 mb-12">
        <div class="card p-10 bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl">
            <h2 class="text-3xl font-bold text-center mb-8 gradient-text">Student Register Portal</h2>
            <p class="text-center text-gray-600 mb-10">Sign up to start generating timetables</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-student-id">Student ID</label>
                        <input type="text" id="reg-student-id" name="student_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="S12345" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-name">Full Name</label>
                        <input type="text" id="reg-name" name="name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="John Doe" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-email">Email Address</label>
                        <input type="email" id="reg-email" name="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="student@example.com" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-reg-no">Register Number</label>
                        <input type="text" id="reg-reg-no" name="register_number"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="2023CSE001" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-gender">Gender</label>
                        <select id="reg-gender" name="gender"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            required>
                            <option value="" disabled selected>Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-dob">Date of Birth</label>
                        <input type="date" id="reg-dob" name="dob"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-mobile">Mobile Number</label>
                        <input type="tel" id="reg-mobile" name="mobile"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="1234567890" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-admission-type">Admission
                            Type</label>
                        <select id="reg-admission-type" name="admission_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            required>
                            <option value="" disabled selected>Select Admission Type</option>
                            <option value="Government Quota">Government Quota</option>
                            <option value="Management Quota">Management Quota</option>
                            <option value="NRI Quota">NRI Quota</option>
                            <option value="Lateral Entry">Lateral Entry</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-password">Password</label>
                        <input type="password" id="reg-password" name="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="••••••••" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-confirm">Confirm Password</label>
                        <input type="password" id="reg-confirm" name="confirm"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="••••••••" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-dept">Department</label>
                        <input type="text" id="reg-dept" name="department"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="CSE" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-course">Course</label>
                        <input type="text" id="reg-course" name="course"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="B.Tech" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-year">Year</label>
                        <input type="number" id="reg-year" name="year"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="2" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-sem">Semester</label>
                        <input type="number" id="reg-sem" name="semester"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="4" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-1" for="reg-section">Section</label>
                        <input type="text" id="reg-section" name="section"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                            placeholder="A" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-1" for="reg-batch">Batch</label>
                    <input type="text" id="reg-batch" name="batch"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-600 transition"
                        placeholder="2022-2026" required>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fields = {
            'reg-name': {
                validate: (v) => v.length >= 2,
                msg: 'Name must be at least 2 characters.'
            },
            'reg-email': {
                validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
                msg: 'Please enter a valid email address.'
            },
            'reg-mobile': {
                validate: (v) => /^[0-9]{10}$/.test(v),
                msg: 'Mobile number must be exactly 10 digits.'
            },
            'reg-password': {
                validate: (v) => v.length >= 6,
                msg: 'Password must be at least 6 characters.'
            },
            'reg-confirm': {
                validate: (v) => v === document.getElementById('reg-password').value,
                msg: 'Passwords do not match.'
            },
            'reg-student-id': { validate: (v) => v.trim().length > 0, msg: 'Required' },
            'reg-reg-no': { validate: (v) => v.trim().length > 0, msg: 'Required' },
            'reg-gender': { validate: (v) => v !== "" && v !== null, msg: 'Please select gender.' },
            'reg-dob': {
                validate: (v) => {
                    const d = new Date(v);
                    return d instanceof Date && !isNaN(d) && d < new Date();
                },
                msg: 'Please enter a valid birth date.'
            },
            'reg-admission-type': { validate: (v) => v !== "" && v !== null, msg: 'Please select admission type.' },
            'reg-dept': { validate: (v) => v.trim().length > 0, msg: 'Required' },
            'reg-course': { validate: (v) => v.trim().length > 0, msg: 'Required' },
            'reg-year': { validate: (v) => v >= 1 && v <= 5, msg: 'Year must be 1-5' },
            'reg-sem': { validate: (v) => v >= 1 && v <= 8, msg: 'Semester must be 1-8' },
            'reg-section': { validate: (v) => v.trim().length > 0, msg: 'Required' },
            'reg-batch': { validate: (v) => v.trim().length > 0, msg: 'Required' },
            'reg-terms': { validate: (v, el) => el.checked, msg: 'You must agree to terms.' }
        };

        function updateValidation(id) {
            const input = document.getElementById(id);
            if (!input) return;

            const val = input.value;
            const cfg = fields[id];
            let msgSpan = input.parentNode.querySelector('.validation-msg');

            if (!msgSpan) {
                msgSpan = document.createElement('span');
                msgSpan.className = 'validation-msg text-xs mt-1 block h-4';
                input.parentNode.appendChild(msgSpan);
            }

            const isValid = cfg.validate(val, input);

            if (isValid) {
                input.classList.remove('border-red-500');
                input.classList.add('border-green-500');
                msgSpan.textContent = '✓ Valid';
                msgSpan.style.color = '#10b981';
            } else {
                input.classList.remove('border-green-500');
                input.classList.add('border-red-500');
                msgSpan.textContent = cfg.msg;
                msgSpan.style.color = '#ef4444';
            }
        }

        Object.keys(fields).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const eventType = el.type === 'checkbox' ? 'change' : 'input';
                el.addEventListener(eventType, () => {
                    updateValidation(id);
                    if (id === 'reg-password') updateValidation('reg-confirm');
                });
                el.addEventListener('blur', () => updateValidation(id));
            }
        });

        // Password Strength Indicator
        const pwd = document.getElementById('reg-password');
        pwd.addEventListener('input', function () {
            let strength = 0;
            if (this.value.length >= 8) strength++;
            if (/[A-Z]/.test(this.value)) strength++;
            if (/[0-9]/.test(this.value)) strength++;
            if (/[^A-Za-z0-9]/.test(this.value)) strength++;

            let strengthText = '';
            let strengthColor = '';
            switch (strength) {
                case 0: case 1: strengthText = 'Weak'; strengthColor = '#ef4444'; break;
                case 2: strengthText = 'Medium'; strengthColor = '#f59e0b'; break;
                case 3: strengthText = 'Strong'; strengthColor = '#10b981'; break;
                case 4: strengthText = 'Very Strong'; strengthColor = '#059669'; break;
            }

            let strengthBar = document.getElementById('pwd-strength');
            if (!strengthBar) {
                strengthBar = document.createElement('div');
                strengthBar.id = 'pwd-strength';
                strengthBar.className = 'text-[10px] uppercase font-bold mt-1';
                this.parentNode.appendChild(strengthBar);
            }
            strengthBar.textContent = 'Strength: ' + strengthText;
            strengthBar.style.color = strengthColor;
        });
    });
</script>

<?php require 'includes/footer.php'; ?>