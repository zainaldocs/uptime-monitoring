<?php
/**
 * Settings and Configuration Page
 * Accessible only by Admin.
 */
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin(); // RESTRICTED to Admin

require_once __DIR__ . '/layout/header.php';

// Fetch current SMTP & system settings to pre-fill the form
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$smtp_host = $settings['smtp_host'] ?? '';
$smtp_port = $settings['smtp_port'] ?? '';
$smtp_auth = $settings['smtp_auth'] ?? 'true';
$smtp_user = $settings['smtp_user'] ?? '';
$alert_email = $settings['alert_target_email'] ?? '';
$theme_mode = $settings['theme_mode'] ?? 'dark';
$email_trigger_down = $settings['email_trigger_down'] ?? '1';
$email_trigger_up = $settings['email_trigger_up'] ?? '1';
$email_trigger_daily_report = $settings['email_trigger_daily_report'] ?? '1';
?>

<!-- Content Header -->
<div class="mb-8">
    <h2 class="text-2xl font-bold dark:text-white">Pengaturan Sistem</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Konfigurasi parameter email notifikasi SMTP dan kelola akun pengguna</p>
</div>

<!-- Alert Placeholder -->
<div id="alertContainer" class="hidden mb-6 p-4 rounded-xl text-sm border flex items-center gap-3 transition-all duration-300"></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: SMTP Configuration Form & Theme Preferences -->
    <div class="lg:col-span-1 space-y-6">
        <!-- SMTP Settings Card -->
        <div class="bg-white dark:bg-[#1e293b] p-6 rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5 uppercase tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-envelope text-blue-500"></i> Pengaturan SMTP
            </h3>
            
            <form id="smtpForm" onsubmit="handleSmtpSubmit(event)" class="space-y-4">
                <!-- Host & Port -->
                <div>
                    <label for="smtp_host" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">SMTP Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" required
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="smtp.example.com">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="smtp_port" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">SMTP Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" required
                               class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               placeholder="587">
                    </div>
                    <div>
                        <label for="smtp_auth" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Autentikasi</label>
                        <select id="smtp_auth" name="smtp_auth"
                                class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="true" <?php echo $smtp_auth === 'true' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="false" <?php echo $smtp_auth === 'false' ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
                
                <!-- SMTP Username -->
                <div>
                    <label for="smtp_user" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">SMTP Username</label>
                    <input type="text" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($smtp_user); ?>"
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="user@example.com">
                </div>
                
                <!-- SMTP Password -->
                <div>
                    <label for="smtp_pass" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">SMTP Password</label>
                    <input type="password" id="smtp_pass" name="smtp_pass"
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="•••••••• (kosongkan jika tidak diubah)">
                </div>
                
                <!-- Email Target Alert -->
                <div>
                    <label for="alert_target_email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email Target Notifikasi</label>
                    <input type="email" id="alert_target_email" name="alert_target_email" value="<?php echo htmlspecialchars($alert_email); ?>" required
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="target@example.com">
                </div>
                
                <!-- Email Triggers -->
                <div class="space-y-2 pt-2 border-t border-gray-150 dark:border-gray-800">
                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Pemicu Notifikasi Email</span>
                    
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="email_trigger_down" value="1" <?php echo $email_trigger_down === '1' ? 'checked' : ''; ?>
                               class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-800 dark:bg-gray-900">
                        <span class="text-xs text-gray-600 dark:text-gray-300">Kirim email saat perangkat <b>DOWN</b></span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="email_trigger_up" value="1" <?php echo $email_trigger_up === '1' ? 'checked' : ''; ?>
                               class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-800 dark:bg-gray-900">
                        <span class="text-xs text-gray-600 dark:text-gray-300">Kirim email saat perangkat kembali <b>UP (Online)</b></span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="email_trigger_daily_report" value="1" <?php echo $email_trigger_daily_report === '1' ? 'checked' : ''; ?>
                               class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-800 dark:bg-gray-900">
                        <span class="text-xs text-gray-600 dark:text-gray-300">Kirim <b>laporan harian</b> otomatis</span>
                    </label>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow-md transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi
                    </button>
                    <button type="button" onclick="openTestSmtpModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow-md transition-colors flex items-center justify-center gap-2" title="Uji Coba Pengaturan SMTP">
                        <i class="fa-solid fa-paper-plane"></i> Uji SMTP
                    </button>
                </div>
            </form>
        </div>

        <!-- System Theme Selection -->
        <div class="bg-white dark:bg-[#1e293b] p-6 rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5 uppercase tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-circle-half-stroke text-indigo-500"></i> Preferensi Tema Default
            </h3>
            <form id="themeForm" onsubmit="handleThemeSubmit(event)" class="space-y-4">
                <div>
                    <label for="theme_mode" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Tema Default</label>
                    <select id="theme_mode" name="theme"
                            class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="dark" <?php echo $theme_mode === 'dark' ? 'selected' : ''; ?>>Dark Mode (Gelap)</option>
                        <option value="light" <?php echo $theme_mode === 'light' ? 'selected' : ''; ?>>Light Mode (Terang)</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm shadow-md transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Tema Default
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: User Management -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Add New User Card -->
        <div class="bg-white dark:bg-[#1e293b] p-6 rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5 uppercase tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-emerald-500"></i> Tambah Pengguna Baru
            </h3>
            
            <form id="newUserForm" onsubmit="handleNewUserSubmit(event)" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Username -->
                <div>
                    <label for="new_username" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username</label>
                    <input type="text" id="new_username" name="username" required
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="admin_baru">
                </div>
                
                <!-- Email -->
                <div>
                    <label for="new_email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email</label>
                    <input type="email" id="new_email" name="email" required
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="user@example.com">
                </div>
                
                <!-- Password -->
                <div>
                    <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Password</label>
                    <input type="password" id="new_password" name="password" required
                           class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="••••••••">
                </div>
                
                <!-- Role -->
                <div>
                    <label for="new_role" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Role</label>
                    <select id="new_role" name="role"
                            class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="Staff">Staff (Hanya Lihat Laporan & Dashboard)</option>
                        <option value="Admin">Admin (Akses Penuh)</option>
                    </select>
                </div>
                
                <div class="sm:col-span-2 pt-2">
                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm shadow-md transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-check"></i> Buat User Baru
                    </button>
                </div>
            </form>
        </div>

        <!-- Users List Card -->
        <div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-xl border border-gray-150 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="font-bold text-gray-900 dark:text-white">Daftar Pengguna Aktif</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Role</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="divide-y divide-gray-200 dark:divide-gray-800">
                        <!-- Populated via AJAX -->
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Memuat data user...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus User -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-[#1e293b] w-full max-w-sm rounded-2xl shadow-2xl border border-gray-150 dark:border-gray-800 p-6 text-center transform scale-95 transition-transform">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-950/30 text-red-600 dark:text-red-400 mb-4 shadow-sm">
            <i class="fa-solid fa-user-minus text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-2">Hapus Akun Pengguna?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Tindakan ini akan menghapus akses masuk pengguna ini secara permanen dari sistem.</p>
        
        <div class="flex justify-center gap-3">
            <button onclick="closeDeleteUserModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-sm font-medium rounded-xl transition-colors">Batal</button>
            <button id="confirmDeleteUserBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">Hapus User</button>
        </div>
    </div>
</div>

<!-- Modal Uji SMTP -->
<div id="testSmtpModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-[#1e293b] w-full max-w-md rounded-2xl shadow-2xl border border-gray-150 dark:border-gray-800 overflow-hidden transform scale-95 transition-transform duration-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Uji Coba Koneksi SMTP</h3>
            <button onclick="closeTestSmtpModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">UMS akan mencoba mengirimkan email uji coba menggunakan pengaturan SMTP yang sedang Anda ketik di formulir saat ini (tidak perlu disimpan terlebih dahulu).</p>
            <div>
                <label for="test_email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email Penerima Uji Coba</label>
                <input type="email" id="test_email" class="block w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="penerima@example.com">
            </div>
            
            <div id="testSmtpAlert" class="hidden p-3 rounded-lg text-xs flex items-center gap-2"></div>
            
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeTestSmtpModal()" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium rounded-xl transition-colors">Tutup</button>
                <button type="button" id="sendTestSmtpBtn" onclick="runSmtpTest()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2">
                    <span>Kirim Email Test</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JS Settings Handlers -->
<script>
    let usersData = [];
    let deleteUserTargetId = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadUsers();
    });

    // Load active users list
    async function loadUsers() {
        const tbody = document.getElementById('usersTableBody');
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/settings.php?action=list_users');
            const result = await res.json();
            
            if (result.success) {
                usersData = result.data;
                renderUsers();
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Error: ${result.error}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 text-center text-red-500">Gagal memuat data pengguna.</td></tr>`;
        }
    }

    // Render users to table
    function renderUsers() {
        const tbody = document.getElementById('usersTableBody');
        const currentUserId = <?php echo intval($_SESSION['user_id']); ?>;
        
        if (usersData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada user terdaftar.</td></tr>`;
            return;
        }
        
        tbody.innerHTML = usersData.map(user => {
            const isSelf = user.id === currentUserId;
            
            const deleteBtn = isSelf 
                ? '<span class="text-xs text-gray-400 dark:text-gray-500 italic pr-3">Sedang Aktif</span>'
                : `<button onclick="confirmDeleteUser(${user.id})" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors mr-1" title="Hapus User"><i class="fa-solid fa-trash-can text-sm"></i></button>`;
                
            const roleBadge = user.role === 'Admin'
                ? '<span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Admin</span>'
                : '<span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Staff</span>';
                
            return `
                <tr class="hover:bg-gray-50/55 dark:hover:bg-gray-800/30 transition-colors">
                    <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-800 dark:text-white">${escapeHtml(user.username)}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${escapeHtml(user.email)}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-sm">${roleBadge}</td>
                    <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">${deleteBtn}</td>
                </tr>
            `;
        }).join('');
    }

    // SMTP Form Submit
    async function handleSmtpSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('smtpForm');
        const formData = new FormData(form);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/settings.php?action=update_smtp', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                showAlert(result.message);
                // Clear password input visually
                document.getElementById('smtp_pass').value = '';
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            showAlert('Gagal menyimpan pengaturan SMTP.', 'danger');
        }
    }

    // Theme Settings Submit
    async function handleThemeSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('themeForm');
        const formData = new FormData(form);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/settings.php?action=update_theme', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                showAlert(result.message);
                // Sync current local theme to matched mode immediately
                const selectedTheme = document.getElementById('theme_mode').value;
                if (selectedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
                if (typeof updateThemeIcon === 'function') updateThemeIcon();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            showAlert('Gagal memperbarui tema.', 'danger');
        }
    }

    // New User Submit
    async function handleNewUserSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('newUserForm');
        const formData = new FormData(form);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/settings.php?action=create_user', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                showAlert(result.message);
                form.reset();
                loadUsers();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            showAlert('Gagal membuat user baru.', 'danger');
        }
    }

    // User Delete Handling
    function confirmDeleteUser(id) {
        deleteUserTargetId = id;
        const modal = document.getElementById('deleteUserModal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 50);
        
        document.getElementById('confirmDeleteUserBtn').onclick = executeDeleteUser;
    }

    function closeDeleteUserModal() {
        const modal = document.getElementById('deleteUserModal');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            deleteUserTargetId = null;
        }, 200);
    }

    async function executeDeleteUser() {
        if (!deleteUserTargetId) return;
        
        const formData = new FormData();
        formData.append('id', deleteUserTargetId);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/settings.php?action=delete_user', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            closeDeleteUserModal();
            
            if (result.success) {
                showAlert(result.message);
                loadUsers();
            } else {
                showAlert(result.error, 'danger');
            }
        } catch (err) {
            closeDeleteUserModal();
            showAlert('Gagal memproses penghapusan user.', 'danger');
        }
    }

    // HTML escape utility
    function escapeHtml(str) {
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // SMTP Test Modal operations
    function openTestSmtpModal() {
        const modal = document.getElementById('testSmtpModal');
        const alert = document.getElementById('testSmtpAlert');
        alert.classList.add('hidden');
        
        // Auto fill email target from settings form input
        const targetEmail = document.getElementById('alert_target_email').value;
        document.getElementById('test_email').value = targetEmail;
        
        modal.classList.remove('hidden');
        setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 50);
    }

    function closeTestSmtpModal() {
        const modal = document.getElementById('testSmtpModal');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    async function runSmtpTest() {
        const testEmail = document.getElementById('test_email').value.trim();
        const alert = document.getElementById('testSmtpAlert');
        const sendBtn = document.getElementById('sendTestSmtpBtn');
        
        if (!testEmail) {
            alert.className = 'p-3 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-lg text-xs';
            alert.textContent = 'Harap isi email penerima.';
            alert.classList.remove('hidden');
            return;
        }
        
        sendBtn.disabled = true;
        const originalBtnHtml = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Sedang Menguji...';
        alert.classList.add('hidden');
        
        // Gather current unsaved settings values to test them
        const formData = new FormData();
        formData.append('smtp_host', document.getElementById('smtp_host').value);
        formData.append('smtp_port', document.getElementById('smtp_port').value);
        formData.append('smtp_auth', document.getElementById('smtp_auth').value);
        formData.append('smtp_user', document.getElementById('smtp_user').value);
        formData.append('smtp_pass', document.getElementById('smtp_pass').value);
        formData.append('test_email', testEmail);
        
        try {
            const res = await fetch('<?php echo $baseUrl; ?>api/settings.php?action=test_smtp', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                alert.className = 'p-3 bg-green-50 text-green-600 dark:bg-green-950/30 dark:text-green-400 rounded-lg text-xs flex items-center gap-2';
                alert.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + result.message;
            } else {
                alert.className = 'p-3 bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400 rounded-lg text-xs flex items-center gap-2';
                alert.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(result.error);
            }
        } catch (err) {
            alert.className = 'p-3 bg-red-50 text-red-600 rounded-lg text-xs';
            alert.textContent = 'Gagal menghubungi server API.';
        } finally {
            alert.classList.remove('hidden');
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalBtnHtml;
        }
    }
</script>

<?php
require_once __DIR__ . '/layout/footer.php';
?>
