<?php
include_once __DIR__ . '/includes/config/db.php';

$error = "";
if (function_exists('isLoggedIn') && isLoggedIn($pdo)) {
    header('Location: index.php');
    exit;
}

?>


<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="bg-white p-8 md:p-12 rounded-3xl shadow-2xl w-full max-w-md border border-slate-100 transform transition-all hover:scale-[1.01]">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold text-slate-800 mb-2">Welcome Back</h1>
            <p class="text-slate-500">Enter your credentials to access your account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-xl" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>



<form id="login-form" class="space-y-6">

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fas fa-envelope text-sm"></i>
                    </span>
                    <input type="email" name="email" required
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                        placeholder="john@example.com">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fas fa-lock text-sm"></i>
                    </span>
                    <input type="password" name="password" required
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition"
                        placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center text-slate-600">
                    <input type="checkbox" class="mr-2 rounded border-gray-300 text-primary focus:ring-primary">
                    Remember me
                </label>
                <a href="#" class="text-primary font-semibold hover:underline">Forgot Password?</a>
            </div>

<button type="submit" id="login-btn"
                class="w-full bg-gradient-to-r from-primary to-secondary text-white py-4 rounded-xl font-bold text-lg hover:shadow-xl hover:shadow-indigo-200 transform transition-all active:scale-95">
                Sign In
            </button>
        </form>

        <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const email = formData.get('email');
            const password = formData.get('password');
            const btn = document.getElementById('login-btn');
            btn.textContent = 'Signing in...';
            btn.disabled = true;
            try {
                const data = await apiCall('POST', 'login.php', {email, password});
                window.location.href = 'index.php';
            } catch (err) {
                // Error toast already shown
            } finally {
                btn.textContent = 'Sign In';
                btn.disabled = false;
            }
        });
        </script>


        <p class="text-center mt-8 text-slate-500">
            Don't have an account? 
            <a href="register.php" class="text-primary font-bold hover:underline">Register Now</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
