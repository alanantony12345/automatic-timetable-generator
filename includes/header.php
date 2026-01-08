<?php if (session_status() === PHP_SESSION_NONE) {
    session_start();
} ?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AutoTime - Automatic Timetable Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .card {
            background: rgb(255, 255, 255);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(66, 15, 51, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #42ae6d 0%, #764ba2 100%);
        }

        .gradient-text {
            background: linear-gradient(to right, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-gradient {
            background: linear-gradient(to right, #667eea, #764ba2);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(to right, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }

        .feature-icon {
            background: linear-gradient(135deg, #939dfb 0%, #501ca3 100%);
        }

        .step-circle {
            background: linear-gradient(135deg, #33023d, #40057b);
        }

        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 12px 16px;
            background-color: #ffffff;
            border: 1px solid #747775;
            border-radius: 100px;
            font-family: 'Roboto', 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #1f1f1f;
            text-decoration: none;
            box-shadow: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            position: relative;
        }

        .google-btn:hover {
            background-color: #f0f4f9;
            border-color: #1f1f1f;
            box-shadow: 0 1px 2px rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
        }

        .google-btn:active {
            background-color: #dfe1e5;
            box-shadow: none;
        }

        .google-btn img {
            width: 20px;
            height: 20px;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg border-b border-gray-200 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div
                    class="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-calendar-check text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">AutoTime</h1>
                    <p class="text-xs text-gray-500">Automatic Timetable Generator</p>
                </div>
            </div>
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php" class="font-medium hover:text-purple-600 transition">Home</a>
                <!-- Role Dropdown -->
                <div class="relative group">
                    <button class="flex items-center gap-1 font-medium hover:text-purple-600 transition">
                        Role <i
                            class="fas fa-chevron-down text-[10px] group-hover:rotate-180 transition-transform duration-300"></i>
                    </button>
                    <div
                        class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 overflow-hidden transform origin-top scale-95 group-hover:scale-100">
                        <a href="admin_login.php"
                            class="block px-6 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-purple-600 transition border-b border-gray-50">
                            <i class="fas fa-user-shield mr-2 opacity-70"></i> Admin
                        </a>
                        <a href="faculty_login.php"
                            class="block px-6 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-purple-600 transition border-b border-gray-50">
                            <i class="fas fa-chalkboard-teacher mr-2 opacity-70"></i> Faculty
                        </a>
                        <a href="login.php"
                            class="block px-6 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-purple-600 transition border-b border-gray-50">
                            <i class="fas fa-user-graduate mr-2 opacity-70"></i> Student
                        </a>
                        <a href="others_dashboard.php"
                            class="block px-6 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-purple-600 transition">
                            <i class="fas fa-users mr-2 opacity-70"></i> Others
                        </a>
                    </div>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $dashboard_url = 'dashboard.php';
                    if (isset($_SESSION['role'])) {
                        if (strcasecmp($_SESSION['role'], 'Admin') === 0) {
                            $dashboard_url = 'admin_dashboard.php';
                        } elseif (strcasecmp($_SESSION['role'], 'Faculty') === 0) {
                            $dashboard_url = 'faculty_dashboard.php';
                        }
                    }
                    ?>
                    <a href="<?php echo $dashboard_url; ?>"
                        class="btn-gradient text-white px-6 py-3 rounded-lg font-semibold shadow-lg">Dashboard</a>
                    <a href="logout.php" class="font-medium hover:text-purple-600 transition">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="font-medium hover:text-purple-600 transition">Login</a>
                    <a href="register.php" class="font-medium hover:text-purple-600 transition">Register</a>
                <?php endif; ?>
            </div>
            <!-- Mobile Menu Button -->
            <button class="md:hidden" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-200 px-6 py-4 space-y-3 shadow-lg">
            <a href="index.php" class="block w-full text-left py-2 font-medium">Home</a>
            <!-- Mobile Role Accordion -->
            <div class="border-t border-gray-100 pt-2 pb-1">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 pl-2">Access Portals</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="admin_login.php"
                        class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg text-sm font-semibold text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition">
                        <i class="fas fa-user-shield text-xs"></i> Admin
                    </a>
                    <a href="faculty_login.php"
                        class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg text-sm font-semibold text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition">
                        <i class="fas fa-chalkboard-teacher text-xs"></i> Faculty
                    </a>
                    <a href="login.php"
                        class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg text-sm font-semibold text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition">
                        <i class="fas fa-user-graduate text-xs"></i> Student
                    </a>
                    <a href="others_dashboard.php"
                        class="flex items-center gap-2 px-3 py-2 bg-gray-50 rounded-lg text-sm font-semibold text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition">
                        <i class="fas fa-users text-xs"></i> Others
                    </a>
                </div>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $dashboard_url = 'dashboard.php';
                if (isset($_SESSION['role'])) {
                    if (strcasecmp($_SESSION['role'], 'Admin') === 0) {
                        $dashboard_url = 'admin_dashboard.php';
                    } elseif (strcasecmp($_SESSION['role'], 'Faculty') === 0) {
                        $dashboard_url = 'faculty_dashboard.php';
                    }
                }
                ?>
                <a href="<?php echo $dashboard_url; ?>"
                    class="block w-full text-left py-2 text-purple-600 font-bold">Dashboard</a>
                <a href="logout.php" class="block w-full text-left py-2 font-medium">Logout</a>
            <?php else: ?>
                <a href="login.php" class="block w-full text-left py-2 font-medium">Login</a>
                <a href="register.php" class="block w-full text-left py-2 font-medium">Register</a>
            <?php endif; ?>
        </div>
    </nav>