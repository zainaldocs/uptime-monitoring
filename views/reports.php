<?php
/**
 * Reports and Analytics Page
 * Accessible by all logged-in users (Admin & Staff).
 */
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

require_once __DIR__ . '/layout/header.php';

$pdo = getDBConnection();

// Fetch all devices for the filter dropdown
$devicesStmt = $pdo->query("SELECT id, name FROM devices ORDER BY name ASC");
$devicesList = $devicesStmt->fetchAll();

// Determine filter parameters
$filterDeviceId = isset($_GET['device_id']) && $_GET['device_id'] !== '' ? intval($_GET['device_id']) : null;
$filterRange = $_GET['range'] ?? 'today'; // 'today', '7days', '30days'

// Calculate interval
switch ($filterRange) {
    case '7days':
        $intervalSql = "INTERVAL 7 DAY";
        $dateFormat = "%Y-%m-%d %H:00"; // group hourly/daily
        break;
    case '30days':
        $intervalSql = "INTERVAL 30 DAY";
        $dateFormat = "%Y-%m-%d"; // group daily
        break;
    case 'today':
    default:
        $intervalSql = "INTERVAL 1 DAY";
        $dateFormat = "%Y-%m-%d %H:00"; // group hourly
        break;
}

// Define params for stats and chart queries
$params = [];
if ($filterDeviceId !== null) {
    $params['device_id'] = $filterDeviceId;
}

// Fetch down status statistics per device for the status table
$tableParams = [];
$statusTableQuery = "
    SELECT d.id, d.name as device_name, d.ip_address, d.port, d.check_type, g.group_name,
           (SELECT COUNT(*) 
            FROM status_logs 
            WHERE device_id = d.id AND status = 'DOWN' AND timestamp >= NOW() - INTERVAL 7 DAY) as down_count
    FROM devices d
    LEFT JOIN `groups` g ON d.group_id = g.id
";

if ($filterDeviceId !== null) {
    $statusTableQuery .= " WHERE d.id = :device_id";
    $tableParams['device_id'] = $filterDeviceId;
}

$statusTableQuery .= " ORDER BY d.name ASC";
$statusTableStmt = $pdo->prepare($statusTableQuery);
$statusTableStmt->execute($tableParams);
$deviceDownStats = $statusTableStmt->fetchAll();

// Calculate total stats for the range
$statQuery = "
    SELECT 
        COUNT(*) as total_checks,
        SUM(CASE WHEN sl.status = 'UP' THEN 1 ELSE 0 END) as up_checks,
        SUM(CASE WHEN sl.status = 'DOWN' THEN 1 ELSE 0 END) as down_checks,
        AVG(sl.latency) as avg_latency
    FROM status_logs sl
    WHERE sl.timestamp >= NOW() - $intervalSql
";
if ($filterDeviceId !== null) {
    $statQuery .= " AND sl.device_id = :device_id";
}
$statStmt = $pdo->prepare($statQuery);
$statStmt->execute($params);
$stats = $statStmt->fetch();

$totalChecks = intval($stats['total_checks'] ?? 0);
$upChecks = intval($stats['up_checks'] ?? 0);
$downChecks = intval($stats['down_checks'] ?? 0);
$avgLatency = $stats['avg_latency'] !== null ? round($stats['avg_latency'], 1) : 0;
$uptimePct = $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : 100.0;

// Gather Chart.js Latency data points (Grouped by time interval)
$chartQuery = "
    SELECT 
        DATE_FORMAT(sl.timestamp, '$dateFormat') as time_bucket,
        AVG(sl.latency) as avg_latency
    FROM status_logs sl
    WHERE sl.timestamp >= NOW() - $intervalSql
";
if ($filterDeviceId !== null) {
    $chartQuery .= " AND sl.device_id = :device_id";
}
$chartQuery .= " GROUP BY time_bucket ORDER BY time_bucket ASC";
$chartStmt = $pdo->prepare($chartQuery);
$chartStmt->execute($params);
$chartData = $chartStmt->fetchAll();

$chartLabels = [];
$chartLatencies = [];
foreach ($chartData as $c) {
    $chartLabels[] = $c['time_bucket'];
    $chartLatencies[] = $c['avg_latency'] !== null ? round($c['avg_latency'], 1) : 0;
}
?>

