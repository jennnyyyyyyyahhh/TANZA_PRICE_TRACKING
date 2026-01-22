<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#4a8c33">
<title>Sign Up - Tanza Public Market</title>
<link rel="stylesheet" href="CSS/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="signup-style.css">
<link rel="stylesheet" href="CSS/price-update-notification.css">
<style>
/* High-Quality OTP Modal Design */
/* Enhanced High-Quality OTP Modal */
#otpModal {
  display: none;
  position: fixed;
  z-index: 1000;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  animation: fadeIn 0.3s ease forwards;
}

/* Modal Container */
#otpModalContent {
  background: linear-gradient(180deg, #ffffff 0%, #f7f9fa 100%);
  margin: 6% auto;
  padding: 3rem 2.5rem;
  border-radius: 25px;
  width: 95%;
  max-width: 520px; /* 🔹 bigger form width */
  text-align: center;
  position: relative;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.4s ease forwards;
  transform-origin: center;
}

/* Close Button */
#otpClose {
  position: absolute;
  top: 15px;
  right: 20px;
  font-size: 1.6rem;
  color: #777;
  cursor: pointer;
  transition: color 0.2s ease;
}
#otpClose:hover {
  color: #222;
}

/* Title and Instruction */
#otpModalContent h3 {
  font-size: 2rem;
  margin-bottom: 0.6rem;
  color: #2f3e46;
  font-weight: 700;
  letter-spacing: 0.5px;
}
.otp-instruction {
  color: #5f6c72;
  font-size: 1rem;
  margin-bottom: 2rem;
  letter-spacing: 0.3px;
}

