/**
 * Live Dashboard Monitoring Script - Table Layout & Pagination
 */

let groupsData = [];
let searchFilter = '';
let statusFilter = 'ALL';
let groupFilter = 'ALL';
let isPaused = false;
let countdownTime = 15;
let countdownTimer = null;
let currentPage = 1;
const itemsPerPage = 10;

document.addEventListener("DOMContentLoaded", () => {
    // Initial load
    initDashboard();
    
    // Bind search and filter events
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchFilter = e.target.value.toLowerCase();
            currentPage = 1; // Reset to first page on search
            renderDashboard();
        });
    }
    
    const filterStatus = document.getElementById('filterStatus');
    if (filterStatus) {
        filterStatus.addEventListener('change', (e) => {
            statusFilter = e.target.value;
            currentPage = 1; // Reset to first page on filter change
            renderDashboard();
        });
    }

    const filterGroup = document.getElementById('filterGroup');
    if (filterGroup) {
        filterGroup.addEventListener('change', (e) => {
            groupFilter = e.target.value;
            currentPage = 1; // Reset to first page on filter change
            renderDashboard();
        });
    }
    
    const pauseBtn = document.getElementById('pauseBtn');
    if (pauseBtn) {
        pauseBtn.addEventListener('click', () => {
            isPaused = !isPaused;
            updatePauseButtonState();
        });
    }
});

/**
 * Initializes dashboard, loads data, and sets up polling.
 */
async function initDashboard() {
    await fetchLiveStatus();
    startCountdown();
}

/**
 * Updates pause/play button UI.
 */
function updatePauseButtonState() {
    const pauseBtn = document.getElementById('pauseBtn');
    if (!pauseBtn) return;
    
    if (isPaused) {
        pauseBtn.innerHTML = '<i class="fa-solid fa-play mr-2"></i> Jalankan Auto-Refresh';
        pauseBtn.classList.remove('bg-amber-600', 'hover:bg-amber-700');
        pauseBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
        clearInterval(countdownTimer);
        document.getElementById('refreshIndicator').textContent = 'Auto-Refresh Dijeda';
    } else {
        pauseBtn.innerHTML = '<i class="fa-solid fa-pause mr-2"></i> Jeda';
        pauseBtn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
        pauseBtn.classList.add('bg-amber-600', 'hover:bg-amber-700');
        countdownTime = 15;
        startCountdown();
    }
}

/**
 * Starts countdown timer for auto-refresh.
 */
function startCountdown() {
    clearInterval(countdownTimer);
    updateIndicatorText();
    
    countdownTimer = setInterval(async () => {
        if (isPaused) return;
        
        countdownTime--;
        updateIndicatorText();
        
        if (countdownTime <= 0) {
            countdownTime = 15; // Reset countdown
            showRefreshingState();
            await fetchLiveStatus();
        }
    }, 1000);
}

function updateIndicatorText() {
    const indicator = document.getElementById('refreshIndicator');
    if (indicator) {
        indicator.textContent = `Pembaruan dalam ${countdownTime} detik`;
    }
}

function showRefreshingState() {
    const indicator = document.getElementById('refreshIndicator');
    if (indicator) {
        indicator.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1.5 text-blue-500"></i> Memperbarui data...';
    }
}

/**
 * Fetches data from status live endpoint.
 */
async function fetchLiveStatus() {
    try {
        const res = await fetch('../api/status_live.php');
        const result = await res.json();
        
        if (result.success) {
            groupsData = result.groups;
            populateGroupFilter();
            renderDashboard();
            updateStatistics();
        } else {
            console.error("Failed to load status:", result.error);
        }
    } catch (err) {
        console.error("Connection error loading status:", err);
    }
}

/**
 * Populates Group Filter dropdown dynamically.
 */
