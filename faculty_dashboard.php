<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Faculty') !== 0) {
    header("Location: faculty_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | AutoTime</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0fdf4;
            /* Very light emerald background */
        }

        .sidebar-item {
            transition: all 0.2s ease-in-out;
        }

        .sidebar-item:hover {
            background-color: rgba(5, 150, 105, 0.1);
            color: #059669;
            /* Emerald 600 */
        }

        .sidebar-item.active {
            background-color: #059669;
            color: white;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            transition: transform 0.2s ease;
        }

        .timetable-slot {
            transition: all 0.2s;
        }

        .timetable-slot:hover {
            background-color: #ecfdf5;
            border-color: #10b981;
        }
    </style>
</head>

<body class="overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-emerald-100 z-50">
        <div class="p-6 h-full flex flex-col">
            <div class="flex items-center gap-3 mb-10">
                <div
                    class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                    <i class="fas fa-graduation-cap text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-slate-800 text-lg leading-tight">AutoTime</h1>
                    <p class="text-xs text-emerald-600 font-bold tracking-wide">Faculty Portal</p>
                </div>
            </div>

            <nav class="space-y-1 flex-1">
                <a href="#" onclick="showSection('overview')" id="link-overview"
                    class="sidebar-item active flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-columns"></i> My Dashboard
                </a>
                <a href="#" onclick="showSection('timetable')" id="link-timetable"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-calendar-alt"></i> My Timetable
                </a>
                <a href="#" onclick="showSection('subjects')" id="link-subjects"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-book-open"></i> My Subjects
                </a>
                <a href="#" onclick="showSection('availability')" id="link-availability"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-clock"></i> Availability
                </a>

                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-10 mb-2 ml-4">Account</p>
                <a href="#" onclick="showSection('profile')" id="link-profile"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
                <a href="#" onclick="showSection('settings')" id="link-settings"
                    class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-slate-600 mb-1">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>

            <div class="mt-auto">
                <a href="logout.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-red-500 hover:bg-red-50 transition border border-transparent hover:border-red-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen relative">
        <!-- Top Bar -->
        <header
            class="h-20 bg-white/80 backdrop-blur-md sticky top-0 border-b border-emerald-50 px-8 flex items-center justify-between z-40">
            <h2 id="section-title" class="text-xl font-bold text-slate-800">My Dashboard</h2>

            <div class="flex items-center gap-6">
                <div class="text-right mr-4 hidden md:block">
                    <p class="text-xs font-bold text-slate-400 uppercase">Academic Year</p>
                    <p class="text-sm font-bold text-emerald-600">2024–25 | Odd Sem</p>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        class="w-10 h-10 rounded-xl hover:bg-emerald-50 flex items-center justify-center text-slate-500 relative transition-colors">
                        <i class="far fa-bell text-lg"></i>
                        <span
                            class="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <div class="h-8 w-[1px] bg-emerald-100 mx-1"></div>
                    <div class="flex items-center gap-3 pl-2">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-slate-800">
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p class="text-[10px] text-emerald-600 font-bold uppercase">Senior Faculty</p>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=10b981&color=fff"
                            class="w-10 h-10 rounded-xl border-2 border-emerald-100 shadow-sm" alt="Avatar">
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Area -->
        <div class="p-8 h-[calc(100vh-80px)] overflow-y-auto custom-scrollbar">

            <!-- OVERVIEW SECTION -->
            <section id="overview-section" class="space-y-8 animate-in fade-in duration-500">
                <!-- Welcome Card -->
                <div
                    class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-emerald-100">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-2">Hello, Prof.
                            <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>!</h3>
                        <p class="opacity-80 max-w-lg mb-6 text-sm">You have 4 classes today. Your first lecture starts
                            in 2 hours at Hall 305.</p>
                        <button onclick="showSection('timetable')"
                            class="px-6 py-2.5 bg-white text-emerald-700 rounded-xl font-bold text-sm shadow-lg hover:shadow-emerald-900/10 transition hover:-translate-y-0.5">
                            View Today's Schedule
                        </button>
                    </div>
                    <i class="fas fa-leaf absolute -right-10 -bottom-10 text-[200px] opacity-10 rotate-12"></i>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-clock text-xl"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Weekly Load</p>
                        <h4 class="text-3xl font-black text-slate-800 mt-1">18 <span
                                class="text-sm font-medium text-slate-400">/ 24 hrs</span></h4>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-book text-xl"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Active Subjects</p>
                        <h4 class="text-3xl font-black text-slate-800 mt-1">04</h4>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-layer-group text-xl"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Sections</p>
                        <h4 class="text-3xl font-black text-slate-800 mt-1">06</h4>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-exclamation-circle text-xl"></i></div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Sync Alerts</p>
                        <h4 class="text-3xl font-black text-rose-500 mt-1">00</h4>
                    </div>
                </div>

                <!-- Personal Highlights -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <div
                        class="xl:col-span-2 bg-white rounded-3xl border border-emerald-50 shadow-sm overflow-hidden p-8">
                        <div class="flex justify-between items-center mb-6">
                            <h5 class="font-bold text-slate-800 text-lg">Quick Tasks</h5>
                            <button class="text-emerald-600 text-xs font-bold hover:underline">Manage All</button>
                        </div>
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-4 p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                <p class="text-sm font-medium text-slate-700 flex-1">Mark attendance for CS-301 Section
                                    B</p>
                                <span
                                    class="text-[10px] font-bold text-emerald-600 bg-white px-2 py-1 rounded-lg">Today</span>
                            </div>
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                <p class="text-sm font-medium text-slate-600 flex-1">Review lab resources for Hall 102
                                </p>
                                <span
                                    class="text-[10px] font-bold text-slate-400 bg-white px-2 py-1 rounded-lg">Pending</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-900 rounded-3xl p-8 text-white shadow-xl">
                        <h5 class="font-bold text-lg mb-4">Availability Status</h5>
                        <p class="text-xs opacity-70 leading-relaxed mb-6">Your availability is synced with the master
                            generator. No leave requests are active for this week.</p>
                        <div class="p-4 bg-white/10 rounded-2xl border border-white/20 mb-6">
                            <div class="flex justify-between text-xs font-bold mb-2"><span>Current
                                    Utilization</span><span>75%</span></div>
                            <div class="h-2 bg-white/10 rounded-full overflow-hidden leading-none">
                                <div class="h-full bg-emerald-400" style="width: 75%"></div>
                            </div>
                        </div>
                        <button
                            class="w-full py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm hover:bg-emerald-500 transition">Request
                            Rescheduling</button>
                    </div>
                </div>
            </section>

            <!-- TIMETABLE SECTION -->
            <section id="timetable-section" class="hidden space-y-6">
                <div class="bg-white p-8 rounded-3xl border border-emerald-50 shadow-sm">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">My Weekly Timetable</h3>
                            <p class="text-sm text-slate-500">Academic Year 2024-25 | Phase 1</p>
                        </div>
                        <div class="flex gap-2">
                            <button class="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition"><i
                                    class="fas fa-print"></i></button>
                            <button class="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition"><i
                                    class="fas fa-download"></i></button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="p-4 text-xs font-bold text-slate-400 uppercase w-24">Time</th>
                                    <th class="p-4 text-xs font-bold text-slate-400 uppercase">Monday</th>
                                    <th class="p-4 text-xs font-bold text-slate-400 uppercase">Tuesday</th>
                                    <th class="p-4 text-xs font-bold text-slate-400 uppercase">Wednesday</th>
                                    <th class="p-4 text-xs font-bold text-slate-400 uppercase">Thursday</th>
                                    <th class="p-4 text-xs font-bold text-slate-400 uppercase">Friday</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr>
                                    <td class="p-4 text-xs font-bold text-slate-500 bg-slate-50/30">09:00 AM</td>
                                    <td class="p-2">
                                        <div
                                            class="timetable-slot p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
                                            <p class="text-[10px] font-bold text-indigo-700 uppercase">Data Structures
                                            </p>
                                            <p class="text-[9px] text-indigo-500">Hall 305 | CS-B</p>
                                        </div>
                                    </td>
                                    <td class="p-2 text-center text-[10px] text-slate-300 italic">-</td>
                                    <td class="p-2">
                                        <div
                                            class="timetable-slot p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
                                            <p class="text-[10px] font-bold text-emerald-700 uppercase">Algorithms</p>
                                            <p class="text-[9px] text-emerald-500">Lab 102 | CS-A</p>
                                        </div>
                                    </td>
                                    <td class="p-2 text-center text-[10px] text-slate-300 italic">-</td>
                                    <td class="p-2">
                                        <div
                                            class="timetable-slot p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
                                            <p class="text-[10px] font-bold text-indigo-700 uppercase">Data Structures
                                            </p>
                                            <p class="text-[9px] text-indigo-500">Hall 201 | CS-C</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="p-4 text-xs font-bold text-slate-500 bg-slate-50/30">10:00 AM</td>
                                    <td class="p-2 text-center text-[10px] text-slate-300 italic">-</td>
                                    <td class="p-2">
                                        <div
                                            class="timetable-slot p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
                                            <p class="text-[10px] font-bold text-indigo-700 uppercase">Discrete Math</p>
                                            <p class="text-[9px] text-indigo-500">Hall 305 | IT-A</p>
                                        </div>
                                    </td>
                                    <td class="p-2 text-center text-[10px] text-slate-300 italic">-</td>
                                    <td class="p-2">
                                        <div
                                            class="timetable-slot p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded-lg">
                                            <p class="text-[10px] font-bold text-indigo-700 uppercase">Discrete Math</p>
                                            <p class="text-[9px] text-indigo-500">Hall 305 | IT-A</p>
                                        </div>
                                    </td>
                                    <td class="p-2 text-center text-[10px] text-slate-300 italic">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- SUBJECTS SECTION -->
            <section id="subjects-section" class="hidden space-y-6">
                <div class="bg-white p-8 rounded-3xl border border-emerald-50 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-800 mb-6">Assigned Subjects</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            class="p-6 bg-slate-50 rounded-2xl border border-slate-100 hover:border-emerald-200 transition-colors group cursor-default">
                            <div class="flex justify-between items-start mb-4">
                                <div
                                    class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center font-bold text-xs">
                                    DS</div>
                                <span
                                    class="text-[9px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md">CORE</span>
                            </div>
                            <h4 class="font-bold text-slate-800">Data Structures & C++</h4>
                            <p class="text-xs text-slate-400 mb-6">Course Code: CS-301 | Credits: 04</p>
                            <div class="flex gap-2">
                                <span
                                    class="px-3 py-1 bg-white text-slate-600 rounded-lg text-[10px] font-bold border border-slate-200">Sem
                                    III</span>
                                <span
                                    class="px-3 py-1 bg-white text-slate-600 rounded-lg text-[10px] font-bold border border-slate-200">3
                                    Sections</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PLACEHOLDER SECTION -->
            <section id="placeholder-section"
                class="hidden h-full flex flex-col items-center justify-center text-center py-20 grayscale opacity-60">
                <i class="fas fa-tools text-6xl text-emerald-200 mb-6"></i>
                <h3 class="text-xl font-bold text-slate-400">Section Under Development</h3>
                <p class="text-slate-400 text-sm mt-2 max-w-xs">We are currently integrating this feature with the main
                    server.</p>
                <button onclick="showSection('overview')"
                    class="mt-8 px-6 py-2 bg-emerald-100 text-emerald-700 rounded-xl font-bold text-sm">Return
                    Home</button>
            </section>

        </div>
    </main>

    <script>
        function showSection(sectionId) {
            // Hide all
            ['overview', 'timetable', 'subjects', 'availability', 'profile', 'settings'].forEach(id => {
                const el = document.getElementById(id + '-section');
                if (el) el.classList.add('hidden');

                const link = document.getElementById('link-' + id);
                if (link) link.classList.remove('active');
            });
            document.getElementById('placeholder-section').classList.add('hidden');

            // Show target
            const target = document.getElementById(sectionId + '-section');
            if (target) {
                target.classList.remove('hidden');
                target.classList.add('animate-in', 'fade-in', 'duration-500');
            } else {
                document.getElementById('placeholder-section').classList.remove('hidden');
            }

            // Update UI
            const activeLink = document.getElementById('link-' + sectionId);
            if (activeLink) activeLink.classList.add('active');

            // Header title
            const titles = {
                'overview': 'My Dashboard',
                'timetable': 'Weekly Schedule',
                'subjects': 'Academic Courses',
                'availability': 'Leave & Availability',
                'profile': 'My Faculty Profile',
                'settings': 'Account Settings'
            };
            document.getElementById('section-title').innerText = titles[sectionId] || 'Dashboard';
        }
    </script>
</body>

</html>