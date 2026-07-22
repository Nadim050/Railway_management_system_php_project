<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RailStream - Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="hero-bg flex items-center justify-center min-h-screen">

    <!-- Back to Home -->
    <a href="index.php" class="fixed top-6 left-6 flex items-center gap-2 text-white/80 hover:text-white transition z-10 group">
        <div class="bg-blue-600/20 border border-blue-500/30 w-9 h-9 rounded-xl flex items-center justify-center group-hover:bg-blue-600/40 transition">
            <i class="fa-solid fa-train-subway text-blue-400 text-sm"></i>
        </div>
        <span class="text-sm font-semibold hidden sm:inline">RailStream</span>
        <i class="fa-solid fa-arrow-left text-xs text-gray-400 group-hover:text-white transition ml-1"></i>
    </a>

    <div class="bg-white/10 backdrop-blur-xl p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/20">
        <div class="text-center mb-8">
            <h1 class="text-white text-3xl font-bold">Create Account</h1>
            <p class="text-blue-400">Join RailStream Today</p>
        </div>

        <form action="register_logic.php" method="POST" class="space-y-4">
            <input type="text" name="username" placeholder="Full Name" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-blue-500" required>
            
            <input type="email" name="email" placeholder="Email Address" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white outline-none focus:border-blue-500" required>
            
            <div class="relative">
                <input type="password" name="password" id="reg_password" placeholder="Create Password" class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 pr-12 text-white outline-none focus:border-blue-500" required>
                <button type="button" onclick="togglePassword('reg_password', 'eye_reg')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition">
                    <i id="eye_reg" class="fa-solid fa-eye"></i>
                </button>
            </div>
            
            <button type="submit" name="register_btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all">
                Register Now
            </button>
        </form>

        <p class="text-gray-400 text-center mt-6 text-sm">
            Already have an account? <a href="login.php" class="text-blue-400 font-bold">Login</a>
        </p>
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
