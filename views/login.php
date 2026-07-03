<?php
/**
 * Login View Page
 */
require_once __DIR__ . '/../includes/auth_guard.php';
startSessionIfNeeded();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: " . getBaseUrl() . "views/dashboard.php");
    exit();
}

$errorMsg = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $errorMsg = 'Username atau password salah.';
    } elseif ($_GET['error'] === 'required') {
        $errorMsg = 'Harap isi semua kolom.';
    } elseif ($_GET['error'] === 'csrf') {
        $errorMsg = 'Validasi keamanan CSRF gagal. Silakan coba lagi.';
    } elseif ($_GET['error'] === 'locked') {
        $errorMsg = 'Akun diblokir sementara karena terlalu banyak kegagalan login. Coba lagi dalam 15 menit.';
    }
}

$successMsg = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $successMsg = 'Anda berhasil keluar dari sistem.';
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Uptime Monitoring System</title>
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        // Apply theme from localStorage (default to dark)
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-[#0f172a] text-gray-900 dark:text-gray-100 flex items-center justify-center p-4 transition-colors duration-200">

<div class="w-full max-w-md">
    <!-- Brand Title -->
    <div class="text-center mb-8">
        <div class="inline-flex p-3.5 bg-blue-600 rounded-2xl text-white shadow-xl shadow-blue-500/30 mb-4 animate-bounce">
            <i class="fa-solid fa-square-poll-vertical text-3xl"></i>
        </div>
        <h2 class="text-3xl font-bold tracking-tight bg-gradient-to-r from-blue-600 to-indigo-500 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent">Uptime Monitoring</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Sistem Pemantauan Infrastruktur IT Terpadu</p>
    </div>

    <!-- Card -->
    <div class="bg-white dark:bg-[#1e293b] p-8 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800">
        <h3 class="text-xl font-bold mb-6 text-gray-800 dark:text-white">Masuk ke Dashboard</h3>

        <!-- Error & Success Alert -->
        <?php if ($errorMsg): ?>
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-xl text-sm flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-base"></i>
                <span><?php echo htmlspecialchars($errorMsg); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="mb-4 p-4 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900/50 text-green-600 dark:text-green-400 rounded-xl text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span><?php echo htmlspecialchars($successMsg); ?></span>
            </div>
        <?php endif; ?>

        <form action="<?php echo getBaseUrl(); ?>api/auth.php?action=login" method="POST" class="space-y-5">
            <!-- CSRF Token Input -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <!-- Username Input -->
            <div>
                <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" id="username" name="username" required 
                           class="block w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="Masukkan username Anda">
                </div>
            </div>

            <!-- Password Input -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" required 
                           class="block w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           placeholder="••••••••">
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl text-sm shadow-lg shadow-blue-500/20 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all flex items-center justify-center gap-2">
                Masuk <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
    </div>

    <!-- Theme Selection at bottom -->
    <div class="flex justify-between items-center mt-6 px-2 text-xs text-gray-400">
        <span>&copy; 2026 UMS Pro.</span>
        <button id="themeToggleBtn" class="flex items-center gap-1.5 hover:text-gray-200 transition-colors">
            <i id="themeToggleIcon" class="fa-solid fa-moon"></i> Theme Mode
        </button>
    </div>
</div>

<script>
    // Theme Toggle Handler on Login Page
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeToggleIcon = document.getElementById('themeToggleIcon');
    
    function updateThemeIcon() {
        if (document.documentElement.classList.contains('dark')) {
            themeToggleIcon.className = 'fa-solid fa-sun';
        } else {
            themeToggleIcon.className = 'fa-solid fa-moon';
        }
    }
    
    updateThemeIcon();
    
    themeToggleBtn.addEventListener('click', () => {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        updateThemeIcon();
    });
</script>
</body>
</html>
