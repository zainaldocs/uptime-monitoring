<?php
/**
 * Live Dashboard Monitoring Page
 * Accessible by all logged-in users (Admin & Staff).
 */
require_once __DIR__ . '/../includes/auth_guard.php';
requireLogin();

require_once __DIR__ . '/layout/header.php';
?>

<!-- Dashboard Header -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-bold dark:text-white">Dashboard Monitoring</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Status ketersediaan infrastruktur jaringan secara real-time</p>
    </div>
    
    <!-- Refresh Controls and Status indicator -->
    <div class="flex items-center gap-3 self-start lg:self-center">
        <span id="refreshIndicator" class="text-xs font-semibold px-3 py-1.5 rounded-full bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center gap-1.5 transition-all">
            <i class="fa-solid fa-circle-notch fa-spin text-[10px]"></i> Memuat...
        </span>
        <button id="pauseBtn" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-xl text-xs transition-colors flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-pause"></i> Jeda
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Stat 1: Total Devices -->
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm flex items-center gap-4">
        <div class="p-3.5 bg-blue-500/10 text-blue-500 rounded-xl">
            <i class="fa-solid fa-server text-xl md:text-2xl"></i>
        </div>
        <div>
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Device</span>
            <h3 id="statTotal" class="text-2xl font-bold mt-0.5">-</h3>
        </div>
    </div>
    
    <!-- Stat 2: Active (UP) -->
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm flex items-center gap-4">
        <div class="p-3.5 bg-emerald-500/10 text-emerald-500 rounded-xl">
            <i class="fa-solid fa-circle-check text-xl md:text-2xl"></i>
        </div>
        <div>
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Perangkat UP</span>
            <h3 id="statUp" class="text-2xl font-bold mt-0.5 text-emerald-500">-</h3>
        </div>
    </div>
    
    <!-- Stat 3: Offline (DOWN) -->
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm flex items-center gap-4">
        <div class="p-3.5 bg-red-500/10 text-red-500 rounded-xl">
            <i class="fa-solid fa-triangle-exclamation text-xl md:text-2xl"></i>
        </div>
        <div>
            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Perangkat DOWN</span>
            <h3 id="statDown" class="text-2xl font-bold mt-0.5 text-red-500">-</h3>
        </div>
    </div>
    
    <!-- Stat 4: Average Uptime -->
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm flex flex-col justify-between">
        <div class="flex items-center gap-4">
            <div class="p-3.5 bg-indigo-500/10 text-indigo-500 rounded-xl">
                <i class="fa-solid fa-chart-pie text-xl md:text-2xl"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Persentase Uptime</span>
                <h3 id="statUptime" class="text-2xl font-bold mt-0.5 text-indigo-500">-</h3>
            </div>
        </div>
        <!-- Progress Bar -->
        <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full mt-3 overflow-hidden">
            <div id="uptimeProgress" class="h-2 bg-blue-600 rounded-full transition-all duration-500" style="width: 0%"></div>
        </div>
    </div>
</div>

<!-- Controls & Search Bar -->
<div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 p-4 bg-white dark:bg-[#1e293b] rounded-2xl shadow-sm border border-gray-150 dark:border-gray-800">
    <!-- Search Input -->
    <div class="relative flex-1 max-w-md">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </span>
        <input type="text" id="searchInput"
               class="block w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
               placeholder="Cari perangkat berdasarkan nama atau IP...">
    </div>
    
    <!-- Filter Options -->
    <div class="flex items-center gap-3">
        <label for="filterStatus" class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 hidden sm:block">Filter Status</label>
        <select id="filterStatus"
                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            <option value="ALL">Semua Perangkat</option>
            <option value="UP">Hanya UP</option>
            <option value="DOWN">Hanya DOWN</option>
        </select>
    </div>
</div>

<!-- Table Container for Devices Monitoring -->
<div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Grup</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Perangkat</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">IP / Host</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Durasi Status</th>
                </tr>
            </thead>
            <tbody id="dashboardTableBody" class="divide-y divide-gray-200 dark:divide-gray-800">
                <!-- Loading State -->
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex justify-center items-center gap-3">
                            <i class="fa-solid fa-circle-notch fa-spin text-blue-500 text-lg"></i>
                            <span>Memuat modul pemantauan real-time...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Footer -->
    <div id="paginationContainer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/10">
        <!-- Filled dynamically via JS -->
    </div>
</div>

<!-- Dashboard Javascript module link -->
<script src="<?php echo $baseUrl; ?>assets/js/dashboard.js"></script>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
