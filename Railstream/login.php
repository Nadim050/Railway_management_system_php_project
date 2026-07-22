<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - RailStream</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen font-sans">

    <!-- Back to Home -->
    <a href="index.php" class="fixed top-6 left-6 flex items-center gap-2 text-white/80 hover:text-white transition z-10 group">
        <div class="bg-blue-600/20 border border-blue-500/30 w-9 h-9 rounded-xl flex items-center justify-center group-hover:bg-blue-600/40 transition">
            <i class="fa-solid fa-train-subway text-blue-400 text-sm"></i>
        </div>
        <span class="text-sm font-semibold hidden sm:inline">RailStream</span>
        <i class="fa-solid fa-arrow-left text-xs text-gray-400 group-hover:text-white transition ml-1"></i>
    </a>

    <div class="w-full max-w-md bg-white/5 border border-white/10 p-10 rounded-3xl shadow-2xl">
        <div class="text-center mb-10">
            <div class="bg-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white text-2xl shadow-lg shadow-blue-600/30">
                <i class="fa-solid fa-train"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">Welcome Back</h1>
            <p class="text-gray-400">Login to manage your bookings</p>
        </div>

        <form action="login_logic.php" method="POST" class="space-y-6">
            <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-300 ml-1">Username</label>
                <input type="text" name="username" class="w-full bg-white/5 border border-white/10 p-4 rounded-xl text-white outline-none focus:border-blue-500 transition" placeholder="Enter username" required>
            </div>
            
            <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-300 ml-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="login_password" class="w-full bg-white/5 border border-white/10 p-4 rounded-xl text-white outline-none focus:border-blue-500 transition pr-12" placeholder="••••••••" required>
                    <button type="button" onclick="togglePassword('login_password', 'eye_login')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition">
                        <i id="eye_login" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login_btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition shadow-lg shadow-blue-600/20">
                Login to Account
            </button>
        </form>

        <div class="mt-8 text-center space-y-2">
            <p class="text-gray-400 text-sm">Don't have an account? <a href="register.php" class="text-blue-500 hover:underline">Register</a></p>
            <p class="text-gray-500 text-xs italic">Facing issues? <a href="index.php#contact" class="text-blue-400 underline">Contact Admin</a></p>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>

</body>
</html>
