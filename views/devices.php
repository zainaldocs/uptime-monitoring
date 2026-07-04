<?php
/**
 * Devices Management View Page
 * Accessible only by Admin.
 */
require_once __DIR__ . '/../includes/auth_guard.php';
requireAdmin(); // RESTRICTED to Admin

require_once __DIR__ . '/layout/header.php';
?>

<!-- Content Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-bold dark:text-white">Kelola Perangkat</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar aset server, IP, port, atau website yang sedang dipantau</p>
    </div>
    <div>
        <button onclick="openDeviceModal('create')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl text-sm shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus text-xs"></i> Tambah Perangkat Baru
        </button>
    </div>
</div>

<!-- Alert Placeholder -->
<div id="alertContainer" class="hidden mb-6 p-4 rounded-xl text-sm border flex items-center gap-3 transition-all duration-300"></div>

<!-- Bulk Actions Bar -->
<div id="bulkActionsBar" class="hidden mb-6 p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-150 dark:border-gray-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all">
    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
        <i class="fa-solid fa-circle-check text-blue-500 mr-2"></i> <span id="selectedCount">0</span> perangkat terpilih
    </span>
    <div class="flex items-center gap-3">
        <!-- Pindah Grup Dropdown -->
        <select id="bulkGroupSelect" class="px-3 py-1.5 bg-white dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">-- Pindahkan ke Grup --</option>
            <option value="none">Tanpa Grup</option>
            <!-- Populated dynamically via populateGroupSelect() -->
        </select>
        <button onclick="executeBulkUpdateGroup()" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl text-xs transition-colors shadow-sm">
            Terapkan
        </button>
        <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>
        <button onclick="confirmBulkDelete()" class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl text-xs transition-colors flex items-center gap-2 shadow-sm shadow-red-500/10">
            <i class="fa-solid fa-trash-can text-xs"></i> Hapus
        </button>
    </div>
</div>

<!-- Devices Table Container -->
<div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 block md:table">
            <thead class="bg-gray-50 dark:bg-gray-900/50 hidden md:table-header-group">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left w-10">
                        <input type="checkbox" id="selectAllDevices" onchange="toggleSelectAllDevices(this)" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-800 dark:bg-gray-900">
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Perangkat</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Grup</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Alamat Host / IP</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Tipe Cek</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Status</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</th>
                </tr>
            </thead>
            <tbody id="devicesTableBody" class="divide-y divide-gray-200 dark:divide-gray-800 block md:table-row-group">
                <!-- Data will be populated via Fetch API -->
                <tr id="loadingRow" class="block md:table-row">
                    <td colspan="7" class="px-6 py-10 text-center text-gray-500 block md:table-cell">
                        <div class="flex justify-center items-center gap-3">
                            <i class="fa-solid fa-circle-notch fa-spin text-blue-500 text-lg"></i>
                            <span>Memuat data perangkat...</span>
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

