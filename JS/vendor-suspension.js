// Vendor Suspension Management JavaScript
class VendorSuspensionManager {
    constructor() {
        this.suspensionModal = document.getElementById('suspensionModal');
        this.currentVendorId = null;
        this.currentVendorName = null;
        
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Suspend vendor buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.suspend-vendor-btn')) {
                const btn = e.target.closest('.suspend-vendor-btn');
                this.openSuspensionModal(
                    btn.dataset.vendorId,
                    btn.dataset.vendorName
                );
            }
        });

        // Close modal buttons
        document.getElementById('closeSuspensionModal')?.addEventListener('click', () => {
            this.closeSuspensionModal();
        });

        document.getElementById('cancelSuspension')?.addEventListener('click', () => {
            this.closeSuspensionModal();
        });

        // Modal overlay click
        document.querySelector('.suspension-modal-overlay')?.addEventListener('click', () => {
            this.closeSuspensionModal();
        });

        // Confirm suspension
        document.getElementById('confirmSuspension')?.addEventListener('click', () => {
            this.confirmSuspension();
        });

        // Duration change handler
        document.getElementById('suspensionDuration')?.addEventListener('change', (e) => {
            this.updateSuspensionSummary(e.target.value);
        });

        // Bulk actions handler
        document.getElementById('bulkActionSelect')?.addEventListener('change', (e) => {
            this.handleBulkActionChange(e.target.value);
        });

        // Apply bulk action
        document.getElementById('applyBulkAction')?.addEventListener('click', () => {
            this.applyBulkAction();
        });
    }

    openSuspensionModal(vendorId, vendorName) {
        this.currentVendorId = vendorId;
        this.currentVendorName = vendorName;

        // Update modal content
        document.getElementById('suspendVendorName').textContent = vendorName;
        document.getElementById('suspendVendorId').textContent = `ID: ${vendorId}`;

        // Reset form
        this.resetSuspensionForm();

        // Show modal
        this.suspensionModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Focus on duration select
        setTimeout(() => {
            document.getElementById('suspensionDuration')?.focus();
        }, 100);
    }

    closeSuspensionModal() {
        this.suspensionModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        this.currentVendorId = null;
        this.currentVendorName = null;
    }

    resetSuspensionForm() {
        document.getElementById('suspensionDuration').value = '';
        document.getElementById('suspensionReason').value = '';
        document.getElementById('suspensionNotes').value = '';
        document.getElementById('suspensionEndDate').textContent = '-';
        document.getElementById('suspensionStartDate').textContent = 'Immediate';
    }

    updateSuspensionSummary(duration) {
        const startDate = new Date();
        const endDateElement = document.getElementById('suspensionEndDate');
        
        let endDate;
        switch(duration) {
            case '1-day':
                endDate = new Date(startDate.getTime() + (24 * 60 * 60 * 1000));
                break;
            case '3-days':
                endDate = new Date(startDate.getTime() + (3 * 24 * 60 * 60 * 1000));
                break;
            case '1-week':
                endDate = new Date(startDate.getTime() + (7 * 24 * 60 * 60 * 1000));
                break;
            case '1-month':
                endDate = new Date(startDate.getTime() + (30 * 24 * 60 * 60 * 1000));
                break;
            case 'indefinite':
                endDateElement.textContent = 'Admin Review Required';
                return;
            default:
                endDateElement.textContent = '-';
                return;
        }

        endDateElement.textContent = endDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    confirmSuspension() {
        const duration = document.getElementById('suspensionDuration').value;
        const reason = document.getElementById('suspensionReason').value;
        const notes = document.getElementById('suspensionNotes').value;

        // Validation
        if (!duration) {
            this.showError('Please select a suspension duration.');
            return;
        }

        if (!reason) {
            this.showError('Please select a reason for suspension.');
            return;
        }

        // Create suspension data
        const suspensionData = {
            vendorId: this.currentVendorId,
            vendorName: this.currentVendorName,
            duration: duration,
            reason: reason,
            notes: notes,
            startDate: new Date().toISOString(),
            adminId: 'ADMIN001', // This would come from logged in admin
            status: 'suspended'
        };

        // Process suspension
        this.processSuspension(suspensionData);
    }

    processSuspension(suspensionData) {
        // Show loading state
        const confirmBtn = document.getElementById('confirmSuspension');
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        confirmBtn.disabled = true;

        // Simulate API call
        setTimeout(() => {
            // Update vendor status in the table
            this.updateVendorStatus(suspensionData.vendorId, 'suspended');
            
            // Log suspension action
            console.log('Vendor suspended:', suspensionData);
            
            // Show success message
            this.showSuccessMessage(`${suspensionData.vendorName} has been suspended for ${this.formatDuration(suspensionData.duration)}.`);
            
            // Close modal
            this.closeSuspensionModal();
            
            // Reset button
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;

            // Send notification (simulate)
            this.sendSuspensionNotification(suspensionData);
            
        }, 1500);
    }

    updateVendorStatus(vendorId, status) {
        // Find the vendor row and update the status
        const rows = document.querySelectorAll('.vendor-table tbody tr');
        rows.forEach(row => {
            const idCell = row.querySelector('td:nth-child(2)');
            if (idCell && idCell.textContent === vendorId) {
                const statusCell = row.querySelector('.status-badge');
                if (statusCell) {
                    statusCell.className = `status-badge ${status}`;
                    statusCell.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                }
                
                // Update action buttons
                const actionButtons = row.querySelector('.action-buttons');
                if (actionButtons && status === 'suspended') {
                    actionButtons.innerHTML = `
                        <button class="btn-icon" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon" title="Activate"><i class="fas fa-play"></i></button>
                        <button class="btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                    `;
                }
            }
        });
    }

    handleBulkActionChange(action) {
        const bulkDurationSelect = document.getElementById('bulkSuspensionDuration');
        if (action === 'suspend') {
            bulkDurationSelect.style.display = 'inline-block';
        } else {
            bulkDurationSelect.style.display = 'none';
        }
    }

    applyBulkAction() {
        const action = document.getElementById('bulkActionSelect').value;
        const selectedVendors = document.querySelectorAll('.vendor-select:checked');
        
        if (!action) {
            this.showError('Please select an action.');
            return;
        }

        if (selectedVendors.length === 0) {
            this.showError('Please select at least one vendor.');
            return;
        }

        if (action === 'suspend') {
            const duration = document.getElementById('bulkSuspensionDuration').value;
            if (!duration) {
                this.showError('Please select a suspension duration.');
                return;
            }
            
            this.processBulkSuspension(selectedVendors, duration);
        } else {
            this.processBulkAction(action, selectedVendors);
        }
    }

    processBulkSuspension(selectedVendors, duration) {
        const count = selectedVendors.length;
        const confirmation = confirm(`Are you sure you want to suspend ${count} vendor(s) for ${this.formatDuration(duration)}?`);
        
        if (confirmation) {
            selectedVendors.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const vendorId = row.querySelector('td:nth-child(2)').textContent;
                this.updateVendorStatus(vendorId, 'suspended');
            });
            
            this.showSuccessMessage(`${count} vendor(s) have been suspended for ${this.formatDuration(duration)}.`);
            this.resetBulkActions();
        }
    }

    processBulkAction(action, selectedVendors) {
        const count = selectedVendors.length;
        const actionText = action.charAt(0).toUpperCase() + action.slice(1);
        const confirmation = confirm(`Are you sure you want to ${action} ${count} vendor(s)?`);
        
        if (confirmation) {
            selectedVendors.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const vendorId = row.querySelector('td:nth-child(2)').textContent;
                this.updateVendorStatus(vendorId, action === 'activate' ? 'active' : action);
            });
            
            this.showSuccessMessage(`${count} vendor(s) have been ${action}d.`);
            this.resetBulkActions();
        }
    }

    resetBulkActions() {
        document.getElementById('bulkActionSelect').value = '';
        document.getElementById('bulkSuspensionDuration').style.display = 'none';
        document.querySelectorAll('.vendor-select:checked').forEach(checkbox => {
            checkbox.checked = false;
        });
    }

    formatDuration(duration) {
        const durations = {
            '1-day': '1 day',
            '3-days': '3 days',
            '1-week': '1 week',
            '1-month': '1 month',
            'indefinite': 'indefinitely'
        };
        return durations[duration] || duration;
    }

    sendSuspensionNotification(suspensionData) {
        // Simulate sending email/SMS notification
        console.log(`Notification sent to ${suspensionData.vendorName}:
            - Email: vendor@example.com
            - SMS: Vendor suspended for ${this.formatDuration(suspensionData.duration)}
            - Reason: ${suspensionData.reason}
        `);
    }

    showError(message) {
        alert(message); // In a real app, you'd use a proper toast/notification system
    }

    showSuccessMessage(message) {
        // Create a temporary success notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            z-index: 11000;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
            animation: slideInRight 0.3s ease;
        `;
        notification.innerHTML = `
            <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Initialize the suspension manager when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.vendorSuspensionManager = new VendorSuspensionManager();
});