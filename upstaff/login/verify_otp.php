<?php
session_start();
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Manila');
$message = "";
$messageType = "";

if (!isset($_SESSION['reset_user'])) {
    header("Location: forgot_password.php");
    exit();
}

$username = $_SESSION['reset_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND otp_code=?");
        $stmt->bind_param("ss", $username, $otp);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Compare in PHP time using UTC
            $expiry_time = strtotime($user['otp_expire'] . ' UTC');
            if ($expiry_time < time()) {
                $message = "OTP has expired! Please request a new one.";
                $messageType = "error";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET password=?, otp_code=NULL, otp_expire=NULL WHERE id=?");
                $stmt2->bind_param("si", $hashed, $user['id']);
                $stmt2->execute();

                unset($_SESSION['reset_user']);
                header("Location: login.php?success=" . urlencode("Password reset successful!"));
                exit();
            }
        } else {
            $message = "Invalid OTP!";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP - Upstaff</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * {
        font-family: 'Inter', sans-serif;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }
    @keyframes progressPulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }
    .progress-pulse {
        animation: progressPulse 1.5s infinite;
    }
    .input-focus-effect {
        transition: all 0.3s ease;
    }
    .input-focus-effect:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.1);
    }
    #progressBar {
        transition: width 0.4s ease-in-out;
    }
    /* OTP input styling */
    .otp-input {
        letter-spacing: 0.5em;
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
    }
    /* Password strength indicator */
    .strength-bar {
        height: 4px;
        transition: all 0.3s ease;
    }
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">

<!-- Main Container -->
<div class="w-full max-w-md fade-in">

    <!-- Main Card -->
    <div class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-100">
        
        <!-- Header with decorative element -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white/20 backdrop-blur-lg rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-white text-xl font-bold">Reset Password</h2>
                    <p class="text-blue-100 text-sm mt-0.5">Enter OTP and set new password</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 md:p-8">

            <!-- OTP Info Banner -->
            <div class="bg-purple-50 rounded-xl p-4 mb-6 border border-purple-100">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-sm text-purple-800">
                        <p class="font-medium mb-1">OTP sent to your email</p>
                        <p class="text-purple-700">Check your inbox for the 6-digit code. It expires in 10 minutes.</p>
                    </div>
                </div>
            </div>

            <!-- Timer Display -->
            <div class="bg-gray-50 rounded-lg p-3 mb-6 text-center border border-gray-200">
                <div class="text-xs text-gray-500 mb-1">OTP expires in</div>
                <div id="timer" class="text-2xl font-bold text-blue-600">10:00</div>
            </div>

            <!-- Error/Success Message -->
            <?php if ($message): ?>
            <div class="mb-6 <?php echo $messageType === 'error' ? 'bg-red-50 border-l-4 border-red-500' : 'bg-green-50 border-l-4 border-green-500'; ?> rounded-lg p-4 fade-in">
                <div class="flex items-center gap-3">
                    <?php if ($messageType === 'error'): ?>
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php endif; ?>
                    <p class="<?php echo $messageType === 'error' ? 'text-red-700' : 'text-green-700'; ?> text-sm font-medium">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form id="otpForm" method="POST" class="space-y-6">

                <!-- OTP Field -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        6-Digit OTP Code
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                            </svg>
                        </div>
                        <input type="text" name="otp" id="otp" required maxlength="6"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50 hover:bg-white input-focus-effect otp-input"
                            placeholder="••••••"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            value="<?php echo isset($_POST['otp']) ? htmlspecialchars($_POST['otp']) : ''; ?>">
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Enter the 6-digit code sent to your email</p>
                </div>

                <!-- New Password Field -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        New Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input type="password" name="new_password" id="new_password" required
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50 hover:bg-white input-focus-effect"
                            placeholder="Enter new password">
                        <button type="button" onclick="togglePassword('new_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="mt-3 space-y-2">
                        <div class="flex gap-1">
                            <div class="strength-bar flex-1 bg-gray-200 rounded-full" id="strength-1"></div>
                            <div class="strength-bar flex-1 bg-gray-200 rounded-full" id="strength-2"></div>
                            <div class="strength-bar flex-1 bg-gray-200 rounded-full" id="strength-3"></div>
                        </div>
                        <p class="text-xs text-gray-500" id="passwordStrengthText">Minimum 8 characters with letters & numbers</p>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <input type="password" name="confirm_password" id="confirm_password" required
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50 hover:bg-white input-focus-effect"
                            placeholder="Confirm new password">
                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-red-500 hidden mt-2" id="passwordMismatch">
                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Passwords do not match
                    </p>
                </div>

                <!-- Enhanced Progress Bar with Status -->
                <div class="space-y-3 mt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-600" id="progressStatus">Ready to reset</span>
                        <span class="text-xs text-gray-400" id="progressPercentage">0%</span>
                    </div>
                    
                    <!-- Main Progress Bar -->
                    <div class="relative">
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div id="progressBar" class="h-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600 rounded-full transition-all duration-500 ease-out" style="width: 0%;"></div>
                        </div>
                        
                        <!-- Progress Steps Indicators -->
                        <div class="flex justify-between mt-2">
                            <div class="flex flex-col items-center">
                                <div id="step1" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium transition-all duration-300 bg-gray-200 text-gray-600">1</div>
                                <span class="text-[10px] text-gray-500 mt-1">Verify</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div id="step2" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium transition-all duration-300 bg-gray-200 text-gray-600">2</div>
                                <span class="text-[10px] text-gray-500 mt-1">Validate</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div id="step3" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium transition-all duration-300 bg-gray-200 text-gray-600">3</div>
                                <span class="text-[10px] text-gray-500 mt-1">Update</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div id="step4" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium transition-all duration-300 bg-gray-200 text-gray-600">4</div>
                                <span class="text-[10px] text-gray-500 mt-1">Complete</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition duration-200 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl focus:ring-4 focus:ring-blue-300 flex items-center justify-center gap-2 text-base group disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>Reset Password</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>

                <!-- Resend OTP Link -->
                <div class="text-center">
                    <a href="forgot_password.php" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 transition-colors group">
                        <svg class="w-4 h-4 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Didn't receive OTP? Request again
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-6 text-xs text-gray-500">
        &copy; 2024 Upstaff. All rights reserved.
        <a href="#" class="hover:text-blue-600 transition-colors">Privacy</a> • 
        <a href="#" class="hover:text-blue-600 transition-colors">Terms</a>
    </div>
