/**
 * Authentication Integration Script
 * Simplified - No database required
 */

class AuthManager {
    constructor() {
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }
    
    setupEventListeners() {
        // Setup login form - simplified, no actual authentication
        const loginForm = document.getElementById('loginFormElement');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        // Setup signup form - simplified, no actual authentication
        const signupForm = document.getElementById('signupFormElement');
        if (signupForm) {
            signupForm.addEventListener('submit', (e) => this.handleSignup(e));
        }
        
        // Check if user is already logged in
        this.checkAuthStatus();
    }
    
    async handleLogin(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Signing In...';
        submitBtn.disabled = true;
        
        try {
            const email = formData.get('email');
            const password = formData.get('password');
            
            // Simple validation - no database required
            if (!email || !password) {
                this.showMessage('Please enter email and password', 'error');
                return;
            }
            
            // Simplified login - just redirect to main page
            this.showMessage('Login successful! Welcome!', 'success');
            
            // Store simple session flag
            sessionStorage.setItem('isLoggedIn', 'true');
            sessionStorage.setItem('userEmail', email);
            
            // Redirect to price page after short delay
            setTimeout(() => {
                window.location.href = '../HTML/pricefront.html';
            }, 1500);
            
        } catch (error) {
            this.showMessage('Login failed. Please try again.', 'error');
        } finally {
            // Restore button state after redirect starts
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 100);
        }
    }
    
    async handleSignup(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validate passwords match
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');
        
        if (password !== confirmPassword) {
            this.showMessage('Passwords do not match!', 'error');
            return;
        } 
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Creating Account...';
        submitBtn.disabled = true;
        
        try {
            const email = formData.get('email');
            const firstName = formData.get('firstName');
            const lastName = formData.get('lastName');
            
            // Simple validation - no database required
            if (!email || !firstName || !lastName || !password) {
                this.showMessage('Please fill in all fields', 'error');
                return;
            }
            
            // Simplified signup - just redirect to main page
            this.showMessage('Account created successfully! Welcome!', 'success');
            
            // Store simple session flag
            sessionStorage.setItem('isLoggedIn', 'true');
            sessionStorage.setItem('userEmail', email);
            sessionStorage.setItem('userName', `${firstName} ${lastName}`);
            
            // Reset form and redirect to price page
            form.reset();
            setTimeout(() => {
                window.location.href = '../HTML/pricefront.html';
            }, 2000);
            
        } catch (error) {
            this.showMessage('Registration failed. Please try again.', 'error');
        } finally {
            // Restore button state
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 100);
        }
    }
    
    async checkAuthStatus() {
        // Simplified auth check - no database required
        try {
            const isLoggedIn = sessionStorage.getItem('isLoggedIn');
            const userName = sessionStorage.getItem('userName');
            
            if (isLoggedIn && userName) {
                this.updateUIForLoggedInUser(userName);
            }
        } catch (error) {
            // Silently fail - user is not logged in
        }
    }
    
    async logout() {
        // Simplified logout - no database required
        try {
            sessionStorage.clear();
            this.showMessage('Logged out successfully!', 'success');
            
            setTimeout(() => {
                window.location.href = '../HTML/landingtrial.html';
            }, 1000);
        } catch (error) {
            this.showMessage('Logout failed. Please try again.', 'error');
        }
    }
    
    updateUIForLoggedInUser(userName) {
        // Update navigation or other UI elements for logged-in user
        const loginBtn = document.querySelector('.btn-login');
        if (loginBtn) {
            loginBtn.innerHTML = `<span>Hello, ${userName}!</span>`;
            loginBtn.style.cursor = 'default';
        }
    }
    
    switchToLogin() {
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        
        if (loginForm && signupForm) {
            loginForm.classList.remove('hidden');
            signupForm.classList.add('hidden');
        }
    }
    
    showMessage(message, type = 'info') {
        // Create or update message element
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
        
        // Set message type styles
        const styles = {
            success: '#27ae60',
            error: '#e74c3c',
            info: '#3498db',
            warning: '#f39c12'
        };
        
        messageEl.style.backgroundColor = styles[type] || styles.info;
        messageEl.textContent = message;
        
        // Show message
        setTimeout(() => {
            messageEl.style.transform = 'translateX(0)';
        }, 100);
        
        // Hide message after delay
        setTimeout(() => {
            messageEl.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.parentNode.removeChild(messageEl);
                }
            }, 300);
        }, 5000);
    }
    
    /**
     * Add user to all_users array in localStorage
     * @param {Object} user - User object to add
     */
    addUserToAllUsers(user) {
        // Get existing all_users array or initialize empty array
        let allUsers = [];
        const existingUsers = localStorage.getItem('all_users');
        
        if (existingUsers) {
            try {
                allUsers = JSON.parse(existingUsers);
            } catch (e) {
                console.error('Error parsing all_users from localStorage:', e);
                allUsers = [];
            }
        }
        
        // Check if user already exists (by email)
        const existingUserIndex = allUsers.findIndex(u => u.email === user.email);
        
        if (existingUserIndex >= 0) {
            // Update existing user
            allUsers[existingUserIndex] = {...allUsers[existingUserIndex], ...user};
        } else {
            // Add new user
            allUsers.push(user);
        }
        
        // Save back to localStorage
        localStorage.setItem('all_users', JSON.stringify(allUsers));
    }
}

// Initialize auth manager
const authManager = new AuthManager();

// Make it globally available for logout button
window.authManager = authManager;