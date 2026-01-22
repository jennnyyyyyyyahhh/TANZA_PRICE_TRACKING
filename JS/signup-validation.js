/**
 * Signup Form Validation and Password Strength Checker
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const signupPassword = document.getElementById('signupPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const signupEmail = document.getElementById('signupEmail');
        const togglePassword = document.getElementById('togglePassword');
        const signupForm = document.getElementById('signupFormElement');
        //const otpSection = document.getElementById('otpSection');
        //const resendOTPBtn = document.getElementById('resendOTP');
        
        let otpSent = false;
        let otpVerified = false;
        let currentOTP = null;

        // Password visibility toggle
        if (togglePassword && signupPassword) {
            togglePassword.addEventListener('click', function() {
                const type = signupPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                signupPassword.setAttribute('type', type);
                
                // Toggle eye icon
                if (type === 'text') {
                    togglePassword.classList.remove('fa-eye');
                    togglePassword.classList.add('fa-eye-slash');
                } else {
                    togglePassword.classList.remove('fa-eye-slash');
                    togglePassword.classList.add('fa-eye');
                }
            });
        }

        // Email validation for Gmail and Yahoo only
        if (signupEmail) {
            signupEmail.addEventListener('blur', validateEmail);
            signupEmail.addEventListener('input', function() {
                const emailValidation = document.getElementById('emailValidation');
                if (emailValidation) {
                    emailValidation.style.display = 'none';
                }
            });
        }

        // Password strength checker
        if (signupPassword) {
            signupPassword.addEventListener('focus', function() {
                const strengthDiv = document.getElementById('passwordStrength');
                if (strengthDiv) {
                    strengthDiv.style.display = 'block';
                }
            });

            signupPassword.addEventListener('input', checkPasswordStrength);
        }

        // Confirm password matching
        if (confirmPassword) {
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }

        // Resend OTP
        /*if (resendOTPBtn) {
            resendOTPBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sendOTP();
            });
        }*/

        // Override form submission
        if (signupForm) {
            signupForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await handleSignupSubmit(e);
            });
        }

        /**
         * Validate email for Gmail or Yahoo only
       
        function validateEmail() {
            const email = signupEmail.value.toLowerCase().trim();
            const emailValidation = document.getElementById('emailValidation');
            
            if (!email) {
                return false;
            }

            const isGmail = email.endsWith('@gmail.com');
            const isYahoo = email.endsWith('@yahoo.com');

            if (!isGmail && !isYahoo) {
                if (emailValidation) {
                    emailValidation.style.display = 'block';
                    emailValidation.textContent = 'Please use a Gmail or Yahoo email address';
                }
                signupEmail.style.borderColor = '#dc3545';
                return false;
            } else {
                if (emailValidation) {
                    emailValidation.style.display = 'none';
                }
                signupEmail.style.borderColor = '#27ae60';
                return true;
            }
        }  */

        /**
         * Check password strength
         */
        function checkPasswordStrength() {
            const password = signupPassword.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            // Check requirements
            const hasMinLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            
            // Update checklist UI
            updateChecklistItem('checkMinLength', hasMinLength);
            updateChecklistItem('checkUppercase', hasUppercase);
            updateChecklistItem('checkLowercase', hasLowercase);
            updateChecklistItem('checkNumber', hasNumber);
            updateChecklistItem('checkSpecial', hasSpecial);
            
            // Calculate strength
            let strength = 0;
            if (hasMinLength) strength += 20;
            if (hasUppercase) strength += 20;
            if (hasLowercase) strength += 20;
            if (hasNumber) strength += 20;
            if (hasSpecial) strength += 20;
            
            // Update strength bar
            if (strengthBar) {
                strengthBar.style.width = strength + '%';
                
                if (strength < 60) {
                    strengthBar.style.backgroundColor = '#dc3545'; // Red - Weak
                    if (strengthText) {
                        strengthText.textContent = 'Weak Password';
                        strengthText.style.color = '#dc3545';
                    }
                } else if (strength < 100) {
                    strengthBar.style.backgroundColor = '#ffc107'; // Yellow - Moderate
                    if (strengthText) {
                        strengthText.textContent = 'Moderate Password';
                        strengthText.style.color = '#ffc107';
                    }
                } else {
                    strengthBar.style.backgroundColor = '#27ae60'; // Green - Strong
                    if (strengthText) {
                        strengthText.textContent = 'Strong Password';
                        strengthText.style.color = '#27ae60';
                    }
                }
            }
            
            return strength === 100;
        }

        /**
         * Update checklist item
         */
        function updateChecklistItem(itemId, isValid) {
            const item = document.getElementById(itemId);
            if (item) {
                const icon = item.querySelector('i');
                if (icon) {
                    if (isValid) {
                        icon.style.color = '#27ae60';
                        icon.classList.remove('fa-circle');
                        icon.classList.add('fa-check-circle');
                    } else {
                        icon.style.color = '#dc3545';
                        icon.classList.remove('fa-check-circle');
                        icon.classList.add('fa-circle');
                    }
                }
            }
        }

        /**
         * Check if passwords match
         */
        function checkPasswordMatch() {
            const password = signupPassword.value;
            const confirm = confirmPassword.value;
            const matchMsg = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                if (matchMsg) {
                    matchMsg.style.display = 'none';
                }
                confirmPassword.style.borderColor = '';
                return false;
            }
            
            if (password === confirm) {
                if (matchMsg) {
                    matchMsg.style.display = 'block';
                    matchMsg.style.color = '#27ae60';
                    matchMsg.textContent = '✓ Passwords match';
                }
                confirmPassword.style.borderColor = '#27ae60';
                return true;
            } else {
                if (matchMsg) {
                    matchMsg.style.display = 'block';
                    matchMsg.style.color = '#dc3545';
                    matchMsg.textContent = '✗ Passwords do not match';
                }
                confirmPassword.style.borderColor = '#dc3545';
                return false;
            }
        }

        /**
         * Send OTP to email
       
        async function sendOTP() {
            const email = signupEmail.value.trim();
            
            if (!validateEmail()) {
                showMessage('Please enter a valid Gmail or Yahoo email address', 'error');
                return false;
            }

            try {
                // Show loading
                if (resendOTPBtn) {
                    resendOTPBtn.textContent = 'Sending...';
                    resendOTPBtn.disabled = true;
                }

                // Frontend-only OTP simulation (no backend required)
                // Generate demo OTP
                currentOTP = '123456'; // Demo OTP for testing
                otpSent = true;
                
                if (otpSection) {
                    otpSection.style.display = 'block';
                }
                
                showMessage('Demo Code: 123456 (Enter this to verify)', 'success');
                
                // Start countdown timer
                startResendTimer();
            } catch (error) {
                showMessage('Failed to send verification code. Please try again.', 'error');
            } finally {
                if (resendOTPBtn) {
                    resendOTPBtn.textContent = 'Resend Code';
                    resendOTPBtn.disabled = false;
                }
            }

            return otpSent;
        }  */

        /**
         * Start resend timer
         
        function startResendTimer() {
            let countdown = 60;
            if (resendOTPBtn) {
                resendOTPBtn.disabled = true;
                
                const interval = setInterval(() => {
                    countdown--;
                    resendOTPBtn.textContent = `Resend Code (${countdown}s)`;
                    
                    if (countdown <= 0) {
                        clearInterval(interval);
                        resendOTPBtn.textContent = 'Resend Code';
                        resendOTPBtn.disabled = false;
                    }
                }, 1000);
            }
        }

        /**
         * Verify OTP
        
        async function verifyOTP() {
            const otpCode = document.getElementById('otpCode');
            if (!otpCode) return false;

            const enteredOTP = otpCode.value.trim();
            
            if (!enteredOTP) {
                showMessage('Please enter the verification code', 'error');
                return false;
            }

            if (enteredOTP.length !== 6) {
                showMessage('Verification code must be 6 digits', 'error');
                return false;
            }

            try {
                // Frontend-only verification (no backend required)
                // Check against demo OTP
                if (enteredOTP === currentOTP) {
                    otpVerified = true;
                    showMessage('Email verified successfully!', 'success');
                    
                    // Visual feedback
                    if (otpCode) {
                        otpCode.style.borderColor = '#27ae60';
                        otpCode.disabled = true;
                    }
                    
                    return true;
                } else {
                    showMessage('Invalid verification code. Use: 123456', 'error');
                    if (otpCode) {
                        otpCode.style.borderColor = '#dc3545';
                    }
                    return false;
                }
            } catch (error) {
                showMessage('Failed to verify code. Please try again.', 'error');
                return false;
            }
        }

        /**
         * Handle signup form submission
      
        async function handleSignupSubmit(event) {
            event.preventDefault();

            // Validate email
            if (!validateEmail()) {
                showMessage('Please use a Gmail or Yahoo email address', 'error');
                signupEmail.focus();
                return;
            }

            // Check password strength
            if (!checkPasswordStrength()) {
                showMessage('Please create a stronger password that meets all requirements', 'error');
                signupPassword.focus();
                return;
            }

            // Check password match
            if (!checkPasswordMatch()) {
                showMessage('Passwords do not match', 'error');
                confirmPassword.focus();
                return;
            }

            // Send OTP if not sent yet
            if (!otpSent) {
                const sent = await sendOTP();
                if (sent) {
                    showMessage('Please enter the verification code sent to your email', 'info');
                }
                return;
            }

            // Verify OTP if not verified yet
            if (!otpVerified) {
                const verified = await verifyOTP();
                if (!verified) {
                    return;
                }
            }

            // If we reach here, all validations passed
            // Call the original auth manager signup handler
            if (window.authManager && typeof window.authManager.handleSignup === 'function') {
                window.authManager.handleSignup(event);
            }
        }

        /**
         * Show message to user
         */
        function showMessage(message, type = 'info') {
            let messageEl = document.getElementById('auth-message');
            if (!messageEl) {
                messageEl = document.createElement('div');
                messageEl.id = 'auth-message';
                messageEl.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 5px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    max-width: 350px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                `;
                document.body.appendChild(messageEl);
            }
            
            const styles = {
                success: '#27ae60',
                error: '#e74c3c',
                info: '#3498db',
                warning: '#f39c12'
            };
            
            messageEl.style.backgroundColor = styles[type] || styles.info;
            messageEl.textContent = message;
            
            setTimeout(() => {
                messageEl.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                messageEl.style.transform = 'translateX(100%)';
            }, 5000);
        }
    });
})();