</div>

<script>
// Timer functionality
let timeLeft = 600; // 10 minutes in seconds
const timerElement = document.getElementById('timer');
const timerInterval = setInterval(updateTimer, 1000);

function updateTimer() {
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timerElement.innerHTML = "00:00";
        timerElement.classList.add('text-red-600');
        showNotification('OTP has expired. Please request a new one.', 'error');
    } else {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        timeLeft--;
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function(e) {
    const password = e.target.value;
    let score = 0;
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
    if (password.match(/\d/)) score++;
    
    const bars = ['strength-1', 'strength-2', 'strength-3'];
    const colors = ['bg-red-500', 'bg-yellow-500', 'bg-green-500'];
    
    bars.forEach((bar, index) => {
        const element = document.getElementById(bar);
        element.className = `strength-bar flex-1 rounded-full transition-all duration-300 ${index < score ? colors[Math.min(score-1, 2)] : 'bg-gray-200'}`;
    });
    
    const strengthText = document.getElementById('passwordStrengthText');
    strengthText.className = `text-xs mt-2 ${score === 1 ? 'text-red-500' : score === 2 ? 'text-yellow-600' : score === 3 ? 'text-green-600' : 'text-gray-500'}`;
    strengthText.textContent = 
        score === 0 ? 'Enter a password' : 
        score === 1 ? 'Weak password' :
        score === 2 ? 'Medium password' : 'Strong password';
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    const mismatch = document.getElementById('passwordMismatch');
    
    if (confirm.length > 0) {
        if (password === confirm) {
            mismatch.classList.add('hidden');
            this.classList.remove('border-red-300', 'focus:ring-red-500');
            this.classList.add('border-green-300', 'focus:ring-green-500');
        } else {
            mismatch.classList.remove('hidden');
            this.classList.remove('border-green-300', 'focus:ring-green-500');
            this.classList.add('border-red-300', 'focus:ring-red-500');
        }
    } else {
        mismatch.classList.add('hidden');
        this.classList.remove('border-red-300', 'border-green-300', 'focus:ring-red-500', 'focus:ring-green-500');
        this.classList.add('border-gray-300');
    }
});

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

// Form submission with progress animation
const form = document.getElementById('otpForm');
const progressBar = document.getElementById('progressBar');
const progressStatus = document.getElementById('progressStatus');
const progressPercentage = document.getElementById('progressPercentage');
const submitBtn = document.getElementById('submitBtn');

// Step indicators
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');
const step4 = document.getElementById('step4');

// Function to update progress
function updateProgress(percent, status, step) {
    progressBar.style.width = percent + '%';
    progressPercentage.textContent = percent + '%';
    progressStatus.textContent = status;
    
    // Update step indicators
    [step1, step2, step3, step4].forEach(s => {
        s.classList.remove('bg-blue-500', 'text-white', 'bg-green-500');
        s.classList.add('bg-gray-200', 'text-gray-600');
    });
    
    if (step >= 1) {
        step1.classList.remove('bg-gray-200', 'text-gray-600');
        step1.classList.add('bg-blue-500', 'text-white');
    }
    if (step >= 2) {
        step2.classList.remove('bg-gray-200', 'text-gray-600');
        step2.classList.add('bg-blue-500', 'text-white');
    }
    if (step >= 3) {
        step3.classList.remove('bg-gray-200', 'text-gray-600');
        step3.classList.add('bg-blue-500', 'text-white');
    }
    if (step >= 4) {
        step4.classList.remove('bg-gray-200', 'text-gray-600');
        step4.classList.add('bg-green-500', 'text-white');
        progressBar.classList.add('progress-pulse');
    }
}

form.addEventListener('submit', function(e) {
    const otp = document.getElementById('otp').value;
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    // Validate OTP
    if (!otp || otp.length !== 6) {
        e.preventDefault();
        showNotification('Please enter a valid 6-digit OTP', 'error');
        return false;
    }
    
    // Validate password
    if (password !== confirm) {
        e.preventDefault();
        showNotification('Passwords do not match!', 'error');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        showNotification('Password must be at least 8 characters', 'error');
        return false;
    }
    
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
    `;
    
    // Animate progress bar
    updateProgress(25, 'Verifying OTP...', 1);
    
    setTimeout(() => { 
        updateProgress(50, 'OTP verified', 2);
    }, 800);
    
    setTimeout(() => { 
        updateProgress(75, 'Updating password...', 3);
    }, 1600);
    
    setTimeout(() => { 
        updateProgress(100, 'Password updated!', 4);
    }, 2400);
    
    // Let the form submit naturally
    return true;
});

// Show notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-0 flex items-center gap-2 ${
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.innerHTML = `
        <span class="font-bold text-lg">${type === 'error' ? '✗' : 'ℹ'}</span>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-format OTP input (numbers only)
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
});

// Reset form state if there's an error (PHP will reload the page)
<?php if ($messageType === 'error'): ?>
updateProgress(0, 'Ready to reset', 0);
<?php endif; ?>
</script>

</body>
</html>