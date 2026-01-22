// Vendor Access Control
// ALL RESTRICTIONS REMOVED - All vendors have full access

// Function to check if the user is approved by admin
function isVendorApproved() {
    // RESTRICTION REMOVED: Always return true - all vendors have full access
    return true;
}

// Function to show pending approval message
function showPendingApprovalMessage() {
    // Check if message already exists
    if (document.getElementById('pending-approval-banner')) return;
    
    // Create pending approval banner
    const banner = document.createElement('div');
    banner.id = 'pending-approval-banner';
    banner.className = 'pending-approval-banner';
    banner.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <div class="banner-message">
            <h3>Pending Admin Approval</h3>
            <p>Your vendor account is pending approval from administrators. 
            Some features are restricted until your account is approved.</p>
        </div>
        <button class="close-banner" aria-label="Close notification">×</button>
    `;
    
    // Add styles to banner
    banner.style.cssText = `
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 40px);
        max-width: 1200px;
        padding: 15px 20px;
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 1000;
        margin-bottom: 20px;
    `;
    
    // Style the icon
    banner.querySelector('i').style.cssText = `
        font-size: 24px;
        margin-right: 15px;
    `;
    
    // Style the close button
    banner.querySelector('.close-banner').style.cssText = `
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #856404;
        padding: 0 10px;
    `;
    
    // Add close functionality
    banner.querySelector('.close-banner').addEventListener('click', () => {
        banner.style.display = 'none';
    });
    
    // Add to document
    document.body.appendChild(banner);
}

// Function to restrict access to specified sections
function restrictSectionAccess(restrictedSectionIds) {
    // RESTRICTION REMOVED: This function now does nothing
    // All vendors have unrestricted access to all sections
    return;
}

// Export functions
window.vendorAccessControl = {
    isVendorApproved,
    showPendingApprovalMessage,
    restrictSectionAccess
};