function populateGroupFilter() {
    const filterGroup = document.getElementById('filterGroup');
    if (!filterGroup) return;
    
    const currentValue = filterGroup.value || 'ALL';
    
    let options = '<option value="ALL">Semua Grup</option>';
    
    // Sort groups by name
    const sortedGroups = [...groupsData].sort((a, b) => a.group_name.localeCompare(b.group_name));
    
    sortedGroups.forEach(group => {
        options += `<option value="${group.group_name}">${escapeHtml(group.group_name)}</option>`;
    });
    
    filterGroup.innerHTML = options;
    filterGroup.value = currentValue;
    
    if (filterGroup.value !== currentValue) {
        filterGroup.value = 'ALL';
        groupFilter = 'ALL';
    }
}

/**
 * Computes statistics and updates top metrics cards.
 */
function updateStatistics() {
    let totalDevices = 0;
    let upDevices = 0;
    let downDevices = 0;
    
    groupsData.forEach(group => {
        group.devices.forEach(device => {
            totalDevices++;
            if (device.status === 'UP') upDevices++;
            if (device.status === 'DOWN') downDevices++;
        });
    });
    
    const uptimePercentage = totalDevices > 0 ? Math.round((upDevices / totalDevices) * 100) : 100;
    
    const statTotal = document.getElementById('statTotal');
    const statUp = document.getElementById('statUp');
    const statDown = document.getElementById('statDown');
    const statUptime = document.getElementById('statUptime');
    const uptimeProgress = document.getElementById('uptimeProgress');
    
    if (statTotal) statTotal.textContent = totalDevices;
    if (statUp) statUp.textContent = upDevices;
    if (statDown) statDown.textContent = downDevices;
    if (statUptime) statUptime.textContent = `${uptimePercentage}%`;
    
    if (uptimeProgress) {
        uptimeProgress.style.width = `${uptimePercentage}%`;
        if (uptimePercentage >= 95) {
            uptimeProgress.className = 'h-2 bg-emerald-500 rounded-full transition-all duration-500';
        } else if (uptimePercentage >= 80) {
            uptimeProgress.className = 'h-2 bg-amber-500 rounded-full transition-all duration-500';
        } else {
            uptimeProgress.className = 'h-2 bg-red-500 rounded-full transition-all duration-500';
        }
    }
}

/**
 * Renders devices as a flat paginated table.
 */
