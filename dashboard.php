<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect if not a student/other role
if (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'Admin') === 0) {
    header("Location: admin_dashboard.php");
    exit();
} elseif (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'Faculty') === 0) {
    header("Location: faculty_dashboard.php");
    exit();
}

require 'includes/header.php';
?>

<style>
    :root {
        --student-primary: #6366f1;
        --student-secondary: #a855f7;
        --student-accent: #ec4899;
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.3);
    }

    .student-body {
        background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%);
        min-height: 100vh;
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }

    .sidebar-student {
        width: 280px;
        transition: all 0.3s ease;
    }

    .nav-link-student {
        display: flex;
        align-items: center;
        padding: 0.875rem 1.25rem;
        border-radius: 0.75rem;
        color: #4b5563;
        transition: all 0.2s;
    }

    .nav-link-student:hover,
    .nav-link-student.active {
        background: white;
        color: var(--student-primary);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    }

    .stat-pill {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .timetable-grid {
        display: grid;
        grid-template-columns: 100px repeat(5, 1fr);
        gap: 1px;
        background: #e5e7eb;
        border-radius: 1rem;
        overflow: hidden;
    }

    .timetable-cell {
        background: white;
        padding: 1rem;
        min-height: 100px;
    }

    .timetable-header {
        background: #f9fafb;
        font-weight: 700;
        color: #374151;
        text-align: center;
        padding: 0.75rem;
    }

    .subject-tag {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
        display: inline-block;
    }

    .animate-float {
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }
    }
</style>

