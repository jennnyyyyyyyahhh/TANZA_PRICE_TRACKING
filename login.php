<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error_message = '';
$success_message = '';
if (!empty($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
if (!empty($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4a8c33">
    <title>Login - Tanza Public Market</title>
    <link rel="stylesheet" href="CSS/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="CSS/price-update-notification.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #4a8c33 0%, #27ae60 100%);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .page-header .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .page-header .logo i {
            font-size: 1.5rem;
        }
        .page-header .header-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        .page-header .header-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        .page-header .header-links a:hover {
            opacity: 0.8;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 1024px) {
            .page-header {
                padding: 1rem 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 0.8rem 1rem;
            }
            .page-header .logo span {
                font-size: 1rem;
            }
            .page-header .header-links {
                gap: 1rem;
                font-size: 0.9rem;
            }
            .page-header .header-links a i {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                padding: 0.7rem 0.8rem;
            }
            .page-header .logo {
                font-size: 1rem;
            }
            .page-header .logo i {
                font-size: 1.2rem;
            }
            .page-header .header-links {
                gap: 0.8rem;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .page-header .logo span {
                font-size: 0.9rem;
            }
            .page-header .header-links {
                gap: 0.6rem;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 375px) {
            .page-header {
                padding: 0.6rem 0.7rem;
            }
            .page-header .logo {
                font-size: 0.85rem;
            }
            .page-header .logo i {
                font-size: 1.1rem;
            }
            .page-header .header-links {
                gap: 0.5rem;
                font-size: 0.75rem;
            }
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
                <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
            </div>
        </div>
    </header>

    <div class="auth-container">
        <!-- Left Side - Image/Branding -->
        <div class="auth-left">
            <div class="auth-brand">
                <h1>Welcome Back!</h1>
                <p>Sign in to access real-time agricultural market prices in Tanza, Cavite</p>
            </div>
            <div class="auth-illustration">
                <i class="fas fa-chart-line"></i>
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

        <!-- Right Side - Login Form -->
        <div class="auth-right">
            <div class="auth-form-wrapper">
                <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i><span>Back to Home</span></a>
                <div class="auth-header" style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                    <h2>Welcome Back!</h2>
                    <p>Sign in to access your market dashboard</p>
                </div>

                <form action="php/login.php" method="post" class="auth-form" id="loginFormElement">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
                        
                        
                        
                        $_SESSION['csrf_token']
                    ); ?>">
                    <div class="form-group">
                        <label for="loginEmail"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" id="loginEmail" name="email" class="auth-input" placeholder="Enter your email" required autocomplete="email">
                        <span class="input-error" id="emailError"></span>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword"><i class="fas fa-lock"></i> Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="loginPassword" name="password" class="auth-input" placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="toggle-password" id="toggleLoginPassword"><i class="fas fa-eye-slash"></i></button>
                        </div>
                        <span class="input-error" id="passwordError"></span>
                    </div>
                    <div class="form-options">
                        <label class="checkbox-container"><input type="checkbox" id="rememberMe" name="rememberMe"><span class="checkmark"></span> Remember me</label>
                        <a href="#" class="forgot-password" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                    <button type="submit" class="auth-btn primary"><span>Sign In</span><i class="fas fa-arrow-right"></i></button>
                    <div class="auth-footer"><p>Don't have an account? <a href="signup.php" class="signup-link">Sign Up</a></p></div>
                </form>
            </div>
        </div>

        <!-- Forgot password modals and logic (matches signup.php EmailJS/template usage) -->
        <!-- Email input modal -->
        <div id="fpEmailModal" style="display:none; position: fixed; inset:0; background: rgba(0,0,0,0.55); z-index:1000;"> 
            <div style="background:#fff; margin:8% auto; padding:1.5rem; border-radius:12px; width:90%; max-width:420px; position:relative;">
                <button id="fpEmailClose" style="position:absolute; right:14px; top:10px; background:transparent;border:none;font-size:20px;">&times;</button>
                <h3>Password Reset</h3>
                <p class="otp-instruction">Enter your registered email to receive a 6-digit verification code.</p>
                <div style="margin-top:1rem;">
                    <label for="fpEmailInput">Email</label>
                    <input type="email" id="fpEmailInput" class="auth-input" placeholder="your@email.com" required>
                    <p class="input-error" id="fpEmailModalError"></p>
                    <div style="margin-top:.75rem; display:flex; gap:.5rem;">
                        <button id="fpEmailSendBtn" class="auth-btn primary">Send Verification Code</button>
                        <button id="fpEmailCancelBtn" class="auth-btn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- OTP Modal (copied from signup.php for identical UX) -->
        <div id="fpOtpModal" style="display:none; position: fixed; inset:0; background: rgba(0,0,0,0.55); z-index:1000;"> 
            <div style="background:linear-gradient(180deg,#fff 0%,#f7f9fa 100%); margin:6% auto; padding:2.5rem; border-radius:25px; width:95%; max-width:520px; text-align:center; position:relative; box-shadow:0 15px 40px rgba(0,0,0,0.25);">
                <button id="fpOtpClose" style="position:absolute; right:15px; top:12px; background:transparent;border:none;font-size:20px;">&times;</button>
                <h3>Email Verification</h3>
                <p class="otp-instruction">Enter the 6-digit code sent to your email</p>
                <div class="otp-inputs" style="display:flex; justify-content:center; gap:12px; margin-bottom:1rem;">
                    <input type="text" maxlength="1" class="fp-otp-box otp-box" />
                    <input type="text" maxlength="1" class="fp-otp-box otp-box" />
                    <input type="text" maxlength="1" class="fp-otp-box otp-box" />
                    <input type="text" maxlength="1" class="fp-otp-box otp-box" />
                    <input type="text" maxlength="1" class="fp-otp-box otp-box" />
                    <input type="text" maxlength="1" class="fp-otp-box otp-box" />
                </div>
                <button type="button" id="fpVerifyOtpBtn" class="auth-btn" style="margin-top:1rem;">Verify Code</button>
            </div>
        </div>
    
        <!-- Reset password modal -->
        <div id="fpResetModal" style="display:none; position: fixed; inset:0; background: rgba(0,0,0,0.55); z-index:1000;"> 
            <div style="background:#fff; margin:8% auto; padding:1.5rem; border-radius:12px; width:90%; max-width:420px; position:relative;">
                <button id="fpResetClose" style="position:absolute; right:14px; top:10px; background:transparent;border:none;font-size:20px;">&times;</button>
                <h3>Set New Password</h3>
                <p class="otp-instruction">Enter a new password for your account.</p>
                <div style="margin-top:1rem;">
                    <label for="fpNewPw">New Password</label>
                    <input type="password" id="fpNewPw" class="auth-input" placeholder="New password" required>
                    <label for="fpNewPwConfirm">Confirm Password</label>
                    <input type="password" id="fpNewPwConfirm" class="auth-input" placeholder="Confirm password" required>
                    <p class="input-error" id="fpResetModalError"></p>
                    <div style="margin-top:.75rem;">
                        <button id="fpResetSubmitBtn" class="auth-btn primary">Reset Password</button>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Loading Overlay (used by EmailJS send and verification flows) -->
        <div class="loading-overlay" id="loadingOverlay" style="display:none;">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Processing...</p>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/emailjs-com@3/dist/email.min.js"></script>
        <script>
        // Initialize EmailJS (use same user id as signup.php)
        if (window.emailjs && window.emailjs.init) {
                emailjs.init("ssI9dsXkLKeH3xhwe");
        } else {
                window.addEventListener('load', function(){ if(window.emailjs && window.emailjs.init) emailjs.init("ssI9dsXkLKeH3xhwe"); });
        }

        // Notification helper (creates #notif if missing) — matches signup.php UX
        function showNotif(message, type = 'success') {
            let notif = document.getElementById('notif');
            if (!notif) {
                notif = document.createElement('div');
                notif.id = 'notif';
                notif.style = 'position: fixed; top: 20px; right: 20px; min-width: 250px; max-width: 350px; padding: 14px 18px; border-radius: 10px; color: #fff; font-weight: 500; display: none; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.2); opacity: 0; transition: opacity 0.3s ease;';
                notif.innerHTML = '<span id="notifMsg"></span><button id="notifClose" style="background: transparent; border: none; color: #fff; float: right; font-size: 18px; cursor: pointer; margin-left: 10px; line-height: 1;">×</button>';
                document.body.appendChild(notif);
            }
            const notifMsg = document.getElementById('notifMsg');
            const closeBtn = document.getElementById('notifClose');
            notifMsg.textContent = message;
            notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
            notif.style.display = 'block';
            setTimeout(() => notif.style.opacity = '1', 10);
            const timer = setTimeout(() => hideNotif(true), 3000);
            if (closeBtn) closeBtn.onclick = () => { clearTimeout(timer); hideNotif(true); };
        }

        function hideNotif(withFade) {
            const notif = document.getElementById('notif');
            if (!notif) return;
            if (withFade) {
                notif.style.opacity = '0';
                setTimeout(() => notif.style.display = 'none', 300);
            } else {
                notif.style.display = 'none';
            }
        }

        (function(){
            // Elements
            const forgotLink = document.getElementById('forgotPasswordLink');
            const fpEmailModal = document.getElementById('fpEmailModal');
            const fpEmailInput = document.getElementById('fpEmailInput');
            const fpEmailSendBtn = document.getElementById('fpEmailSendBtn');
            const fpEmailCancelBtn = document.getElementById('fpEmailCancelBtn');
            const fpEmailClose = document.getElementById('fpEmailClose');

            const fpOtpModal = document.getElementById('fpOtpModal');
            const fpOtpBoxes = Array.from(document.querySelectorAll('.fp-otp-box'));
            const fpVerifyOtpBtn = document.getElementById('fpVerifyOtpBtn');
            const fpOtpClose = document.getElementById('fpOtpClose');

            const fpResetModal = document.getElementById('fpResetModal');
            const fpResetClose = document.getElementById('fpResetClose');
            const fpNewPw = document.getElementById('fpNewPw');
            const fpNewPwConfirm = document.getElementById('fpNewPwConfirm');
            const fpResetSubmitBtn = document.getElementById('fpResetSubmitBtn');
            const fpResetModalError = document.getElementById('fpResetModalError');

            const fpEmailModalError = document.getElementById('fpEmailModalError');

            let serverCode = null; // for dev fallback only; server returns code but we prefer not to expose in prod
            let currentEmail = null;

            function openModal(el){ if(el) el.style.display = 'block'; }
            function closeModal(el){ if(el) el.style.display = 'none'; }

            function showOverlay(show){ const overlay = document.getElementById('loadingOverlay'); if(overlay) overlay.style.display = show ? 'flex' : 'none'; }

            // Password visibility toggle for login form (attach early so it always works)
            const toggleBtn = document.getElementById('toggleLoginPassword');
            const pwInput = document.getElementById('loginPassword');
            if (toggleBtn && pwInput) {
                toggleBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    const icon = this.querySelector('i');
                    if (pwInput.type === 'password') {
                        pwInput.type = 'text';
                        if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
                    } else {
                        pwInput.type = 'password';
                        if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
                    }
                    pwInput.focus();
                });
            }

            // Open email modal from link
            if (forgotLink) forgotLink.addEventListener('click', function(e){ e.preventDefault(); fpEmailInput.value=''; fpEmailModalError.textContent=''; openModal(fpEmailModal); fpEmailInput.focus(); });
            if (fpEmailCancelBtn) fpEmailCancelBtn.addEventListener('click', function(e){ e.preventDefault(); closeModal(fpEmailModal); });
            if (fpEmailClose) fpEmailClose.addEventListener('click', function(){ closeModal(fpEmailModal); });

            // OTP modal close
            if (fpOtpClose) fpOtpClose.addEventListener('click', function(){ closeModal(fpOtpModal); });
            if (fpResetClose) fpResetClose.addEventListener('click', function(){ closeModal(fpResetModal); });

            // Autofocus behaviour for fp otp boxes
            fpOtpBoxes.forEach((box, idx) => {
                box.addEventListener('input', (e) => { if (e.target.value.length === 1 && idx < fpOtpBoxes.length - 1) fpOtpBoxes[idx+1].focus(); });
                box.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !e.target.value && idx > 0) fpOtpBoxes[idx-1].focus(); });
            });

            // Send code: call request_reset.php, then use same EmailJS template as signup.php
            if (fpEmailSendBtn) fpEmailSendBtn.addEventListener('click', function(e){
                e.preventDefault();
                fpEmailModalError.textContent = '';
                const email = fpEmailInput.value.trim();
                if (!email) { fpEmailModalError.textContent = 'Please enter your email.'; return; }
                showOverlay(true);
                fetch('php/request_reset.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ email: email }) })
                .then(res=>res.json())
                .then(data=>{
                    showOverlay(false);
                    if (!data || !data.success) { fpEmailModalError.textContent = data && data.message ? data.message : 'Unable to send verification code.'; return; }
                    serverCode = data.code || null; // only for dev fallback
                    const toName = data.name || '';
                    currentEmail = email;
                    // Prepare template params exactly like signup.php
                    const templateParams = { to_name: toName, to_email: email, otp_code: serverCode };
                    // Send via EmailJS using same service/template as signup.php
                    if (window.emailjs && window.emailjs.send) {
                        document.getElementById('loadingOverlay').style.display = 'flex';
                        emailjs.send('service_l9b49th','template_gvu8j9t', templateParams)
                            .then(() => {
                                document.getElementById('loadingOverlay').style.display = 'none';
                                closeModal(fpEmailModal);
                                // clear otp boxes
                                fpOtpBoxes.forEach(b=>b.value='');
                                openModal(fpOtpModal);
                            })
                            .catch(err => {
                                document.getElementById('loadingOverlay').style.display = 'none';
                                console.error('EmailJS send error', err);
                                fpEmailModalError.textContent = 'Failed to send email. Please try again later.';
                            });
                    } else {
                        // Fallback: open OTP modal so user can enter server-provided code (dev only)
                        closeModal(fpEmailModal);
                        fpOtpBoxes.forEach(b=>b.value='');
                        openModal(fpOtpModal);
                    }
                }).catch(err=>{ showOverlay(false); fpEmailModalError.textContent='Server error.'; console.error(err); });
            });

            // Verify OTP (match signup.js behaviour: compare client-side if server provided code)
            if (fpVerifyOtpBtn) fpVerifyOtpBtn.addEventListener('click', function(e){
                e.preventDefault();
                const entered = fpOtpBoxes.map(b=>b.value).join('');
                if (!entered || entered.length < 6) { showNotif('Enter the full 6-digit code.', 'error'); return; }

                // If server returned the raw code (client-side send via EmailJS), compare here (same as signup.js)
                if (serverCode !== null) {
                    if (String(entered) !== String(serverCode)) {
                        showNotif('Incorrect verification code. Please try again.', 'error');
                        return;
                    }

                    
                    // matched locally
                    closeModal(fpOtpModal);
                    fpNewPw.value = ''; fpNewPwConfirm.value = '';
                    fpResetModalError.textContent = '';
                    openModal(fpResetModal);
                    return;
                }

                // Fallback: ask server to verify (safer when server didn't return code)
                showOverlay(true);
                fetch('php/verify_reset_code.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ email: currentEmail, code: entered }) })
                .then(res=>res.json())
                .then(data=>{
                    showOverlay(false);
                    if (!data || !data.success) { showNotif(data && data.message ? data.message : 'Invalid or expired code.', 'error'); return; }
                    closeModal(fpOtpModal);
                    fpNewPw.value = ''; fpNewPwConfirm.value = '';
                    fpResetModalError.textContent = '';
                    openModal(fpResetModal);
                }).catch(err=>{ showOverlay(false); showNotif('Server error during verification.', 'error'); console.error(err); });
            });

            // Reset password submit
            if (fpResetSubmitBtn) fpResetSubmitBtn.addEventListener('click', function(e){
                e.preventDefault();
                fpResetModalError.textContent = '';
                const pw = fpNewPw.value || '';
                const pwc = fpNewPwConfirm.value || '';
                if (pw.length < 6) { fpResetModalError.textContent = 'Password must be at least 6 characters.'; return; }
                if (pw !== pwc) { fpResetModalError.textContent = 'Passwords do not match.'; return; }
                showOverlay(true);
                fetch('php/reset_password.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ email: currentEmail, password: pw }) })
                .then(res=>res.json())
                .then(data=>{
                    showOverlay(false);
                    if (!data || !data.success) {
                        const errMsg = data && data.message ? data.message : 'Unable to reset password.';
                        fpResetModalError.textContent = errMsg;
                        showNotif(errMsg, 'error');
                        console.error('Reset error response:', data);
                        return;
                    }
                    closeModal(fpResetModal);
                    const msg = (data && data.message) ? data.message : 'Password reset successful. You can now sign in.';
                    showNotif(msg, 'success');
                    console.log('Password reset success', data);
                }).catch(err=>{ showOverlay(false); const e = 'Server error.'; fpResetModalError.textContent=e; showNotif(e,'error'); console.error(err); });
            });
        })();

        // Show server-side messages (error/success) as top-right popups
        document.addEventListener('DOMContentLoaded', function(){
            var serverError = <?php echo json_encode($error_message); ?>;
            var serverSuccess = <?php echo json_encode($success_message); ?>;
            if (serverError) showNotif(serverError, 'error');
            if (serverSuccess) showNotif(serverSuccess, 'success');
        });

        </script>

    
    <script src="JS/price-update-notification.js" defer></script>
</body>
</html>
