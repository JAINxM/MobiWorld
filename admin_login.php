<?php
require_once __DIR__ . '/includes/config/db.php';
ensureSessionStarted();

if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn(): bool {
        ensureSessionStarted();
        return isset($_SESSION['admin_logged_in']);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

$error = "";

if (isAdminLoggedIn()) {
    redirect('admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

// Hardcoded Admin (table optional)
    if ($email === 'admin@mobiworld.com' && $password === 'admin123') {


        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'Super Admin';
        redirect('admin_dashboard.php');
    } else {
        $error = "Incorrect admin credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | MobiWorld</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-900 font-['Outfit'] min-h-screen flex items-center justify-center p-6">

<div class="max-w-md w-full">
    <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-600 rounded-3xl shadow-2xl shadow-indigo-500/50 mb-6 transform rotate-12">
            <i class="fas fa-shield-halved text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-extrabold text-white mb-2 tracking-tight">MobiWorld</h1>
        <p class="text-gray-400 font-medium">Administration Portal</p>
    </div>

    <div class="bg-gray-800 p-10 rounded-[2.5rem] shadow-2xl border border-gray-700">
        <?php if ($error): ?>
            <div class="bg-red-500/10 border-l-4 border-red-500 text-red-400 p-4 mb-8 rounded-r-xl text-sm" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 pl-1">Email ID</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500">
                        <i class="fas fa-envelope text-sm"></i>
                    </span>
                    <input type="email" name="email" required
                        class="w-full pl-12 pr-4 py-4 bg-gray-900 border border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-white transition"
                        placeholder="admin@mobiworld.com">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 pl-1">Secret Key</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-500">
                        <i class="fas fa-key text-sm"></i>
                    </span>
                    <input type="password" name="password" required
                        class="w-full pl-12 pr-4 py-4 bg-gray-900 border border-gray-700 rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-white transition"
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 py-5 rounded-2xl font-bold text-white hover:bg-indigo-500 transition-all transform active:scale-95 shadow-xl shadow-indigo-600/20 mt-4">
                Enter Dashboard
            </button>
        </form>

        <div class="mt-8 pt-8 border-t border-gray-700 text-center">
            <a href="index.php" class="text-gray-500 text-sm hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Customer Site
            </a>
        </div>
    </div>
    
    <p class="text-center text-gray-600 text-xs mt-10">
        &copy; <?php echo date('Y'); ?> MobiWorld Control Center. v1.0.0
    </p>
</div>

</body>
</html>
