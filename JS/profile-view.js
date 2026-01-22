/**
 * Profile View Handler
 * Manages profile display and editing functionality
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Load user profile data
        loadProfileData();
        
        // Setup event listeners
        setupEventListeners();
    });

    function loadProfileData() {
        // Get user data from localStorage
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        
        // Set profile information
        if (userData.first_name) {
            document.getElementById('profileFullName').textContent = `${userData.first_name} ${userData.last_name || ''}`;
            document.getElementById('displayFirstName').textContent = userData.first_name;
        }
        
        if (userData.last_name) {
            document.getElementById('displayLastName').textContent = userData.last_name;
        }
        
        if (userData.email) {
            document.getElementById('displayEmail').textContent = userData.email;
        }
        
        // Set vendor-specific data
        if (userData.business_name) {
            document.getElementById('displayBusinessName').textContent = userData.business_name;
        }
        
        // Set verification status
        updateVerificationStatus(userData);
        
        // Load profile image if exists
        const savedAvatar = localStorage.getItem('profile_avatar');
        if (savedAvatar) {
            document.getElementById('profileAvatarImg').src = savedAvatar;
        }
    }

    function updateVerificationStatus(userData) {
        const statusElement = document.getElementById('displayVerificationStatus');
        if (!statusElement) return;
        
        const isVerified = userData.is_approved === true || 
                          userData.approval_status === 'verified' || 
                          userData.permit_status === 'verified';
        
        if (isVerified) {
            statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Verified';
            statusElement.className = 'status-badge verified';
        } else {
            statusElement.innerHTML = '<i class="fas fa-clock"></i> Pending Verification';
            statusElement.className = 'status-badge pending';
        }
    }

    function setupEventListeners() {
        // Change Avatar Button
        const changeAvatarBtn = document.getElementById('changeAvatarBtn');
        if (changeAvatarBtn) {
            changeAvatarBtn.addEventListener('click', handleChangeAvatar);
        }
        
        // Edit Profile Button
        const editProfileBtn = document.getElementById('editProfileBtn');
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', handleEditProfile);
        }
        
        // Change Password Button
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', handleChangePassword);
        }
        
        // Notification Settings Button
        const notificationSettingsBtn = document.getElementById('notificationSettingsBtn');
        if (notificationSettingsBtn) {
            notificationSettingsBtn.addEventListener('click', handleNotificationSettings);
        }
        
        // Privacy Settings Button
        const privacySettingsBtn = document.getElementById('privacySettingsBtn');
        if (privacySettingsBtn) {
            privacySettingsBtn.addEventListener('click', handlePrivacySettings);
        }
    }

    function handleChangeAvatar() {
        // Create file input
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        
        input.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showMessage('Image size must be less than 2MB', 'error');
                    return;
                }
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    showMessage('Please select an image file', 'error');
                    return;
                }
                
                // Read and display image
                const reader = new FileReader();
                reader.onload = function(event) {
                    const imgUrl = event.target.result;
                    
                    // Update avatar image
                    document.getElementById('profileAvatarImg').src = imgUrl;
                    
                    // Save to localStorage
                    localStorage.setItem('profile_avatar', imgUrl);
                    
                    showMessage('Profile photo updated successfully!', 'success');
                };
                reader.readAsDataURL(file);
            }
        };
        
        input.click();
    }

    function handleEditProfile() {
        showMessage('Edit profile functionality coming soon!', 'info');
        
        // In a full implementation, this would:
        // 1. Show an edit modal or form
        // 2. Pre-fill with current data
        // 3. Allow user to update information
        // 4. Save changes to backend and localStorage
    }

    function handleChangePassword() {
        // Create modal for password change
        const modal = createModal({
            title: 'Change Password',
            content: `
                <form id="changePasswordForm" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Current Password</label>
                        <input type="password" id="currentPassword" class="auth-input" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">New Password</label>
                        <input type="password" id="newPassword" class="auth-input" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Confirm New Password</label>
                        <input type="password" id="confirmNewPassword" class="auth-input" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px;">
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 16px;">
                        <button type="button" class="btn-secondary" onclick="closeModal()" style="padding: 10px 24px; border-radius: 6px;">Cancel</button>
                        <button type="submit" class="btn-primary" style="padding: 10px 24px; background: #4a8c33; color: white; border: none; border-radius: 6px; cursor: pointer;">Change Password</button>
                    </div>
                </form>
            `
        });
        
        document.body.appendChild(modal);
        
        // Handle form submission
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;
            
            if (newPassword !== confirmNewPassword) {
                showMessage('New passwords do not match', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showMessage('Password must be at least 8 characters long', 'error');
                return;
            }
            
            // In production, validate with backend
            showMessage('Password changed successfully!', 'success');
            closeModal();
        });
    }

    function handleNotificationSettings() {
        showMessage('Notification settings functionality coming soon!', 'info');
    }

    function handlePrivacySettings() {
        showMessage('Privacy settings functionality coming soon!', 'info');
    }

    function createModal({ title, content }) {
        const modal = document.createElement('div');
        modal.className = 'profile-modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="closeModal()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <button class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `;
        
        // Add modal styles
        const style = document.createElement('style');
        style.textContent = `
            .profile-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
            }
            
            .modal-content {
                position: relative;
                background: white;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: modalSlideIn 0.3s ease;
            }
            
            @keyframes modalSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 24px;
                border-bottom: 2px solid #e0e0e0;
            }
            
            .modal-header h2 {
                margin: 0;
                font-size: 1.5rem;
                color: #333;
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 4px;
                transition: color 0.3s ease;
            }
            
            .modal-close:hover {
                color: #e74c3c;
            }
            
            .modal-body {
                padding: 24px;
            }
        `;
        document.head.appendChild(style);
        
        return modal;
    }

    function showMessage(message, type = 'info') {
        let messageEl = document.getElementById('profile-message');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.id = 'profile-message';
            messageEl.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                max-width: 350px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateX(400px);
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
            messageEl.style.transform = 'translateX(400px)';
        }, 4000);
    }

    // Global function to close modal
    window.closeModal = function() {
        const modal = document.querySelector('.profile-modal');
        if (modal) {
            modal.remove();
        }
    };

})();
