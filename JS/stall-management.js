// Stall Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Only run if we're on the stall management page
    if (!document.getElementById('stall-management-section')) return;

    setupStallManagement();
});

function setupStallManagement() {
    // Setup filter functionality
    setupFilters();
    
    // Setup bulk actions
    setupBulkActions();
    
    // Setup view details functionality
    setupViewDetails();
    
    // Setup interactive map (placeholder for now)
    setupStallMap();
}

function setupFilters() {
    const sectionFilter = document.getElementById('stallSectionFilter');
    const statusFilter = document.getElementById('stallStatusFilter');
    const categoryFilter = document.getElementById('stallCategoryFilter');
    
    if (!sectionFilter || !statusFilter || !categoryFilter) return;
    
    const filters = [sectionFilter, statusFilter, categoryFilter];
    
    filters.forEach(filter => {
        filter.addEventListener('change', function() {
            applyFilters();
        });
    });
}

function applyFilters() {
    const sectionFilter = document.getElementById('stallSectionFilter').value;
    const statusFilter = document.getElementById('stallStatusFilter').value;
    const categoryFilter = document.getElementById('stallCategoryFilter').value;
    
    const rows = document.querySelectorAll('.stall-table tbody tr');
    
    rows.forEach(row => {
        const section = row.querySelector('td:nth-child(3)').textContent.toLowerCase().replace(' ', '-');
        const statusElement = row.querySelector('.status-badge');
        const status = statusElement ? statusElement.classList[1] : '';
        const category = row.querySelector('td:nth-child(4)').textContent.toLowerCase().replace(' & ', '-');
        
        const sectionMatch = sectionFilter === 'all' || section.includes(sectionFilter);
        const statusMatch = statusFilter === 'all' || status === statusFilter;
        const categoryMatch = categoryFilter === 'all' || category.includes(categoryFilter);
        
        if (sectionMatch && statusMatch && categoryMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function setupBulkActions() {
    const bulkActionSelect = document.getElementById('bulkStallActionSelect');
    const applyBtn = document.getElementById('applyBulkStallAction');
    const selectAllCheckbox = document.getElementById('selectAllStalls');
    
    if (!bulkActionSelect || !applyBtn || !selectAllCheckbox) return;
    
    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.stall-select');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    });
    
    // Apply bulk action
    applyBtn.addEventListener('click', function() {
        const action = bulkActionSelect.value;
        const selectedStalls = document.querySelectorAll('.stall-select:checked');
        
        if (action === '' || selectedStalls.length === 0) {
            alert('Please select an action and at least one stall.');
            return;
        }
        
        const stallIds = [];
        selectedStalls.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const stallId = row.querySelector('td:nth-child(2)').textContent;
            stallIds.push(stallId);
        });
        
        alert(`Action "${action}" will be applied to stalls: ${stallIds.join(', ')}`);
        
        // Reset selection after action
        bulkActionSelect.value = '';
        selectAllCheckbox.checked = false;
        document.querySelectorAll('.stall-select').forEach(checkbox => {
            checkbox.checked = false;
        });
    });
}

function setupViewDetails() {
    const viewButtons = document.querySelectorAll('.stall-table .btn-icon[title="View Details"]');
    const closeBtn = document.getElementById('closeStallDetails');
    const detailsContainer = document.querySelector('.stall-details-container');
    
    if (!detailsContainer) return;
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = button.closest('tr');
            const stallId = row.querySelector('td:nth-child(2)').textContent;
            
            // Update stall ID in details view
            document.getElementById('stallIdDisplay').textContent = stallId;
            
            // Show details container
            detailsContainer.style.display = 'block';
            
            // In a real application, you'd fetch full stall details here
            // For now, we'll just show a placeholder alert
            alert(`Viewing details for stall ${stallId}`);
        });
    });
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            detailsContainer.style.display = 'none';
        });
    }
}

function setupStallMap() {
    // Since we've removed the map, we'll repurpose this function for status management
    setupStatusActions();
}

function setupStatusActions() {
    // Add click handlers for status action buttons
    const statusButtons = document.querySelectorAll('.status-action');
    
    statusButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the stall information
            const row = this.closest('tr');
            const stallId = row.querySelector('td:nth-child(2)').textContent;
            const currentStatus = row.querySelector('.status-badge').classList[1];
            const newStatus = getNewStatus(this.getAttribute('title'), currentStatus);
            
            // Confirmation prompt
            if (confirm(`Are you sure you want to change stall ${stallId} status to ${formatStatusName(newStatus)}?`)) {
                // In a real app, this would make an API call to update the database
                updateStallStatus(row, newStatus);
            }
        });
    });
}

