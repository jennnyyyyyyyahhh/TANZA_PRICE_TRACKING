// Report Management Functionality
function setupReportManagement() {
    // Check if report management section exists
    const reportManagementSection = document.getElementById('report-management-section');
    if (!reportManagementSection) return;

    // Select All Reports Checkbox
    const selectAllReports = document.getElementById('selectAllReports');
    if (selectAllReports) {
        selectAllReports.addEventListener('change', function() {
            const reportCheckboxes = document.querySelectorAll('.report-select');
            reportCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Report Status Filter
    const reportStatusFilter = document.getElementById('reportStatusFilter');
    if (reportStatusFilter) {
        reportStatusFilter.addEventListener('change', function() {
            filterReports();
        });
    }

    // Report Category Filter
    const reportCategoryFilter = document.getElementById('reportCategoryFilter');
    if (reportCategoryFilter) {
        reportCategoryFilter.addEventListener('change', function() {
            filterReports();
        });
    }

    // Apply Bulk Action
    const applyBulkReportAction = document.getElementById('applyBulkReportAction');
    if (applyBulkReportAction) {
        applyBulkReportAction.addEventListener('click', function() {
            const selectedAction = document.getElementById('bulkReportActionSelect').value;
            if (!selectedAction) {
                alert('Please select an action to perform');
                return;
            }

            const selectedReports = document.querySelectorAll('.report-select:checked');
            if (selectedReports.length === 0) {
                alert('Please select at least one report');
                return;
            }

            // Get report IDs
            const reportIds = [];
            selectedReports.forEach(checkbox => {
                const reportRow = checkbox.closest('tr');
                const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
                reportIds.push(reportId);
            });

            // Confirm action
            if (confirm(`Are you sure you want to ${selectedAction.replace('-', ' ')} ${reportIds.length} report(s)?`)) {
                console.log(`Applying ${selectedAction} to reports:`, reportIds);
                
                // Simulate API call
                setTimeout(() => {
                    alert(`Successfully applied ${selectedAction.replace('-', ' ')} to ${reportIds.length} report(s)`);
                    // Here you would typically refresh the report list
                    
                    // For demo, update the status badges based on action
                    if (selectedAction.startsWith('mark-')) {
                        const newStatus = selectedAction.replace('mark-', '');
                        selectedReports.forEach(checkbox => {
                            const reportRow = checkbox.closest('tr');
                            const statusBadge = reportRow.querySelector('.status-badge');
                            if (statusBadge) {
                                statusBadge.className = `status-badge ${newStatus}`;
                                statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            }
                        });
                    }
                }, 500);
            }
        });
    }

    // Setup report detail view buttons
    setupReportDetailButtons();
}

// Filter reports based on filter selections
function filterReports() {
    const statusFilter = document.getElementById('reportStatusFilter').value;
    const categoryFilter = document.getElementById('reportCategoryFilter').value;
    
    const reportRows = document.querySelectorAll('.report-table tbody tr');
    
    reportRows.forEach(row => {
        const categoryBadge = row.querySelector('.category-badge');
        const category = categoryBadge ? categoryBadge.textContent.toLowerCase() : '';
        const statusBadge = row.querySelector('.status-badge');
        const status = statusBadge ? statusBadge.textContent.toLowerCase() : '';
        
        const matchesStatus = statusFilter === 'all' || status === statusFilter;
        const matchesCategory = categoryFilter === 'all' || category.includes(categoryFilter.replace('-', ' '));
        
        if (matchesStatus && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Setup report detail view functionality
function setupReportDetailButtons() {
    // View Details buttons
    document.querySelectorAll('.report-table .btn-icon[title="View Details"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportRow = this.closest('tr');
            const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
            const reportDate = reportRow.querySelector('td:nth-child(3)').textContent;
            const customerName = reportRow.querySelector('td:nth-child(4)').textContent;
            const vendorName = reportRow.querySelector('td:nth-child(5)').textContent;
            const stallId = reportRow.querySelector('td:nth-child(6)').textContent;
            const categoryBadge = reportRow.querySelector('.category-badge');
            const category = categoryBadge ? categoryBadge.textContent : '';
            const statusBadge = reportRow.querySelector('.status-badge');
            const status = statusBadge ? statusBadge.textContent : '';
            
            // Populate report details
            document.getElementById('reportIdDisplay').textContent = reportId;
            document.getElementById('reportDateDisplay').textContent = reportDate;
            document.getElementById('customerNameDisplay').textContent = customerName;
            document.getElementById('vendorNameDisplay').textContent = vendorName;
            document.getElementById('stallDisplay').textContent = stallId;
            
            // Set select dropdowns
            document.getElementById('reportStatusUpdate').value = status.toLowerCase();
            document.getElementById('reportCategoryUpdate').value = category.toLowerCase().replace(' ', '-');
            
            // Show report details container
            const reportDetailsContainer = document.querySelector('.report-details-container');
            if (reportDetailsContainer) {
                reportDetailsContainer.style.display = 'block';
            }
            
            // Scroll to details
            reportDetailsContainer.scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Close Details button
    const closeReportDetails = document.getElementById('closeReportDetails');
    if (closeReportDetails) {
        closeReportDetails.addEventListener('click', function() {
            const reportDetailsContainer = document.querySelector('.report-details-container');
            if (reportDetailsContainer) {
                reportDetailsContainer.style.display = 'none';
            }
        });
    }
    
    // Save Changes button
    const saveReportChanges = document.getElementById('saveReportChanges');
    if (saveReportChanges) {
        saveReportChanges.addEventListener('click', function() {
            const reportId = document.getElementById('reportIdDisplay').textContent;
            const newStatus = document.getElementById('reportStatusUpdate').value;
            const newCategory = document.getElementById('reportCategoryUpdate').value;
            const adminNotes = document.getElementById('adminNotesInput').value;
            
            if (!adminNotes) {
                alert('Please add notes about the changes you are making.');
                return;
            }
            
            console.log(`Saving changes to report ${reportId}:`, {
                status: newStatus,
                category: newCategory,
                notes: adminNotes
            });
            
            // Add action to list
            const actionsList = document.querySelector('.actions-list');
            if (actionsList) {
                const now = new Date();
                const formattedDate = now.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const newAction = document.createElement('li');
                newAction.innerHTML = `<span class="action-date">${formattedDate}</span> - Status updated to <strong>${newStatus}</strong>. Notes: ${adminNotes}`;
                actionsList.appendChild(newAction);
                
                // Clear notes
                document.getElementById('adminNotesInput').value = '';
                
                // Update status in table
                const reportRows = document.querySelectorAll('.report-table tbody tr');
                reportRows.forEach(row => {
                    const rowReportId = row.querySelector('td:nth-child(2)').textContent;
                    if (rowReportId === reportId) {
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = `status-badge ${newStatus}`;
                            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        }
                    }
                });
                
                alert('Changes saved successfully!');
            }
        });
    }
    
    // Contact buttons
    const contactCustomer = document.getElementById('contactCustomer');
    if (contactCustomer) {
        contactCustomer.addEventListener('click', function() {
            const customerName = document.getElementById('customerNameDisplay').textContent;
            alert(`Contacting customer: ${customerName}`);
        });
    }
    
    const contactVendor = document.getElementById('contactVendor');
    if (contactVendor) {
        contactVendor.addEventListener('click', function() {
            const vendorName = document.getElementById('vendorNameDisplay').textContent;
            alert(`Contacting vendor: ${vendorName}`);
        });
    }
    
    // Status action buttons (Investigate, Resolve, Dismiss)
    setupStatusActionButtons();
}

// Setup status action buttons
function setupStatusActionButtons() {
    // Investigate buttons
    document.querySelectorAll('.btn-icon[title="Investigate"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportRow = this.closest('tr');
            const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
            const vendorName = reportRow.querySelector('td:nth-child(5)').textContent;
            
            if (confirm(`Start investigation for report ${reportId} about vendor ${vendorName}?`)) {
                console.log(`Investigating report ${reportId}`);
                
                // Update the status badge
                const statusBadge = reportRow.querySelector('.status-badge');
                statusBadge.textContent = 'Investigating';
                statusBadge.className = 'status-badge investigating';
                
                alert(`Investigation started for report ${reportId}`);
            }
        });
    });
    
    // Mark Resolved buttons
    document.querySelectorAll('.btn-icon[title="Mark Resolved"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportRow = this.closest('tr');
            const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
            
            if (confirm(`Mark report ${reportId} as resolved?`)) {
                console.log(`Resolving report ${reportId}`);
                
                // Update the status badge
                const statusBadge = reportRow.querySelector('.status-badge');
                statusBadge.textContent = 'Resolved';
                statusBadge.className = 'status-badge resolved';
                
                alert(`Report ${reportId} has been marked as resolved`);
            }
        });
    });
    
    // Dismiss buttons
    document.querySelectorAll('.btn-icon[title="Dismiss"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportRow = this.closest('tr');
            const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
            
            if (confirm(`Dismiss report ${reportId}? This action cannot be undone without reopening the report.`)) {
                console.log(`Dismissing report ${reportId}`);
                
                // Update the status badge
                const statusBadge = reportRow.querySelector('.status-badge');
                statusBadge.textContent = 'Dismissed';
                statusBadge.className = 'status-badge dismissed';
                
                alert(`Report ${reportId} has been dismissed`);
            }
        });
    });
    
    // Reopen buttons
    document.querySelectorAll('.btn-icon[title="Reopen"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportRow = this.closest('tr');
            const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
            
            if (confirm(`Reopen report ${reportId}?`)) {
                console.log(`Reopening report ${reportId}`);
                
                // Update the status badge
                const statusBadge = reportRow.querySelector('.status-badge');
                statusBadge.textContent = 'Investigating';
                statusBadge.className = 'status-badge investigating';
                
                alert(`Report ${reportId} has been reopened and marked as investigating`);
            }
        });
    });
    
    // Archive buttons
    document.querySelectorAll('.btn-icon[title="Archive"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportRow = this.closest('tr');
            const reportId = reportRow.querySelector('td:nth-child(2)').textContent;
            
            if (confirm(`Archive report ${reportId}? This will remove it from the active reports list.`)) {
                console.log(`Archiving report ${reportId}`);
                
                // Remove the row with animation
                reportRow.style.transition = 'opacity 0.5s';
                reportRow.style.opacity = '0';
                
                setTimeout(() => {
                    reportRow.remove();
                    alert(`Report ${reportId} has been archived`);
                }, 500);
            }
        });
    });
}