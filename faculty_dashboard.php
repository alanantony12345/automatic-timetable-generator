<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config/db.php';

// Auth Check
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        .sidebar-item {
            transition: all 0.2s ease-in-out;
        }

        .sidebar-item:hover {
            background-color: rgba(5, 150, 105, 0.1);
            color: #059669;
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
            </nav>
            <div class="mt-auto">
                <a href="logout.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-red-500 hover:bg-red-50 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen relative">
        <header
            class="h-20 bg-white/80 backdrop-blur-md sticky top-0 border-b border-emerald-50 px-8 flex items-center justify-between z-40">
            <h2 id="section-title" class="text-xl font-bold text-slate-800">My Dashboard</h2>
            <div class="flex items-center gap-6">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($faculty_name); ?></p>
                    <p class="text-[10px] text-emerald-600 font-bold uppercase">
                        <?php echo htmlspecialchars($designation); ?>
                    </p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($faculty_name); ?>&background=10b981&color=fff"
                    class="w-10 h-10 rounded-xl border-2 border-emerald-100 shadow-sm">
            </div>
        </header>

        <div class="p-8 h-[calc(100vh-80px)] overflow-y-auto custom-scrollbar">

            <!-- OVERVIEW SECTION -->
            <section id="overview-section" class="space-y-8 animate-in fade-in duration-500">
                <div
                    class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-8 text-white relative overflow-hidden shadow-xl shadow-emerald-100">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-2">Hello, Prof. <?php echo explode(' ', $faculty_name)[0]; ?>!
                        </h3>
                        <?php if ($active_version_id): ?>
                            <p class="opacity-80 max-w-lg mb-6 text-sm">
                                <?php if ($next_class): ?>
                                    Create impact! Your next class <strong><?php echo $next_class['subject_code']; ?></strong>
                                    starts at <?php echo $next_class_time; ?> in
                                    <?php echo $next_class['room_name'] ?? 'TBA'; ?>.
                                <?php else: ?>
                                    You have no more classes scheduled for today.
                                <?php endif; ?>
                            </p>
                            <button onclick="showSection('timetable')"
                                class="px-6 py-2.5 bg-white text-emerald-700 rounded-xl font-bold text-sm shadow-lg hover:shadow-emerald-900/10 transition hover:-translate-y-0.5">View
                                Full Schedule</button>
                        <?php else: ?>
                            <p class="opacity-80 max-w-lg mb-6 text-sm">The timetable for this semester has not been
                                published yet.</p>
                            <span class="px-4 py-2 bg-white/20 rounded-lg text-sm">Status: <strong>Pending
                                    Publication</strong></span>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-leaf absolute -right-10 -bottom-10 text-[200px] opacity-10 rotate-12"></i>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Weekly Load</p>
                        <h4 class="text-3xl font-black text-slate-800 mt-1"><?php echo $total_load; ?> <span
                                class="text-sm font-medium text-slate-400">Hours</span></h4>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Active Subjects</p>
                        <h4 class="text-3xl font-black text-slate-800 mt-1"><?php echo count($unique_subjects); ?></h4>
                    </div>
                    <div class="stat-card glass-card p-6 rounded-3xl shadow-sm border border-emerald-50">
                        <div
                            class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-4">
                            <i class="fas fa-layer-group text-xl"></i>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Sections</p>
                        <h4 class="text-3xl font-black text-slate-800 mt-1"><?php echo count($active_sections); ?></h4>
                    </div>
                </div>
            </section>

            <!-- TIMETABLE SECTION -->
            <section id="timetable-section" class="hidden space-y-6">
                <div class="bg-white p-8 rounded-3xl border border-emerald-50 shadow-sm">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800">My Weekly Timetable</h3>
                            <?php if ($active_version_name): ?>
                                <p class="text-sm text-emerald-600 font-bold"><i class="fas fa-check-circle"></i> Active
                                    Version: <?php echo $active_version_name; ?></p>
                            <?php else: ?>
                                <p class="text-sm text-red-500 font-bold"><i class="fas fa-lock"></i> Not yet published by
                                    Admin</p>
                            <?php endif; ?>
                        </div>
                        <?php if ($active_version_id): ?>
                            <button onclick="downloadMyTimetable()"
                                class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg hover:bg-emerald-700 transition flex items-center gap-2">
                                <i class="fas fa-download"></i> Download PDF
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($active_version_id): ?>
                        <div class="overflow-x-auto rounded-2xl border border-slate-100">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100">
                                        <th class="p-4 text-xs font-bold text-slate-400 uppercase w-24">Time</th>
                                        <?php foreach ($days_list as $day): ?>
                                            <th class="p-4 text-xs font-bold text-slate-400 uppercase"><?php echo $day; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-5">
                                    <?php
                                    $start_time = strtotime("09:00 AM");
                                    for ($p = 1; $p <= $periods_count; $p++):
                                        $time_str = date("h:i A", $start_time);
                                        ?>
                                        <tr>
                                            <td class="p-4 text-xs font-bold text-slate-500 bg-slate-50/30">
                                                <?php echo $time_str; ?>
                                            </td>
                                            <?php foreach ($days_list as $day): ?>
                                                <td class="p-2">
                                                    <?php if (isset($timetable_data[$day][$p])):
                                                        $entry = $timetable_data[$day][$p];
                                                        ?>
                                                        <div
                                                            class="timetable-slot p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
                                                            <p class="text-[10px] font-bold text-emerald-700 uppercase">
                                                                <?php echo htmlspecialchars($entry['subject_name']); ?>
                                                            </p>
                                                            <p class="text-[9px] text-emerald-600 font-bold mt-1">
                                                                <?php echo htmlspecialchars($entry['dept_code'] . ' - ' . $entry['section_name']); ?>
                                                            </p>
                                                            <p class="text-[9px] text-emerald-500"><i class="fas fa-map-marker-alt"></i>
                                                                <?php echo htmlspecialchars($entry['room_name'] ?? 'TBA'); ?></p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-center text-[10px] text-slate-300 italic">-</div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php
                                        $start_time += 3600;
                                    endfor;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-10 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                            <i class="fas fa-lock text-4xl text-slate-300 mb-4"></i>
                            <h4 class="text-slate-500 font-bold">Timetable Locked</h4>
                            <p class="text-slate-400 text-sm">The administrator has not published the timetable yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- SUBJECTS SECTION -->
            <section id="subjects-section" class="hidden space-y-6">
                <div class="bg-white p-8 rounded-3xl border border-emerald-50 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-800 mb-6">Mys Subjects</h3>
                    <?php if (empty($unique_subjects)): ?>
                        <p class="text-slate-400">No subjects assigned yet.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($unique_subjects as $sub): ?>
                                <div
                                    class="p-6 bg-slate-50 rounded-2xl border border-slate-100 hover:border-emerald-200 transition-colors">
                                    <div class="flex justify-between items-start mb-4">
                                        <div
                                            class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center font-bold text-xs">
                                            <?php echo substr($sub['code'], 0, 3); ?>
                                        </div>
                                        <span
                                            class="text-[9px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md">CREDIT</span>
                                    </div>
                                    <h4 class="font-bold text-slate-800"><?php echo htmlspecialchars($sub['name']); ?></h4>
                                    <p class="text-xs text-slate-400 mb-6"><?php echo htmlspecialchars($sub['code']); ?></p>
                                    <div class="flex gap-2">
                                        <span
                                            class="px-3 py-1 bg-white text-slate-600 rounded-lg text-[10px] font-bold border border-slate-200">Sem
                                            <?php echo $sub['sem']; ?></span>
                                        <span
                                            class="px-3 py-1 bg-white text-slate-600 rounded-lg text-[10px] font-bold border border-slate-200"><?php echo $sub['sections_count']; ?>
                                            Sections</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <script>
        function showSection(sectionId) {
            ['overview', 'timetable', 'subjects'].forEach(id => {
                const el = document.getElementById(id + '-section');
                if (el) el.classList.add('hidden');
                const link = document.getElementById('link-' + id);
                if (link) link.classList.remove('active');
            });

            const target = document.getElementById(sectionId + '-section');
            if (target) {
                target.classList.remove('hidden');
                target.classList.add('animate-in', 'fade-in', 'duration-500');
            }

            const activeLink = document.getElementById('link-' + sectionId);
            if (activeLink) activeLink.classList.add('active');

            const titles = { 'overview': 'My Dashboard', 'timetable': 'Weekly Schedule', 'subjects': 'My Subjects' };
            document.getElementById('section-title').innerText = titles[sectionId] || 'Dashboard';
        }

        function downloadMyTimetable() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            doc.setFontSize(16);
            doc.text("Faculty Timetable", 14, 20);
            doc.setFontSize(11);
            doc.text("Prof. <?php echo $faculty_name; ?>", 14, 28);
            doc.text("Generated: " + new Date().toLocaleDateString(), 14, 34);

            doc.autoTable({
                html: 'table',
                startY: 40,
                theme: 'grid',
                headStyles: { fillColor: [5, 150, 105] },
                pageBreak: 'avoid',
                styles: { fontSize: 8 },
            });

            doc.save('My_Timetable.pdf');
        }
    </script>
</body>

</html>