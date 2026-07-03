<?php
/**
 * Main Layout Header
 */
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';
startSessionIfNeeded();
requireLogin();

try {
    $pdoTheme = getDBConnection();
    $themeStmt = $pdoTheme->query("SELECT setting_value FROM system_settings WHERE setting_key = 'theme_mode'");
    $themeMode = $themeStmt->fetchColumn() ?: 'dark';
} catch (Exception $e) {
    $themeMode = 'dark';
}

$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? 'Staff';
$isAdmin = isAdmin();
$baseUrl = getBaseUrl();

// Active menu helper
$currentScript = basename($_SERVER['SCRIPT_NAME']);
function isActive(string $page, string $currentScript): string {
    return $page === $currentScript 
        ? 'bg-blue-600/10 text-blue-500 border-l-4 border-blue-600' 
        : 'text-gray-400 hover:bg-gray-800/50 hover:text-gray-200';
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uptime Monitoring System (UMS)</title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Inline Theme Initializer (Anti-Flicker) -->
    <script>
        const systemTheme = '<?php echo $themeMode; ?>';
        if (systemTheme === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    <!-- Meta CSRF Token -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    
    <!-- Global JavaScript Utils -->
    <script src="<?php echo $baseUrl; ?>assets/js/utils.js"></script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-[#0f172a] text-gray-900 dark:text-gray-100 transition-colors duration-200 font-sans">

<div class="flex h-full overflow-hidden">
    <!-- Sidebar Navigation -->
    <aside class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-64 bg-white dark:bg-[#1e293b] border-r border-gray-200 dark:border-gray-800">
            <!-- Brand Logo / Header -->
            <div class="flex items-center h-16 px-6 border-b border-gray-200 dark:border-gray-800 gap-3">
                <div class="p-2 bg-blue-600 rounded-lg text-white shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-square-poll-vertical text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-indigo-500 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent">UMS Pro</h1>
                    <span class="text-xs text-gray-400">Uptime Monitoring</span>
                </div>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-3 mb-2">Main Menu</div>
                
                <a href="<?php echo $baseUrl; ?>views/dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 <?php echo isActive('dashboard.php', $currentScript); ?>">
                    <i class="fa-solid fa-chart-line w-5 mr-3 text-lg"></i>
                    Dashboard
                </a>
                
                <a href="<?php echo $baseUrl; ?>views/reports.php" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 <?php echo isActive('reports.php', $currentScript); ?>">
                    <i class="fa-solid fa-chart-column w-5 mr-3 text-lg"></i>
                    Laporan Analitik
                </a>
                
                <?php if ($isAdmin): ?>
                <div class="pt-6 text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-3 mb-2">Inventaris & Grup</div>
                
                <a href="<?php echo $baseUrl; ?>views/groups.php" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 <?php echo isActive('groups.php', $currentScript); ?>">
                    <i class="fa-solid fa-folder-tree w-5 mr-3 text-lg"></i>
                    Kelola Grup
                </a>
                
                <a href="<?php echo $baseUrl; ?>views/devices.php" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 <?php echo isActive('devices.php', $currentScript); ?>">
                    <i class="fa-solid fa-server w-5 mr-3 text-lg"></i>
                    Kelola Perangkat
                </a>
                
                <div class="pt-6 text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-3 mb-2">Konfigurasi</div>
                
                <a href="<?php echo $baseUrl; ?>views/settings.php" class="flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 <?php echo isActive('settings.php', $currentScript); ?>">
                    <i class="fa-solid fa-sliders w-5 mr-3 text-lg"></i>
                    Pengaturan Sistem
                </a>
                <?php endif; ?>
            </nav>
            
            <!-- User Status & Profile Panel -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-[#1e293b]/50">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center overflow-hidden">
                        <div class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold shadow-inner">
                            <?php echo strtoupper(substr($username, 0, 2)); ?>
                        </div>
                        <div class="ml-3 overflow-hidden">
                            <p class="text-sm font-semibold truncate leading-tight dark:text-white"><?php echo htmlspecialchars($username); ?></p>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold mt-1 <?php echo $isAdmin ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'; ?>">
                                <?php echo $role; ?>
                            </span>
                        </div>
                    </div>
                    
                    <a href="<?php echo $baseUrl; ?>api/auth.php?action=logout" class="flex-shrink-0 flex items-center px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors border border-red-200/50 dark:border-red-900/20" title="Keluar">
                        <i class="fa-solid fa-right-from-bracket mr-1.5"></i>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Content Area Container -->
    <div class="flex flex-col flex-1 w-0 overflow-hidden">
        <!-- Topbar Mobile Header -->
        <header class="flex items-center justify-between h-16 px-6 bg-white dark:bg-[#1e293b] border-b border-gray-200 dark:border-gray-800 md:hidden">
            <div class="flex items-center gap-3">
                <div class="p-1.5 bg-blue-600 rounded-md text-white">
                    <i class="fa-solid fa-square-poll-vertical text-lg"></i>
                </div>
                <h1 class="text-base font-bold bg-gradient-to-r from-blue-600 to-indigo-500 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent">UMS Pro</h1>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Dropdown Trigger for Mobile Navigation Menu -->
                <button id="mobileMenuBtn" class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </header>

        <!-- Mobile Navigation Menu Dropdown (Hidden by default) -->
        <div id="mobileMenu" class="hidden md:hidden border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-[#1e293b] px-4 py-4 space-y-2">
            <a href="<?php echo $baseUrl; ?>views/dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg <?php echo isActive('dashboard.php', $currentScript); ?>">
                <i class="fa-solid fa-chart-line w-5 mr-3"></i> Dashboard
            </a>
            <a href="<?php echo $baseUrl; ?>views/reports.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg <?php echo isActive('reports.php', $currentScript); ?>">
                <i class="fa-solid fa-file-invoice-with-usdollar w-5 mr-3"></i> Laporan Analitik
            </a>
            <?php if ($isAdmin): ?>
            <a href="<?php echo $baseUrl; ?>views/groups.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg <?php echo isActive('groups.php', $currentScript); ?>">
                <i class="fa-solid fa-folder-tree w-5 mr-3"></i> Kelola Grup
            </a>
            <a href="<?php echo $baseUrl; ?>views/devices.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg <?php echo isActive('devices.php', $currentScript); ?>">
                <i class="fa-solid fa-server w-5 mr-3"></i> Kelola Perangkat
            </a>
            <a href="<?php echo $baseUrl; ?>views/settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg <?php echo isActive('settings.php', $currentScript); ?>">
                <i class="fa-solid fa-sliders w-5 mr-3"></i> Pengaturan Sistem
            </a>
            <?php endif; ?>
            <div class="border-t border-gray-200 dark:border-gray-800 pt-2 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400 pl-4">Masuk sebagai: <b><?php echo htmlspecialchars($username); ?></b></span>
                <a href="<?php echo $baseUrl; ?>api/auth.php?action=logout" class="flex items-center px-4 py-2 text-sm font-semibold text-red-600 rounded-lg">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i> Keluar
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 relative overflow-y-auto focus:outline-none p-6 md:p-8 bg-gray-50 dark:bg-[#0f172a]">