<!-- Content Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4 no-print">
    <div>
        <h2 class="text-2xl font-bold dark:text-white">Laporan Analitik Performa</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pantau riwayat ketersediaan dan rata-rata latency perangkat</p>
    </div>
    <div class="flex gap-2">
        <button onclick="exportToCSV()" class="px-4 py-2 bg-white dark:bg-[#1e293b] hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold rounded-xl text-sm border border-gray-200 dark:border-gray-800 shadow-sm transition-colors flex items-center gap-2">
            <i class="fa-solid fa-file-excel text-emerald-500"></i> Ekspor CSV
        </button>
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
            <i class="fa-solid fa-print"></i> Cetak Laporan (PDF)
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl shadow-sm border border-gray-150 dark:border-gray-800 mb-8 no-print">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Device Filter -->
        <div>
            <label for="device_id" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Pilih Perangkat</label>
            <select name="device_id" id="device_id"
                    class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                <option value="">-- Semua Perangkat --</option>
                <?php foreach ($devicesList as $dev): ?>
                    <option value="<?php echo $dev['id']; ?>" <?php echo $filterDeviceId === $dev['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dev['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Range Filter -->
        <div>
            <label for="range" class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Rentang Waktu</label>
            <select name="range" id="range"
                    class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                <option value="today" <?php echo $filterRange === 'today' ? 'selected' : ''; ?>>Hari Ini (24 Jam Terakhir)</option>
                <option value="7days" <?php echo $filterRange === '7days' ? 'selected' : ''; ?>>7 Hari Terakhir</option>
                <option value="30days" <?php echo $filterRange === '30days' ? 'selected' : ''; ?>>30 Hari Terakhir</option>
            </select>
        </div>
        
        <!-- Submit Button -->
        <div class="flex items-end">
            <button type="submit" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-white font-semibold rounded-xl text-sm transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-filter text-xs"></i> Terapkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Print-Only Header -->
<div class="hidden print:block mb-8 border-b-2 border-gray-300 pb-4">
    <h1 class="text-3xl font-bold">Laporan Ringkasan Ketersediaan Perangkat</h1>
    <p class="text-sm text-gray-600 mt-1">Dibuat pada: <?php echo date('Y-m-d H:i:s'); ?> | Rentang Waktu: <?php echo htmlspecialchars($filterRange); ?></p>
</div>

<!-- Summary Metrics Row -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm">
        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Persentase Uptime</span>
        <h3 class="text-3xl font-bold mt-1 text-emerald-500"><?php echo $uptimePct; ?>%</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Total cek berhasil: <?php echo $upChecks; ?> dari <?php echo $totalChecks; ?></p>
    </div>
    
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm">
        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rata-rata Latency</span>
        <h3 class="text-3xl font-bold mt-1 text-indigo-500"><?php echo $avgLatency; ?> ms</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Response time seluruh cek aktif</p>
    </div>
    
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-sm">
        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Terdeteksi DOWN</span>
        <h3 class="text-3xl font-bold mt-1 text-red-500"><?php echo $downChecks; ?> kali</h3>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Kejadian mati dalam rentang waktu</p>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Latency Line Chart -->
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl shadow-sm border border-gray-150 dark:border-gray-800 lg:col-span-2">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase mb-4 tracking-tight">Tren Latency (ms)</h3>
        <div class="h-72 w-full">
            <canvas id="latencyChart"></canvas>
        </div>
    </div>
    
    <!-- Uptime Ratio Doughnut Chart -->
    <div class="bg-white dark:bg-[#1e293b] p-5 rounded-2xl shadow-sm border border-gray-150 dark:border-gray-800">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase mb-4 tracking-tight">Rasio Ketersediaan (Checks)</h3>
        <div class="h-72 w-full flex items-center justify-center">
            <canvas id="uptimeChart"></canvas>
        </div>
    </div>
</div>

<!-- Insiden & Gangguan Perangkat Table -->
<div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="font-bold text-gray-800 dark:text-white">Status Gangguan Perangkat (7 Hari Terakhir)</h3>
    </div>
    <div class="overflow-x-auto">
        <table id="downStatsTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Grup</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Perangkat</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Alamat Host</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Tipe Cek</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Jumlah Down (7 Hari Terakhir)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php if (empty($deviceDownStats)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada data perangkat terdaftar.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deviceDownStats as $row): ?>
                        <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($row['group_name'] ?: 'Tanpa Grup'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['device_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($row['ip_address']); ?><?php echo $row['port'] ? ":".$row['port'] : ""; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="uppercase text-xs tracking-wider px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-200 dark:border-gray-700"><?php echo $row['check_type']; ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($row['down_count'] > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-900/40">
                                        <i class="fa-solid fa-triangle-exclamation mr-1.5 animate-pulse"></i> <?php echo $row['down_count']; ?> Kali Down
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-900/40">
                                        <i class="fa-solid fa-circle-check mr-1.5"></i> 0 (Normal)
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Footer -->
    <div id="reportsPaginationContainer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/10 no-print">
        <!-- Filled dynamically via JS -->
    </div>
</div>

<!-- Include Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Initialize Chart.js
    const labels = <?php echo json_encode($chartLabels); ?>;
    const latencies = <?php echo json_encode($chartLatencies); ?>;
    
    // 1. Latency Line Chart
    const latencyCtx = document.getElementById('latencyChart').getContext('2d');
    new Chart(latencyCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Latency (ms)',
                data: latencies,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(156, 163, 175, 0.1)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Uptime Doughnut Chart
    const uptimeCtx = document.getElementById('uptimeChart').getContext('2d');
    const upCount = <?php echo $upChecks; ?>;
    const downCount = <?php echo $downChecks; ?>;
    
    new Chart(uptimeCtx, {
        type: 'doughnut',
        data: {
            labels: ['Berhasil (UP)', 'Gagal (DOWN)'],
            datasets: [{
                data: [upCount, downCount],
                backgroundColor: ['#10b981', '#ef4444'],
                hoverOffset: 4,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 20 }
                }
            },
            cutout: '70%'
        }
    });

    /**
     * Client-side CSV Exporter
     */
    function exportToCSV() {
        const table = document.getElementById('downStatsTable');
        let csv = [];
        
        // Headers
        const headers = [];
        table.querySelectorAll('thead th').forEach(th => headers.push(th.textContent.trim()));
        csv.push(headers.join(','));
        
        // Rows
        table.querySelectorAll('tbody tr').forEach(tr => {
            const row = [];
            const cells = tr.querySelectorAll('td');
            if (cells.length > 1) { // Skip empty row message
                cells.forEach(td => {
                    let text = td.textContent.trim();
                    text = text.replace(/"/g, '""');
                    if (text.includes(',') || text.includes('\n')) {
                        text = `"${text}"`;
                    }
                    row.push(text);
                });
                csv.push(row.join(','));
            }
        });
        
        if (csv.length <= 1) {
            alert('Tidak ada data status untuk diekspor.');
            return;
        }
        
        const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `UMS_Down_Stats_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Client-side Reports table pagination
    let currentReportsPage = 1;
    const reportsItemsPerPage = 10;

    function changeReportsPage(page) {
        currentReportsPage = page;
        paginateReportsTable();
    }

    function paginateReportsTable() {
        const table = document.getElementById('downStatsTable');
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const totalItems = rows.length;
        
        if (totalItems <= 1 && rows[0] && rows[0].cells.length === 1) {
            const pagination = document.getElementById('reportsPaginationContainer');
            if (pagination) pagination.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / reportsItemsPerPage);
        
        // Hide all rows
        rows.forEach(row => row.classList.add('hidden'));
        
        // Show current page rows
        const startRange = (currentReportsPage - 1) * reportsItemsPerPage;
        const endRange = Math.min(startRange + reportsItemsPerPage, totalItems);
        
        for (let i = startRange; i < endRange; i++) {
            if (rows[i]) rows[i].classList.remove('hidden');
        }
        
        renderPagination(totalItems, totalPages, currentReportsPage, reportsItemsPerPage, 'reportsPaginationContainer', changeReportsPage, 'perangkat');
    }

    document.addEventListener("DOMContentLoaded", () => {
        paginateReportsTable();
    });
</script>

<!-- CSS Print support styling -->
<style>
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        .no-print {
            display: none !important;
        }
        main {
            padding: 0 !important;
        }
        aside {
            display: none !important;
        }
        /* Make tables print fully */
        table {
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        /* Override pagination hidden state during print */
        tr.hidden {
            display: table-row !important;
        }
    }
    
    /* Global hidden helper */
    tr.hidden {
        display: none !important;
    }
</style>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
