<?php require 'includes/header.php'; ?>

<!-- Hero Section with Glassmorphism -->
<section id="home" class="relative pt-32 pb-32 overflow-hidden bg-white">
    <!-- Animated Background Blobs -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 bg-[#f8fafc]">
        <div
            class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-purple-200/40 rounded-full blur-[100px] mix-blend-multiply filter animate-blob">
        </div>
        <div
            class="absolute top-0 right-1/4 w-[500px] h-[500px] bg-indigo-200/40 rounded-full blur-[100px] mix-blend-multiply filter animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute -bottom-8 left-1/3 w-[500px] h-[500px] bg-pink-200/40 rounded-full blur-[100px] mix-blend-multiply filter animate-blob animation-delay-4000">
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 text-center z-10 relative">
        <div
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-purple-100 shadow-sm mb-8 animate-fade-in-up">
            <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
            <span class="text-sm font-medium text-gray-600 tracking-wide">v2.0 is now live</span>
            <span class="ml-2 text-xs font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md">NEW</span>
        </div>

        <h1 class="text-6xl md:text-8xl font-black text-gray-900 tracking-tight leading-none mb-8">
            Scheduling <br>
            <span
                class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-indigo-600 to-pink-600">Reimagined.</span>
        </h1>

        <p class="text-xl md:text-2xl text-gray-600 mb-12 leading-relaxed max-w-3xl mx-auto font-light">
            Generate clash-free, optimized academic timetables in seconds using our advanced AI engine.
            Trusted by modern institutions worldwide.
        </p>

        <div class="flex flex-col sm:flex-row gap-5 justify-center mb-24">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php"
                    class="btn-gradient text-white px-10 py-5 rounded-2xl text-lg font-bold shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center min-w-[200px]">
                    Launch Dashboard
                </a>
            <?php else: ?>
                <a href="register.php"
                    class="btn-gradient text-white px-10 py-5 rounded-2xl text-lg font-bold shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center min-w-[200px]">
                    Get Started Free
                </a>
            <?php endif; ?>
            <button onclick="document.getElementById('how-it-works').scrollIntoView({behavior: 'smooth'})"
                class="bg-white text-gray-700 hover:text-gray-900 border border-gray-200 hover:border-gray-300 px-10 py-5 rounded-2xl text-lg font-bold shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-center min-w-[200px]">
                How it Works
            </button>
        </div>

        <!-- 3D Perspective Dashboard Preview -->
        <div class="relative max-w-6xl mx-auto mt-10 perspective-[2000px] group">
            <div
                class="relative transform rotate-x-12 group-hover:rotate-x-0 transition-transform duration-700 ease-out preserve-3d">
                <div
                    class="absolute inset-0 bg-gradient-to-t from-gray-900/20 to-transparent rounded-2xl blur-xl transform translate-y-12 scale-95 -z-10">
                </div>
                <div
                    class="bg-gray-900 rounded-2xl p-2 shadow-2xl border border-gray-200/50 backdrop-blur-sm ring-1 ring-white/10">
                    <div class="bg-white rounded-xl overflow-hidden shadow-inner">
                        <img src="assets/images/dashboard-preview.png" alt="Dashboard Interface" class="w-full h-auto">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trusted By Section -->
<section class="border-y border-gray-100 bg-white py-12">
    <div class="max-w-7xl mx-auto px-6">
        <p class="text-center text-sm font-semibold text-gray-400 uppercase tracking-widest mb-8">Powering top
            institutions globally</p>
        <div
            class="flex flex-wrap justify-center gap-12 md:gap-20 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
            <!-- Replaced text with FontAwesome icons for a 'logo' feel -->
            <div class="flex items-center gap-2 text-2xl font-bold text-gray-700"><i
                    class="fas fa-university text-3xl"></i> HARVARD</div>
            <div class="flex items-center gap-2 text-2xl font-bold text-gray-800"><i
                    class="fas fa-graduation-cap text-3xl"></i> STANFORD</div>
            <div class="flex items-center gap-2 text-2xl font-bold text-gray-600"><i
                    class="fas fa-building text-3xl"></i> MIT</div>
            <div class="flex items-center gap-2 text-2xl font-bold text-indigo-900"><i
                    class="fas fa-landmark text-3xl"></i> OXFORD</div>
            <div class="flex items-center gap-2 text-2xl font-bold text-blue-800"><i
                    class="fas fa-globe-americas text-3xl"></i> YALE</div>
        </div>
    </div>
</section>

<!-- How It Works (Timeline) -->
<section id="how-it-works" class="py-32 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-24">
            <h2 class="text-base text-purple-600 font-bold tracking-wide uppercase mb-2">Workflow</h2>
            <h3 class="text-4xl md:text-5xl font-extrabold text-gray-900">From Chaos to Order in 4 Steps</h3>
        </div>

        <div class="relative">
            <!-- Connecting Line -->
            <div class="hidden md:block absolute left-1/2 top-0 bottom-0 w-0.5 bg-gray-200 -translate-x-1/2"></div>

            <!-- Step 1 -->
            <div class="relative flex flex-col md:flex-row items-center justify-between mb-24 group">
                <div class="md:w-5/12 text-right order-2 md:order-1 pr-0 md:pr-12">
                    <h4 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-purple-600 transition">1. Input
                        Data</h4>
                    <p class="text-lg text-gray-600 leading-relaxed">Simply upload or enter your departments, courses,
                        faculty details, and available classrooms. Our bulk upload feature makes this a breeze.</p>
                </div>
                <div
                    class="z-10 bg-white border-4 border-purple-100 rounded-full w-16 h-16 flex items-center justify-center shadow-lg order-1 md:order-2 mb-6 md:mb-0">
                    <span class="text-xl font-bold text-purple-600">01</span>
                </div>
                <div class="md:w-5/12 pl-0 md:pl-12 order-3">
                    <div
                        class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 transform group-hover:scale-105 transition duration-300">
                        <i class="fas fa-database text-4xl text-purple-200 mb-4 block"></i>
                        <div class="h-2 w-24 bg-gray-100 rounded mb-2"></div>
                        <div class="h-2 w-16 bg-gray-100 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="relative flex flex-col md:flex-row items-center justify-between mb-24 group">
                <div class="md:w-5/12 text-right order-2 md:order-3 md:text-left pl-0 md:pl-12">
                    <h4 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-purple-600 transition">2. set
                        Constraints</h4>
                    <p class="text-lg text-gray-600 leading-relaxed">Define rules: "No Math classes on Friday
                        afternoons", "Professor X needs a Smart Board", or "Lunch break at 1 PM".</p>
                </div>
                <div
                    class="z-10 bg-white border-4 border-indigo-100 rounded-full w-16 h-16 flex items-center justify-center shadow-lg order-1 md:order-2 mb-6 md:mb-0">
                    <span class="text-xl font-bold text-indigo-600">02</span>
                </div>
                <div class="md:w-5/12 pr-0 md:pr-12 order-3 md:order-1 flex justify-end">
                    <div
                        class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 transform group-hover:scale-105 transition duration-300">
                        <i class="fas fa-sliders-h text-4xl text-indigo-200 mb-4 block"></i>
                        <div class="h-2 w-24 bg-gray-100 rounded mb-2"></div>
                        <div class="h-2 w-32 bg-gray-100 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="relative flex flex-col md:flex-row items-center justify-between mb-24 group">
                <div class="md:w-5/12 text-right order-2 md:order-1 pr-0 md:pr-12">
                    <h4 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-purple-600 transition">3. AI
                        Generation</h4>
                    <p class="text-lg text-gray-600 leading-relaxed">Click 'Generate' and watch the magic happen. Our
                        Genetic Algorithm iterates thousands of times to find the optimal schedule.</p>
                </div>
                <div
                    class="z-10 bg-white border-4 border-pink-100 rounded-full w-16 h-16 flex items-center justify-center shadow-lg order-1 md:order-2 mb-6 md:mb-0">
                    <span class="text-xl font-bold text-pink-600">03</span>
                </div>
                <div class="md:w-5/12 pl-0 md:pl-12 order-3">
                    <div
                        class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 transform group-hover:scale-105 transition duration-300">
                        <i class="fas fa-microchip text-4xl text-pink-200 mb-4 block"></i>
                        <div class="space-y-2">
                            <div class="h-1.5 w-full bg-pink-100 rounded animate-pulse"></div>
                            <div class="h-1.5 w-3/4 bg-pink-100 rounded animate-pulse"></div>
                            <div class="h-1.5 w-full bg-pink-100 rounded animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="relative flex flex-col md:flex-row items-center justify-between group">
                <div class="md:w-5/12 text-right order-2 md:order-3 md:text-left pl-0 md:pl-12">
                    <h4 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-purple-600 transition">4. Export &
                        Publish</h4>
                    <p class="text-lg text-gray-600 leading-relaxed">Satisfied? Export the master timetable to PDF,
                        Excel, or publish it directly to the dashboard for students to see.</p>
                </div>
                <div
                    class="z-10 bg-white border-4 border-green-100 rounded-full w-16 h-16 flex items-center justify-center shadow-lg order-1 md:order-2 mb-6 md:mb-0">
                    <span class="text-xl font-bold text-green-600">04</span>
                </div>
                <div class="md:w-5/12 pr-0 md:pr-12 order-3 md:order-1 flex justify-end">
                    <div
                        class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 transform group-hover:scale-105 transition duration-300">
                        <i class="fas fa-file-export text-4xl text-green-200 mb-4 block"></i>
                        <div class="flex gap-2">
                            <div
                                class="h-8 w-8 bg-red-100 rounded flex items-center justify-center text-xs text-red-500 font-bold">
                                PDF</div>
                            <div
                                class="h-8 w-8 bg-green-100 rounded flex items-center justify-center text-xs text-green-500 font-bold">
                                XLS</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Showcase / Visual Tour -->
<section class="py-32 bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-20">
            <h2 class="text-base text-indigo-600 font-bold tracking-wide uppercase mb-2">Visual Tour</h2>
            <h3 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-6">Experience the Interface</h3>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Designed for clarity, efficiency, and ease of use. Take a closer look at our key screens.</p>
        </div>

        <div class="space-y-32">
            <!-- Showcase Item 1: Setup UI -->
            <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="md:w-1/2">
                    <div class="relative group perspective-1000">
                        <div class="absolute -inset-4 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-gray-100 bg-white transform transition duration-500 group-hover:rotate-y-2">
                           <img src="assets/images/setup-ui.png" alt="Timetable Constraints Setup" class="w-full h-auto">
                        </div>
                        <!-- Floating Badge -->
                        <div class="absolute -top-6 -right-6 bg-white p-4 rounded-xl shadow-xl animate-float">
                            <i class="fas fa-sliders-h text-2xl text-purple-600"></i>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2">
                    <div class="inline-block p-3 rounded-xl bg-purple-50 text-purple-600 mb-6">
                        <i class="fas fa-cog text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">Intuitive Constraint Management</h3>
                    <p class="text-lg text-gray-600 leading-relaxed mb-8">
                        Stop fighting with complex spreadsheets. Our modern interface lets you easily define rules for professors, rooms, and subjects using simple toggles and dropdowns.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700 font-medium">Drag-and-drop ease</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700 font-medium">Real-time validation checks</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                            <span class="text-gray-700 font-medium">Smart suggestions for conflicts</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Showcase Item 2: Results View -->
            <div class="flex flex-col md:flex-row-reverse items-center gap-16">
                <div class="md:w-1/2">
                    <div class="relative group perspective-1000">
                        <div class="absolute -inset-4 bg-gradient-to-r from-green-400 to-blue-500 rounded-2xl blur-lg opacity-30 group-hover:opacity-50 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-gray-100 bg-white transform transition duration-500 group-hover:-rotate-y-2">
                           <img src="assets/images/result-view.png" alt="Timetable Grid View" class="w-full h-auto">
                        </div>
                         <!-- Floating Badge -->
                         <div class="absolute -bottom-6 -left-6 bg-white p-4 rounded-xl shadow-xl animate-float animation-delay-2000">
                            <i class="fas fa-table text-2xl text-blue-500"></i>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2">
                    <div class="inline-block p-3 rounded-xl bg-blue-50 text-blue-600 mb-6">
                        <i class="fas fa-eye text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">Clear, Color-Coded Schedules</h3>
                    <p class="text-lg text-gray-600 leading-relaxed mb-8">
                        Visualize your entire week at a glance. Our intelligent color-coding system instantly differentiates between departments, lecture types, and lab sessions.
                    </p>
                    <a href="#" class="text-blue-600 font-bold hover:text-blue-800 transition flex items-center group">
                        See Example Timetable <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition"></i>
                    </a>
                </div>
            </div>

             <!-- Showcase Item 3: Mobile View -->
             <div class="flex flex-col md:flex-row items-center gap-16">
                <div class="w-full text-center max-w-4xl mx-auto">
                    <div class="inline-block p-3 rounded-xl bg-pink-50 text-pink-600 mb-6">
                        <i class="fas fa-mobile-alt text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">Access Anywhere, Anytime</h3>
                    <p class="text-lg text-gray-600 leading-relaxed mb-8">
                        Don't just print it — publish it. Students and faculty can access their personalized schedules continuously updated on any mobile device.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <button class="bg-gray-900 text-white px-6 py-3 rounded-xl flex items-center hover:bg-gray-800 transition">
                            <i class="fab fa-apple text-2xl mr-3"></i>
                            <div class="text-left">
                                <div class="text-xs uppercase">Download on the</div>
                                <div class="font-bold leading-none">App Store</div>
                            </div>
                        </button>
                        <button class="bg-gray-900 text-white px-6 py-3 rounded-xl flex items-center hover:bg-gray-800 transition">
                            <i class="fab fa-google-play text-2xl mr-3"></i>
                            <div class="text-left">
                                <div class="text-xs uppercase">Get it on</div>
                                <div class="font-bold leading-none">Google Play</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-32 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-24">
            <h2 class="text-base text-purple-600 font-bold tracking-wide uppercase mb-2">Pricing</h2>
            <h3 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-6">Simple, Transparent Plans</h3>
            <p class="text-xl text-gray-600">Choose the solution that fits your institution size.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- Free Plan -->
            <div
                class="p-8 rounded-3xl border border-gray-200 bg-white hover:shadow-2xl transition duration-300 relative">
                <h4 class="text-2xl font-bold text-gray-900 mb-2">Starter</h4>
                <p class="text-gray-500 mb-6">Perfect for small depts</p>
                <div class="text-5xl font-extrabold text-gray-900 mb-8">$0<span
                        class="text-lg text-gray-500 font-medium">/mo</span></div>
                <ul class="space-y-4 mb-8 text-gray-600">
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> Up to 10 Classes</li>
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> Basic Constraints
                    </li>
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> PDF Export</li>
                </ul>
                <a href="register.php"
                    class="block w-full py-4 text-center font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition">Get
                    Started</a>
            </div>

            <!-- Pro Plan -->
            <div
                class="p-8 rounded-3xl border-2 border-purple-600 bg-white shadow-2xl relative transform md:-translate-y-4">
                <div
                    class="absolute top-0 right-1/2 translate-x-1/2 -translate-y-1/2 bg-purple-600 text-white px-4 py-1 rounded-full text-sm font-bold tracking-wide">
                    MOST POPULAR</div>
                <h4 class="text-2xl font-bold text-gray-900 mb-2">Department</h4>
                <p class="text-gray-500 mb-6">For growing colleges</p>
                <div class="text-5xl font-extrabold text-gray-900 mb-8">$49<span
                        class="text-lg text-gray-500 font-medium">/mo</span></div>
                <ul class="space-y-4 mb-8 text-gray-600">
                    <li class="flex items-center"><i class="fas fa-check text-purple-600 mr-3"></i> Unlimited Classes
                    </li>
                    <li class="flex items-center"><i class="fas fa-check text-purple-600 mr-3"></i> Advanced AI Engine
                    </li>
                    <li class="flex items-center"><i class="fas fa-check text-purple-600 mr-3"></i> Excel & Image Export
                    </li>
                    <li class="flex items-center"><i class="fas fa-check text-purple-600 mr-3"></i> Priority Support
                    </li>
                </ul>
                <a href="register.php"
                    class="block w-full py-4 text-center font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition shadow-lg">Start
                    Free Trial</a>
            </div>

            <!-- Enterprise Plan -->
            <div
                class="p-8 rounded-3xl border border-gray-200 bg-white hover:shadow-2xl transition duration-300 relative">
                <h4 class="text-2xl font-bold text-gray-900 mb-2">Institution</h4>
                <p class="text-gray-500 mb-6">For entire universities</p>
                <div class="text-5xl font-extrabold text-gray-900 mb-8">Custom</div>
                <ul class="space-y-4 mb-8 text-gray-600">
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> Multi-Department</li>
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> Custom Integration
                    </li>
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> Dedicated Server</li>
                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-3"></i> 24/7 SLA Support</li>
                </ul>
                <a href="#"
                    class="block w-full py-4 text-center font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition">Contact
                    Sales</a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-32 bg-gray-50">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-4">Frequently Asked Questions</h2>
            <p class="text-xl text-gray-600">Have questions? We're here to help.</p>
        </div>

        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <button class="w-full px-8 py-6 text-left flex justify-between items-center focus:outline-none"
                    onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('i').classList.toggle('rotate-180')">
                    <span class="text-lg font-bold text-gray-900">Is the generated timetable really clash-free?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                </button>
                <div class="hidden px-8 pb-8 text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                    Yes! Our algorithm is mathematically proven to detect and resolve conflicts. It checks for room
                    double-bookings, professor availability, and student group overlaps simultaneously.
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <button class="w-full px-8 py-6 text-left flex justify-between items-center focus:outline-none"
                    onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('i').classList.toggle('rotate-180')">
                    <span class="text-lg font-bold text-gray-900">Can I manually edit the timetable after
                        generation?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                </button>
                <div class="hidden px-8 pb-8 text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                    Absolutely. The AI gives you a perfect starting point, but our drag-and-drop dashboard allows you to
                    make manual adjustments. We'll even highlight if your manual move creates a new conflict.
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <button class="w-full px-8 py-6 text-left flex justify-between items-center focus:outline-none"
                    onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('i').classList.toggle('rotate-180')">
                    <span class="text-lg font-bold text-gray-900">Do you support exporting to Google Calendar?</span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                </button>
                <div class="hidden px-8 pb-8 text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                    In our Pro and Enterprise plans, we offer iCal integration which syncs directly with Google
                    Calendar, Outlook, and Apple Calendar.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA with Pattern -->
<section class="py-32 relative overflow-hidden bg-gray-900">
    <!-- Abstract Shapes -->
    <div
        class="absolute top-0 right-0 w-96 h-96 bg-purple-600/30 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/2">
    </div>
    <div
        class="absolute bottom-0 left-0 w-96 h-96 bg-blue-600/30 rounded-full blur-[100px] translate-y-1/2 -translate-x-1/2">
    </div>

    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="text-5xl md:text-6xl font-black text-white mb-8 tracking-tight">
            Ready to reclaim your time?
        </h2>
        <p class="text-xl text-gray-400 mb-12 max-w-2xl mx-auto">
            Join 1,000+ academic administrators who have switched to the future of scheduling. No credit card required.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php"
                    class="bg-white text-gray-900 hover:bg-gray-100 px-12 py-5 rounded-2xl text-xl font-bold transition transform hover:scale-105 shadow-2xl">
                    Go to Dashboard
                </a>
            <?php else: ?>
                <a href="register.php"
                    class="bg-purple-600 text-white hover:bg-purple-500 px-12 py-5 rounded-2xl text-xl font-bold transition transform hover:scale-105 shadow-2xl shadow-purple-900/50">
                    Get Started Now
                </a>
            <?php endif; ?>
        </div>
        <p class="mt-8 text-sm text-gray-500">Free 14-day trial • No setup fees • Cancel anytime</p>
    </div>
</section>

<?php require 'includes/footer.php'; ?>