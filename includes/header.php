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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
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
            padding: 12px;
            background-color: #ffffff;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            color: #3c4043;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .google-btn:hover {
            background-color: #f8f9fa;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.15);
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php"
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="block w-full text-left py-2 text-purple-600 font-bold">Dashboard</a>
                <a href="logout.php" class="block w-full text-left py-2 font-medium">Logout</a>
            <?php else: ?>
                <a href="login.php" class="block w-full text-left py-2 font-medium">Login</a>
                <a href="register.php" class="block w-full text-left py-2 font-medium">Register</a>
            <?php endif; ?>
        </div>
    </nav>