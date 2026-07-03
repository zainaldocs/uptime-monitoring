<?php
/**
 * Groups Management View Page
 * Accessible only by Admin.
 */
require_once __DIR__ . '/../includes/auth_guard.php';
requireAdmin(); // RESTRICTED to Admin

require_once __DIR__ . '/layout/header.php';
?>

<!-- Content Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-bold dark:text-white">Kelola Grup Perangkat</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kelompokkan server dan layanan Anda secara hierarkis</p>
    </div>
    <div>
        <button onclick="openGroupModal('create')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl text-sm shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
            <i class="fa-solid fa-plus text-xs"></i> Tambah Grup Baru
        </button>
    </div>
</div>

<!-- Alert Placeholder -->
<div id="alertContainer" class="hidden mb-6 p-4 rounded-xl text-sm border flex items-center gap-3 transition-all duration-300"></div>

<!-- Groups Table/Grid -->
<div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">ID</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Nama Grup</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Tanggal Dibuat</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Aksi</th>
                </tr>
            </thead>
            <tbody id="groupsTableBody" class="divide-y divide-gray-200 dark:divide-gray-800">
                <!-- Data will be populated via Fetch API -->
                <tr id="loadingRow">
                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                        <div class="flex justify-center items-center gap-3">
                            <i class="fa-solid fa-circle-notch fa-spin text-blue-500 text-lg"></i>
                            <span>Memuat data grup...</span>
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

<!-- Modal CRUD Grup -->
<div id="groupModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white dark:bg-[#1e293b] w-full max-w-md rounded-2xl shadow-2xl border border-gray-150 dark:border-gray-800 overflow-hidden transform scale-95 transition-transform duration-300">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900 dark:text-white">Tambah Grup</h3>
            <button onclick="closeGroupModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        
        <!-- Modal Form -->
        <form id="groupForm" onsubmit="handleGroupSubmit(event)" class="p-6 space-y-4">
            <input type="hidden" id="groupId" name="id">
            
            <div>
                <label for="groupName" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Nama Grup</label>
                <input type="text" id="groupName" name="group_name" required
                       class="block w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                       placeholder="Misal: Server Internal, Public API">
            </div>
            
            <!-- Error message in modal -->
            <div id="modalError" class="hidden p-3 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-lg text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span id="modalErrorText"></span>
            </div>
            
            <!-- Modal Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeGroupModal()" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 font-medium rounded-xl text-sm transition-colors">Batal</button>
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
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-2">Hapus Grup?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Tindakan ini tidak dapat dibatalkan. Perangkat di dalam grup ini akan dikelompokkan ke grup 'Tanpa Grup'.</p>
        
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium rounded-xl transition-colors">Batal</button>
            <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">Hapus</button>
        </div>
    </div>
</div>

<!-- JS Logic for Groups Management -->
<script>
    let groupsData = [];
    let deleteTargetId = null;
    let modalMode = 'create'; // 'create' or 'update'
    let currentPage = 1;
    const itemsPerPage = 10;

    document.addEventListener("DOMContentLoaded", () => {
        loadGroups();
    });



    // Load groups data
    async function loadGroups() {
        const tbody = document.getElementById('groupsTableBody');
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/groups.php');
            const result = await res.json();
            
            if (result.success) {
                groupsData = result.data;
                renderGroups();
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">Error: ${result.error}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">Error fetching data.</td></tr>`;
        }
    }

    // Render groups data to table
    function renderGroups() {
        const tbody = document.getElementById('groupsTableBody');
        const pagination = document.getElementById('paginationContainer');
        
        if (groupsData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Belum ada grup yang ditambahkan.</td></tr>`;
            if (pagination) pagination.innerHTML = '';
            return;
        }
        
        const totalItems = groupsData.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        if (currentPage > totalPages) {
            currentPage = Math.max(1, totalPages);
        }
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const paginatedGroups = groupsData.slice(startIndex, startIndex + itemsPerPage);
        
        tbody.innerHTML = paginatedGroups.map(group => `
            <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/30 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500 dark:text-gray-400">#${group.id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white">${escapeHtml(group.group_name)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${group.created_at}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end gap-2">
                        <button onclick="openGroupModal('update', ${group.id})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20 rounded-lg transition-colors" title="Edit Grup">
                            <i class="fa-solid fa-pen-to-square text-base"></i>
                        </button>
                        <button onclick="confirmDelete(${group.id})" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors" title="Hapus Grup">
                            <i class="fa-solid fa-trash text-base"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        renderPagination(totalItems, totalPages, currentPage, itemsPerPage, 'paginationContainer', changePage, 'grup');
    }

    function changePage(page) {
        currentPage = page;
        renderGroups();
    }

    // Modal Operations
    function openGroupModal(mode, id = null) {
        modalMode = mode;
        const modal = document.getElementById('groupModal');
        const form = document.getElementById('groupForm');
        const title = document.getElementById('modalTitle');
        const errorDiv = document.getElementById('modalError');
        
        form.reset();
        errorDiv.classList.add('hidden');
        
        if (mode === 'create') {
            title.textContent = 'Tambah Grup Baru';
            document.getElementById('groupId').value = '';
        } else {
            title.textContent = 'Edit Grup';
            const group = groupsData.find(g => g.id === id);
            if (group) {
                document.getElementById('groupId').value = group.id;
                document.getElementById('groupName').value = group.group_name;
            }
        }
        
        modal.classList.remove('hidden');
        // Animation scale trigger
        setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 50);
    }

    function closeGroupModal() {
        const modal = document.getElementById('groupModal');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    // Handle Form Submit
    async function handleGroupSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('groupForm');
        const formData = new FormData(form);
        
        const action = modalMode === 'create' ? 'create' : 'update';
        const url = `<?php echo $baseUrl; ?>api/groups.php?action=${action}`;
        const errorDiv = document.getElementById('modalError');
        const errorText = document.getElementById('modalErrorText');
        
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                closeGroupModal();
                showAlert(result.message);
                loadGroups();
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
            const res = await fetch('<?php echo $baseUrl; ?>api/groups.php?action=delete', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            closeDeleteModal();
            
            if (result.success) {
                showAlert(result.message);
                loadGroups();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            closeDeleteModal();
            showAlert('Gagal menghubungi server.', 'danger');
        }
    }


</script>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
