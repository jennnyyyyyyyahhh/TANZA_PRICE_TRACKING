// Vendor Request Management Functionality
function setupVendorRequestsManagement() {
    // Check if vendor requests section exists
    const vendorRequestsSection = document.getElementById('vendor-requests-section');
    if (!vendorRequestsSection) return;

    // Select All Requests Checkbox
    const selectAllRequests = document.getElementById('selectAllRequests');
    if (selectAllRequests) {
        selectAllRequests.addEventListener('change', function() {
            const requestCheckboxes = document.querySelectorAll('.request-select');
            requestCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Request Status Filter
    const requestStatusFilter = document.getElementById('requestStatusFilter');
    if (requestStatusFilter) {
        requestStatusFilter.addEventListener('change', function() {
            filterRequests();
        });
    }

    // Request Category Filter
    const requestCategoryFilter = document.getElementById('requestCategoryFilter');
    if (requestCategoryFilter) {
        requestCategoryFilter.addEventListener('change', function() {
            filterRequests();
        });
    }

    // Apply Bulk Action
    const applyBulkRequestAction = document.getElementById('applyBulkRequestAction');
    if (applyBulkRequestAction) {
        applyBulkRequestAction.addEventListener('click', function() {
            const selectedAction = document.getElementById('bulkRequestActionSelect').value;
            if (!selectedAction) {
                alert('Please select an action to perform');
                return;
            }

            const selectedRequests = document.querySelectorAll('.request-select:checked');
            if (selectedRequests.length === 0) {
                alert('Please select at least one request');
                return;
            }

            // Get request IDs
            const requestIds = [];
            selectedRequests.forEach(checkbox => {
                const requestRow = checkbox.closest('tr');
                const requestId = requestRow.querySelector('td:nth-child(2)').textContent;
                requestIds.push(requestId);
            });

            // Confirm action
            if (confirm(`Are you sure you want to ${selectedAction} ${requestIds.length} request(s)?`)) {
                console.log(`Applying ${selectedAction} to requests:`, requestIds);
                
                // Simulate API call
                setTimeout(() => {
                    alert(`Successfully ${selectedAction}d ${requestIds.length} request(s)`);
                    
                    if (selectedAction === 'approve') {
                        selectedRequests.forEach(checkbox => {
                            const requestRow = checkbox.closest('tr');
                            const statusBadge = requestRow.querySelector('.status-badge');
                            if (statusBadge) {
                                statusBadge.className = 'status-badge approved';
                                statusBadge.textContent = 'Approved';
                            }
                            
                            // Update action buttons
                            const actionCell = requestRow.querySelector('.action-buttons');
                            if (actionCell) {
                                actionCell.innerHTML = `
                                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn-icon" title="Send Welcome Email"><i class="fas fa-envelope"></i></button>
                                `;
                            }
                        });
                    } else if (selectedAction === 'decline') {
                        selectedRequests.forEach(checkbox => {
                            const requestRow = checkbox.closest('tr');
                            const statusBadge = requestRow.querySelector('.status-badge');
                            if (statusBadge) {
                                statusBadge.className = 'status-badge declined';
                                statusBadge.textContent = 'Declined';
                            }
                            
                            // Update action buttons
                            const actionCell = requestRow.querySelector('.action-buttons');
                            if (actionCell) {
                                actionCell.innerHTML = `
                                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn-icon" title="Reconsider"><i class="fas fa-redo"></i></button>
                                `;
                            }
                        });
                    }
                    
                    // Re-attach event listeners to new buttons
                    setupRequestDetailButtons();
                }, 500);
            }
        });
    }

    // Setup request detail view buttons
    setupRequestDetailButtons();
}

// Filter requests based on filter selections
function filterRequests() {
    const statusFilter = document.getElementById('requestStatusFilter').value;
    const categoryFilter = document.getElementById('requestCategoryFilter').value;
    
    const requestRows = document.querySelectorAll('.request-table tbody tr');
    
    requestRows.forEach(row => {
        const category = row.querySelector('td:nth-child(6)').textContent.toLowerCase();
        const statusBadge = row.querySelector('.status-badge');
        const status = statusBadge ? statusBadge.textContent.toLowerCase() : '';
        
        const matchesStatus = statusFilter === 'all' || status === statusFilter;
        const matchesCategory = categoryFilter === 'all' || category.toLowerCase() === categoryFilter.toLowerCase();
        
        if (matchesStatus && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Setup request detail view buttons
function setupRequestDetailButtons() {
    // View Details buttons
    document.querySelectorAll('.request-table .btn-icon[title="View Details"]').forEach(button => {
        button.addEventListener('click', function() {
            const requestRow = this.closest('tr');
            const requestId = requestRow.querySelector('td:nth-child(2)').textContent;
            const requestDate = requestRow.querySelector('td:nth-child(3)').textContent;
            const applicantName = requestRow.querySelector('td:nth-child(4)').textContent;
            const businessName = requestRow.querySelector('td:nth-child(5)').textContent;
            const category = requestRow.querySelector('td:nth-child(6)').textContent;
            const contact = requestRow.querySelector('td:nth-child(7)').textContent;
            const statusBadge = requestRow.querySelector('.status-badge');
            const status = statusBadge ? statusBadge.textContent : '';
            
            // Populate request details
            document.getElementById('requestIdDisplay').textContent = requestId;
            document.getElementById('requestDateDisplay').textContent = requestDate;
            document.getElementById('applicantNameDisplay').textContent = applicantName;
            document.getElementById('businessNameDisplay').textContent = businessName;
            document.getElementById('categoryDisplay').textContent = category;
            document.getElementById('contactDisplay').textContent = contact;
            
            // Set select dropdowns
            document.getElementById('requestStatusUpdate').value = status.toLowerCase();
            
            // Show request details container
            const requestDetailsContainer = document.querySelector('.request-details-container');
            if (requestDetailsContainer) {
                requestDetailsContainer.style.display = 'block';
            }
            
            // Scroll to details
            requestDetailsContainer.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Close Details button
    const closeRequestDetails = document.getElementById('closeRequestDetails');
    if (closeRequestDetails) {
        closeRequestDetails.addEventListener('click', function() {
            const requestDetailsContainer = document.querySelector('.request-details-container');
            if (requestDetailsContainer) {
                requestDetailsContainer.style.display = 'none';
            }
        });
    }
    
    // Save Changes button
    const saveRequestChanges = document.getElementById('saveRequestChanges');
    if (saveRequestChanges) {
        saveRequestChanges.addEventListener('click', function() {
            const requestId = document.getElementById('requestIdDisplay').textContent;
            const newStatus = document.getElementById('requestStatusUpdate').value;
            const adminNotes = document.getElementById('requestNotesInput').value;
            
            if (!adminNotes) {
                alert('Please add notes about the changes you are making.');
                return;
            }
            
            console.log(`Saving changes to request ${requestId}:`, {
                status: newStatus,
                notes: adminNotes
            });
            
            // Simulate API call
            setTimeout(() => {
                alert('Changes saved successfully!');
                
                // Update status in table
                const requestRows = document.querySelectorAll('.request-table tbody tr');
                requestRows.forEach(row => {
                    const rowRequestId = row.querySelector('td:nth-child(2)').textContent;
                    if (rowRequestId === requestId) {
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = `status-badge ${newStatus}`;
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        }
                        
                        // Update action buttons based on new status
                        const actionCell = row.querySelector('.action-buttons');
                        if (actionCell) {
                            if (newStatus === 'approved') {
                                actionCell.innerHTML = `
                                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn-icon" title="Send Welcome Email"><i class="fas fa-envelope"></i></button>
                                `;
                            } else if (newStatus === 'declined') {
                                actionCell.innerHTML = `
                                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn-icon" title="Reconsider"><i class="fas fa-redo"></i></button>
                                `;
                            } else {
                                actionCell.innerHTML = `
                                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn-icon approve-btn" title="Approve"><i class="fas fa-check"></i></button>
                                    <button class="btn-icon decline-btn" title="Decline"><i class="fas fa-times"></i></button>
                                `;
                            }
                        }
                    }
                });
                
                // Re-attach event listeners
                setupRequestDetailButtons();
                setupActionButtons();
                
                // Clear notes
                document.getElementById('requestNotesInput').value = '';
            }, 500);
        });
    }
    
    // Approve Request button
    const approveRequest = document.getElementById('approveRequest');
    if (approveRequest) {
        approveRequest.addEventListener('click', function() {
            const requestId = document.getElementById('requestIdDisplay').textContent;
            const applicantName = document.getElementById('applicantNameDisplay').textContent;
            
            if (confirm(`Approve vendor request from ${applicantName}?`)) {
                // Set status dropdown
                document.getElementById('requestStatusUpdate').value = 'approved';
                
                // Prompt for notes
                document.getElementById('requestNotesInput').focus();
                document.getElementById('requestNotesInput').value = 'Application approved. Vendor meets all requirements.';
                
                alert('Please add any additional notes and click "Save Changes" to confirm approval.');
            }
        });
    }
    
    // Decline Request button
    const declineRequest = document.getElementById('declineRequest');
    if (declineRequest) {
        declineRequest.addEventListener('click', function() {
            const requestId = document.getElementById('requestIdDisplay').textContent;
            const applicantName = document.getElementById('applicantNameDisplay').textContent;
            
            if (confirm(`Decline vendor request from ${applicantName}? This can be reconsidered later if needed.`)) {
                // Set status dropdown
                document.getElementById('requestStatusUpdate').value = 'declined';
                
                // Prompt for notes
                document.getElementById('requestNotesInput').focus();
                document.getElementById('requestNotesInput').value = 'Application declined. Please specify reasons for declining.';
                
                alert('Please add detailed reasons for declining and click "Save Changes" to confirm.');
            }
        });
    }
    
    // Contact Applicant button
    const contactApplicant = document.getElementById('contactApplicant');
    if (contactApplicant) {
        contactApplicant.addEventListener('click', function() {
            const applicantName = document.getElementById('applicantNameDisplay').textContent;
            const email = document.getElementById('emailDisplay').textContent;
            
            alert(`Opening email to ${applicantName} at ${email}`);
            // In a real application, this would open an email interface or a messaging system
        });
    }
    
    // Setup action buttons
    setupActionButtons();
}

// Setup direct action buttons (approve/decline)
function setupActionButtons() {
    // Approve buttons
    document.querySelectorAll('.approve-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestRow = this.closest('tr');
            const requestId = requestRow.querySelector('td:nth-child(2)').textContent;
            const applicantName = requestRow.querySelector('td:nth-child(4)').textContent;
            
            if (confirm(`Approve vendor request from ${applicantName}?`)) {
                console.log(`Approving request ${requestId}`);
                
                // Update the status badge
                const statusBadge = requestRow.querySelector('.status-badge');
                statusBadge.textContent = 'Approved';
                statusBadge.className = 'status-badge approved';
                
                // Update action buttons
                const actionCell = requestRow.querySelector('.action-buttons');
                actionCell.innerHTML = `
                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon" title="Send Welcome Email"><i class="fas fa-envelope"></i></button>
                `;
                
                // Re-attach event listeners
                setupRequestDetailButtons();
                
                alert(`Vendor request ${requestId} has been approved!`);
            }
        });
    });
    
    // Decline buttons
    document.querySelectorAll('.decline-btn').forEach(button => {
        button.addEventListener('click', function() {
            const requestRow = this.closest('tr');
            const requestId = requestRow.querySelector('td:nth-child(2)').textContent;
            const applicantName = requestRow.querySelector('td:nth-child(4)').textContent;
            
            if (confirm(`Decline vendor request from ${applicantName}? This can be reconsidered later if needed.`)) {
                console.log(`Declining request ${requestId}`);
                
                // Update the status badge
                const statusBadge = requestRow.querySelector('.status-badge');
                statusBadge.textContent = 'Declined';
                statusBadge.className = 'status-badge declined';
                
                // Update action buttons
                const actionCell = requestRow.querySelector('.action-buttons');
                actionCell.innerHTML = `
                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon" title="Reconsider"><i class="fas fa-redo"></i></button>
                `;
                
                // Re-attach event listeners
                setupRequestDetailButtons();
                
                alert(`Vendor request ${requestId} has been declined.`);
            }
        });
    });
    
    // Reconsider buttons
    document.querySelectorAll('.btn-icon[title="Reconsider"]').forEach(button => {
        button.addEventListener('click', function() {
            const requestRow = this.closest('tr');
            const requestId = requestRow.querySelector('td:nth-child(2)').textContent;
            const applicantName = requestRow.querySelector('td:nth-child(4)').textContent;
            
            if (confirm(`Reconsider vendor request from ${applicantName}?`)) {
                console.log(`Reconsidering request ${requestId}`);
                
                // Update the status badge
                const statusBadge = requestRow.querySelector('.status-badge');
                statusBadge.textContent = 'Pending';
                statusBadge.className = 'status-badge pending';
                
                // Update action buttons
                const actionCell = requestRow.querySelector('.action-buttons');
                actionCell.innerHTML = `
                    <button class="btn-icon" title="View Details"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon approve-btn" title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn-icon decline-btn" title="Decline"><i class="fas fa-times"></i></button>
                `;
                
                // Re-attach event listeners
                setupRequestDetailButtons();
                
                alert(`Vendor request ${requestId} has been moved back to Pending for reconsideration.`);
            }
        });
    });
    
    // Send Welcome Email buttons
    document.querySelectorAll('.btn-icon[title="Send Welcome Email"]').forEach(button => {
        button.addEventListener('click', function() {
            const requestRow = this.closest('tr');
            const requestId = requestRow.querySelector('td:nth-child(2)').textContent;
            const applicantName = requestRow.querySelector('td:nth-child(4)').textContent;
            
            alert(`Sending welcome email to ${applicantName}`);
            // In a real application, this would trigger an email sending function
        });
    });
}