function getNewStatus(actionTitle, currentStatus) {
    // Determine new status based on the action button title
    if (actionTitle.includes('Available')) {
        return 'available';
    } else if (actionTitle.includes('Maintenance') || actionTitle.includes('Repair')) {
        return 'maintenance';
    } else if (actionTitle.includes('Reserved') || actionTitle.includes('Reservation')) {
        return 'reserved';
    } else if (actionTitle.includes('Occupancy') || actionTitle.includes('Occupied')) {
        return 'occupied';
    }
    
    // Default to current status if no match
    return currentStatus;
}

function formatStatusName(status) {
    switch(status) {
        case 'occupied': return 'Occupied';
        case 'available': return 'Available';
        case 'maintenance': return 'Under Maintenance';
        case 'reserved': return 'Reserved';
        default: return status.charAt(0).toUpperCase() + status.slice(1);
    }
}

function updateStallStatus(row, newStatus) {
    // Get the status cell
    const statusCell = row.querySelector('td:nth-child(7)');
    const ownerCell = row.querySelector('td:nth-child(8)');
    const contractCell = row.querySelector('td:nth-child(9)');
    const actionsCell = row.querySelector('td:nth-child(10)');
    
    // Update status badge
    const statusBadge = statusCell.querySelector('.status-badge');
    statusBadge.className = `status-badge ${newStatus}`;
    statusBadge.textContent = formatStatusName(newStatus);
    
    // Update owner and contract info depending on new status
    if (newStatus === 'available') {
        ownerCell.textContent = '—';
        contractCell.textContent = '—';
    }
    
    // Update action buttons to match the new status
    updateActionButtons(actionsCell, newStatus);
    
    // Show success message
    alert(`Stall status successfully updated to ${formatStatusName(newStatus)}`);
}

function updateActionButtons(actionsCell, status) {
    // Clear existing buttons
    const viewDetailsBtn = actionsCell.querySelector('button[title="View Details"]');
    const editBtn = actionsCell.querySelector('button[title="Edit"]');
    
    // Keep only the first two standard buttons
    actionsCell.innerHTML = '';
    actionsCell.appendChild(viewDetailsBtn);
    actionsCell.appendChild(editBtn);
    
    // Add appropriate buttons based on new status
    switch(status) {
        case 'occupied':
            addActionButton(actionsCell, 'Manage Contract', 'fa-file-contract', 'primary-action');
            addActionButton(actionsCell, 'Mark as Available', 'fa-store-slash', 'status-action');
            addActionButton(actionsCell, 'Mark Under Maintenance', 'fa-tools', 'status-action');
            break;
        case 'available':
            addActionButton(actionsCell, 'Assign Stall', 'fa-user-plus', 'primary-action');
            addActionButton(actionsCell, 'Mark as Reserved', 'fa-bookmark', 'status-action');
            addActionButton(actionsCell, 'Mark Under Maintenance', 'fa-tools', 'status-action');
            break;
        case 'maintenance':
            addActionButton(actionsCell, 'Maintenance Log', 'fa-clipboard-list', 'primary-action');
            addActionButton(actionsCell, 'Mark as Available', 'fa-check-circle', 'status-action');
            addActionButton(actionsCell, 'Schedule Repair', 'fa-hammer', 'status-action');
            break;
        case 'reserved':
            addActionButton(actionsCell, 'Application Status', 'fa-clipboard-check', 'primary-action');
            addActionButton(actionsCell, 'Confirm Occupancy', 'fa-user-check', 'status-action');
            addActionButton(actionsCell, 'Cancel Reservation', 'fa-times-circle', 'status-action');
            break;
    }
}

function addActionButton(container, title, iconClass, buttonClass) {
    const button = document.createElement('button');
    button.className = `btn-icon ${buttonClass}`;
    button.setAttribute('title', title);
    
    const icon = document.createElement('i');
    icon.className = `fas ${iconClass}`;
    
    button.appendChild(icon);
    container.appendChild(button);
    
    // Add event listener for status action buttons
    if (buttonClass === 'status-action') {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the stall information
            const row = this.closest('tr');
            const stallId = row.querySelector('td:nth-child(2)').textContent;
            const currentStatus = row.querySelector('.status-badge').classList[1];
            const newStatus = getNewStatus(title, currentStatus);
            
            // Confirmation prompt
            if (confirm(`Are you sure you want to change stall ${stallId} status to ${formatStatusName(newStatus)}?`)) {
                // In a real app, this would make an API call to update the database
                updateStallStatus(row, newStatus);
            }
        });
    }
}