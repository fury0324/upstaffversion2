<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = "";
$messageType = "";

if (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = "error";
}

if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=" . urlencode("Username and password are required"));
        exit();
    }

    // Prepare statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    // Get result
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {

        if (password_verify($password, $user['password'])) {

            if ($user['status'] === "approved") {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                // --- LOG LOGIN EVENT ---
                $ip = $_SERVER['REMOTE_ADDR'];
                $logStmt = $conn->prepare("INSERT INTO employee_logs (user_id, action, ip_address) VALUES (?, 'Login', ?)");
                $logStmt->bind_param("is", $user['id'], $ip);
                $logStmt->execute();
                $logStmt->close();

                // Set remember me cookie (30 days)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    // Store token in database (you'll need to add a remember_token column)
                    setcookie('remember_token', $token, time() + (86400 * 30), "/");
                }
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/dashboard.php"); // admin dashboard
                } else {
                    header("Location: ../employee/dashboard.php");
                }
                exit();

            } elseif ($user['status'] === "pending") {
                header("Location: login.php?error=" . urlencode("Your account is pending approval. Please check back later."));
                exit();
            } else {
                header("Location: login.php?error=" . urlencode("Your account has been rejected. Please contact support."));
                exit();
            }

        } else {
            header("Location: login.php?error=" . urlencode("Invalid username or password"));
            exit();
        }

    } else {
        header("Location: login.php?error=" . urlencode("Invalid username or password"));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upstaff - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">

<!-- Main Container -->
<div class="w-full max-w-md fade-in">

    <!-- Login Card -->
    <div class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-100">
        
        <!-- Header with decorative element -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
            <h2 class="text-white text-xl font-bold flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Welcome Back
            </h2>
            <p class="text-blue-100 text-sm mt-1">Sign in to access your Upstaff account</p>
        </div>

        <!-- Form Container -->
        <div class="p-6 md:p-8">

            <!-- Error/Success Message -->
            <?php if ($message): ?>
            <div class="<?php echo $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?> rounded-lg p-4 mb-6 fade-in">
                <div class="flex items-center gap-3">
                    <?php if ($messageType === 'success'): ?>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php endif; ?>
                    <p class="<?php echo $messageType === 'success' ? 'text-green-700' : 'text-red-700'; ?> text-sm font-medium">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST" id="loginForm" class="space-y-5" novalidate>

                <!-- Username Field -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Username
                        </span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">@</span>
                        <input type="text" name="username" id="username" required
                            class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                            placeholder="Enter your username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            autocomplete="username">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Password
                        </span>
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                            placeholder="Enter your password"
                            autocomplete="current-password">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eyeIcon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition">
                        <span class="text-sm text-gray-600 group-hover:text-gray-800 transition">Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                        Forgot password?
                    </a>
                </div>

                <!-- Sign In Button -->
                <button type="submit" id="loginBtn"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg transition duration-200 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] shadow-lg focus:ring-4 focus:ring-blue-300 flex items-center justify-center gap-2 text-base mt-6">
                    <span>Sign In</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>

                <!-- Register Link -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">New to Upstaff?</span>
                    </div>
                </div>

                <p class="text-center">
                    <a href="register.php" class="inline-flex items-center gap-2 text-blue-600 font-medium hover:text-blue-700 hover:underline group">
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Create an account
                    </a>
                </p>
            </form>
        </div>
    </div>

<script>
// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
        `;
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

// Form validation and loading state
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        e.preventDefault();
        alert('Please enter both username and password');
        return false;
    }
    
    // Show loading state
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Signing In...
    `;
    btn.disabled = true;
});

// Input validation feedback
document.getElementById('username').addEventListener('input', function() {
    if (this.value.length > 0 && this.value.length < 3) {
        this.classList.add('border-yellow-500');
        this.classList.remove('border-green-500');
    } else if (this.value.length >= 3) {
        this.classList.remove('border-yellow-500');
        this.classList.add('border-green-500');
    } else {
        this.classList.remove('border-yellow-500', 'border-green-500');
    }
});

// Auto-hide error message after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const errorDiv = document.querySelector('.bg-red-50');
    if (errorDiv) {
        setTimeout(() => {
            errorDiv.style.transition = 'opacity 0.5s';
            errorDiv.style.opacity = '0';
            setTimeout(() => {
                errorDiv.remove();
            }, 500);
        }, 5000);
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

</body>
</html>