/* OTP Input Boxes */
.otp-inputs {
  display: flex;
  justify-content: center;
  gap: 16px; /* more spacing between boxes */
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.otp-box {
  width: 60px; /* 🔹 larger input size */
  height: 75px;
  border-radius: 15px;
  border: 2.5px solid #d1d5db;
  background: #fff;
  text-align: center;
  font-size: 2rem;
  font-weight: 700;
  color: #2f3e46;
  transition: all 0.2s ease-in-out;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
}
.otp-box:focus {
  border-color: #4a8c33;
  background: #f4fff2;
  box-shadow: 0 0 14px rgba(74, 140, 51, 0.35);
  transform: scale(1.1);
  outline: none;
}

/* Verify Button */
.auth-btn {
  background: linear-gradient(135deg, #4a8c33, #66bb6a);
  color: #fff;
  font-weight: 700;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s ease;
  width: 100%;
  box-shadow: 0 5px 14px rgba(74, 140, 51, 0.25);
  font-size: 1.1rem;
  letter-spacing: 0.4px;
}
.auth-btn:hover {
  background: linear-gradient(135deg, #3d7b2b, #56a65a);
  transform: translateY(-3px);
  box-shadow: 0 8px 18px rgba(74, 140, 51, 0.3);
}

/* Responsive */
@media (max-width: 480px) {
  #otpModalContent {
    width: 90%;
    padding: 2.5rem 1.5rem;
  }

  .otp-box {
    width: 48px;
    height: 60px;
    font-size: 1.6rem;
  }

  #otpModalContent h3 {
    font-size: 1.6rem;
  }

  .otp-instruction {
    font-size: 0.9rem;
  }
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(40px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

</head>
<body>


<!-- Header -->
<header class="page-header">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <i class="fas fa-seedling"></i>
            <span>Tanza Public Market</span>
        </a>
        <div class="header-links">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        </div>
    </div>
</header>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-brand">
            <h1>Welcome!</h1>
            <p>Create your account to start tracking agricultural market prices in Tanza, Cavite</p>
        </div>
        <div class="auth-illustration">
            <i class="fas fa-shopping-basket"></i>
        </div>
        <div class="auth-features">
            <div class="feature-item"><i class="fas fa-heart"></i><span>Support Local Farmers</span></div>
            <div class="feature-item"><i class="fas fa-piggy-bank"></i><span>Public Market Saves Your Money</span></div>
            <div class="feature-item"><i class="fas fa-leaf"></i><span>Farm-Fresh Products Daily</span></div>
            <div class="feature-item"><i class="fas fa-users"></i><span>Community of Local Vendors</span></div>
            <div class="feature-item"><i class="fas fa-seedling"></i><span>Supporting Philippine Agriculture</span></div>
            <div class="feature-item"><i class="fas fa-map-marker-alt"></i><span>Tanza Public Market, Cavite</span></div>
        </div>
    </div>


    <div id="notif"
        style="position: fixed; top: 20px; right: 20px;
                min-width: 250px; max-width: 350px;
                padding: 14px 18px; border-radius: 10px;
                color: #fff; font-weight: 500;
                display: none; z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                opacity: 0; transition: opacity 0.3s ease;">
        <span id="notifMsg"></span>
        <button id="notifClose"
                style="background: transparent; border: none; color: #fff;
                    float: right; font-size: 18px; cursor: pointer;
                    margin-left: 10px; line-height: 1;">×</button>
    </div>
    <div class="auth-right">
        <div class="auth-form-wrapper">
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Login</span>
            </a>

            <div class="auth-header" style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h2>Create Account</h2>
                <p>Start tracking the best prices today</p>
            </div>

            <form class="auth-form" id="signupFormElement" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName"><i class="fas fa-user"></i> First Name</label>
                        <input type="text" id="firstName" name="firstName" class="auth-input" placeholder="Juan" required autocomplete="given-name">
                        <span class="input-error" id="firstNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="lastName"><i class="fas fa-user"></i> Last Name</label>
                        <input type="text" id="lastName" name="lastName" class="auth-input" placeholder="Dela Cruz" required autocomplete="family-name">
                        <span class="input-error" id="lastNameError"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="signupEmail"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="signupEmail" name="email" class="auth-input" placeholder="juandelacruz@gmail.com" required autocomplete="email">
                    <small class="input-hint">Use Gmail or Yahoo email for verification</small>
                    <span class="input-error" id="emailValidation"></span>
                </div>

                <div class="form-group">
                    <label for="signupPassword"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="signupPassword" name="password" class="auth-input" placeholder="Create a strong password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" id="toggleSignupPassword">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div id="passwordStrength" class="password-strength" style="display: none;">
                        <div class="strength-bar"><div class="strength-bar-fill" id="strengthBarFill"></div></div>
                        <div class="strength-text" id="strengthText"></div>
                        <div class="password-requirements">
                            <div class="requirement" id="checkMinLength"><i class="fas fa-circle"></i><span>At least 8 characters</span></div>
                            <div class="requirement" id="checkUppercase"><i class="fas fa-circle"></i><span>One uppercase letter</span></div>
                            <div class="requirement" id="checkLowercase"><i class="fas fa-circle"></i><span>One lowercase letter</span></div>
                            <div class="requirement" id="checkNumber"><i class="fas fa-circle"></i><span>One number</span></div>
                            <div class="requirement" id="checkSpecial"><i class="fas fa-circle"></i><span>One special character (!@#$%^&*)</span></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" class="auth-input" placeholder="Re-enter your password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" id="toggleConfirmPassword">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <span class="input-error" id="passwordMatch"></span>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="agreeTerms" required>
                        <span class="checkmark"></span>
                        <span class="checkbox-label">
                            I agree to the <a href="#" class="terms-link">Terms of Service</a> and 
                            <a href="#" class="terms-link">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="newsletter" name="newsletter">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">Send me market updates and price alerts</span>
                    </label>
                </div>

                <button type="submit" id="createAccount" class="auth-btn primary">Create Account</button>

                

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php" class="login-link">Sign In</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- OTP Modal -->
<div id="otpModal">
  <div id="otpModalContent">
    <span id="otpClose">&times;</span>
    <h3>Email Verification</h3>
    <p class="otp-instruction">Enter the 6-digit code sent to your email</p>
    
    <div class="otp-inputs">
      <input type="text" maxlength="1" class="otp-box" />
      <input type="text" maxlength="1" class="otp-box" />
      <input type="text" maxlength="1" class="otp-box" />
      <input type="text" maxlength="1" class="otp-box" />
      <input type="text" maxlength="1" class="otp-box" />
      <input type="text" maxlength="1" class="otp-box" />
    </div>
    
    <button type="button" id="verifyOTPBtn" class="auth-btn" style="margin-top:1rem;">Verify Email</button>
  </div>


</div>


<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display:none;">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Creating your account...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/emailjs-com@3/dist/email.min.js"></script>
<script>
emailjs.init("ssI9dsXkLKeH3xhwe"); 

let generatedOTP = null;

// Toggle password visibility
document.getElementById('toggleSignupPassword').addEventListener('click', function() {
  const passwordInput = document.getElementById('signupPassword');
  const icon = this.querySelector('i');
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  } else {
    passwordInput.type = 'password';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  }
});

// Toggle confirm password visibility
document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
  const passwordInput = document.getElementById('confirmPassword');
  const icon = this.querySelector('i');
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    passwordInput.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
});

// Password requirement evaluation and UI updates
function evaluatePassword(pwd) {
  return {
    minLength: pwd.length >= 8,
    uppercase: /[A-Z]/.test(pwd),
    lowercase: /[a-z]/.test(pwd),
    number: /[0-9]/.test(pwd),
    special: /[!@#\$%\^&\*()_+\-=[\]{};:\"\\|,.<>\/?`~]/.test(pwd)
  };
}

function updatePasswordUI(pwd) {
  const checks = evaluatePassword(pwd);
  const map = {
    minLength: 'checkMinLength',
    uppercase: 'checkUppercase',
    lowercase: 'checkLowercase',
    number: 'checkNumber',
    special: 'checkSpecial'
  };
  let passed = 0;
  Object.keys(map).forEach(key => {
    const el = document.getElementById(map[key]);
    if (!el) return;
    const icon = el.querySelector('i');
    if (checks[key]) {
      passed++;
      if (icon) { icon.className = 'fas fa-check'; icon.style.color = '#16a34a'; }
      el.style.opacity = '1';
    } else {
      if (icon) { icon.className = 'fas fa-circle'; icon.style.color = '#9ca3af'; }
      el.style.opacity = '0.7';
    }
  });

  const fill = document.getElementById('strengthBarFill');
  const strengthText = document.getElementById('strengthText');
  const strengthContainer = document.getElementById('passwordStrength');
  if (strengthContainer) strengthContainer.style.display = pwd ? 'block' : 'none';
  const percent = Math.round((passed / 5) * 100);
  if (fill) {
    fill.style.width = percent + '%';
    fill.style.background = percent < 40 ? '#ef4444' : percent < 80 ? '#f59e0b' : '#16a34a';
  }
  if (strengthText) {
    strengthText.textContent = percent < 40 ? 'Weak' : percent < 80 ? 'Medium' : 'Strong';
    strengthText.style.color = percent < 40 ? '#ef4444' : percent < 80 ? '#f59e0b' : '#16a34a';
  }
  return { checks, all: passed === 5 };
}

// OTP modal
const otpModal = document.getElementById('otpModal');
document.getElementById('otpClose').onclick = () => otpModal.style.display = 'none';

// Intercept form submission
document.getElementById('signupFormElement').addEventListener('submit', function(e) {
  e.preventDefault();

  // Validate password match before proceeding
  const pwdEl = document.getElementById('signupPassword');
  const confEl = document.getElementById('confirmPassword');
  const pwd = pwdEl ? pwdEl.value : '';
  const conf = confEl ? confEl.value : '';
  const pwdMsgEl = document.getElementById('passwordMatch');
  if (pwdMsgEl) pwdMsgEl.textContent = '';
  if (pwd !== conf) {
    if (pwdMsgEl) pwdMsgEl.textContent = 'Passwords do not match.';
    showNotif('Passwords do not match. Please confirm your password.', 'error');
    return;
  }

  // Enforce password requirements
  const pwdCheck = updatePasswordUI(pwd);
  if (!pwdCheck.all) {
    showNotif('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 'error');
    return;
  }

  if (!generatedOTP) {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();

    if (!firstName || !lastName || !email) {
      showNotif("Please fill all required fields.", "error");
      return;
    }

    generatedOTP = Math.floor(100000 + Math.random() * 900000);

    const templateParams = {
      to_name: `${firstName} ${lastName}`,
      to_email: email,
      otp_code: generatedOTP
    };

    document.getElementById('loadingOverlay').style.display = 'flex';

    emailjs.send("service_l9b49th", "template_gvu8j9t", templateParams)
      .then(() => {
        showNotif("A verification code has been sent to your email. Check your inbox or spam folder.", "success");
        otpModal.style.display = 'block';
        document.getElementById('loadingOverlay').style.display = 'none';
      })
      .catch(err => {
        showNotif("Failed to send OTP: " + err.text + ". Check your EmailJS service/template IDs.", "error");
        document.getElementById('loadingOverlay').style.display = 'none';
        generatedOTP = null;
      });
  }
});

document.getElementById('verifyOTPBtn').addEventListener('click', function() {
  const enteredOTP = Array.from(document.querySelectorAll('.otp-box'))
    .map(box => box.value)
    .join('');

  if (enteredOTP !== String(generatedOTP)) {
    showNotif("Incorrect verification code. Please try again.", "error");
    return;
  }

  otpModal.style.display = 'none';
  document.getElementById('loadingOverlay').style.display = 'flex';

  const formData = new FormData(document.getElementById('signupFormElement'));
  formData.append('verified', '1');

  fetch('php/register.php', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById('loadingOverlay').style.display = 'none';
    if (data && data.success) {
      showNotif(data.message || 'Account created successfully.', 'success');
      setTimeout(() => window.location.href = 'login.php', 1600);
    } else {
      showNotif(data.message || 'Registration failed.', 'error');
    }
  })
  .catch(err => {
    showNotif("Error while creating account: " + err, "error");
    document.getElementById('loadingOverlay').style.display = 'none';
  });
});

// OTP input autofocus
const otpBoxes = document.querySelectorAll('.otp-box');
otpBoxes.forEach((box, idx) => {
  box.addEventListener('input', (e) => {
    if (e.target.value.length === 1 && idx < otpBoxes.length - 1) {
      otpBoxes[idx + 1].focus();
    }
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === "Backspace" && !e.target.value && idx > 0) {
      otpBoxes[idx - 1].focus();
    }
  });
});

// Clear password mismatch message when user edits either password field
['signupPassword', 'confirmPassword'].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('input', () => {
      const pm = document.getElementById('passwordMatch');
      if (pm) pm.textContent = '';
      if (id === 'signupPassword') {
        updatePasswordUI(el.value);
      }
    });
  }
});

// Notification system
function showNotif(message, type = 'success') {
  const notif = document.getElementById('notif');
  const notifMsg = document.getElementById('notifMsg');
  const closeBtn = document.getElementById('notifClose');

  notifMsg.textContent = message;
  notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
  notif.style.display = 'block';
  setTimeout(() => notif.style.opacity = '1', 10);

  const timer = setTimeout(() => hideNotif(true), 3000);
  closeBtn.onclick = () => { clearTimeout(timer); hideNotif(true); };
}

function hideNotif(withFade) {
  const notif = document.getElementById('notif');
  if (withFade) {
    notif.style.opacity = '0';
    setTimeout(() => notif.style.display = 'none', 300);
  } else {
    notif.style.display = 'none';
  }
}
</script>
  <?php
        session_start();
        if (isset($_SESSION['notif_message'])) {
            $msg = $_SESSION['notif_message'];
            $type = $_SESSION['notif_type'] ?? 'success';
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showNotif(" . json_encode($msg) . ", " . json_encode($type) . ");
                });
            </script>";
            unset($_SESSION['notif_message'], $_SESSION['notif_type']);
        }
    ?>


</body>
</html>
