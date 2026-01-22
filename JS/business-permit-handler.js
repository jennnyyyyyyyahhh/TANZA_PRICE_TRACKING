/**
 * Business Permit Handler
 * Manages business permit uploads and verification status
 */

(function() {
    'use strict';

    let currentUser = null;
    let selectedFile = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Load current user
        currentUser = JSON.parse(localStorage.getItem('user') || '{}');
        
        // Initialize business permit section
        initBusinessPermitSection();
        
        // Check verification status and show/hide overlays
        checkVerificationStatus();
        
        // Setup event listeners
        setupEventListeners();
    });

    function initBusinessPermitSection() {
        const businessPermitFile = document.getElementById('businessPermitFile');
        const uploadArea = document.getElementById('uploadArea');
        const businessPermitForm = document.getElementById('businessPermitForm');
        
        // Drag and drop functionality
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#27ae60';
                this.style.backgroundColor = '#f0f9f4';
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ddd';
                this.style.backgroundColor = 'white';
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ddd';
                this.style.backgroundColor = 'white';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0]);
                }
            });
        }

        // File input change
        if (businessPermitFile) {
            businessPermitFile.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });
        }

        // Form submission
        if (businessPermitForm) {
            businessPermitForm.addEventListener('submit', handlePermitSubmit);
        }
        
        // Load existing permit if available
        loadExistingPermit();
    }

    function setupEventListeners() {
        // Remove file button
        const removeFileBtn = document.getElementById('removeFile');
        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', function() {
                selectedFile = null;
                document.getElementById('businessPermitFile').value = '';
                document.querySelector('.upload-placeholder').style.display = 'block';
                document.getElementById('filePreview').style.display = 'none';
            });
        }

        // Cancel upload
        const cancelBtn = document.getElementById('cancelPermitUpload');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                document.getElementById('businessPermitForm').reset();
                selectedFile = null;
                document.querySelector('.upload-placeholder').style.display = 'block';
                document.getElementById('filePreview').style.display = 'none';
            });
        }

        // Update permit button
        const updateBtn = document.getElementById('updatePermit');
        if (updateBtn) {
            updateBtn.addEventListener('click', function() {
                document.getElementById('permitUploadCard').style.display = 'block';
                document.getElementById('permitDisplayCard').style.display = 'none';
            });
        }

        // Close banner
        const closeBannerBtn = document.getElementById('closeBanner');
        if (closeBannerBtn) {
            closeBannerBtn.addEventListener('click', function() {
                document.getElementById('verificationBanner').style.display = 'none';
            });
        }
    }

    function handleFileSelect(file) {
        // Validate file
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

        if (!allowedTypes.includes(file.type)) {
            showMessage('Invalid file type. Please upload JPG, PNG, or PDF files only.', 'error');
            return;
        }

        if (file.size > maxSize) {
            showMessage('File is too large. Maximum size is 5MB.', 'error');
            return;
        }

        selectedFile = file;

        // Show file preview
        document.querySelector('.upload-placeholder').style.display = 'none';
        const filePreview = document.getElementById('filePreview');
        filePreview.style.display = 'flex';

        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
    }

    async function handlePermitSubmit(e) {
    e.preventDefault();

    if (!selectedFile) {
        showMessage('Please select a file to upload', 'error');
        return;
    }

    const permitNumber = document.getElementById('permitNumber').value;
    const issueDate = document.getElementById('permitIssueDate').value;
    const expiryDate = document.getElementById('permitExpiryDate').value;
    const businessName = document.getElementById('businessName').value;

    if (new Date(expiryDate) <= new Date(issueDate)) {
        showMessage('Expiry date must be after issue date', 'error');
        return;
    }

    const submitBtn = document.getElementById('submitPermit');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    submitBtn.disabled = true;

    const formData = new FormData();
    formData.append('businessPermit', selectedFile);
    formData.append('permitNumber', permitNumber);
    formData.append('issueDate', issueDate);
    formData.append('expiryDate', expiryDate);
    formData.append('businessName', businessName);
    formData.append('userId', currentUser.id || '');

    try {
        const response = await fetch('/php/save_business_permit.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.status === 'success') {
            showMessage('Business permit uploaded successfully!', 'success');
            checkVerificationStatus();
        } else {
            showMessage(result.message || 'Upload failed.', 'error');
        }
    } catch (err) {
        console.error(err);
        showMessage('Server error. Please try again later.', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}


    function loadExistingPermit() {
        const permitData = JSON.parse(localStorage.getItem('business_permit') || 'null');
        
        if (permitData) {
            updatePermitDisplay(permitData);
            document.getElementById('permitUploadCard').style.display = 'none';
            document.getElementById('permitDisplayCard').style.display = 'block';
        }
    }

    function updatePermitDisplay(permitData) {
        // Update status card
        const statusIcon = document.querySelector('.status-icon');
        const statusTitle = document.getElementById('permitStatusTitle');
        const statusMessage = document.getElementById('permitStatusMessage');
        const statusBadge = document.getElementById('permitStatusBadge');

        const status = currentUser?.is_approved ? 'verified' : permitData.status;

        if (status === 'verified' || currentUser?.is_approved) {
            statusIcon.className = 'status-icon verified';
            statusIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            statusTitle.textContent = 'Verified';
            statusMessage.textContent = 'Your business permit has been verified by the admin.';
            statusBadge.className = 'status-badge verified';
            statusBadge.textContent = 'Verified';
        } else if (status === 'pending') {
            statusIcon.className = 'status-icon pending';
            statusIcon.innerHTML = '<i class="fas fa-clock"></i>';
            statusTitle.textContent = 'Pending Verification';
            statusMessage.textContent = 'Your business permit is under review. You will be notified once verified.';
            statusBadge.className = 'status-badge pending';
            statusBadge.textContent = 'Pending Approval';
        } else if (status === 'rejected') {
            statusIcon.className = 'status-icon rejected';
            statusIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
            statusTitle.textContent = 'Rejected';
            statusMessage.textContent = 'Your business permit was rejected. Please upload a valid permit.';
            statusBadge.className = 'status-badge rejected';
            statusBadge.textContent = 'Rejected';
        }

        // Update display card
        document.getElementById('displayPermitNumber').textContent = permitData.permitNumber;
        document.getElementById('displayBusinessName').textContent = permitData.businessName;
        document.getElementById('displayIssueDate').textContent = formatDate(permitData.issueDate);
        document.getElementById('displayExpiryDate').textContent = formatDate(permitData.expiryDate);
        document.getElementById('displayUploadDate').textContent = formatDate(permitData.uploadDate);

        // Update sidebar verification status
        updateSidebarStatus(status);
    }

    function updateSidebarStatus(status) {
        const sidebarStatus = document.getElementById('sidebarVerificationStatus');
        if (!sidebarStatus) return;

        const icon = sidebarStatus.querySelector('.verification-icon');
        const statusText = sidebarStatus.querySelector('.status-text');
        const small = sidebarStatus.querySelector('small');

        if (status === 'verified' || currentUser?.is_approved) {
            icon.className = 'verification-icon verified';
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            statusText.className = 'status-text verified';
            statusText.textContent = 'Verified';
            small.textContent = 'All features unlocked';
        } else if (status === 'pending') {
            icon.className = 'verification-icon pending';
            icon.innerHTML = '<i class="fas fa-clock"></i>';
            statusText.className = 'status-text pending';
            statusText.textContent = 'Pending Approval';
            small.textContent = 'Waiting for admin verification';
        } else {
            icon.className = 'verification-icon pending';
            icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
            statusText.className = 'status-text pending';
            statusText.textContent = 'Not Verified';
            small.textContent = 'Upload your business permit';
        }
    }

    function checkVerificationStatus() {
        const isVerified = currentUser?.is_approved === true || 
                          currentUser?.permit_status === 'verified' ||
                          currentUser?.approval_status === 'verified';
        
        const hasPermit = localStorage.getItem('business_permit') !== null;

        // Show/hide verification banner
        const banner = document.getElementById('verificationBanner');
        if (banner && !isVerified) {
            banner.style.display = 'flex';
        }

        // Show/hide overlays for restricted sections
        const surveyOverlay = document.getElementById('surveyVerificationOverlay');
        const cleaningOverlay = document.getElementById('cleaningVerificationOverlay');
        const surveyContent = document.getElementById('surveyContent');
        const cleaningContent = document.getElementById('cleaningContent');

        if (!isVerified) {
            // Show overlays, hide content
            if (surveyOverlay) surveyOverlay.style.display = 'flex';
            if (cleaningOverlay) cleaningOverlay.style.display = 'flex';
            if (surveyContent) surveyContent.style.display = 'none';
            if (cleaningContent) cleaningContent.style.display = 'none';
        } else {
            // Hide overlays, show content
            if (surveyOverlay) surveyOverlay.style.display = 'none';
            if (cleaningOverlay) cleaningOverlay.style.display = 'none';
            if (surveyContent) surveyContent.style.display = 'block';
            if (cleaningContent) cleaningContent.style.display = 'block';
            
            // Hide banner
            if (banner) banner.style.display = 'none';
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    function showMessage(message, type = 'info') {
        let messageEl = document.getElementById('permit-message');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.id = 'permit-message';
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

    // Global function for navigation
    window.navigateToBusinessPermit = function() {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show business permit section
        const permitSection = document.getElementById('business-permit-section');
        if (permitSection) {
            permitSection.classList.add('active');
        }
        
        // Update sidebar navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const permitNavItem = document.querySelector('a[href="#business-permit"]')?.parentElement;
        if (permitNavItem) {
            permitNavItem.classList.add('active');
        }
    };

})();
