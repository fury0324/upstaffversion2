<?php
require '../config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require_once __DIR__ . '/../PHP-Mailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHP-Mailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHP-Mailer-master/src/Exception.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $dob = $_POST['dob'];
    $position = trim($_POST['position']); // ✅ GET POSITION
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // Validate passwords
    if ($password !== $confirm) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } else {

        try {
            // Check if username or email exists
            $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $message = "Username or email already exists!";
                $messageType = "error";
            } else {

                // Hash password
                $hash = password_hash($password, PASSWORD_DEFAULT);

                      //insert
                              $stmt = $conn->prepare("
                                  INSERT INTO users 
                                  (firstname, lastname, username, email, password, phone, address, dob, position, status, role)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'employee')
                              ");

                              $stmt->bind_param(
                                  "sssssssss",
                                  $firstname,
                                  $lastname,
                                  $username,
                                  $email,
                                  $hash,
                                  $phone,
                                  $address,
                                  $dob,
                                  $position
                              );

                if ($stmt->execute()) {
                    $message = "Registration successful! Please wait for admin approval.";
                    $messageType = "success";

                    // Send Email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'upstaff7@gmail.com';
                        $mail->Password = 'adthzbjsnqjfhjky';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            ]
                        ];

                        $mail->setFrom('upstaff7@gmail.com', 'Upstaff Zamboanga');
                        $mail->addAddress('upstaff7@gmail.com');

                        $mail->isHTML(true);
                        $mail->Subject = 'New Employee Registration Pending Approval';
                        $mail->Body = "
                            <h2>New Employee Registration</h2>
                            <p><strong>Name:</strong> {$firstname} {$lastname}</p>
                            <p><strong>Position:</strong> {$position}</p>
                            <p><strong>Username:</strong> {$username}</p>
                            <p><strong>Email:</strong> {$email}</p>
                            <p><strong>Phone:</strong> {$phone}</p>
                        ";

                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Mailer Error: " . $mail->ErrorInfo);
                    }

                } else {
                    $message = "Registration failed.";
                    $messageType = "error";
                }
            }

        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
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
    <title>Upstaff - Create Account</title>
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
        .success-message {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .error-message {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">

<!-- Main Container - Fully Responsive -->
<div class="w-full max-w-2xl fade-in">

    <!-- Register Card -->
    <div class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-100">
        
        <!-- Header with decorative element -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
            <h2 class="text-white text-xl font-bold flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Create Your Account
            </h2>
            <p class="text-blue-100 text-sm mt-1">Join thousands of professionals on Upstaff</p>
        </div>

        <!-- PHP Message with improved styling -->
        <?php if ($message): ?>
        <div class="mx-6 mt-6 <?php echo $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?> rounded-lg p-4 fade-in">
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

        <!-- Form Container with padding -->
        <div class="p-6 md:p-8">
            
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-600">Account Details</span>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full">Step 1 of 1</span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full w-full"></div>
                </div>
            </div>

          <!-- Form -->
<form method="POST" id="registerForm" class="space-y-5" novalidate>
    
    <!-- 2 Column Grid for better responsiveness -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
        
        <!-- First Name -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                First Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="firstname" id="firstname" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                placeholder="John"
                value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
        </div>

        <!-- LAST NAME - NEW FIELD -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Last Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="lastname" id="lastname" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                placeholder="Doe"
                value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
        </div>

        <!-- POSITION - NEW FIELD (Full width) -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Position <span class="text-red-500">*</span>
            </label>
            <select name="position" id="position" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white">
                <option value="" disabled <?php echo !isset($_POST['position']) ? 'selected' : ''; ?>>Select your position</option>
                <option value="Software Developer" <?php echo (isset($_POST['position']) && $_POST['position'] == "Software Developer") ? 'selected' : ''; ?>>Software Developer</option>
                <option value="HR Manager" <?php echo (isset($_POST['position']) && $_POST['position'] == "HR Manager") ? 'selected' : ''; ?>>HR Manager</option>
                <option value="Project Manager" <?php echo (isset($_POST['position']) && $_POST['position'] == "Project Manager") ? 'selected' : ''; ?>>Project Manager</option>
                <option value="System Administrator" <?php echo (isset($_POST['position']) && $_POST['position'] == "System Administrator") ? 'selected' : ''; ?>>System Administrator</option>
                <option value="UI/UX Designer" <?php echo (isset($_POST['position']) && $_POST['position'] == "UI/UX Designer") ? 'selected' : ''; ?>>UI/UX Designer</option>
                <option value="Quality Assurance" <?php echo (isset($_POST['position']) && $_POST['position'] == "Quality Assurance") ? 'selected' : ''; ?>>Quality Assurance</option>
                <option value="Business Analyst" <?php echo (isset($_POST['position']) && $_POST['position'] == "Business Analyst") ? 'selected' : ''; ?>>Business Analyst</option>
                <option value="IT Support" <?php echo (isset($_POST['position']) && $_POST['position'] == "IT Support") ? 'selected' : ''; ?>>IT Support</option>
                <option value="Sales Executive" <?php echo (isset($_POST['position']) && $_POST['position'] == "Sales Executive") ? 'selected' : ''; ?>>Sales Executive</option>
                <option value="Marketing Specialist" <?php echo (isset($_POST['position']) && $_POST['position'] == "Marketing Specialist") ? 'selected' : ''; ?>>Marketing Specialist</option>
                <option value="Accountant" <?php echo (isset($_POST['position']) && $_POST['position'] == "Accountant") ? 'selected' : ''; ?>>Accountant</option>
                <option value="Administrative Staff" <?php echo (isset($_POST['position']) && $_POST['position'] == "Administrative Staff") ? 'selected' : ''; ?>>Administrative Staff</option>
            </select>
        </div>

        <!-- Username -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Username <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">@</span>
                <input type="text" name="username" id="username" required
                    class="w-full pl-8 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                    placeholder="username"
                    pattern="[a-zA-Z0-9_]{3,20}"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <span class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-green-500" id="usernameAvailable">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1.5">3-20 characters (letters, numbers, underscore)</p>
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Email Address <span class="text-red-500">*</span>
            </label>
            <input type="email" name="email" id="email" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                placeholder="you@example.com"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <!-- Phone -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Phone Number <span class="text-red-500">*</span>
            </label>
            <div class="flex">
                <select name="country_code" class="w-20 px-2 py-3 border border-r-0 border-gray-300 rounded-l-lg bg-gray-100 text-gray-700 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="+1">+1</option>
                    <option value="+44">+44</option>
                    <option value="+63" selected>+63</option>
                    <option value="+91">+91</option>
                </select>
                <input type="tel" name="phone" id="phone" required
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-r-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                    placeholder="912 345 6789"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Password <span class="text-red-500">*</span>
            </label>
            <input type="password" name="password" id="password" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                placeholder="Create password">
            <!-- Password strength indicator -->
            <div class="flex gap-1.5 mt-2.5">
                <div class="h-1.5 flex-1 bg-gray-200 rounded-full transition-all duration-300" id="strength-1"></div>
                <div class="h-1.5 flex-1 bg-gray-200 rounded-full transition-all duration-300" id="strength-2"></div>
                <div class="h-1.5 flex-1 bg-gray-200 rounded-full transition-all duration-300" id="strength-3"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1.5" id="passwordStrengthText">Min. 8 characters with letters & numbers</p>
        </div>

        <!-- Confirm Password -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Confirm Password <span class="text-red-500">*</span>
            </label>
            <input type="password" name="confirm_password" id="confirm_password" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                placeholder="Re-enter password">
            <p class="text-xs text-red-500 hidden mt-1.5" id="passwordMismatch">
                <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Passwords do not match
            </p>
        </div>

        <!-- Address (full width) -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Complete Address <span class="text-red-500">*</span>
            </label>
            <input type="text" name="address" id="address" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                placeholder="Street address, City, State, ZIP"
                value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
        </div>

        <!-- Date of Birth -->
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Date of Birth <span class="text-red-500">*</span>
            </label>
            <input type="date" name="dob" id="dob" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-gray-50 hover:bg-white"
                max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
            <p class="text-xs text-gray-500 mt-1.5">You must be at least 18 years old to register</p>
        </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="flex items-start gap-3 mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <input type="checkbox" id="terms" required class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
        <label for="terms" class="text-sm text-gray-600 leading-relaxed">
            I agree to the 
            <a href="#" class="text-blue-600 hover:underline font-medium">Terms of Service</a> and 
            <a href="#" class="text-blue-600 hover:underline font-medium">Privacy Policy</a>
        </label>
    </div>

    <!-- Submit Button with loading state -->
    <button type="submit" id="registerBtn"
        class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg transition duration-200 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] shadow-lg focus:ring-4 focus:ring-blue-300 flex items-center justify-center gap-2 text-base">
        <span>Create Account</span>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
        </svg>
    </button>

    <!-- Login Link -->
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500">Already registered?</span>
        </div>
    </div>

    <p class="text-center">
        <a href="../login/login.php" class="inline-flex items-center gap-2 text-blue-600 font-medium hover:text-blue-700 hover:underline group">
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            Sign in to your account
        </a>
    </p>
</form>
        </div>
    </div>

<script>
// Password strength checker
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    let score = 0;
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
    if (password.match(/\d/)) score++;
    
    const bars = ['strength-1', 'strength-2', 'strength-3'];
    const colors = ['bg-red-500', 'bg-yellow-500', 'bg-green-500'];
    
    bars.forEach((bar, index) => {
        const element = document.getElementById(bar);
        element.className = `h-1.5 flex-1 rounded-full transition-all duration-300 ${index < score ? colors[Math.min(score-1, 2)] : 'bg-gray-200'}`;
    });
    
    const strengthText = document.getElementById('passwordStrengthText');
    strengthText.className = `text-xs mt-1.5 ${score === 1 ? 'text-red-500' : score === 2 ? 'text-yellow-600' : score === 3 ? 'text-green-600' : 'text-gray-500'}`;
    strengthText.textContent = 
        score === 0 ? 'Enter a password' : 
        score === 1 ? 'Weak password' :
        score === 2 ? 'Medium password' : 'Strong password';
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
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

// Form validation with loading state
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms');
    
    if (password !== confirm) {
        e.preventDefault();
        alert('❌ Passwords do not match!');
        document.getElementById('confirm_password').focus();
        return false;
    }
    
    if (!terms.checked) {
        e.preventDefault();
        alert('Please agree to the Terms and Privacy Policy');
        terms.focus();
        return false;
    }
    
    // Show loading state
    const btn = document.getElementById('registerBtn');
    btn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Creating Account...
    `;
    btn.disabled = true;
});

// Phone formatting
document.getElementById('phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.slice(0, 3) + ' ' + value.slice(3);
        } else {
            value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 10);
        }
        e.target.value = value;
    }
});

// Set max date for DOB (18 years ago)
document.addEventListener('DOMContentLoaded', function() {
    const dobField = document.getElementById('dob');
    if (dobField) {
        const today = new Date();
        const minDate = new Date(today.setFullYear(today.getFullYear() - 18));
        const year = minDate.getFullYear();
        const month = String(minDate.getMonth() + 1).padStart(2, '0');
        const day = String(minDate.getDate()).padStart(2, '0');
        dobField.max = `${year}-${month}-${day}`;
    }
    
    // Restore form values after PHP submission (except password fields)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('submitted')) {
        // Clear sensitive fields
        document.getElementById('password').value = '';
        document.getElementById('confirm_password').value = '';
    }
});

// Username availability check (simulated)
let usernameTimeout;
document.getElementById('username').addEventListener('input', function(e) {
    clearTimeout(usernameTimeout);
    const username = e.target.value;
    const usernameIcon = document.getElementById('usernameAvailable');
    
    if (username.length >= 3) {
        if (usernameIcon) {
            usernameIcon.innerHTML = `
                <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
            usernameIcon.classList.remove('hidden');
        }
        
        usernameTimeout = setTimeout(() => {
            // Simulate username check - In production, replace with AJAX call
            const takenUsernames = ['admin', 'test', 'user'];
            const isAvailable = !takenUsernames.includes(username.toLowerCase());
            
            if (usernameIcon) {
                if (isAvailable) {
                    usernameIcon.innerHTML = `
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    `;
                } else {
                    usernameIcon.innerHTML = `
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    `;
                }
            }
        }, 500);
    } else {
        if (usernameIcon) usernameIcon.classList.add('hidden');
    }
});
</script>

</body>
</html>