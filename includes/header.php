<?php
require_once __DIR__ . '/config/db.php';
ensureSessionStarted();

if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn(): bool {
        ensureSessionStarted();
        return isset($_SESSION['admin_logged_in']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo htmlspecialchars(rtrim(appUrl(), '/') . '/', ENT_QUOTES, 'UTF-8'); ?>">
    <title>MobiWorld | Premium Smartphone E-store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        window.APP_BASE_URL = <?php echo json_encode(rtrim(appUrl(), '/')); ?>;
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#6366f1',
                        secondary: '#4f46e5',
                        dark: '#0f172a',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #6366f1;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">

<nav class="glass sticky top-0 z-50 py-4 px-6 mb-8 shadow-sm">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary flex items-center">
            <i class="fas fa-mobile-alt mr-2"></i>MobiWorld
        </a>

        <div class="hidden md:flex space-x-8 items-center">
            <a href="index.php" class="nav-link font-medium text-slate-700 hover:text-primary">Home</a>
            <a href="cart.php" class="nav-link font-medium text-slate-700 hover:text-primary relative">
                Cart
                <span class="cart-count absolute -top-2 -right-4 bg-red-500 text-white text-xs rounded-full w-5 h-5 hidden items-center justify-center animate-bounce">
                    0
                </span>
            </a>
            <a href="wishlist.php" class="nav-link font-medium text-slate-700 hover:text-primary relative">
                Wishlist
                <span class="wishlist-count absolute -top-2 -right-4 bg-pink-500 text-white text-xs rounded-full w-5 h-5 hidden items-center justify-center animate-bounce">
                    0
                </span>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="profile.php" class="nav-link font-medium text-slate-700 hover:text-primary">Profile</a>
                <a href="logout.php" class="px-5 py-2 bg-red-500 text-white rounded-full font-semibold hover:bg-red-600 transition shadow-lg shadow-red-200">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link font-medium text-slate-700 hover:text-primary">Login</a>
                <a href="register.php" class="px-5 py-2 bg-primary text-white rounded-full font-semibold hover:bg-secondary transition shadow-lg shadow-indigo-200">Register</a>
            <?php endif; ?>
        </div>

        <div class="md:hidden">
            <button id="menu-btn" class="text-slate-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
    </div>

    <div id="mobile-menu" class="hidden md:hidden mt-4 bg-white rounded-xl p-4 shadow-xl border border-slate-100 flex-col space-y-4">
        <a href="index.php" class="block font-medium text-slate-700">Home</a>
        <a href="cart.php" class="block font-medium text-slate-700 relative">
            Cart
            <span class="cart-count absolute -top-1 -right-6 hidden h-4 w-4 items-center justify-center rounded-full bg-red-500 text-xs text-white">
                0
            </span>
        </a>
        <a href="wishlist.php" class="block font-medium text-slate-700 relative">
            Wishlist
            <span class="wishlist-count absolute -top-1 -right-6 hidden h-4 w-4 items-center justify-center rounded-full bg-pink-500 text-xs text-white">
                0
            </span>
        </a>
        <?php if (isLoggedIn()): ?>
            <a href="profile.php" class="block font-medium text-slate-700">Profile</a>
            <a href="logout.php" class="block font-medium text-red-500">Logout</a>
        <?php else: ?>
            <a href="login.php" class="block font-medium text-slate-700">Login</a>
            <a href="register.php" class="block font-medium text-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>

<script>
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('flex');
        });
    }
</script>

<script src="assets/js/app.js"></script>

<main class="max-w-7xl mx-auto px-4 md:px-6 pb-20">
