<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'includes/header.php';
?>

<!-- Dashboard Section -->
<section id="dashboard" class="section pt-24 min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-4 gradient-text">Welcome,
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="text-xl text-gray-600">Start generating timetables or manage your data</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 mb-16">
            <div class="card p-8 text-center hover:scale-105 transition">
                <i class="fas fa-plus-circle text-6xl text-purple-600 mb-6"></i>
                <h3 class="text-2xl font-bold mb-4">Create New Timetable</h3>
                <p class="text-gray-600 mb-6">Add subjects, faculty, rooms and generate a new timetable</p>
                <button class="btn-gradient text-white px-6 py-3 rounded-lg font-semibold">Get Started</button>
            </div>
            <div class="card p-8 text-center hover:scale-105 transition">
                <i class="fas fa-edit text-6xl text-purple-600 mb-6"></i>
                <h3 class="text-2xl font-bold mb-4">Edit Existing</h3>
                <p class="text-gray-600 mb-6">Modify previously generated timetables</p>
                <button class="btn-gradient text-white px-6 py-3 rounded-lg font-semibold">View All</button>
            </div>
            <div class="card p-8 text-center hover:scale-105 transition">
                <i class="fas fa-download text-6xl text-purple-600 mb-6"></i>
                <h3 class="text-2xl font-bold mb-4">Export & Share</h3>
                <p class="text-gray-600 mb-6">Download your timetables in PDF or Excel</p>
                <button class="btn-gradient text-white px-6 py-3 rounded-lg font-semibold">Export</button>
            </div>
        </div>

        <div class="text-center">
            <a href="logout.php" class="text-purple-600 hover:underline font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i> Log Out
            </a>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>