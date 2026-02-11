<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: admin_login.php");
    exit();
}

// Include database connection
// We assume config/db.php exists and sets up $conn
$db_path = __DIR__ . '/config/db.php';
if (file_exists($db_path)) {
    require_once $db_path;
} else {
    // Fallback or error if config is missing (during testing)
    // echo "DB Config not found";
    $conn = null;
}

// Stats Array
$stats = [
    'faculties' => 0,
    'departments' => 0,
    'subjects' => 0,
    'classrooms' => 0,
    'timetables' => 0,
    'conflicts' => 0
];

// Data Arrays
$subjects_list = [];
$faculties_list = [];
$sections_list = [];
$all_sections_list = [];
$rooms_list = [];
$logs_list = [];

if ($conn) {
    // 1. Fetch Stats
    if ($res = $conn->query("SELECT COUNT(*) FROM faculties"))
        $stats['faculties'] = $res->fetch_row()[0];
    // Check if tables exist before querying to avoid fatal errors if migration failed
    // We suppress errors with @ or just try catch blocks if we were using exceptions

    // Departments
    $departments_list = [];
    $res = $conn->query("SHOW TABLES LIKE 'departments'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT COUNT(*) FROM departments"))
            $stats['departments'] = $res->fetch_row()[0];

        // Fetch all departments for dropdowns
        if ($res = $conn->query("SELECT * FROM departments")) {
            while ($row = $res->fetch_assoc())
                $departments_list[] = $row;
        }
    }

    // Subjects
    $all_subjects_list = []; // Unfiltered list for dropdowns
    $res = $conn->query("SHOW TABLES LIKE 'subjects'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT COUNT(*) FROM subjects"))
            $stats['subjects'] = $res->fetch_row()[0];

        // Fetch Subjects (Limited)
        $sub_query = "SELECT * FROM subjects ORDER BY created_at DESC LIMIT 10";
        if ($res = $conn->query($sub_query)) {
            while ($row = $res->fetch_assoc())
                $subjects_list[] = $row;
        }

        // Fetch All Subjects
        if ($res = $conn->query("SELECT * FROM subjects ORDER BY name ASC")) {
            while ($row = $res->fetch_assoc())
                $all_subjects_list[] = $row;
        }
    }

    // Classrooms
    $res = $conn->query("SHOW TABLES LIKE 'classrooms'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT COUNT(*) FROM classrooms"))
            $stats['classrooms'] = $res->fetch_row()[0];
        // Fetch Rooms
        if ($res = $conn->query("SELECT * FROM classrooms LIMIT 10")) {
            while ($row = $res->fetch_assoc())
                $rooms_list[] = $row;
        }
    }

    // Timetables
    $res = $conn->query("SHOW TABLES LIKE 'timetables'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT COUNT(*) FROM timetables"))
            $stats['timetables'] = $res->fetch_row()[0];
    }

    // Faculties List
    $all_faculties_list = []; // Unfiltered list for dropdowns
    // We try to join but if departments table is missing, use simple select
    $fac_query = "SELECT f.* FROM faculties f LIMIT 10";
    if ($res = $conn->query($fac_query)) {
        while ($row = $res->fetch_assoc())
            $faculties_list[] = $row;
    }

    // Fetch All Faculties
    if ($res = $conn->query("SELECT * FROM faculties ORDER BY name ASC")) {
        while ($row = $res->fetch_assoc())
            $all_faculties_list[] = $row;
    }

    // Sections
    $res = $conn->query("SHOW TABLES LIKE 'sections'");
    if ($res && $res->num_rows > 0) {
        $sec_query = "SELECT s.*, d.name as dept_name FROM sections s JOIN departments d ON s.department_id = d.id ORDER BY s.id DESC LIMIT 10";
        if ($res = $conn->query($sec_query)) {
            while ($row = $res->fetch_assoc())
                $sections_list[] = $row;
        }

        // Use 'department_name' alias for compatibility with view logic
        $all_sec_query = "SELECT s.*, d.name as department_name FROM sections s JOIN departments d ON s.department_id = d.id ORDER BY s.section_name ASC";
        if ($res = $conn->query($all_sec_query)) {
            while ($row = $res->fetch_assoc())
                $all_sections_list[] = $row;
        }
    }

    // Audit Logs
    $res = $conn->query("SHOW TABLES LIKE 'timetable_audit_logs'");
    if ($res && $res->num_rows > 0) {
        $log_query = "SELECT * FROM timetable_audit_logs ORDER BY created_at DESC LIMIT 5";
        if ($res = $conn->query($log_query)) {
            while ($row = $res->fetch_assoc())
                $logs_list[] = $row;
        }
    }


    // Academic Settings
    $academic_settings = [];
    $last_settings_update = null;
    $res = $conn->query("SHOW TABLES LIKE 'academic_settings'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT *, updated_at FROM academic_settings")) {
            while ($row = $res->fetch_assoc()) {
                $academic_settings[$row['key_name']] = $row['value'];
                if (!$last_settings_update || $row['updated_at'] > $last_settings_update) {
                    $last_settings_update = $row['updated_at'];
                }
            }
        }
    }

    // Announcements
    $announcements_list = [];
    $res = $conn->query("SHOW TABLES LIKE 'announcements'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5")) {
            while ($row = $res->fetch_assoc())
                $announcements_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | AutoTime</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .sidebar-item {
            transition: all 0.2s ease-in-out;
        }

        .sidebar-item:hover {
            background-color: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
        }

        .sidebar-item.active {
            background-color: #4f46e5;
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            transition: transform 0.2s ease;
        }

        .toggle-dot {
            transition: all 0.3s ease-in-out;
        }

        input:checked~.toggle-dot {
            transform: translateX(100%);
            background-color: white;
        }

        input:checked~.toggle-bg {
            background-color: #4f46e5;
        }

        /* Professional Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(10px);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .animate-fade-out {
            animation: fadeOut 0.4s ease-in forwards;
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .toast {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4f46e5;
            display: flex;
            items-center: center;
            gap: 0.75rem;
            min-width: 300px;
            animation: fadeIn 0.3s ease-out;
        }

        .toast.success {
            border-left-color: #10b981;
        }

        .toast.error {
            border-left-color: #ef4444;
        }

        .toast.info {
            border-left-color: #3b82f6;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
        const ALL_SUBJECTS = <?php echo json_encode($all_subjects_list); ?>;
        const ALL_FACULTIES = <?php echo json_encode($all_faculties_list); ?>;
        const ALL_ROOMS = <?php echo json_encode($rooms_list); ?>;
        const ALL_SECTIONS = <?php echo json_encode($all_sections_list); ?>;
    </script>
</head>

<body class="overflow-hidden">
    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-slate-200 z-50 transition-transform duration-300">
        <div class="p-6 h-full flex flex-col">
            <div class="flex items-center gap-3 mb-10">
                <div
                    class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-slate-800 text-lg leading-tight">AutoTime</h1>
                    <p class="text-xs text-slate-500 font-medium">Admin Portal</p>
                </div>
            </div>

            <nav class="space-y-1 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                <a href="#" onclick="showSection('overview')" id="link-overview"
                    class="sidebar-item active flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>

                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-6 mb-2 ml-4">Academic</p>
                <a href="#" onclick="showSection('course-manage')" id="link-course-manage"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-book"></i> Course Management
                </a>
                <a href="#" onclick="showSection('academic-settings')" id="link-academic-settings"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-clock"></i> Academic Settings
                </a>
                <a href="#" onclick="showSection('faculty-allocation')" id="link-faculty-allocation"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty Allocation
                </a>
                <a href="#" onclick="showSection('class-mapping')" id="link-class-mapping"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-sitemap"></i> Class & Section
                </a>

                <a href="#" onclick="showSection('dept-manage')" id="link-dept-manage"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-building"></i> Departments
                </a>

                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-6 mb-2 ml-4">Management</p>
                <a href="#" onclick="showSection('faculty-manage')" id="link-faculty-manage"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-users-cog"></i> Faculty & Constraints
                </a>
                <a href="#" onclick="showSection('room-manage')" id="link-room-manage"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-door-open"></i> Room & Resources
                </a>

                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-6 mb-2 ml-4">Operations</p>
                <a href="#" onclick="showSection('generate')" id="link-generate"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-magic"></i> Generate Timetable
                </a>
                <a href="#" onclick="showSection('audit-logs')" id="link-audit-logs"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-history"></i> Audit Logs
                </a>
                <a href="#" onclick="showSection('conflicts')" id="link-conflicts"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-exclamation-circle"></i> Conflicts
                </a>

                <div class="relative group">
                    <button
                        class="w-full sidebar-item flex items-center justify-between px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                        <div class="flex items-center gap-3"><i class="fas fa-eye"></i> View Timetable</div>
                        <i class="fas fa-chevron-down text-[10px] transition-transform group-hover:rotate-180"></i>
                    </button>
                    <div class="hidden group-hover:block pl-11 pb-2 space-y-1">
                        <a href="javascript:void(0)" onclick="showSection('view-dept-wise')"
                            class="block py-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">Department-wise</a>
                        <a href="javascript:void(0)" onclick="showSection('view-fac-wise')"
                            class="block py-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">Faculty-wise</a>
                    </div>
                </div>

                <a href="#" onclick="showSection('regenerate')" id="link-regenerate"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-sync"></i> Regenerate
                </a>



                <div class="relative group">
                    <button
                        class="w-full sidebar-item flex items-center justify-between px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                        <div class="flex items-center gap-3"><i class="fas fa-file-export"></i> Export</div>
                        <i class="fas fa-chevron-down text-[10px] transition-transform group-hover:rotate-180"></i>
                    </button>
                    <div class="hidden group-hover:block pl-11 pb-2 space-y-1">
                        <a href="actions/export_timetable.php?format=pdf"
                            class="block py-1.5 text-sm text-slate-500 hover:text-red-500 transition"><i
                                class="far fa-file-pdf mr-2"></i>PDF Format</a>
                        <a href="actions/export_timetable.php?format=excel"
                            class="block py-1.5 text-sm text-slate-500 hover:text-green-600 transition"><i
                                class="far fa-file-excel mr-2"></i>Excel Format</a>
                    </div>
                </div>

                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-6 mb-2 ml-4">Account</p>
                <a href="#" onclick="showSection('profile')" id="link-profile"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-user-circle"></i> Admin Profile
                </a>
                <a href="#" onclick="showSection('password')" id="link-password"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-lock"></i> Change Password
                </a>
                <a href="#" onclick="showSection('user-manage')" id="link-user-manage"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-users-cog"></i> User Management
                </a>
            </nav>

            <div class="mt-4 pt-4 border-t border-slate-100">
                <a href="logout.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-red-500 hover:bg-red-50 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen relative">
        <!-- Top Bar -->
        <header
            class="h-20 bg-white/80 backdrop-blur-md sticky top-0 border-b border-slate-200 px-8 flex items-center justify-between z-40">
            <h2 id="section-title" class="text-xl font-bold text-slate-800">Dashboard Overview</h2>
            <div class="flex items-center gap-6">
                <div class="flex flex-col text-right mr-4">
                    <span class="text-xs font-bold text-slate-500 uppercase">Current Session</span>
                    <span class="text-sm font-bold text-indigo-600">2024–25 | Odd Semester</span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="w-10 h-10 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 relative">
                        <i class="far fa-bell text-lg"></i>
                        <span
                            class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <div class="h-8 w-[1px] bg-slate-200 mx-2"></div>
                    <div class="flex items-center gap-3 pl-2">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=4f46e5&color=fff"
                            class="w-10 h-10 rounded-xl border border-slate-200 shadow-sm" alt="Avatar">
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-8 h-[calc(100vh-80px)] overflow-y-auto custom-scrollbar">

            <!-- 1. DASHBOARD OVERVIEW -->
            <section id="overview-section" class="space-y-8">
                <!-- Status Widget -->
                <div
                    class="bg-indigo-900 rounded-3xl p-8 text-white flex items-center justify-between shadow-xl relative overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 skew-x-12 transform translate-x-10">
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold mb-2">Timetable Status</h3>
                        <p class="text-indigo-200 text-sm" id="status-desc">Current active timetable generation status.
                        </p>
                    </div>
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="text-right mr-4">
                            <p class="text-xs font-bold text-indigo-300 uppercase">Last Updated</p>
                            <p class="font-bold" id="status-last-updated">-</p>
                        </div>
                        <div
                            class="px-4 py-2 bg-white/20 backdrop-blur-md rounded-xl border border-white/10 flex flex-col items-center">
                            <span class="text-[10px] font-bold text-indigo-200 uppercase tracking-wider">Status</span>
                            <span class="text-lg font-black" id="status-badge">Checking...</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-slate-100">
                        <div
                            class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-slate-800"><?php echo $stats['faculties']; ?></h4>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Total Faculties</p>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-slate-100">
                        <div
                            class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-slate-800"><?php echo $stats['departments']; ?></h4>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Departments</p>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-slate-100">
                        <div
                            class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-book"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-slate-800"><?php echo $stats['subjects']; ?></h4>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Total Subjects</p>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-slate-100">
                        <div
                            class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-slate-800"><?php echo $stats['classrooms']; ?></h4>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Classrooms</p>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-slate-100">
                        <div
                            class="w-10 h-10 bg-pink-50 text-pink-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-slate-800"><?php echo $stats['timetables']; ?></h4>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Timetables</p>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-slate-100">
                        <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-slate-800 text-red-500"><?php echo $stats['conflicts']; ?>
                        </h4>
                        <p class="text-xs text-slate-500 font-medium uppercase mt-1">Conflicts</p>
                    </div>
                </div>

                <!-- Quick Status Summary -->
                <div
                    class="bg-indigo-900 p-6 rounded-3xl text-white shadow-xl shadow-indigo-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg mb-1">Timetable Status</h3>
                        <p class="text-xs text-indigo-200">Current active timetable generation status.</p>
                    </div>
                    <div class="flex gap-4 items-center">
                        <div class="px-4 py-2 bg-white/10 rounded-xl border border-white/20">
                            <span class="text-[10px] uppercase font-bold text-indigo-300 block">Status</span>
                            <span class="text-sm font-bold">Draft Mode</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. COURSE / PROGRAM MANAGEMENT SECTION -->
            <section id="course-manage-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">Course / Program Management</h3>
                            <p class="text-sm text-slate-500">Manage subjects, codes, credits, and batches.</p>
                        </div>
                        <button onclick="toggleAddSubjectForm()"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                            <i class="fas fa-plus mr-2 text-xs"></i> Add Subject
                        </button>
                    </div>

                    <form id="add-subject-form" class="hidden" onsubmit="event.preventDefault(); addSubject();">
                        <div
                            class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-8 items-end bg-slate-50/50 p-6 rounded-2xl border border-dashed border-slate-200">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject
                                    Name</label>
                                <input type="text" name="name" required placeholder="e.g. Data Structures"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject
                                    Code</label>
                                <input type="text" name="code" required placeholder="CS101"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Credits</label>
                                <input type="number" name="credits" required value="3" min="1" max="10"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Batch /
                                    Year</label>
                                <select name="batch_year"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 transition">
                                    <option value="2024-2028">2024-2028</option>
                                    <option value="2023-2027">2023-2027</option>
                                    <option value="2022-2026">2022-2026</option>
                                    <option value="2021-2025">2021-2025</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Department</label>
                                <select name="department_id" id="subject_dept_id" required
                                    onchange="filterSubjectSections()"
                                    class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 transition">
                                    <option value="">Select Dept</option>
                                    <?php if (empty($departments_list)): ?>
                                        <option value="" disabled>No departments found</option>
                                    <?php else: ?>
                                        <?php foreach ($departments_list as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>">
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($departments_list)): ?>
                                    <p class="text-xs text-red-500 mt-1">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        No departments available. <a href="#" onclick="showSection('dept-manage')"
                                            class="text-indigo-600 underline">Add departments first</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="md:col-span-6 flex justify-end">
                                <button type="submit"
                                    class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                                    <i class="fas fa-plus mr-2"></i> Add Subject
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-100">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Code</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Credits</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Details</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="subjects-table-body" class="divide-y divide-slate-50">
                                <?php if (!empty($subjects_list)): ?>
                                    <?php foreach ($subjects_list as $sub): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                <?php echo htmlspecialchars($sub['code'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600">
                                                <?php echo htmlspecialchars($sub['name'] ?? ''); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-indigo-600">
                                                <?php echo htmlspecialchars($sub['credits'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500">
                                                <?php
                                                $details = [];
                                                if (!empty($sub['batch_year']))
                                                    $details[] = $sub['batch_year'];
                                                if (!empty($sub['academic_year']))
                                                    $details[] = "Yr " . $sub['academic_year'];
                                                if (!empty($sub['semester']))
                                                    $details[] = "Sem " . $sub['semester'];
                                                if (!empty($sub['section_id']))
                                                    $details[] = "Sec ID:" . $sub['section_id']; // Could map to name if joined
                                                echo implode(' • ', $details);
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick='openEditSubjectModal(<?php echo json_encode($sub); ?>)'
                                                    class="text-slate-400 hover:text-indigo-600 mx-1 p-2 rounded-lg hover:bg-indigo-50 transition"><i
                                                        class="fas fa-edit"></i></button>
                                                <button onclick="deleteSubject(<?php echo $sub['id']; ?>, this)"
                                                    class="text-slate-400 hover:text-red-500 mx-2"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No subjects
                                            found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 3. CLASS & SECTION MAPPING -->
            <section id="class-mapping-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">Class & Section Mapping</h3>
                            <p class="text-sm text-slate-500">Map departments to years and sections with student
                                strength.</p>
                        </div>
                        <button onclick="document.getElementById('section-modal').classList.remove('hidden')"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-indigo-700 transition"><i
                                class="fas fa-plus mr-2 text-xs"></i> New Mapping</button>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-100">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Department</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Year/Sem</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Section</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Strength</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="sections-table-body" class="divide-y divide-slate-50">
                                <?php if (!empty($sections_list)): ?>
                                    <?php foreach ($sections_list as $sec): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                <?php echo htmlspecialchars($sec['dept_name'] ?? $sec['department_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600">
                                                Yr <?php echo htmlspecialchars($sec['year']); ?>, Sem
                                                <?php echo htmlspecialchars($sec['semester']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-indigo-600">
                                                <?php echo htmlspecialchars($sec['section_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500">
                                                <?php echo htmlspecialchars($sec['student_strength']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="text-slate-400 hover:text-indigo-600 mx-2"><i
                                                        class="fas fa-edit"></i></button>
                                                <button onclick="deleteSection(<?php echo $sec['id']; ?>, this)"
                                                    class="text-slate-400 hover:text-red-500 mx-2"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No sections
                                            found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 4. FACULTY MANAGEMENT SECTION -->
            <section id="faculty-manage-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">Faculty Management</h3>
                            <p class="text-sm text-slate-500">Track workload, availability, and constraints.</p>
                        </div>
                        <button onclick="document.getElementById('faculty-modal').classList.remove('hidden')"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg">New
                            Faculty</button>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full text-left min-w-[1000px]">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Faculty Profile
                                    </th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Max Hrs/Week</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="faculty-table-body" class="divide-y divide-slate-50">
                                <?php if (!empty($faculties_list)): ?>
                                    <?php foreach ($faculties_list as $fac): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs uppercase">
                                                        <?php echo substr($fac['name'], 0, 2); ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-800">
                                                            <?php echo htmlspecialchars($fac['name']); ?>
                                                        </p>
                                                        <p class="text-[10px] text-slate-400">
                                                            <?php echo htmlspecialchars($fac['email']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                <?php echo htmlspecialchars($fac['max_hours_week'] ?? 20); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="text-slate-400 hover:text-indigo-600 mx-2"
                                                    title="Manage Constraints"><i class="fas fa-sliders-h"></i></button>
                                                <button onclick="deleteFaculty(<?php echo $fac['id']; ?>, this)"
                                                    class="text-slate-400 hover:text-red-500 mx-2" title="Delete Faculty"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-400 text-sm">No faculties
                                            found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 5. ROOM & RESOURCE MANAGEMENT SECTION -->
            <section id="room-manage-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">Resource Matrix</h3>
                            <p class="text-sm text-slate-500">Manage classroom capacities, types, and equipment.</p>
                        </div>
                        <button onclick="document.getElementById('room-modal').classList.remove('hidden')"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs hover:shadow-lg transition">Add
                            Room</button>
                    </div>

                    <div id="rooms-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php if (!empty($rooms_list)): ?>
                            <?php foreach ($rooms_list as $room): ?>
                                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 relative group overflow-hidden">
                                    <button onclick="deleteRoom(<?php echo $room['id']; ?>, this)"
                                        class="absolute top-2 right-2 text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition z-10"><i
                                            class="fas fa-trash text-xs"></i></button>
                                    <div class="flex justify-between items-start mb-6">
                                        <div
                                            class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-sm">
                                            <i class="fas fa-laptop-code text-sm"></i>
                                        </div>
                                        <span
                                            class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-[9px] font-bold uppercase"><?php echo htmlspecialchars($room['type']); ?></span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($room['name']); ?>
                                    </h4>
                                    <div class="mt-4 flex flex-wrap gap-1">
                                        <span
                                            class="px-2 py-0.5 border border-slate-200 rounded text-[8px] text-slate-500"><?php echo htmlspecialchars($room['equipment'] ?? 'Standard'); ?></span>
                                    </div>
                                    <div class="mt-4 flex items-end justify-between">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase">Capacity</p>
                                            <p class="text-xl font-extrabold text-slate-800">
                                                <?php echo htmlspecialchars($room['capacity']); ?>
                                            </p>
                                        </div>
                                        <div
                                            class="w-10 h-10 rounded-full border-2 border-emerald-500 flex items-center justify-center text-emerald-500">
                                            <span class="text-[10px] font-bold">OK</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-slate-400 text-sm">No rooms found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- DEPARTMENT MANAGEMENT SECTION -->
            <section id="dept-manage-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">Department Management</h3>
                            <p class="text-sm text-slate-500">Add or remove academic departments.</p>
                        </div>
                        <button onclick="document.getElementById('dept-modal').classList.remove('hidden')"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg">New
                            Department</button>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Dept Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Code</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="dept-table-body" class="divide-y divide-slate-50">
                                <?php if (!empty($departments_list)): ?>
                                    <?php foreach ($departments_list as $dept): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-6 py-4 font-bold text-slate-800">
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500">
                                                <?php echo htmlspecialchars($dept['code'] ?? '-'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="deleteDept(<?php echo $dept['id']; ?>, this)"
                                                    class="text-slate-400 hover:text-red-500"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-400 text-sm">No departments
                                            found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 6. Audit Logs Section -->
            <section id="audit-logs-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-800 mb-6">Audit Logs</h3>
                    <div class="overflow-hidden rounded-2xl border border-slate-100">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Timestamp</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">User</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Action</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (!empty($logs_list)): ?>
                                    <?php foreach ($logs_list as $log): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-6 py-4 text-xs text-slate-500"><?php echo $log['created_at']; ?></td>
                                            <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                <?php echo $log['user_id']; ?>
                                            </td>
                                            <td class="px-6 py-4"><span
                                                    class="px-2 py-1 bg-green-50 text-green-600 rounded text-[10px] font-bold"><?php echo $log['action_type']; ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600">
                                                <?php echo htmlspecialchars($log['details']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">No logs found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- 7. Conflicts Section -->
            <section id="conflicts-section" class="hidden space-y-8">
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-800 mb-6 text-red-500">Conflict Details</h3>
                    <div class="space-y-4">
                        <div
                            class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center text-sm text-slate-400">
                            No conflicts detected in the current timetable.
                        </div>
                    </div>
                </div>
            </section>

            <section id="generate-section"
                class="hidden h-full flex flex-col items-center justify-center text-center py-20">
                <div class="bg-white p-12 rounded-[2.5rem] shadow-xl border border-slate-100 max-w-2xl w-full">
                    <div
                        class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center mb-6 text-indigo-600 text-4xl mx-auto shadow-sm">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3 class="text-3xl font-black text-slate-800 mb-2">Timetable Generator</h3>
                    <p class="text-slate-500 font-medium text-sm mb-10 max-w-md mx-auto">
                        Ready to generate the timetable. The system will use current faculty allocations, room
                        capacities, and subject constraints.
                    </p>

                    <div class="flex flex-col gap-4 max-w-xs mx-auto">
                        <button onclick="startGeneration()" id="btn-generate"
                            class="w-full px-8 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-3">
                            <i class="fas fa-play"></i> Start Generation
                        </button>

                        <div id="generation-results" class="hidden space-y-3 animate-fade-in">
                            <div
                                class="p-4 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-bold border border-emerald-100">
                                <i class="fas fa-check-circle mr-2"></i> Generation Successful!
                            </div>
                            <div class="flex gap-3">
                                <button onclick="publishTimetable()" id="btn-publish"
                                    class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition shadow-lg shadow-emerald-100">
                                    Publish
                                </button>
                                <button onclick="showSection('overview')"
                                    class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition">
                                    Later
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="console-output"
                        class="hidden mt-8 text-left bg-slate-900 rounded-xl p-4 font-mono text-xs text-slate-300 h-32 overflow-y-auto">
                        <div
                            class="flex items-center gap-2 mb-2 text-slate-500 uppercase tracking-wider font-bold text-[10px]">
                            <i class="fas fa-terminal"></i> System Log
                        </div>
                        <div id="console-lines" class="space-y-1"></div>
                    </div>
                </div>
            </section>

            <!-- OPERATIONS Placeholder -->
            <!-- View Timetable Department Wise -->
            <section id="view-dept-wise-section" class="hidden space-y-8">
                <!-- Wrapper -->
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm relative">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">View Timetable</h3>
                            <p class="text-slate-400 text-sm">Select constraints to view the generated schedule.</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="downloadTimetablePDF()"
                                class="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold text-sm hover:bg-red-100 transition shadow-sm border border-red-100">
                                <i class="fas fa-file-pdf mr-2"></i> Export PDF
                            </button>
                            <select id="view-version-id"
                                class="px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Version...</option>
                                <!-- Populated by JS -->
                            </select>
                            <button onclick="loadViewVersions()"
                                class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-xl text-slate-600 hover:bg-slate-200 transition"><i
                                    class="fas fa-sync"></i></button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Department</label>
                            <select id="view-dept-id"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700">
                                <option value="">Select Dept</option>
                                <?php foreach ($departments_list as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Section</label>
                            <select id="view-section-id"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700">
                                <option value="">Select Section</option>
                                <option value="">Select Department First</option>
                                <!-- Populated by JS -->
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="loadTimetableGrid()"
                                class="w-full py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                                <i class="fas fa-search mr-2"></i> Load Timetable
                            </button>
                        </div>
                    </div>

                    <!-- Grid Container -->
                    <div id="view-grid-container" class="overflow-x-auto rounded-xl border border-slate-200 hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 font-bold w-32 bg-slate-50 sticky left-0 z-10">Day / Period
                                    </th>
                                    <th class="px-6 py-4 text-center">1<br><span
                                            class="text-[10px] opacity-70">9:30-10:20</span></th>
                                    <th class="px-6 py-4 text-center">2<br><span
                                            class="text-[10px] opacity-70">10:20-11:10</span></th>
                                    <th class="px-6 py-4 text-center">3<br><span
                                            class="text-[10px] opacity-70">11:10-12:00</span></th>
                                    <th class="px-6 py-4 text-center">4<br><span
                                            class="text-[10px] opacity-70">12:00-12:50</span></th>
                                    <th class="px-6 py-4 text-center bg-slate-100/50">LUNCH</th>
                                    <th class="px-6 py-4 text-center">5<br><span
                                            class="text-[10px] opacity-70">01:30-02:20</span></th>
                                    <th class="px-6 py-4 text-center">6<br><span
                                            class="text-[10px] opacity-70">02:20-03:10</span></th>
                                    <th class="px-6 py-4 text-center">7<br><span
                                            class="text-[10px] opacity-70">03:10-04:00</span></th>
                                </tr>
                            </thead>
                            <tbody id="view-grid-body" class="divide-y divide-slate-100">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                    <div id="view-empty-state" class="py-20 text-center">
                        <div
                            class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 text-2xl">
                            <i class="fas fa-table"></i>
                        </div>
                        <p class="text-slate-400 font-medium">Select constraints and load to view schedule.</p>
                    </div>

                    <!-- Allocations Summary -->
                    <div id="view-allocations-wrapper"
                        class="hidden mt-8 border-t border-slate-100 pt-8 animate-fade-in">
                        <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-list-ul text-indigo-500"></i> Course Allocations
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="view-allocations-list">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- View Timetable Faculty Wise -->
            <section id="view-fac-wise-section" class="hidden space-y-8">
                <!-- Wrapper -->
                <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm relative">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">View Faculty Timetable</h3>
                            <p class="text-slate-400 text-sm">View allocations and schedule for a specific faculty
                                member.</p>
                        </div>
                        <!-- Version Select (Reused/Synced) -->
                        <div class="flex gap-2">
                            <select id="view-fac-version-id"
                                class="px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Version...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Faculty Member</label>
                            <select id="view-faculty-id"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700">
                                <option value="">Select Faculty</option>
                                <?php foreach ($all_faculties_list as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="loadFacultyTimetable()"
                                class="w-full py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                                <i class="fas fa-search mr-2"></i> Load Schedule
                            </button>
                        </div>
                    </div>

                    <!-- Grid Container -->
                    <div id="view-fac-grid-container" class="overflow-x-auto rounded-xl border border-slate-200 hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 font-bold w-32 bg-slate-50 sticky left-0 z-10">Day / Period
                                    </th>
                                    <th class="px-6 py-4 text-center">1</th>
                                    <th class="px-6 py-4 text-center">2</th>
                                    <th class="px-6 py-4 text-center">3</th>
                                    <th class="px-6 py-4 text-center">4</th>
                                    <th class="px-6 py-4 text-center bg-slate-100/50">LUNCH</th>
                                    <th class="px-6 py-4 text-center">5</th>
                                    <th class="px-6 py-4 text-center">6</th>
                                    <th class="px-6 py-4 text-center">7</th>
                                </tr>
                            </thead>
                            <tbody id="view-fac-grid-body" class="divide-y divide-slate-100">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                    <div id="view-fac-empty-state" class="py-20 text-center">
                        <div
                            class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 text-2xl">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <p class="text-slate-400 font-medium">Select a faculty to view their schedule.</p>
                    </div>

                    <!-- Allocations Summary -->
                    <div id="view-fac-allocations-wrapper"
                        class="hidden mt-8 border-t border-slate-100 pt-8 animate-fade-in">
                        <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-list-ul text-indigo-500"></i> Allocated Subjects
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                            id="view-fac-allocations-list">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- ADMIN PROFILE SECTION -->
            <section id="profile-section" class="hidden space-y-8 animate-fade-in">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Profile Card -->
                    <div
                        class="bg-white p-8 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50 flex flex-col items-center text-center relative overflow-hidden group">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition duration-500">
                        </div>
                        <div
                            class="w-32 h-32 rounded-full p-1 bg-gradient-to-br from-indigo-500 to-purple-500 mb-6 relative z-10 shadow-lg shadow-indigo-200">
                            <div class="w-full h-full bg-white rounded-full p-1">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=random&size=128"
                                    class="w-full h-full rounded-full object-cover">
                            </div>
                            <button
                                class="absolute bottom-0 right-0 bg-white text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center shadow-md border border-indigo-100 hover:bg-indigo-50 transition">
                                <i class="fas fa-camera text-xs"></i>
                            </button>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 relative z-10 mb-1">
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </h3>
                        <span
                            class="px-4 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold uppercase tracking-wider mb-6 relative z-10">Administrator</span>

                        <div class="w-full grid grid-cols-2 gap-4 relative z-10">
                            <div class="p-4 bg-slate-50 rounded-2xl">
                                <p class="text-[10px] uppercase text-slate-400 font-bold mb-1">Role Type</p>
                                <p class="font-bold text-slate-700">Super Admin</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl">
                                <p class="text-[10px] uppercase text-slate-400 font-bold mb-1">Member Since</p>
                                <p class="font-bold text-slate-700">2024</p>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Forms -->
                    <div class="md:col-span-2 space-y-8">
                        <!-- Personal Details -->
                        <div
                            class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden">
                            <div class="flex items-center gap-4 mb-6">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="fas fa-user-edit"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg text-slate-800">Personal Details</h4>
                                    <p class="text-xs text-slate-400">Update your public profile information.</p>
                                </div>
                            </div>

                            <form id="profile-form" onsubmit="event.preventDefault(); updateProfile(this);">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Full
                                            Name</label>
                                        <input type="text" name="name"
                                            value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>"
                                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition text-sm font-bold text-slate-700">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Email
                                            Address</label>
                                        <input type="email" name="email"
                                            value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@college.edu'); ?>"
                                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition text-sm font-bold text-slate-700">
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 transform active:scale-95">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Security -->
                        <div
                            class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden">
                            <div class="flex items-center gap-4 mb-6">
                                <div
                                    class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg text-slate-800">Security & Password</h4>
                                    <p class="text-xs text-slate-400">Manage your account security.</p>
                                </div>
                            </div>

                            <form id="password-form" onsubmit="event.preventDefault(); changePassword(this);">
                                <div class="space-y-4 mb-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Current
                                            Password</label>
                                        <div class="relative">
                                            <input type="password" name="current_password"
                                                placeholder="Enter current password"
                                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white transition text-sm font-bold text-slate-700">
                                            <i class="fas fa-lock absolute right-4 top-3.5 text-slate-300"></i>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">New
                                                Password</label>
                                            <input type="password" name="new_password" placeholder="Min 8 chars"
                                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white transition text-sm font-bold text-slate-700">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Confirm
                                                Password</label>
                                            <input type="password" name="confirm_password"
                                                placeholder="Repeat new password"
                                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:bg-white transition text-sm font-bold text-slate-700">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-8 py-3 bg-white border-2 border-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-50 hover:text-orange-600 hover:border-orange-100 transition transform active:scale-95">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <?php
            $sections_path = __DIR__ . '/includes/admin_sections.html';
            if (file_exists($sections_path)) {
                include $sections_path;
            }
            ?>


        </div>

        <!-- Modals -->

        <!-- Add Faculty Modal -->
        <div id="faculty-modal"
            class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl relative">
                <button onclick="document.getElementById('faculty-modal').classList.add('hidden')"
                    class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><i
                        class="fas fa-times"></i></button>
                <h3 class="text-xl font-bold text-slate-800 mb-6">Add New Faculty</h3>
                <form id="add-faculty-form" onsubmit="event.preventDefault(); addFaculty();" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Name</label>
                        <input type="text" name="name" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                        <input type="email" name="email" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Designation</label>
                        <input type="text" name="designation" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Department</label>
                        <select name="department_id" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                            <?php foreach ($departments_list as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Max Hours/Week</label>
                        <input type="number" name="max_hours_week" value="20"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="document.getElementById('faculty-modal').classList.add('hidden')"
                            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200">Cancel</button>
                        <button type="submit"
                            class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700">Save
                            Faculty</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Section Modal -->
        <div id="section-modal"
            class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl relative">
                <button onclick="document.getElementById('section-modal').classList.add('hidden')"
                    class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><i
                        class="fas fa-times"></i></button>
                <h3 class="text-xl font-bold text-slate-800 mb-6">Add Class/Section Mapping</h3>
                <form id="add-section-form" onsubmit="event.preventDefault(); addSection();" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Department</label>
                        <select name="department_id" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                            <?php foreach ($departments_list as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Year</label>
                            <input type="number" name="year" required placeholder="e.g 1, 2, 3, 4"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Semester</label>
                            <input type="number" name="semester" required placeholder="1-8"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Section Name</label>
                        <input type="text" name="section_name" required placeholder="A, B, C"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Student Strength</label>
                        <input type="number" name="student_strength" value="60"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="document.getElementById('section-modal').classList.add('hidden')"
                            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200">Cancel</button>
                        <button type="submit"
                            class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700">Save
                            Mapping</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Room Modal -->
        <div id="room-modal"
            class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xlrelative">
                <button onclick="document.getElementById('room-modal').classList.add('hidden')"
                    class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><i
                        class="fas fa-times"></i></button>
                <h3 class="text-xl font-bold text-slate-800 mb-6">Add New Room</h3>
                <form id="add-room-form" onsubmit="event.preventDefault(); addRoom();" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Room Name/Number</label>
                        <input type="text" name="name" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Type</label>
                            <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                                <option value="Lecture">Lecture Hall</option>
                                <option value="Lab">Laboratory</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Capacity</label>
                            <input type="number" name="capacity" value="60" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Equipment</label>
                        <input type="text" name="equipment" placeholder="Projector, Whiteboard, PCs"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="document.getElementById('room-modal').classList.add('hidden')"
                            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200">Cancel</button>
                        <button type="submit"
                            class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700">Add
                            Room</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Department Modal -->
        <div id="dept-modal"
            class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl w-full max-w-sm shadow-2xl relative">
                <button onclick="document.getElementById('dept-modal').classList.add('hidden')"
                    class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><i
                        class="fas fa-times"></i></button>
                <h3 class="text-xl font-bold text-slate-800 mb-6">New Department</h3>
                <form id="add-dept-form" onsubmit="event.preventDefault(); addDept();" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Name</label>
                        <input type="text" name="name" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Code</label>
                        <input type="text" name="code" placeholder="e.g. CS, ME"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2">
                    </div>
                    <div class="pt-4">
                        <button type="submit"
                            class="w-full py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700">Save
                            Department</button>
                    </div>
                </form>
                </form>
            </div>
        </div>

        <!-- Edit Entry Modal -->
        <div id="edit-entry-modal"
            class="hidden fixed inset-0 bg-black/50 z-[70] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-6 rounded-3xl w-full max-w-sm shadow-2xl relative">
                <button onclick="document.getElementById('edit-entry-modal').classList.add('hidden')"
                    class="absolute top-4 right-4 text-slate-300 hover:text-slate-500"><i
                        class="fas fa-times"></i></button>
                <h3 class="text-lg font-bold text-slate-800 mb-4">Edit Timetable Slot</h3>
                <form id="edit-entry-form" onsubmit="event.preventDefault(); saveEditEntry();" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Subject</label>
                        <select name="subject_id" name="edit_subject_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Faculty</label>
                        <select name="faculty_id" name="edit_faculty_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Room</label>
                        <select name="room_id" name="edit_room_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm"></select>
                    </div>
                    <div class="pt-2">
                        <button type="submit" id="btn-save-edit"
                            class="w-full py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <!-- Edit Subject Modal -->
    <div id="edit-subject-modal"
        class="hidden fixed inset-0 bg-black/50 z-[80] flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white p-6 rounded-3xl w-full max-w-2xl shadow-2xl relative max-h-[90vh] overflow-y-auto">
            <button onclick="document.getElementById('edit-subject-modal').classList.add('hidden')"
                class="absolute top-4 right-4 text-slate-300 hover:text-slate-500"><i class="fas fa-times"></i></button>
            <h3 class="text-xl font-bold text-slate-800 mb-1">Edit Subject</h3>
            <p class="text-slate-400 text-sm mb-6">Modify subject details.</p>

            <form id="edit-subject-form" onsubmit="event.preventDefault(); updateSubject();" class="space-y-6">
                <input type="hidden" name="id" id="edit_subject_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject Name</label>
                        <input type="text" name="name" id="edit_subject_name" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition font-bold text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject Code</label>
                        <input type="text" name="code" id="edit_subject_code" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition font-bold text-slate-700">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Department</label>
                        <select name="department_id" id="edit_subject_dept" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition font-bold text-slate-700">
                            <?php
                            $dept_query = "SELECT * FROM departments ORDER BY name ASC";
                            $dept_result = $conn->query($dept_query);
                            if ($dept_result->num_rows > 0) {
                                while ($d = $dept_result->fetch_assoc()) {
                                    echo "<option value='" . $d['id'] . "'>" . $d['name'] . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Credits</label>
                        <input type="number" name="credits" id="edit_subject_credits" value="3" min="1" max="10"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 transition font-bold text-slate-700">
                    </div>
                </div>

                <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100">
                    <h4 class="text-sm font-bold text-indigo-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-layer-group"></i> Batch & Class Details (Optional)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-1">Batch Year</label>
                            <input type="text" name="batch_year" id="edit_subject_batch" placeholder="e.g. 2024-2028"
                                class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-indigo-900">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-1">Academic
                                Year</label>
                            <select name="academic_year" id="edit_subject_year"
                                class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-indigo-900">
                                <option value="">-- Select --</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-1">Semester</label>
                            <select name="semester" id="edit_subject_sem"
                                class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-indigo-900">
                                <option value="">-- Select --</option>
                                <option value="1">Odd (1,3,5,7)</option>
                                <option value="2">Even (2,4,6,8)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-1">Section</label>
                            <select name="section_id" id="edit_subject_sec"
                                class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-indigo-900">
                                <option value="">-- General / Common --</option>
                                <?php
                                $sec_q = "SELECT s.id, s.section_name, d.code FROM sections s JOIN departments d ON s.department_id = d.id ORDER BY d.code, s.section_name";
                                $sec_r = $conn->query($sec_q);
                                if ($sec_r->num_rows > 0) {
                                    while ($sec = $sec_r->fetch_assoc()) {
                                        echo "<option value='" . $sec['id'] . "'>" . $sec['code'] . " - " . $sec['section_name'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" id="btn-update-subject"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-200 transition transform active:scale-95 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            // Update URL hash without jumping if possible
            window.location.hash = sectionId;

            // Alias regenerate to generate
            if (sectionId === 'regenerate') sectionId = 'generate';

            // 1. Hide all sections
            const sections = document.querySelectorAll('section');
            sections.forEach(sec => {
                if (sec.id && sec.id.endsWith('-section')) {
                    sec.classList.add('hidden');
                }
                // Custom check if manual sections array was used previously, but querySelectorAll is safer
            });

            // 2. Remove active class from all links
            const links = document.querySelectorAll('.sidebar-item');
            links.forEach(link => link.classList.remove('active'));

            // 3. Show specific section
            const targetSection = document.getElementById(sectionId + '-section');
            if (targetSection) {
                targetSection.classList.remove('hidden');
            }

            // 4. Add active class to clicked link
            const activeLink = document.getElementById('link-' + sectionId);
            if (activeLink) {
                activeLink.classList.add('active');
            }

            // 5. Update Header Title
            const titles = {
                'overview': 'Dashboard Overview',
                'course-manage': 'Course Management',
                'academic-settings': 'Academic Settings',
                'faculty-allocation': 'Faculty Allocation',
                'class-mapping': 'Class & Section Mapping',
                'dept-manage': 'Department Management',
                'faculty-manage': 'Faculty Management',
                'room-manage': 'Room & Resource Management',
                'generate': 'Generate Timetable',
                'audit-logs': 'System Audit Logs',
                'conflicts': 'Conflict Resolution',
                'regenerate': 'Regenerate Timetable',
                'profile': 'Admin Profile',
                'password': 'Change Password',
                'user-manage': 'User Management',
                'password': 'Change Password',
                'user-manage': 'User Management',
                'view-dept-wise': 'View Timetable (Department)',
                'view-fac-wise': 'View Timetable (Faculty)',
                'reports': 'Reports & Analytics'
            };
            const titleEl = document.getElementById('section-title');
            if (titleEl) {
                titleEl.innerText = titles[sectionId] || 'Admin Dashboard';
            }
        }

        // Handle path on load and hash change
        window.addEventListener('hashchange', () => {
            const h = window.location.hash.replace('#', '');
            if (h) showSection(h);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const h = window.location.hash.replace('#', '');
            showSection(h || 'overview');
        });

        // Backend Interactions
        function saveAcademicSettings(btn) {
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

            const formData = new FormData(document.getElementById('academic-form'));
            fetch('actions/save_academic_settings.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        btn.innerHTML = '<i class="fas fa-check mr-2"></i> Saved!';
                        btn.classList.replace('bg-indigo-600', 'bg-emerald-600');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert(d.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred while saving.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
        }

        function openAllocationModal() {
            document.getElementById('allocation-modal').classList.remove('hidden');
        }

        function saveAllocation() {
            const formData = new FormData(document.getElementById('allocation-form'));
            fetch('actions/manage_allocations.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { alert(d.message); if (d.success) location.reload(); });
        }

        function postAnnouncement() {
            const formData = new FormData(document.getElementById('announcement-form'));
            fetch('actions/manage_announcements.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { alert(d.message); if (d.success) location.reload(); });
        }

        function deleteAnnouncement(id) {
            if (confirm('Delete this announcement?')) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);
                fetch('actions/manage_announcements.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => { if (d.success) location.reload(); });
            }
        }

        function createUserLogin(facId, name, email) {
            const password = prompt("Enter default password for " + name + ":", "Faculty@123");
            if (password) {
                const fd = new FormData();
                fd.append('action', 'create_login');
                fd.append('faculty_id', facId);
                fd.append('name', name);
                fd.append('email', email);
                fd.append('password', password);

                fetch('actions/manage_users.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => { alert(d.message); });
            }
        }

        function addAllocationRow() {
            const container = document.getElementById('allocation-rows');
            const template = container.firstElementChild.cloneNode(true);

            // Reset inputs
            const inputs = template.querySelectorAll('select, input');
            inputs.forEach(input => {
                if (input.tagName === 'SELECT') input.selectedIndex = 0;
                else input.value = input.defaultValue || '';
            });

            // Ensure remove button works and is visible (if hidden in template)
            const removeBtn = template.querySelector('button');
            if (removeBtn) {
                removeBtn.classList.remove('hidden'); // Ensure it's not hidden if the template had it hidden
                // Note: The onclick "this.closest('.allocation-row').remove()" is already on the button in HTML
            }

            container.appendChild(template);

            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        function addDept() {
            const form = document.getElementById('add-dept-form');
            const btn = form.querySelector('button[type="submit"]');
            const originalBtn = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

            const data = new FormData(form);
            fetch('actions/add_dept.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;

                    if (res.success) {
                        showToast(res.message, 'success');
                        const tbody = document.getElementById('dept-table-body');
                        if (tbody.rows.length === 1 && tbody.rows[0].innerText.includes('No departments found')) tbody.innerHTML = '';

                        const d = res.dept;
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-slate-50 transition animate-fade-in';
                        row.innerHTML = `
                            <td class="px-6 py-4 font-bold text-slate-800">${d.name}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">${d.code}</td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="deleteDept(${d.id}, this)" class="text-slate-400 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        tbody.insertBefore(row, tbody.firstChild);
                        form.reset();
                        document.getElementById('dept-modal').classList.add('hidden');

                        // Optionally reload to update dropdowns elsewhere if needed, 
                        // but for now just update the table.
                    } else showToast(res.message, 'error');
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;
                    showToast('Error adding department', 'error');
                });
        }

        function deleteDept(id, btn) {
            if (!confirm('Delete this department? This might affect subjects and faculty linked to it.')) return;

            const row = btn.closest('tr');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const fd = new FormData();
            fd.append('id', id);

            fetch('actions/delete_dept.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        row.classList.add('animate-fade-out');
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.getElementById('dept-table-body');
                            if (tbody.children.length === 0) tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-slate-400 text-sm">No departments found.</td></tr>';
                        }, 400);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        showToast(res.message, 'error');
                    }
                });
        }

        function addRoom() {
            const form = document.getElementById('add-room-form');
            const data = new FormData(form);

            fetch('actions/add_room.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        const container = document.getElementById('rooms-container');
                        if (container.querySelector('p')?.innerText.includes('No rooms found')) {
                            container.innerHTML = '';
                        }
                        const room = res.room;
                        const card = document.createElement('div');
                        card.className = 'p-6 bg-slate-50 rounded-2xl border border-slate-100 relative group overflow-hidden animate-fade-in';
                        card.innerHTML = `
                            <button onclick="deleteRoom(${room.id}, this)" class="absolute top-2 right-2 text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition z-10"><i class="fas fa-trash text-xs"></i></button>
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-sm">
                                    <i class="fas fa-laptop-code text-sm"></i>
                                </div>
                                <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-[9px] font-bold uppercase">${room.type}</span>
                            </div>
                            <h4 class="text-lg font-bold text-slate-800">${room.name}</h4>
                            <div class="mt-4 flex flex-wrap gap-1">
                                <span class="px-2 py-0.5 border border-slate-200 rounded text-[8px] text-slate-500">${room.equipment || 'Standard'}</span>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Capacity</p>
                                    <p class="text-xl font-extrabold text-slate-800">${room.capacity}</p>
                                </div>
                                <div class="w-10 h-10 rounded-full border-2 border-emerald-500 flex items-center justify-center text-emerald-500">
                                    <span class="text-[10px] font-bold">OK</span>
                                </div>
                            </div>
                        `;
                        container.insertBefore(card, container.firstChild);
                        form.reset();
                        document.getElementById('room-modal').classList.add('hidden');
                    } else {
                        alert(res.message);
                    }
                });
        }

        function deleteRoom(id, btn) {
            if (!confirm('Are you sure you want to delete this room?')) return;
            const fd = new FormData();
            fd.append('id', id);
            fetch('actions/delete_room.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        btn.closest('.group').remove();
                        const container = document.getElementById('rooms-container');
                        if (container.children.length === 0) {
                            container.innerHTML = '<p class="text-slate-400 text-sm">No rooms found.</p>';
                        }
                    } else {
                        alert(res.message);
                    }
                });
        }

        function addSection() {
            const form = document.getElementById('add-section-form');
            const data = new FormData(form);

            fetch('actions/add_section.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        const tbody = document.getElementById('sections-table-body');
                        if (tbody.rows.length === 1 && tbody.rows[0].innerText.includes('No sections found')) {
                            tbody.innerHTML = '';
                        }
                        const sec = res.section;
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-slate-50 transition animate-fade-in';
                        row.innerHTML = `
                            <td class="px-6 py-4 text-sm font-bold text-indigo-600">${sec.department_id}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">Yr ${sec.year}, Sem ${sec.semester}</td>
                            <td class="px-6 py-4 text-sm font-bold text-indigo-600">${sec.section_name}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">${sec.student_strength}</td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-indigo-600 mx-2"><i class="fas fa-edit"></i></button>
                                <button onclick="deleteSection(${sec.id}, this)" class="text-slate-400 hover:text-red-500 mx-2"><i class="fas fa-trash"></i></button>
                            </td>
                        `;
                        tbody.insertBefore(row, tbody.firstChild);
                        form.reset();
                        document.getElementById('section-modal').classList.add('hidden');
                    } else {
                        alert(res.message);
                    }
                });
        }

        function deleteSection(id, btn) {
            if (!confirm('Are you sure you want to delete this section?')) return;

            const row = btn.closest('tr');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const fd = new FormData();
            fd.append('id', id);
            fetch('actions/delete_section.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        row.classList.add('animate-fade-out');
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.getElementById('sections-table-body');
                            if (tbody.children.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No sections found.</td></tr>';
                            }
                        }, 400);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        showToast(res.message, 'error');
                    }
                });
        }

        function toggleAddSubjectForm() {
            const form = document.getElementById('add-subject-form');
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                form.classList.add('animate-fade-in');
                form.querySelector('input').focus();
            } else {
                form.classList.add('animate-fade-out');
                setTimeout(() => {
                    form.classList.add('hidden');
                    form.classList.remove('animate-fade-out');
                }, 400);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            checkGenerationStatus();
            loadViewVersions(); // Load versions for viewer
            // Poll status every 30 seconds
            setInterval(checkGenerationStatus, 30000);
        });

        // Generation Logic
        let currentDraftId = null;

        function log(msg) {
            const out = document.getElementById('console-output');
            const lines = document.getElementById('console-lines');
            out.classList.remove('hidden');
            const p = document.createElement('p');
            p.innerHTML = `<span class="text-indigo-400">[${new Date().toLocaleTimeString()}]</span> ${msg}`;
            lines.appendChild(p);
            out.scrollTop = out.scrollHeight;
        }

        function checkGenerationStatus() {
            fetch('actions/get_generation_status.php')
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const statusBadge = document.getElementById('status-badge');
                        const statusDesc = document.getElementById('status-desc');
                        const lastUpd = document.getElementById('status-last-updated');

                        // Status Widget Update
                        if (d.active) {
                            statusBadge.innerText = 'Active';
                            statusDesc.innerText = 'System is running on Version: ' + d.active.version_name;
                            lastUpd.innerText = new Date(d.active.created_at).toLocaleDateString();
                        } else if (d.draft) {
                            statusBadge.innerText = 'Draft Mode';
                            statusDesc.innerText = 'Draft available: ' + d.draft.version_name;
                            lastUpd.innerText = new Date(d.draft.created_at).toLocaleDateString();
                            currentDraftId = d.draft.id;
                        } else {
                            statusBadge.innerText = 'Not Generated';
                            statusDesc.innerText = 'No timetable versions found.';
                            lastUpd.innerText = '-';
                        }
                    }
                });
        }

        function startGeneration() {
            const btn = document.getElementById('btn-generate');
            const resDiv = document.getElementById('generation-results');
            const consoleOut = document.getElementById('console-output');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-cog fa-spin"></i> Generating...';
            resDiv.classList.add('hidden');
            consoleOut.classList.remove('hidden');
            document.getElementById('console-lines').innerHTML = ''; // Request clear

            log('Initializing generation process...');

            fetch('actions/generate_timetable_v2.php')
                .then(r => r.text()) // Get text first
                .then(text => {
                    try {
                        const d = JSON.parse(text); // Try parse
                        if (d.success) {
                            log('Generation complete!');
                            log(`Entries created: ${d.entries_count}`);
                            log(`Conflicts detected: ${d.conflicts_count}`);

                            currentDraftId = d.version_id;

                            btn.innerHTML = '<i class="fas fa-play"></i> Regenerate';
                            btn.disabled = false;

                            resDiv.classList.remove('hidden');
                            resDiv.classList.add('animate-fade-in');

                            checkGenerationStatus(); // Update widget
                        } else {
                            log('Error: ' + d.message);
                            alert('Generation Failed: ' + d.message);
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-redo"></i> Retry';
                        }
                    } catch (e) {
                        // JSON Parse Failed
                        console.error('Raw Server Response:', text);
                        log('Fatal Error: Invalid Server Response');
                        log('Raw Output: ' + text.substring(0, 100) + '...');
                        alert('Server Error: ' + text); // Show the user the raw PHP error
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-redo"></i> Retry';
                    }
                })
                .catch(e => {
                    log('Network Error: ' + e);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-redo"></i> Retry';
                });
        }

        function publishTimetable() {
            if (!currentDraftId) return;

            const btn = document.getElementById('btn-publish');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const fd = new FormData();
            fd.append('version_id', currentDraftId);

            fetch('actions/publish_timetable.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast('Timetable Published Successfully!', 'success');
                        checkGenerationStatus();
                        // Reset UI
                        document.getElementById('generation-results').classList.add('hidden');
                        document.getElementById('console-output').classList.add('hidden');
                    } else {
                        showToast(d.message, 'error');
                        btn.disabled = false;
                        btn.innerHTML = 'Publish';
                    }
                });
        }

        // View Timetable Logic
        function loadViewVersions() {
            fetch('actions/get_generation_status.php')
                .then(r => r.json())
                .then(d => {
                    const updateSelect = (id) => {
                        const sel = document.getElementById(id);
                        if (!sel) return;
                        sel.innerHTML = '<option value="">Select Version...</option>';
                        if (d.active) {
                            const opt = document.createElement('option');
                            opt.value = d.active.id;
                            opt.innerText = `Active: ${d.active.version_name} (${new Date(d.active.created_at).toLocaleDateString()})`;
                            opt.selected = true;
                            sel.appendChild(opt);
                        }
                        if (d.draft) {
                            const opt = document.createElement('option');
                            opt.value = d.draft.id;
                            opt.innerText = `Draft: ${d.draft.version_name} (${new Date(d.draft.created_at).toLocaleDateString()})`;
                            sel.appendChild(opt);
                        }
                    };
                    updateSelect('view-version-id');
                    updateSelect('view-fac-version-id');
                });
        }

        function loadTimetableGrid() {
            const vId = document.getElementById('view-version-id').value;
            const sId = document.getElementById('view-section-id').value;

            if (!vId || !sId) {
                showToast('Please select Version and Section', 'info');
                return;
            }

            const btn = document.querySelector('button[onclick="loadTimetableGrid()"]');
            const originalInfo = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            fetch(`actions/fetch_timetable_grid.php?version_id=${vId}&section_id=${sId}`)
                .then(r => r.json())
                .then(d => {
                    btn.disabled = false;
                    btn.innerHTML = originalInfo;

                    if (d.success) {
                        renderGrid(d.entries);
                        renderAllocations(d.allocations);
                    } else {
                        showToast(d.message, 'error');
                    }
                });
        }

        function renderGrid(entries) {
            const tbody = document.getElementById('view-grid-body');
            const container = document.getElementById('view-grid-container');
            const empty = document.getElementById('view-empty-state');

            tbody.innerHTML = '';

            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

            days.forEach(day => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition border-b border-slate-50 last:border-b-0';

                let html = `<td class="px-6 py-6 font-bold text-slate-800 bg-white sticky left-0 border-r border-slate-100">${day}</td>`;

                // Periods 1-4
                for (let i = 1; i <= 4; i++) {
                    html += getCellHtml(day, i, entries);
                }

                // Lunch
                html += `<td class="px-2 py-4 text-center bg-slate-50/50"><div class="h-full w-px mx-auto bg-slate-200 dashed"></div></td>`;

                // Periods 5-7
                for (let i = 5; i <= 7; i++) {
                    html += getCellHtml(day, i, entries);
                }

                tr.innerHTML = html;
                tbody.appendChild(tr);
            });

            container.classList.remove('hidden');
            empty.classList.add('hidden');
        }

        function getCellHtml(day, period, entries) {
            const key = `${day}-${period}`;
            const entry = entries[key];

            if (entry) {
                return `
                    <td class="px-4 py-4 align-top h-32 w-48 border-r border-slate-100 last:border-r-0">
                        <div class="h-full flex flex-col justify-between group cursor-pointer hover:bg-white p-2 rounded-xl transition border border-transparent hover:border-indigo-100 hover:shadow-sm">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-1 block">
                                    ${entry.subject_code}
                                </span>
                                <h4 class="text-sm font-bold text-slate-800 leading-tight mb-2 line-clamp-2" title="${entry.subject_name}">
                                    ${entry.subject_name}
                                </h4>
                            </div>
                            <div class="border-t border-slate-100 pt-2 mt-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="text-xs text-slate-500 font-medium truncate max-w-[100px]" title="${entry.faculty_name}">
                                        ${entry.faculty_name}
                                    </span>
                                </div>
                                 <div class="flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <span class="text-xs text-slate-500 font-medium">
                                        ${entry.room_number || 'N/A'}
                                    </span>
                                </div>
                            </div>
                            <button onclick='openEditModal(${JSON.stringify(entry)})' class="absolute top-2 right-2 text-slate-400 hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition bg-white rounded-full w-6 h-6 shadow-sm border border-slate-100 flex items-center justify-center">
                                <i class="fas fa-pencil-alt text-[10px]"></i>
                            </button>
                        </div>
                    </td>
                 `;
            } else {
                return `
                    <td class="px-4 py-4 align-top h-32 border-r border-slate-100 last:border-r-0">
                        <div class="h-full flex items-center justify-center rounded-xl border border-dashed border-slate-200/50 bg-slate-50/30">
                            <span class="text-[10px] font-bold text-slate-300">FREE</span>
                        </div>
                    </td>
                `;
            }
        }

        function renderAllocations(allocations) {
            const wrapper = document.getElementById('view-allocations-wrapper');
            const container = document.getElementById('view-allocations-list');
            wrapper.classList.add('hidden');
            container.innerHTML = '';

            if (allocations && allocations.length > 0) {
                wrapper.classList.remove('hidden');
                allocations.forEach(a => {
                    const div = document.createElement('div');
                    div.className = 'p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-4 hover:shadow-md transition bg-white';
                    div.innerHTML = `
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm font-bold text-xs uppercase border border-indigo-100">
                            ${a.subject_code ? a.subject_code.substring(0, 3) : 'SUB'}
                        </div>
                        <div class="overflow-hidden">
                           <h5 class="text-sm font-bold text-slate-800 truncate" title="${a.subject_name}">${a.subject_name}</h5>
                           <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                <span class="flex items-center gap-1"><i class="fas fa-user-circle"></i> ${a.faculty_name}</span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span class="font-medium text-slate-600">${a.weekly_hours} Hrs</span>
                           </div>
                        </div>
                    `;
                    container.appendChild(div);
                });
            }
        }

        // --- Existing Functions Below ---
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            let icon = 'fa-check-circle';
            if (type === 'error') icon = 'fa-exclamation-circle';
            if (type === 'info') icon = 'fa-info-circle';

            toast.innerHTML = `
                <i class="fas ${icon} ${type === 'success' ? 'text-emerald-500' : type === 'error' ? 'text-red-500' : 'text-blue-500'}"></i>
                <div class="flex-1">
                    <p class="text-sm font-bold text-slate-800">${message}</p>
                </div>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('animate-fade-out');
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        // --- New Filter Functions ---
        function filterSubjectSections() {
            const deptId = document.getElementById('subject_dept_id').value;
            const secSel = document.getElementById('subject_section_id');
            const yearSel = document.getElementById('subject_year');
            const semSel = document.getElementById('subject_semester');

            secSel.innerHTML = '<option value="">-- All Sections --</option>';

            if (!deptId) return;

            // Filter ALL_SECTIONS
            if (typeof ALL_SECTIONS !== 'undefined') {
                const filtered = ALL_SECTIONS.filter(s => s.department_id == deptId);
                filtered.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.innerText = `${s.section_name} (Yr ${s.year}, Sem ${s.semester})`;
                    opt.dataset.year = s.year;
                    opt.dataset.sem = s.semester;
                    secSel.appendChild(opt);
                });
            }

            // Auto-select logic
            secSel.onchange = function () {
                const opt = secSel.options[secSel.selectedIndex];
                if (opt.value && opt.dataset.year) {
                    yearSel.value = opt.dataset.year;
                    semSel.value = opt.dataset.sem;
                }
            }
        }

        // Init View Filter
        document.addEventListener('DOMContentLoaded', () => {
            const viewDept = document.getElementById('view-dept-id');
            if (viewDept) {
                viewDept.addEventListener('change', function () {
                    const deptId = this.value;
                    const secSel = document.getElementById('view-section-id');
                    secSel.innerHTML = '<option value="">Select Section</option>';
                    if (!deptId) return;

                    if (typeof ALL_SECTIONS !== 'undefined') {
                        const filtered = ALL_SECTIONS.filter(s => s.department_id == deptId);
                        filtered.forEach(s => {
                            const opt = document.createElement('option');
                            opt.value = s.id;
                            opt.innerText = `${s.section_name} (Yr ${s.year}, Sem ${s.semester})`;
                            secSel.appendChild(opt);
                        });
                    }
                });
            }
        });


        function addSubject() {
            const form = document.getElementById('add-subject-form');
            const btn = form.querySelector('button[type="submit"]');
            const originalBtn = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';

            const data = new FormData(form);

            fetch('actions/add_subject.php', { method: 'POST', body: data })
                .then(res => res.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;

                    if (res.success) {
                        showToast(res.message, 'success');

                        const tbody = document.getElementById('subjects-table-body');
                        if (tbody.rows.length === 1 && tbody.rows[0].innerText.includes('No subjects found')) {
                            tbody.innerHTML = '';
                        }

                        const sub = res.subject;
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-slate-50 transition animate-fade-in';

                        let details = [];
                        if (sub.batch_year) details.push(sub.batch_year);
                        if (sub.academic_year) details.push("Yr " + sub.academic_year);
                        if (sub.semester) details.push("Sem " + sub.semester);
                        if (sub.section_id) details.push("Sec ID:" + sub.section_id);

                        row.innerHTML = `
                            <td class="px-6 py-4 text-sm font-bold text-slate-700">${sub.code}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${sub.name}</td>
                            <td class="px-6 py-4 text-sm font-bold text-indigo-600">${sub.credits}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">${details.join(' • ')}</td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='openEditSubjectModal(${JSON.stringify(sub)})' class="text-slate-400 hover:text-indigo-600 mx-1 p-2 rounded-lg hover:bg-indigo-50 transition"><i class="fas fa-edit"></i></button>
                                <button onclick="deleteSubject(${sub.id}, this)" class="text-slate-400 hover:text-red-500 mx-1 p-2 rounded-lg hover:bg-red-50 transition"><i class="fas fa-trash"></i></button>
                            </td>
                        `;
                        tbody.insertBefore(row, tbody.firstChild);
                        form.reset();

                        // NEW: Dynamically update Allocation Dropdowns
                        // 1. Update existing selects in the modal
                        const allocSelects = document.querySelectorAll('select[name="subject_id[]"]');
                        allocSelects.forEach(sel => {
                            const opt = document.createElement('option');
                            opt.value = sub.id;
                            opt.innerText = `${sub.name} (${sub.code})`;
                            sel.appendChild(opt);
                        });

                        // 2. Update global ALL_SUBJECTS array if it exists (for robustness)
                        if (typeof ALL_SUBJECTS !== 'undefined') {
                            ALL_SUBJECTS.push(sub);
                        }
                    } else {
                        showToast(res.message, 'error');
                        if (res.message && res.message.includes('Unauthorized')) {
                            window.location.href = 'admin_login.php';
                        }
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;
                    console.error(err);
                    showToast('Error adding subject. Check console.', 'error');
                });
        }

        function addFaculty() {
            const form = document.getElementById('add-faculty-form');
            const data = new FormData(form);

            fetch('actions/add_faculty.php', { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        const tbody = document.getElementById('faculty-table-body');
                        if (tbody.rows.length === 1 && tbody.rows[0].innerText.includes('No faculties found')) {
                            tbody.innerHTML = '';
                        }
                        const fac = res.faculty;
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-slate-50 transition animate-fade-in';
                        row.innerHTML = `
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs uppercase">
                                        ${fac.name.substring(0, 2)}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">${fac.name}</p>
                                        <p class="text-[10px] text-slate-400">${fac.email}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-700">${data.get('max_hours_week') || 20}</td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-indigo-600 mx-2" title="Manage Constraints"><i class="fas fa-sliders-h"></i></button>
                                <button onclick="deleteFaculty(${fac.id}, this)" class="text-slate-400 hover:text-red-500 mx-2" title="Delete Faculty"><i class="fas fa-trash"></i></button>
                            </td>
                        `;
                        tbody.insertBefore(row, tbody.firstChild);
                        form.reset();
                        document.getElementById('faculty-modal').classList.add('hidden');
                    } else {
                        alert(res.message);
                    }
                });
        }

        function deleteFaculty(id, btn) {
            if (!confirm('Are you sure you want to delete this faculty member?')) return;

            const row = btn.closest('tr');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const fd = new FormData();
            fd.append('id', id);
            fetch('actions/delete_faculty.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        row.classList.add('animate-fade-out');
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.getElementById('faculty-table-body');
                            if (tbody.children.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-slate-400 text-sm">No faculties found.</td></tr>';
                            }
                        }, 400);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        showToast(res.message, 'error');
                    }
                });
        }

        function deleteSubject(id, btn) {
            if (!confirm('Are you sure you want to delete this subject?')) return;

            const row = btn.closest('tr');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const fd = new FormData();
            fd.append('id', id);

            fetch('actions/delete_subject.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        row.classList.add('animate-fade-out');
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.getElementById('subjects-table-body');
                            if (tbody.children.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No subjects found.</td></tr>';
                            }
                        }, 400);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        showToast(res.message, 'error');
                        if (res.message && res.message.includes('Unauthorized')) {
                            window.location.href = 'admin_login.php';
                        }
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    console.error(err);
                    showToast('Error deleting subject.', 'error');
                });
        }

        function openEditSubjectModal(sub) {
            const modal = document.getElementById('edit-subject-modal');
            const form = document.getElementById('edit-subject-form');

            // Populate form
            document.getElementById('edit_subject_id').value = sub.id;
            document.getElementById('edit_subject_name').value = sub.name;
            document.getElementById('edit_subject_code').value = sub.code;
            document.getElementById('edit_subject_credits').value = sub.credits;
            document.getElementById('edit_subject_dept').value = sub.department_id;
            document.getElementById('edit_subject_batch').value = sub.batch_year || '';

            // Optional Fields
            if (sub.academic_year) document.getElementById('edit_subject_year').value = sub.academic_year;
            if (sub.semester) document.getElementById('edit_subject_sem').value = sub.semester;
            if (sub.section_id) document.getElementById('edit_subject_sec').value = sub.section_id;

            modal.classList.remove('hidden');
        }

        function updateSubject() {
            const form = document.getElementById('edit-subject-form');
            const btn = document.getElementById('btn-update-subject');
            const originalHTML = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const fd = new FormData(form);

            fetch('actions/update_subject.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;

                    if (res.success) {
                        showToast(res.message, 'success');
                        document.getElementById('edit-subject-modal').classList.add('hidden');
                        setTimeout(() => location.reload(), 1000); // Reload to reflect changes
                    } else {
                        showToast(res.message, 'error');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    showToast('Error updating subject', 'error');
                });
        }



        function updateProfile(form) {
            const btn = form.querySelector('button[type="submit"]');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const fd = new FormData(form);
            fetch('actions/update_profile.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    if (res.success) {
                        showToast(res.message, 'success');
                    } else {
                        showToast(res.message, 'error');
                    }
                })
                .catch(e => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    showToast('Connection error', 'error');
                });
        }

        function changePassword(form) {
            const btn = form.querySelector('button[type="submit"]');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            const fd = new FormData(form);
            fetch('actions/change_password.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    if (res.success) {
                        showToast(res.message, 'success');
                        form.reset();
                    } else {
                        showToast(res.message, 'error');
                    }
                })
                .catch(e => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    showToast('Connection error', 'error');
                });
        }
        // PDF Export
        async function downloadTimetablePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4'); // Landscape

            const deptSel = document.getElementById('view-dept-id');
            const deptName = deptSel.options[deptSel.selectedIndex].text;
            const versionSel = document.getElementById('view-version-id');
            const verName = versionSel.options[versionSel.selectedIndex].text;

            doc.setFontSize(18);
            doc.text("Master Timetable - " + deptName, 14, 22);
            doc.setFontSize(11);
            doc.text("Version: " + verName, 14, 30);
            doc.text("Generated: " + new Date().toLocaleDateString(), 14, 36);

            const table = document.querySelector('#view-grid-container table');

            if (!table) {
                alert("Please load a timetable first.");
                return;
            }

            doc.autoTable({
                html: table,
                startY: 45,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [16, 185, 129] }, // Emerald color
                didParseCell: function (data) {
                    // Start from 1 because index 0 is time column
                    if (data.section === 'body' && data.column.index > 0) {
                        // Clean up cell text (remove icons etc if needed)
                        let text = data.cell.raw.innerText || "";
                        // Replace newlines with comma for compactness if needed, or keep as is
                        data.cell.text = text.trim();
                    }
                }
            });

            doc.save(`Timetable_${deptName.replace(/\s+/g, '_')}.pdf`);
        }
    </script>
    async function downloadTimetablePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); // Landscape

    // Check if table is visible
    const container = document.getElementById('view-grid-container');
    if (container.classList.contains('hidden')) {
    showToast('Please load a timetable first!', 'error');
    return;
    }

    // Get Title Info
    const deptSelect = document.getElementById('view-dept-id');
    const sectionSelect = document.getElementById('view-section-id');

    const deptName = deptSelect.options[deptSelect.selectedIndex].text.trim();
    const sectionName = sectionSelect.options[sectionSelect.selectedIndex].text.trim();

    doc.setFontSize(22);
    doc.setTextColor(40);
    doc.text('Class Timetable', 14, 15);

    doc.setFontSize(12);
    doc.setTextColor(100);
    doc.text(`Department: ${deptName}`, 14, 25);
    doc.text(`Section: ${sectionName}`, 14, 32);
    doc.text(`Generated: ${new Date().toLocaleDateString()}`, 250, 32, { align: 'right' });

    // Prepare Data for AutoTable
    // We manually parse to ensure clean output, as HTML parsing of complex divs can be messy
    const table = container.querySelector('table');
    const rows = [];
    const headers = ['Day / Period', '1', '2', '3', 'Lunch', '4', '5', '6', '7']; // Simplified headers

    // Iterate Rows
    const trs = table.querySelectorAll('tbody tr');
    trs.forEach(tr => {
    const rowData = [];
    // Day
    rowData.push(tr.cells[0].innerText.trim());

    // Periods
    for(let i=1; i<tr.cells.length; i++) { let cellText=tr.cells[i].innerText.trim(); // Clean up newlines from the UI
        card layout to make it comma separated or just clean space cellText=cellText.replace(/\n\s*\n/g, '\n' ); //
        Remove multiple empty lines rowData.push(cellText); } rows.push(rowData); }); doc.autoTable({ head: [headers],
        body: rows, startY: 40, theme: 'grid' , headStyles: { fillColor: [79, 70, 229], // Indigo 600 textColor: 255,
        fontStyle: 'bold' }, styles: { fontSize: 10, cellPadding: 4, valign: 'middle' , overflow: 'linebreak' },
        columnStyles: { 0: { fontStyle: 'bold' , fillColor: [248, 250, 252] } // First Col (Day) }, didDrawPage:
        function (data) { // Footer doc.setFontSize(10); doc.text('Generated by AutoTime', data.settings.margin.left,
        doc.internal.pageSize.height - 10); } }); doc.save(`Timetable_${sectionName.replace(/[^a-z0-9]/gi, '_' )}.pdf`);
        showToast('PDF Downloaded!', 'success' ); } // --- Manual Override Logic --- let currentEditEntryId=null;
        function openEditModal(entry) { currentEditEntryId=entry.id; // Populate Dropdowns const
        subSel=document.querySelector('select[name="edit_subject_id" ]'); const
        facSel=document.querySelector('select[name="edit_faculty_id" ]'); const
        roomSel=document.querySelector('select[name="edit_room_id" ]'); const
        viewDeptId=document.getElementById('view-dept-id').value; subSel.innerHTML='' ; ALL_SUBJECTS.forEach(s=> {
        if (viewDeptId && s.department_id != viewDeptId) return;

        const opt = document.createElement('option');
        opt.value = s.id;
        opt.text = `${s.name} (${s.code})`;
        if(s.id == entry.subject_id) opt.selected = true;
        subSel.appendChild(opt);
        });

        facSel.innerHTML = '';
        ALL_FACULTIES.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.id;
        opt.text = f.name;
        if(f.id == entry.faculty_id) opt.selected = true;
        facSel.appendChild(opt);
        });

        roomSel.innerHTML = '<option value="">No Room</option>';
        ALL_ROOMS.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.text = `${r.name} (${r.type})`;
        if(r.id == entry.room_id) opt.selected = true;
        roomSel.appendChild(opt);
        });

        document.getElementById('edit-entry-modal').classList.remove('hidden');
        }

        function saveEditEntry(force = false) {
        const fd = new FormData(document.getElementById('edit-entry-form'));
        fd.append('entry_id', currentEditEntryId);
        if(force) fd.append('force', 'true');

        const btn = document.getElementById('btn-save-edit');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('actions/update_timetable_entry.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
        btn.disabled = false;
        btn.innerHTML = original;

        if(d.success) {
        showToast(d.message, 'success');
        document.getElementById('edit-entry-modal').classList.add('hidden');
        loadTimetableGrid(); // Refresh
        } else if (d.status === 'conflict') {
        if(confirm("Conflict Detected:\n" + d.message + "\n\nDo you want to FORCE this assignment anyway?")) {
        saveEditEntry(true);
        }
        } else {
        showToast(d.message, 'error');
        }
        });
        }

        // --- Reports Logic ---
        function loadWorkloadReport() {
        const container = document.getElementById('workload-container');
        if(!container) return; // Guard clause

        container.innerHTML = '<div class="text-slate-400 text-sm italic">Loading workload data...</div>';

        fetch('actions/fetch_workload.php')
        .then(r => r.json())
        .then(res => {
        if(res.success) {
        renderWorkload(res.data);
        } else {
        container.innerHTML = `<div class="text-red-500 text-sm font-bold">${res.message}</div>`;
        }
        })
        .catch(err => {
        container.innerHTML = `<div class="text-red-500 text-sm">Error: ${err.message}</div>`;
        });
        }

        function renderWorkload(data) {
        const container = document.getElementById('workload-container');
        if(!data || data.length === 0) {
        container.innerHTML = '<div class="text-slate-400">No data found.</div>';
        return;
        }

        let html = '';
        const maxLoad = 20; // Assumption for bar scaling

        data.forEach(fac => {
        const hours = parseInt(fac.total_hours);
        const percent = Math.min((hours / maxLoad) * 100, 100);

        let colorClass = 'bg-emerald-500';
        if(hours > 18) colorClass = 'bg-red-500'; // Overload
        else if (hours < 12) colorClass='bg-amber-400' ; // Underload html +=` <div
            class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100 animate-fade-in">
            <div
                class="w-10 h-10 rounded-full bg-white flex items-center justify-center font-bold text-slate-600 text-xs shadow-sm">
                ${fac.name.substring(0, 2).toUpperCase()}
            </div>
            <div class="flex-1">
                <div class="flex justify-between mb-1">
                    <h4 class="font-bold text-slate-700 text-sm">${fac.name} <span
                            class="text-slate-400 font-normal text-xs">(${fac.dept_name || 'N/A'})</span></h4>
                    <span class="font-bold text-slate-800 text-sm">${hours} Hrs</span>
                </div>
                <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full ${colorClass}" style="width: ${percent}%"></div>
                </div>
            </div>
            </div>
            `;
            });
            container.innerHTML = html;
            }

            // --- Faculty View Logic ---
            function loadFacultyTimetable() {
            const vId = document.getElementById('view-fac-version-id').value ||
            document.getElementById('view-version-id').value;
            const fId = document.getElementById('view-faculty-id').value;

            // Sync versions if needed
            if (!document.getElementById('view-fac-version-id').value && vId) {
            // Try to populate if empty
            loadViewVersions();
            // Small delay or just proceed
            }

            if (!vId || !fId) {
            showToast('Please select Version and Faculty', 'info');
            return;
            }

            const btn = document.querySelector('button[onclick="loadFacultyTimetable()"]');
            const originalInfo = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            fetch(`actions/fetch_faculty_timetable.php?version_id=${vId}&faculty_id=${fId}`)
            .then(r => r.json())
            .then(d => {
            btn.disabled = false;
            btn.innerHTML = originalInfo;

            if (d.success) {
            renderFacultyGrid(d.entries);
            renderFacultyAllocations(d.allocations);
            document.getElementById('view-fac-allocations-wrapper').classList.remove('hidden');
            } else {
            showToast(d.message, 'error');
            }
            });
            }

            function renderFacultyGrid(entries) {
            const tbody = document.getElementById('view-fac-grid-body');
            const container = document.getElementById('view-fac-grid-container');
            const empty = document.getElementById('view-fac-empty-state');

            tbody.innerHTML = '';
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

            days.forEach(day => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition border-b border-slate-50 last:border-b-0';
            let html = `<td class="px-6 py-6 font-bold text-slate-800 bg-white sticky left-0 border-r border-slate-100">
                ${day}</td>`;

            for (let i = 1; i <= 4; i++) { html +=getFacultyCellHtml(day, i, entries); } html +=`<td
                class="px-2 py-4 text-center bg-slate-50/50">
                <div class="h-full w-px mx-auto bg-slate-200 dashed"></div>
                </td>`;
                for (let i = 5; i <= 7; i++) { html +=getFacultyCellHtml(day, i, entries); } tr.innerHTML=html;
                    tbody.appendChild(tr); }); container.classList.remove('hidden'); empty.classList.add('hidden'); }
                    function getFacultyCellHtml(day, period, entries) { const key=`${day}-${period}`; const
                    entry=entries[key]; if (entry) { return ` <td
                    class="px-4 py-4 align-top h-32 w-48 border-r border-slate-100 last:border-r-0">
                    <div
                        class="h-full flex flex-col justify-between group cursor-pointer hover:bg-white p-2 rounded-xl transition border border-transparent hover:border-indigo-100 hover:shadow-sm">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 mb-1 block">
                                ${entry.subject_code}
                            </span>
                            <h4 class="text-sm font-bold text-slate-800 leading-tight mb-2 line-clamp-2"
                                title="${entry.subject_name}">
                                ${entry.subject_name}
                            </h4>
                        </div>
                        <div class="border-t border-slate-100 pt-2 mt-2">
                            <div class="flex items-center gap-2 mb-1">
                                <div
                                    class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <span class="text-xs text-slate-500 font-medium truncate max-w-[100px]"
                                    title="${entry.section}">
                                    ${entry.section}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span class="text-xs text-slate-500 font-medium">
                                    ${entry.room || 'N/A'}
                                </span>
                            </div>
                        </div>
                    </div>
                    </td>
                    `;
                    } else {
                    return `
                    <td class="px-4 py-4 align-top h-32 border-r border-slate-100 last:border-r-0">
                        <div
                            class="h-full rounded-xl border border-dashed border-slate-100 flex items-center justify-center text-slate-200">
                            <span class="text-xs">Free</span>
                        </div>
                    </td>`;
                    }
                    }

                    // Faculty Allocations Renderer (Clean Version)
                    function renderFacultyAllocations(allocations) {
                    const containerId = 'view-fac-allocations-list';
                    const container = document.getElementById(containerId);
                    if (!container) return;
                    container.innerHTML = '';

                    if (!allocations || allocations.length === 0) {
                    container.innerHTML = '<p class="col-span-3 text-center text-slate-400">No subjects allocated.</p>';
                    return;
                    }

                    allocations.forEach(a => {
                    const card = document.createElement('div');
                    card.className = 'p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-start
                    justify-between hover:shadow-sm transition';

                    // Safe property access
                    const subjectCode = a.subject_code || 'N/A';
                    const subjectName = a.subject_name || 'Unknown Subject';
                    const sectionDisplay = a.section || a.section_name || 'All Sections';
                    const hours = a.weekly_hours || 0;

                    card.innerHTML = `
                    <div>
                        <span
                            class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider mb-1 block">${subjectCode}</span>
                        <h5 class="font-bold text-slate-800 text-sm mb-1">${subjectName}</h5>
                        <div class="flex gap-2 text-xs text-slate-500">
                            <span
                                class="bg-white px-2 py-0.5 rounded border border-slate-200 text-[10px] font-bold uppercase">${sectionDisplay}</span>
                            <span
                                class="bg-white px-2 py-0.5 rounded border border-slate-200 text-[10px] font-bold">${hours}
                                Hrs/Wk</span>
                        </div>
                    </div>
                    `;
                    container.appendChild(card);
                    });
                    document.getElementById(containerId.replace('list', 'wrapper')).classList.remove('hidden');
                    }
                    // Lazy load trigger
                    window.addEventListener('hashchange', () => {
                    if(window.location.hash === '#reports') loadWorkloadReport();});
                    </script>
</body>

</html>