<div class="student-body pt-20 flex">
    <!-- Sidebar -->
    <aside class="sidebar-student hidden lg:block p-6">
        <div class="glass-card rounded-3xl p-6 h-[calc(100vh-140px)] sticky top-28 flex flex-col">
            <div class="flex items-center gap-4 mb-10 px-2">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-user-graduate text-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800 leading-tight">Student Portal</h4>
                    <span class="text-xs text-indigo-500 font-bold uppercase tracking-wider">Academics</span>
                </div>
            </div>

            <nav class="space-y-2 flex-grow">
                <a href="#" class="nav-link-student active">
                    <i class="fas fa-th-large mr-3 text-lg"></i> Overview
                </a>
                <a href="#" class="nav-link-student">
                    <i class="fas fa-calendar-alt mr-3 text-lg"></i> My Timetable
                </a>
                <a href="#" class="nav-link-student">
                    <i class="fas fa-book mr-3 text-lg"></i> Courses
                </a>
                <a href="#" class="nav-link-student">
                    <i class="fas fa-chart-line mr-3 text-lg"></i> Attendance
                </a>
                <a href="#" class="nav-link-student">
                    <i class="fas fa-bullhorn mr-3 text-lg"></i> Notices
                </a>
            </nav>

            <div class="mt-auto space-y-4">
                <div class="bg-indigo-50 rounded-2xl p-4">
                    <p class="text-[10px] font-bold text-indigo-400 uppercase mb-2">Upcoming Class</p>
                    <h5 class="text-sm font-bold text-gray-800">Advanced Algorithms</h5>
                    <p class="text-xs text-gray-500">Hall 402 • 11:30 AM</p>
                </div>
                <a href="logout.php"
                    class="flex items-center gap-3 px-5 py-3 rounded-xl text-red-500 hover:bg-red-50 transition font-bold">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow p-6 lg:p-10 max-w-7xl mx-auto">
        <!-- Top Bar / Welcome -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div>
                <h1 class="text-4xl font-black text-gray-900 mb-2">
                    Welcome back, <span class="gradient-text">
                        <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>!
                    </span> 👋
                </h1>
                <p class="text-gray-500 font-medium">Semester 4 • Academic Year 2024-25</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="glass-card px-4 py-2 rounded-xl flex items-center gap-3">
                    <i class="far fa-calendar text-indigo-500"></i>
                    <span class="text-sm font-bold text-gray-700">
                        <?php echo date('D, M d Y'); ?>
                    </span>
                </div>
                <button
                    class="w-12 h-12 glass-card rounded-xl flex items-center justify-center text-gray-600 hover:text-indigo-600 transition">
                    <i class="far fa-bell text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="glass-card p-6 rounded-3xl border-b-4 border-indigo-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <span class="text-xs font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg">+2.4%</span>
                </div>
                <h3 class="text-2xl font-black text-gray-800">88.5%</h3>
                <p class="text-sm text-gray-500 font-medium">Overall Attendance</p>
            </div>
            <div class="glass-card p-6 rounded-3xl border-b-4 border-purple-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-gray-800">24h</h3>
                <p class="text-sm text-gray-500 font-medium">Weekly Lectures</p>
            </div>
            <div class="glass-card p-6 rounded-3xl border-b-4 border-pink-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-gray-800">8.42</h3>
                <p class="text-sm text-gray-500 font-medium">Current CGPA</p>
            </div>
            <div class="glass-card p-6 rounded-3xl border-b-4 border-blue-500">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-gray-800">04</h3>
                <p class="text-sm text-gray-500 font-medium">Pending Tasks</p>
            </div>
        </div>

        <!-- Timetable & Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <!-- Weekly Timetable -->
                <div class="glass-card rounded-3xl p-8 overflow-hidden">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Weekly Schedule</h3>
                            <p class="text-sm text-gray-500">Next class in 45 minutes</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-200">This
                                Week</button>
                            <button
                                class="px-4 py-2 text-gray-500 text-sm font-bold hover:bg-gray-100 rounded-xl transition">Export
                                PDF</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="timetable-grid" style="min-width: 800px;">
                            <!-- Header -->
                            <div class="timetable-header">TIME</div>
                            <div class="timetable-header">MON</div>
                            <div class="timetable-header">TUE</div>
                            <div class="timetable-header">WED</div>
                            <div class="timetable-header">THU</div>
                            <div class="timetable-header">FRI</div>

                            <!-- Row 1: 09:00 -->
                            <div
                                class="timetable-cell text-center flex flex-col justify-center border-r border-gray-100">
                                <span class="text-xs font-bold text-gray-400 uppercase">Start</span>
                                <span class="text-sm font-black text-gray-700">09:00 AM</span>
                            </div>
                            <div class="timetable-cell">
                                <div class="p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
                                    <h6 class="text-xs font-bold text-indigo-700">DS-401</h6>
                                    <p class="text-[10px] text-gray-600">Data Science</p>
                                </div>
                            </div>
                            <div class="timetable-cell"></div>
                            <div class="timetable-cell">
                                <div class="p-3 bg-purple-50 border-l-4 border-purple-500 rounded-lg">
                                    <h6 class="text-xs font-bold text-purple-700">CO-202</h6>
                                    <p class="text-[10px] text-gray-600">Computer Org</p>
                                </div>
                            </div>
                            <div class="timetable-cell"></div>
                            <div class="timetable-cell">
                                <div class="p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
                                    <h6 class="text-xs font-bold text-indigo-700">DS-401</h6>
                                    <p class="text-[10px] text-gray-600">Data Science</p>
                                </div>
                            </div>

                            <!-- Row 2: 10:30 -->
                            <div
                                class="timetable-cell text-center flex flex-col justify-center border-r border-gray-100">
                                <span class="text-xs font-bold text-gray-400 uppercase">Break</span>
                                <span class="text-sm font-black text-gray-700">10:30 AM</span>
                            </div>
                            <div
                                class="timetable-cell bg-gray-50/50 flex items-center justify-center col-span-5 italic text-gray-400 text-sm font-medium">
                                Short Recess </div>

                            <!-- Row 3: 11:00 -->
                            <div
                                class="timetable-cell text-center flex flex-col justify-center border-r border-gray-100">
                                <span class="text-xs font-bold text-gray-400 uppercase">Slot</span>
                                <span class="text-sm font-black text-gray-700">11:00 AM</span>
                            </div>
                            <div class="timetable-cell"></div>
                            <div class="timetable-cell">
                                <div class="p-3 bg-pink-50 border-l-4 border-pink-500 rounded-lg">
                                    <h6 class="text-xs font-bold text-pink-700">MA-305</h6>
                                    <p class="text-[10px] text-gray-600">Math III</p>
                                </div>
                            </div>
                            <div class="timetable-cell"></div>
                            <div class="timetable-cell">
                                <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
                                    <h6 class="text-xs font-bold text-blue-700">CS-405</h6>
                                    <p class="text-[10px] text-gray-600">Algorithms</p>
                                </div>
                            </div>
                            <div class="timetable-cell"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Widgets -->
            <div class="space-y-8">
                <!-- Notifications -->
                <div class="glass-card rounded-3xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-bell text-indigo-500"></i> Announcements
                    </h3>
                    <div class="space-y-4">
                        <div class="flex gap-4 p-3 hover:bg-white/50 rounded-2xl transition group cursor-pointer">
                            <div
                                class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex-shrink-0 flex items-center justify-center font-bold">
                                12</div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-800 group-hover:text-indigo-600 transition">
                                    Seminar Postponed</h5>
                                <p class="text-xs text-gray-500">Robotics seminar moved to Wednesday...</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-3 hover:bg-white/50 rounded-2xl transition group cursor-pointer">
                            <div
                                class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex-shrink-0 flex items-center justify-center font-bold">
                                10</div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-800 group-hover:text-indigo-600 transition">Lab
                                    Manuals Due</h5>
                                <p class="text-xs text-gray-500">Submit your OS lab reports by Friday...</p>
                            </div>
                        </div>
                        <div class="flex gap-4 p-3 hover:bg-white/50 rounded-2xl transition group cursor-pointer">
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex-shrink-0 flex items-center justify-center font-bold">
                                08</div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-800 group-hover:text-indigo-600 transition">
                                    Holiday Notice</h5>
                                <p class="text-xs text-gray-500">College will remain closed on 30th...</p>
                            </div>
                        </div>
                    </div>
                    <button
                        class="w-full mt-6 py-3 border border-indigo-100 rounded-xl text-xs font-bold text-indigo-500 hover:bg-indigo-50 transition uppercase tracking-wider">View
                        All Updates</button>
                </div>

                <!-- Learning Progress -->
                <div
                    class="glass-card rounded-3xl p-6 bg-gradient-to-br from-indigo-50 to-white text-gray-800 border overflow-hidden relative group">
                    <div
                        class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-100 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700">
                    </div>
                    <h4 class="text-indigo-500 text-sm font-bold uppercase tracking-widest mb-4">Course Progress</h4>
                    <div class="space-y-6 relative z-10">
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-2 text-gray-700">
                                <span>Computer Architecture</span>
                                <span>75%</span>
                            </div>
                            <div class="h-1.5 w-full bg-indigo-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-2 text-gray-700">
                                <span>Database Management</span>
                                <span>45%</span>
                            </div>
                            <div class="h-1.5 w-full bg-indigo-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: 45%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 relative z-10">
                        <p class="text-[10px] text-gray-400 mb-2 font-bold uppercase">CURRENT GOAL</p>
                        <h5 class="text-lg font-black tracking-tight mb-4 text-gray-800">Mastering Microservices
                            Architecture</h5>
                        <button
                            class="w-full px-6 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black shadow-lg shadow-indigo-200 transform hover:-translate-y-1 transition-all">Keep
                            Learning</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require 'includes/footer.php'; ?>