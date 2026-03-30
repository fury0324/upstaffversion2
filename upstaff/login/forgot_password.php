<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Show all PHP errors (for development only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// PHPMailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../PHP-Mailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHP-Mailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHP-Mailer-master/src/Exception.php';

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    if (!$stmt) die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate OTP and expiry
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Update user with OTP and expiry
        $stmt2 = $conn->prepare("UPDATE users SET otp_code=?, otp_expire=? WHERE id=?");
        $stmt2->bind_param("ssi", $otp, $expiry, $user['id']);
        
        if ($stmt2->execute()) {
            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'upstaff7@gmail.com';
                $mail->Password = 'adthzbjsnqjfhjky';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Bypass SSL verification for localhost
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom('upstaff7@gmail.com', 'Upstaff');
                $mail->addAddress($user['email'], $user['firstname']);
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP - Upstaff';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;'>
                            <h2 style='color: white; margin: 0;'>Upstaff</h2>
                        </div>
                        <div style='padding: 30px; background: #f9f9f9;'>
                            <h3>Hello {$user['firstname']},</h3>
                            <p>We received a request to reset your password. Use the OTP below to proceed:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <div style='background: #fff; border: 2px solid #667eea; border-radius: 10px; padding: 15px; display: inline-block;'>
                                    <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #667eea;'>{$otp}</span>
                                </div>
                            </div>
                            <p><strong>Note:</strong> This OTP will expire in 10 minutes.</p>
                            <p>If you didn't request this, please ignore this email.</p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='color: #666; font-size: 12px;'>For security reasons, never share your OTP with anyone.</p>
                        </div>
                    </div>
                ";
                $mail->send();

                // Store username in session for OTP verification
                $_SESSION['reset_user'] = $user['username'];

                // Success message
                $_SESSION['success_message'] = "OTP sent successfully!";
                header("Location: verify_otp.php");
                exit();

            } catch (Exception $e) {
                $message = "Failed to send email. Please try again.";
                $messageType = "error";
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        } else {
            $message = "System error. Please try again.";
            $messageType = "error";
        }

    } else {
        $message = "Username not found!";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Upstaff</title>
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
                    <h2 class="text-white text-xl font-bold">Forgot Password?</h2>
                    <p class="text-blue-100 text-sm mt-0.5">No worries, we'll help you reset it</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 md:p-8">

            <!-- Instructions -->
            <div class="bg-blue-50 rounded-xl p-4 mb-6 border border-blue-100">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">How it works:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-700">
                            <li>Enter your username below</li>
                            <li>We'll send a 6-digit OTP to your email</li>
                            <li>Enter the OTP to reset your password</li>
                            <li>OTP expires in 10 minutes</li>
                        </ul>
                    </div>
                </div>
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
            <form id="forgotForm" method="POST" class="space-y-6">

                <!-- Username Field -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Username
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input type="text" name="username" id="username" required
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50 hover:bg-white input-focus-effect"
                            placeholder="Enter your username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            autocomplete="off">
                    </div>
                </div>

                <!-- Enhanced Progress Bar with Status -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-600" id="progressStatus">Ready to send OTP</span>
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
                                <span class="text-[10px] text-gray-500 mt-1">Generate</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div id="step3" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium transition-all duration-300 bg-gray-200 text-gray-600">3</div>
                                <span class="text-[10px] text-gray-500 mt-1">Send</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div id="step4" class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium transition-all duration-300 bg-gray-200 text-gray-600">4</div>
                                <span class="text-[10px] text-gray-500 mt-1">Complete</span>
                            </div>
                        </div>
                    </div>

                    <!-- Timer/Status Message -->
                    <div id="timerMessage" class="text-xs text-center text-gray-500 mt-2 hidden">
                        <svg class="w-3 h-3 inline mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="timerText"></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition duration-200 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl focus:ring-4 focus:ring-blue-300 flex items-center justify-center gap-2 text-base group disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>Send OTP</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <!-- Back to Login Link -->
                <div class="text-center">
                    <a href="../login/login.php" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors group">
                        <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Login
                    </a>
                </div>
            </form>

            <!-- Security Note -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center flex items-center justify-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Your information is secure and encrypted
                </p>
            </div>
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
const form = document.getElementById('forgotForm');
const progressBar = document.getElementById('progressBar');
const progressStatus = document.getElementById('progressStatus');
const progressPercentage = document.getElementById('progressPercentage');
const submitBtn = document.getElementById('submitBtn');
const usernameInput = document.getElementById('username');
const timerMessage = document.getElementById('timerMessage');
const timerText = document.getElementById('timerText');

// Step indicators
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');
const step4 = document.getElementById('step4');

// Input validation and visual feedback
usernameInput.addEventListener('input', function() {
    if (this.value.length > 0) {
        this.classList.add('border-green-500', 'bg-green-50');
        this.classList.remove('border-gray-300', 'bg-gray-50');
    } else {
        this.classList.remove('border-green-500', 'bg-green-50');
        this.classList.add('border-gray-300', 'bg-gray-50');
    }
});

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

// Form submission with enhanced animation
form.addEventListener('submit', function(e) {
    const username = usernameInput.value.trim();
    
    if (!username) {
        e.preventDefault();
        showNotification('Please enter your username', 'error');
        return false;
    }
    
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Sending OTP...
    `;
    
    // Show timer message
    timerMessage.classList.remove('hidden');
    
    // Animate progress bar with step indicators
    updateProgress(10, 'Checking username...', 1);
    timerText.textContent = 'Verifying your account...';
    
    setTimeout(() => { 
        updateProgress(35, 'Username verified', 1);
        step1.classList.add('bg-green-500');
    }, 800);
    
    setTimeout(() => { 
        updateProgress(60, 'Generating secure OTP...', 2);
        timerText.textContent = 'Creating unique code...';
    }, 1600);
    
    setTimeout(() => { 
        updateProgress(75, 'OTP generated', 2);
        step2.classList.add('bg-green-500');
    }, 2000);
    
    setTimeout(() => { 
        updateProgress(85, 'Connecting to mail server...', 3);
        timerText.textContent = 'Establishing secure connection...';
    }, 2400);
    
    setTimeout(() => { 
        updateProgress(95, 'Sending email...', 3);
        timerText.textContent = 'Almost there...';
    }, 3000);
    
    setTimeout(() => { 
        updateProgress(100, 'OTP sent successfully!', 4);
        timerText.textContent = 'Redirecting to verification page...';
        step3.classList.add('bg-green-500');
    }, 3500);
    
    // Form will submit naturally after animations
    setTimeout(() => {
        // Let the form submit naturally
        return true;
    }, 4000);
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

// Prevent multiple submissions
let submitted = false;
form.addEventListener('submit', function() {
    if (submitted) {
        return false;
    }
    submitted = true;
});

// Reset form state if there's an error (PHP will reload the page)
<?php if ($messageType === 'error'): ?>
submitted = false;
submitBtn.disabled = false;
submitBtn.innerHTML = `Send OTP`;
timerMessage.classList.add('hidden');
updateProgress(0, 'Ready to send OTP', 0);
<?php endif; ?>

// Smooth focus effect
usernameInput.addEventListener('focus', function() {
    this.parentElement.classList.add('scale-[1.02]');
    this.parentElement.style.transition = 'transform 0.2s ease';
});

usernameInput.addEventListener('blur', function() {
    this.parentElement.classList.remove('scale-[1.02]');
});
</script>

</body>
</html>