function renderDashboard() {
    const tbody = document.getElementById('dashboardTableBody');
    const pagination = document.getElementById('paginationContainer');
    if (!tbody) return;
    
    // 1. Flatten all devices from groups
    let allDevices = [];
    groupsData.forEach(group => {
        const groupName = group.group_name;
        group.devices.forEach(device => {
            allDevices.push({
                ...device,
                group_name: groupName
            });
        });
    });
    
    // 2. Filter devices based on Search query, Status filter & Group filter
    const filteredDevices = allDevices.filter(device => {
        const matchesSearch = device.name.toLowerCase().includes(searchFilter) || 
                              device.ip_address.toLowerCase().includes(searchFilter) ||
                              device.group_name.toLowerCase().includes(searchFilter);
        const matchesStatus = statusFilter === 'ALL' || device.status === statusFilter;
        const matchesGroup = groupFilter === 'ALL' || device.group_name === groupFilter;
        return matchesSearch && matchesStatus && matchesGroup;
    });
    
    const totalItems = filteredDevices.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // Adjust current page if it exceeds total pages after filtering
    if (currentPage > totalPages) {
        currentPage = Math.max(1, totalPages);
    }
    
    // 3. Slice devices array for the current page
    const startIndex = (currentPage - 1) * itemsPerPage;
    const paginatedDevices = filteredDevices.slice(startIndex, startIndex + itemsPerPage);
    
    // 4. Render Table Rows
    if (paginatedDevices.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <div class="max-w-xs mx-auto">
                        <i class="fa-solid fa-magnifying-glass text-2xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
                        <span class="font-bold block text-sm">Tidak Ada Data Perangkat</span>
                        <span class="text-xs text-gray-400 block mt-1">Coba sesuaikan kata kunci pencarian atau filter status Anda.</span>
                    </div>
                </td>
            </tr>
        `;
        if (pagination) pagination.innerHTML = '';
        return;
    }
    
    tbody.innerHTML = paginatedDevices.map(device => {
        let statusBadge = '';
        if (device.status === 'UP') {
            statusBadge = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-900/40">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                    UP (${device.latency ? device.latency + ' ms' : 'OK'})
                </span>
            `;
        } else if (device.status === 'DOWN') {
            statusBadge = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-900/40">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500 mr-1.5 animate-pulse"></span>
                    DOWN
                </span>
            `;
        } else {
            statusBadge = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800/40 dark:text-gray-400">
                    UNKNOWN
                </span>
            `;
        }
        
        const portSuffix = device.port ? `:${device.port}` : '';
        const checkTypeFormatted = `
            <span class="text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 ml-2">
                ${device.check_type}
            </span>
        `;
        
        return `
            <tr class="flex flex-col md:table-row hover:bg-gray-50/55 dark:hover:bg-gray-800/30 transition-colors p-4 mb-4 bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-150 dark:border-gray-800 md:p-0 md:mb-0 md:border-none">
                <!-- Baris 1 di Mobile: Nama (Kiri) & Status Badge (Kanan) -->
                <td class="px-0 py-1 md:px-6 md:py-4 whitespace-nowrap block md:table-cell">
                    <div class="flex items-center justify-between md:contents">
                        <span class="text-sm font-bold text-gray-800 dark:text-white md:hidden">${escapeHtml(device.name)}${checkTypeFormatted}</span>
                        <div class="md:hidden">${statusBadge}</div>
                        <!-- Desktop Only -->
                        <span class="hidden md:inline text-sm font-bold text-gray-500 dark:text-gray-400">${escapeHtml(device.group_name)}</span>
                    </div>
                </td>
                <!-- Baris 2 di Mobile: Grup (Kiri) & Durasi (Kanan) -->
                <td class="px-0 py-1 md:px-6 md:py-4 whitespace-nowrap block md:table-cell">
                    <div class="flex items-center justify-between md:contents">
                        <span class="text-xs text-gray-500 dark:text-gray-400 md:hidden flex items-center gap-1.5">
                            <i class="fa-solid fa-folder text-gray-400 text-[10px]"></i>
                            ${escapeHtml(device.group_name)}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 md:hidden flex items-center gap-1.5">
                            <i class="fa-regular fa-clock text-gray-400 text-[10px]"></i>
                            ${device.duration_text}
                        </span>
                        <!-- Desktop Only -->
                        <span class="hidden md:inline text-sm font-bold text-gray-800 dark:text-white">${escapeHtml(device.name)}${checkTypeFormatted}</span>
                    </div>
                </td>
                <!-- Baris 3 di Mobile: IP / Host (Kiri) -->
                <td class="px-0 py-1 md:px-6 md:py-4 whitespace-nowrap block md:table-cell">
                    <div class="flex items-center justify-between md:contents">
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 md:hidden flex items-center gap-1.5">
                            <i class="fa-solid fa-link text-gray-400 text-[10px]"></i>
                            ${escapeHtml(device.ip_address)}${portSuffix}
                        </span>
                        <!-- Desktop Only -->
                        <span class="hidden md:inline text-sm font-mono text-gray-500 dark:text-gray-400">${escapeHtml(device.ip_address)}${portSuffix}</span>
                    </div>
                </td>
                <!-- Desktop Only columns -->
                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-300">
                    <i class="fa-regular fa-clock text-[10px] mr-1.5 text-gray-400"></i>${device.duration_text}
                </td>
            </tr>
        `;
    }).join('');
    
    // 5. Render Pagination Controls
    if (pagination) {
        renderPagination(totalItems, totalPages, currentPage, itemsPerPage, 'paginationContainer', changePage, 'perangkat');
    }
}

/**
 * Changes page index and re-renders table view.
 */
function changePage(page) {
    currentPage = page;
    renderDashboard();
}
