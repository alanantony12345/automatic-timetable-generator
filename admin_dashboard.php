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
        $sub_query = "SELECT s.*, d.name AS department_name FROM subjects s LEFT JOIN departments d ON s.department_id = d.id ORDER BY s.created_at DESC LIMIT 10";
        if ($res = $conn->query($sub_query)) {
            while ($row = $res->fetch_assoc())
                $subjects_list[] = $row;
        }

        // Fetch All Subjects
        if ($res = $conn->query("SELECT s.*, d.name AS department_name FROM subjects s LEFT JOIN departments d ON s.department_id = d.id ORDER BY s.name ASC")) {
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
        $sec_query = "SELECT * FROM sections LIMIT 10";
        if ($res = $conn->query($sec_query)) {
            while ($row = $res->fetch_assoc())
                $sections_list[] = $row;
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
    $res = $conn->query("SHOW TABLES LIKE 'academic_settings'");
    if ($res && $res->num_rows > 0) {
        if ($res = $conn->query("SELECT * FROM academic_settings")) {
            while ($row = $res->fetch_assoc())
                $academic_settings[$row['key_name']] = $row['value'];
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
    </style>
</head>

<body class="overflow-hidden">
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
                        <a href="#"
                            class="block py-1.5 text-sm text-slate-500 hover:text-indigo-600 transition">Department-wise</a>
                        <a href="#"
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
                        <button
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-indigo-700 transition"><i
                                class="fas fa-plus mr-2 text-xs"></i> Add Subject</button>
                    </div>

                    <form id="add-subject-form" onsubmit="event.preventDefault(); addSubject();">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8 items-end">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject
                                    Name</label>
                                <input type="text" name="name" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject
                                    Code</label>
                                <input type="text" name="code" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Credits</label>
                                <input type="number" name="credits" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm"
                                    placeholder="e.g. 4">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Batch /
                                    Year</label>
                                <select name="batch_year"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                                    <option value="2024-2028">2024-2028</option>
                                    <option value="2023-2027">2023-2027</option>
                                    <option value="2022-2026">2022-2026</option>
                                    <option value="2021-2025">2021-2025</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Department</label>
                                <select name="department_id" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                                    <option value="">Select Dept</option>
                                    <?php foreach ($departments_list as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>">
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-indigo-700 transition">
                                    Add Subject
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
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Batch/Year</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Department</th>
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
                                                <?php echo htmlspecialchars($sub['batch_year'] ?? 'All'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                <?php echo htmlspecialchars($sub['department_name'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="text-slate-400 hover:text-indigo-600 mx-2"><i
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
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Year</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Section</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Strength</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (!empty($sections_list)): ?>
                                    <?php foreach ($sections_list as $sec): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-6 py-4 text-sm font-bold text-slate-700">
                                                <?php echo htmlspecialchars($sec['department_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600">
                                                <?php echo htmlspecialchars($sec['year']); ?>
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
                                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">Constraint Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
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
                                            <td class="px-6 py-4">
                                                <button class="text-slate-400 hover:text-indigo-600 mx-2"
                                                    title="Manage Constraints"><i class="fas fa-sliders-h"></i> Manage
                                                    Constraints</button>
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

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php if (!empty($rooms_list)): ?>
                            <?php foreach ($rooms_list as $room): ?>
                                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 relative group overflow-hidden">
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
                <div
                    class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 text-slate-200 text-4xl">
                    <i class="fas fa-magic"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-400">Timetable Generator</h3>
                <p class="text-slate-400 text-sm mt-2 max-w-xs mb-6">Ready to generate the timetable based on current
                    constraints.</p>
                <form action="actions/generate_timetable.php" method="POST">
                    <button type="submit"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition">Start
                        Generation</button>
                </form>
            </section>

            <!-- OPERATIONS Placeholder -->
            <?php
            $sections_path = __DIR__ . '/includes/admin_sections.html';
            if (file_exists($sections_path)) {
                include $sections_path;
            }
            ?>

            <section id="generate-section"
                class="hidden h-full flex flex-col items-center justify-center text-center py-20">
                <div
                    class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 text-slate-200 text-4xl">
                    <i class="fas fa-magic"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-400">Timetable Generator</h3>
                <p class="text-slate-400 text-sm mt-2 max-w-xs mb-6">Ready to generate the timetable based on current
                    constraints.</p>
                <button
                    class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition">Start
                    Generation</button>
            </section>
        </div>

        <!-- Modals -->

        <!-- Edit Subject Modal -->
        <div id="edit-subject-modal"
            class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl w-full max-w-4xl shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-6">Edit Subject</h3>
                <form id="edit-subject-form" onsubmit="event.preventDefault(); updateSubject();">
                    <input type="hidden" name="id" id="edit-subject-id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject Name</label>
                            <input type="text" name="name" id="edit-subject-name" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Subject Code</label>
                            <input type="text" name="code" id="edit-subject-code" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Credits</label>
                            <input type="number" name="credits" id="edit-subject-credits" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Batch / Year</label>
                            <select name="batch_year" id="edit-subject-batch"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                                <option value="2024-2028">2024-2028</option>
                                <option value="2023-2027">2023-2027</option>
                                <option value="2022-2026">2022-2026</option>
                                <option value="2021-2025">2021-2025</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Department</label>
                            <select name="department_id" id="edit-subject-dept" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm">
                                <option value="">Select Dept</option>
                                <?php foreach ($departments_list as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button type="button"
                            onclick="document.getElementById('edit-subject-modal').classList.add('hidden')"
                            class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition">Cancel</button>
                        <button type="submit"
                            class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg">Update
                            Subject</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Faculty Modal -->
        <div id="faculty-modal"
            class="hidden fixed inset-0 bg-black/50 z-[60] flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-6">Add New Faculty</h3>
                <form action="actions/add_faculty.php" method="POST" class="space-y-4">
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
            <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-6">Add Class/Section Mapping</h3>
                <form action="actions/add_section.php" method="POST" class="space-y-4">
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
            <div class="bg-white p-8 rounded-3xl w-full max-w-lg shadow-2xl">
                <h3 class="text-xl font-bold text-slate-800 mb-6">Add New Room</h3>
                <form action="actions/add_room.php" method="POST" class="space-y-4">
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

    </main>

    <script>
        function showSection(sectionId) {
            // 1. Hide all sections
            const sections = document.querySelectorAll('section');
            sections.forEach(sec => {
                if (sec.id && sec.id.endsWith('-section')) {
                    sec.classList.add('hidden');
                }
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

            // 5. Update Header Title (Optional)
            const titles = {
                'overview': 'Dashboard Overview',
                'course-manage': 'Course Management',
                'academic-settings': 'Academic Settings',
                'faculty-allocation': 'Faculty Allocation',
                'class-mapping': 'Class & Section Mapping',
                'faculty-manage': 'Faculty Management',
                'room-manage': 'Room & Resource Management',
                'generate': 'Generate Timetable',
                'audit-logs': 'System Audit Logs',
                'conflicts': 'Conflict Resolution',
                'regenerate': 'Regenerate Timetable',
                'profile': 'Admin Profile',
                'password': 'Change Password',
                'user-manage': 'User Management'
            };
            const titleEl = document.getElementById('section-title');
            if (titleEl) {
                titleEl.innerText = titles[sectionId] || 'Admin Dashboard';
            }
        }

        // Backend Interactions
        function saveAcademicSettings() {
            const formData = new FormData(document.getElementById('academic-form'));
            fetch('actions/save_academic_settings.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => { alert(d.message); if (d.success) location.reload(); });
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

        function addSubject() {
            const form = document.getElementById('add-subject-form');
            const data = new FormData(form);

            fetch('actions/add_subject.php', {
                method: 'POST',
                body: data
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);

                        const tbody = document.getElementById('subjects-table-body');
                        // Remove "No subjects found" row if present (text content check is safer than cell count sometimes)
                        if (tbody.rows.length === 1 && tbody.rows[0].innerText.includes('No subjects found')) {
                            tbody.innerHTML = '';
                        }

                        const sub = res.subject;

                        // Get department name from dropdown
                        const deptSelect = form.querySelector('select[name="department_id"]');
                        const deptName = deptSelect.options[deptSelect.selectedIndex].text;

                        const row = document.createElement('tr');
                        row.className = 'hover:bg-slate-50 transition animate-fade-in';
                        row.innerHTML = `
                        <td class="px-6 py-4 text-sm font-bold text-slate-700">${sub.code}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">${sub.name}</td>
                        <td class="px-6 py-4 text-sm font-bold text-indigo-600">${sub.credits}</td>
                        <td class="px-6 py-4 text-sm text-slate-500">${sub.batch_year}</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-700">${deptName}</td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-slate-400 hover:text-indigo-600 mx-2"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteSubject(${sub.id}, this)" class="text-slate-400 hover:text-red-500 mx-2"><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                        tbody.insertBefore(row, tbody.firstChild);
                        form.reset();
                    } else {
                        alert(res.message);
                        if (res.message && res.message.includes('Unauthorized')) {
                            window.location.href = 'admin_login.php';
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error adding subject. Check console for details.');
                });
        }

        function deleteSubject(id, btn) {
            if (!confirm('Are you sure you want to delete this subject?')) return;

            const fd = new FormData();
            fd.append('id', id);

            fetch('actions/delete_subject.php', {
                method: 'POST',
                body: fd
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        // Remove the row
                        const row = btn.closest('tr');
                        row.remove();
                        // If no rows left, maybe show empty message? Optional.
                        const tbody = document.getElementById('subjects-table-body');
                        if (tbody.children.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No subjects found.</td></tr>';
                        }
                    } else {
                        alert(res.message);
                        if (res.message && res.message.includes('Unauthorized')) {
                            window.location.href = 'admin_login.php';
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error deleting subject.');
                });
        }

        document.addEventListener('DOMContentLoaded', () => { showSection('overview'); });
    </script>
</body>

</html>