<!-- Modal CRUD Perangkat -->
<div id="deviceModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white dark:bg-[#1e293b] w-full max-w-lg rounded-2xl shadow-2xl border border-gray-150 dark:border-gray-800 overflow-hidden transform scale-95 transition-transform duration-300">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900 dark:text-white">Tambah Perangkat</h3>
            <button onclick="closeDeviceModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form id="deviceForm" onsubmit="handleDeviceSubmit(event)" class="p-6 space-y-4">
            <input type="hidden" id="deviceId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nama Perangkat -->
                <div>
                    <label for="deviceName" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Nama Perangkat</label>
                    <input type="text" id="deviceName" name="name" required
                           class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="Misal: Server Utama, API Gateway">
                </div>
                
                <!-- Grup Perangkat -->
                <div>
                    <label for="deviceGroupId" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Grup</label>
                    <select id="deviceGroupId" name="group_id"
                            class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">-- Tanpa Grup --</option>
                        <!-- Populated via JS -->
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- IP Address / Host -->
                <div class="md:col-span-2">
                    <label for="deviceIpAddress" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">IP Address / Hostname</label>
                    <input type="text" id="deviceIpAddress" name="ip_address" required
                           class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="Misal: 192.168.1.100 atau google.com">
                </div>
                
                <!-- Port -->
                <div>
                    <label for="devicePort" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Port (Opsional)</label>
                    <input type="number" id="devicePort" name="port" min="1" max="65535"
                           class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="Misal: 80, 443">
                </div>
            </div>

            <!-- Tipe Pemeriksaan -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Tipe Cek</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="relative flex items-center justify-center p-3 border border-gray-200 dark:border-gray-800 rounded-xl cursor-pointer text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-all">
                        <input type="radio" name="check_type" value="ping" checked class="sr-only" onchange="togglePortRequired()">
                        <span class="flex items-center gap-2 select-none">
                            <i class="fa-solid fa-network-wired text-blue-500"></i> Ping (ICMP)
                        </span>
                    </label>
                    
                    <label class="relative flex items-center justify-center p-3 border border-gray-200 dark:border-gray-800 rounded-xl cursor-pointer text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-all">
                        <input type="radio" name="check_type" value="tcp" class="sr-only" onchange="togglePortRequired()">
                        <span class="flex items-center gap-2 select-none">
                            <i class="fa-solid fa-plug text-indigo-500"></i> TCP Socket
                        </span>
                    </label>
                    
                    <label class="relative flex items-center justify-center p-3 border border-gray-200 dark:border-gray-800 rounded-xl cursor-pointer text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-all">
                        <input type="radio" name="check_type" value="http" class="sr-only" onchange="togglePortRequired()">
                        <span class="flex items-center gap-2 select-none">
                            <i class="fa-solid fa-globe text-emerald-500"></i> HTTP/HTTPS
                        </span>
                    </label>
                </div>
            </div>
            
            <!-- Error message in modal -->
            <div id="modalError" class="hidden p-3 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-lg text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span id="modalErrorText"></span>
            </div>
            
            <!-- Modal Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeDeviceModal()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 font-medium rounded-xl text-sm transition-colors">Batal</button>
                <button type="submit" id="modalSubmitBtn" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl text-sm transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-[#1e293b] w-full max-w-sm rounded-2xl shadow-2xl border border-gray-150 dark:border-gray-800 p-6 text-center transform scale-95 transition-transform">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-400 mb-4 shadow-sm">
            <i class="fa-solid fa-trash-can text-lg animate-pulse"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-2">Hapus Perangkat?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Tindakan ini tidak dapat dibatalkan. Riwayat log pemantauan perangkat ini juga akan dihapus permanen.</p>
        
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium rounded-xl transition-colors">Batal</button>
            <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">Hapus</button>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Massal -->
<div id="bulkDeleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-[#1e293b] w-full max-w-sm rounded-2xl shadow-2xl border border-gray-150 dark:border-gray-800 p-6 text-center transform scale-95 transition-transform">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-400 mb-4 shadow-sm">
            <i class="fa-solid fa-trash-can text-lg animate-pulse"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-2">Hapus Perangkat Terpilih?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Tindakan ini tidak dapat dibatalkan. Semua perangkat yang dipilih dan seluruh riwayat log ketersediaannya akan dihapus secara permanen.</p>
        
        <div class="flex justify-center gap-3">
            <button onclick="closeBulkDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium rounded-xl transition-colors">Batal</button>
            <button id="confirmBulkDeleteBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">Hapus Semua</button>
        </div>
    </div>
</div>

<!-- JS Logic for Devices Management -->
<script>
    let devicesData = [];
    let groupsData = [];
    let deleteTargetId = null;
    let modalMode = 'create'; // 'create' or 'update'
    let currentPage = 1;
    const itemsPerPage = 10;
    let selectedDevices = new Set();

    document.addEventListener("DOMContentLoaded", () => {
        loadData();
    });

    // Toggle Port styling & requirement based on Check Type
    function togglePortRequired() {
        const checkType = document.querySelector('input[name="check_type"]:checked').value;
        const portInput = document.getElementById('devicePort');
        
        if (checkType === 'tcp') {
            portInput.required = true;
            portInput.placeholder = 'Wajib untuk TCP';
        } else {
            portInput.required = false;
            portInput.placeholder = 'Misal: 80, 443';
        }
        
        // Highlight active radio button style
        document.getElementsByName('check_type').forEach(el => {
            const label = el.closest('label');
            if (el.checked) {
                label.classList.add('border-blue-500', 'bg-blue-50/10', 'dark:bg-blue-900/10');
            } else {
                label.classList.remove('border-blue-500', 'bg-blue-50/10', 'dark:bg-blue-900/10');
            }
        });
    }

    // Load Groups & Devices data concurrently
    async function loadData() {
        const tbody = document.getElementById('devicesTableBody');
        try {
            // Fetch groups first for select dropdown mapping
            const resGroups = await fetch('<?php echo $baseUrl; ?>api/groups.php');
            const resultGroups = await resGroups.json();
            if (resultGroups.success) {
                groupsData = resultGroups.data;
                populateGroupSelect();
            }
            
            // Fetch devices
            const resDevices = await fetch('<?php echo $baseUrl; ?>api/devices.php');
            const resultDevices = await resDevices.json();
            if (resultDevices.success) {
                devicesData = resultDevices.data;
                // Clear selection if some devices no longer exist
                const validIds = new Set(devicesData.map(d => d.id));
                selectedDevices = new Set([...selectedDevices].filter(id => validIds.has(id)));
                renderDevices();
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Error: ${resultDevices.error}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Error fetching data.</td></tr>`;
        }
    }

    // Populate group select dropdown in modal and bulk action bar
    function populateGroupSelect() {
        const select = document.getElementById('deviceGroupId');
        select.innerHTML = '<option value="">-- Tanpa Grup --</option>' + 
            groupsData.map(g => `<option value="${g.id}">${escapeHtml(g.group_name)}</option>`).join('');
            
        const bulkSelect = document.getElementById('bulkGroupSelect');
        bulkSelect.innerHTML = '<option value="">-- Pindahkan ke Grup --</option>' + 
            '<option value="none">Tanpa Grup</option>' +
            groupsData.map(g => `<option value="${g.id}">${escapeHtml(g.group_name)}</option>`).join('');
    }

    // Render devices list
    function renderDevices() {
        const tbody = document.getElementById('devicesTableBody');
        const pagination = document.getElementById('paginationContainer');
        
        if (devicesData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada perangkat yang terdaftar.</td></tr>`;
            if (pagination) pagination.innerHTML = '';
            updateBulkActionsBar();
            return;
        }
        
        const totalItems = devicesData.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        if (currentPage > totalPages) {
            currentPage = Math.max(1, totalPages);
        }
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const paginatedDevices = devicesData.slice(startIndex, startIndex + itemsPerPage);
        
        tbody.innerHTML = paginatedDevices.map(device => {
            let statusBadge = '';
            if (device.status === 'UP') {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"><span class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>UP</span>';
            } else if (device.status === 'DOWN') {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400"><span class="h-1.5 w-1.5 rounded-full bg-red-500 mr-1.5 animate-pulse"></span>DOWN</span>';
            } else {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800/40 dark:text-gray-400">UNKNOWN</span>';
            }
            
            const groupName = device.group_name ? escapeHtml(device.group_name) : '<span class="text-gray-400 text-xs italic">Tanpa Grup</span>';
            const portText = device.port ? `:${device.port}` : '';
            const checkTypeFormatted = `<span class="uppercase font-semibold text-xs tracking-wider px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">${device.check_type}</span>`;
            
            return `
                <tr class="flex flex-col md:table-row hover:bg-gray-50/55 dark:hover:bg-gray-800/30 transition-colors p-4 mb-4 bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-150 dark:border-gray-800 md:p-0 md:mb-0 md:border-none">
                    <!-- Baris 1: Checkbox & Nama (kiri), Status (kanan) -->
                    <td class="px-0 py-1 md:px-6 md:py-4 whitespace-nowrap block md:table-cell">
                        <div class="flex items-center justify-between md:contents">
                            <div class="flex items-center gap-2 md:hidden">
                                <input type="checkbox" value="${device.id}" onchange="toggleSelectDevice(this, ${device.id})" ${selectedDevices.has(device.id) ? 'checked' : ''} class="device-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-sm font-bold text-gray-800 dark:text-white">${escapeHtml(device.name)}</span>
                            </div>
                            <div class="md:hidden">${statusBadge}</div>
                            <!-- Desktop Only -->
                            <input type="checkbox" value="${device.id}" onchange="toggleSelectDevice(this, ${device.id})" ${selectedDevices.has(device.id) ? 'checked' : ''} class="hidden md:inline group-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-800 dark:bg-gray-900">
                        </div>
                    </td>
                    <!-- Baris 2: Grup (kiri) & Tipe Cek (kanan) -->
                    <td class="px-0 py-1 md:px-6 md:py-4 whitespace-nowrap block md:table-cell">
                        <div class="flex items-center justify-between md:contents">
                            <span class="text-xs text-gray-500 dark:text-gray-400 md:hidden flex items-center gap-1.5">
                                <i class="fa-solid fa-folder text-gray-400 text-[10px]"></i>
                                ${groupName}
                            </span>
                            <span class="md:hidden">${checkTypeFormatted}</span>
                            <!-- Desktop Only -->
                            <span class="hidden md:inline text-sm font-bold text-gray-800 dark:text-white">${escapeHtml(device.name)}</span>
                        </div>
                    </td>
                    <!-- Baris 3: IP/Host (kiri) & Aksi (kanan) -->
                    <td class="px-0 py-1.5 md:px-6 md:py-4 whitespace-nowrap block md:table-cell">
                        <div class="flex items-center justify-between md:contents">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400 md:hidden flex items-center gap-1.5">
                                <i class="fa-solid fa-link text-gray-400 text-[10px]"></i>
                                ${escapeHtml(device.ip_address)}${portText}
                            </span>
                            <div class="flex gap-1 md:hidden">
                                <button onclick="openDeviceModal('update', ${device.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20 rounded-lg transition-colors" title="Edit Perangkat">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </button>
                                <button onclick="confirmDelete(${device.id})" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors" title="Hapus Perangkat">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                            <!-- Desktop Only -->
                            <span class="hidden md:inline text-sm text-gray-800 dark:text-gray-200">${groupName}</span>
                        </div>
                    </td>
                    <!-- Desktop-only columns -->
                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">${escapeHtml(device.ip_address)}${portText}</td>
                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm">${checkTypeFormatted}</td>
                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm">${statusBadge}</td>
                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <button onclick="openDeviceModal('update', ${device.id})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20 rounded-lg transition-colors" title="Edit Perangkat">
                                <i class="fa-solid fa-pen-to-square text-base"></i>
                            </button>
                            <button onclick="confirmDelete(${device.id})" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors" title="Hapus Perangkat">
                                <i class="fa-solid fa-trash text-base"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        renderPagination(totalItems, totalPages, currentPage, itemsPerPage, 'paginationContainer', changePage, 'perangkat');
        updateSelectAllHeader();
        updateBulkActionsBar();
    }

    function changePage(page) {
        currentPage = page;
        renderDevices();
    }

    // Checkbox selection functions
    function toggleSelectDevice(el, id) {
        if (el.checked) {
            selectedDevices.add(id);
        } else {
            selectedDevices.delete(id);
        }
        updateBulkActionsBar();
        updateSelectAllHeader();
    }

    function toggleSelectAllDevices(el) {
        const pageCheckboxes = document.querySelectorAll('.device-checkbox');
        pageCheckboxes.forEach(cb => {
            const id = parseInt(cb.value);
            cb.checked = el.checked;
            if (el.checked) {
                selectedDevices.add(id);
            } else {
                selectedDevices.delete(id);
            }
        });
        updateBulkActionsBar();
    }

    function updateSelectAllHeader() {
        const pageCheckboxes = document.querySelectorAll('.device-checkbox');
        const selectAllHeader = document.getElementById('selectAllDevices');
        if (pageCheckboxes.length === 0) {
            if (selectAllHeader) selectAllHeader.checked = false;
            return;
        }
        let allChecked = true;
        pageCheckboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });
        if (selectAllHeader) selectAllHeader.checked = allChecked;
    }

    function updateBulkActionsBar() {
        const bar = document.getElementById('bulkActionsBar');
        const countEl = document.getElementById('selectedCount');
        if (selectedDevices.size > 0) {
            bar.classList.remove('hidden');
            countEl.textContent = selectedDevices.size;
        } else {
            bar.classList.add('hidden');
            const selectAllHeader = document.getElementById('selectAllDevices');
            if (selectAllHeader) selectAllHeader.checked = false;
        }
    }

    // Modal Operations
    function openDeviceModal(mode, id = null) {
        modalMode = mode;
        const modal = document.getElementById('deviceModal');
        const form = document.getElementById('deviceForm');
        const title = document.getElementById('modalTitle');
        const errorDiv = document.getElementById('modalError');
        
        form.reset();
        errorDiv.classList.add('hidden');
        
        if (mode === 'create') {
            title.textContent = 'Tambah Perangkat Baru';
            document.getElementById('deviceId').value = '';
            // Select default radio checked
            form.querySelector('input[name="check_type"][value="ping"]').checked = true;
        } else {
            title.textContent = 'Edit Perangkat';
            const device = devicesData.find(d => d.id === id);
            if (device) {
                document.getElementById('deviceId').value = device.id;
                document.getElementById('deviceName').value = device.name;
                document.getElementById('deviceGroupId').value = device.group_id ? device.group_id : '';
                document.getElementById('deviceIpAddress').value = device.ip_address;
                document.getElementById('devicePort').value = device.port ? device.port : '';
                
                // Select active radio button
                form.querySelector(`input[name="check_type"][value="${device.check_type}"]`).checked = true;
            }
        }
        
        togglePortRequired();
        modal.classList.remove('hidden');
        // Animation scale trigger
        setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 50);
    }

    function closeDeviceModal() {
        const modal = document.getElementById('deviceModal');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    // Handle Form Submit
    async function handleDeviceSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('deviceForm');
        const formData = new FormData(form);
        
        const action = modalMode === 'create' ? 'create' : 'update';
        const url = `<?php echo $baseUrl; ?>api/devices.php?action=${action}`;
        const errorDiv = document.getElementById('modalError');
        const errorText = document.getElementById('modalErrorText');
        
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                closeDeviceModal();
                showAlert(result.message);
                loadData();
            } else {
                errorText.textContent = result.error;
                errorDiv.classList.remove('hidden');
            }
        } catch (err) {
            errorText.textContent = 'Gagal menyimpan data ke server.';
            errorDiv.classList.remove('hidden');
        }
    }

    // Delete Operations
    function confirmDelete(id) {
        deleteTargetId = id;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 50);
        
        document.getElementById('confirmDeleteBtn').onclick = executeDelete;
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            deleteTargetId = null;
        }, 200);
    }

    async function executeDelete() {
        if (!deleteTargetId) return;
        
        const formData = new FormData();
        formData.append('id', deleteTargetId);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/devices.php?action=delete', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            closeDeleteModal();
            
            if (result.success) {
                showAlert(result.message);
                loadData();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            closeDeleteModal();
            showAlert('Gagal menghubungi server.', 'danger');
        }
    }

    // Bulk Delete Operations
    function confirmBulkDelete() {
        if (selectedDevices.size === 0) return;
        const modal = document.getElementById('bulkDeleteModal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 50);
        
        document.getElementById('confirmBulkDeleteBtn').onclick = executeBulkDelete;
    }

    function closeBulkDeleteModal() {
        const modal = document.getElementById('bulkDeleteModal');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    async function executeBulkDelete() {
        const formData = new FormData();
        const idsArray = Array.from(selectedDevices);
        idsArray.forEach(id => formData.append('ids[]', id));
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/devices.php?action=bulk_delete', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            closeBulkDeleteModal();
            
            if (result.success) {
                showAlert(result.message);
                selectedDevices.clear();
                loadData();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            closeBulkDeleteModal();
            showAlert('Gagal menghubungi server.', 'danger');
        }
    }

    // Bulk Update Group Operations
    async function executeBulkUpdateGroup() {
        if (selectedDevices.size === 0) return;
        const groupSelect = document.getElementById('bulkGroupSelect');
        const groupId = groupSelect.value;
        
        if (groupId === '') {
            showAlert('Harap pilih grup tujuan terlebih dahulu.', 'danger');
            return;
        }
        
        const formData = new FormData();
        const idsArray = Array.from(selectedDevices);
        idsArray.forEach(id => formData.append('ids[]', id));
        formData.append('group_id', groupId);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/devices.php?action=bulk_update_group', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                showAlert(result.message);
                selectedDevices.clear();
                groupSelect.value = ''; // Reset select dropdown
                loadData();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            showAlert('Gagal menghubungi server.', 'danger');
        }
    }


</script>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
