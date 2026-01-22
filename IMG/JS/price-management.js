// Price Management JavaScript for Admin Dashboard
class PriceManager {
    constructor() {
        // Wait for price data manager to be available
        if (typeof window.priceDataManager === 'undefined') {
            console.error('PriceDataManager not loaded. Please include price-data.js before price-management.js');
            return;
        }
        
        this.dataManager = window.priceDataManager;
        this.currentPrices = this.loadPricesFromStorage();
        this.currentEditingIndex = -1;
        this.initializeEventListeners();
        this.updateAllPreviews();
        this.setupRealTimeUpdates();
    }

    // Load prices from the shared data manager
    loadPricesFromStorage() {
        return this.dataManager.getStoredPrices() || [];
    }

    // Save prices using the shared data manager
    savePricesToStorage() {
        return this.dataManager.savePrices(this.currentPrices);
    }

    // Setup real-time updates from other windows/tabs
    setupRealTimeUpdates() {
        this.dataManager.onPriceUpdate((updatedPrices) => {
            this.currentPrices = updatedPrices || [];
            this.updateAllPreviews();
            this.showNotification('Prices updated from another session', 'info');
        });
    }

    // Initialize all event listeners
    initializeEventListeners() {
        // Edit buttons for each price card
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-edit-price')) {
                const index = parseInt(e.target.closest('.btn-edit-price').dataset.index);
                this.openEditForm(index);
            }
        });

        // Close form button
        const closeBtn = document.getElementById('closePriceForm');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeEditForm());
        }

        // Cancel button
        const cancelBtn = document.getElementById('cancelEditPrice');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.closeEditForm());
        }

        // Form submission
        const form = document.getElementById('priceEditForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Real-time updates for form inputs
        this.setupRealTimeUpdates();

        // Image upload handling
        this.setupImageUpload();
    }

    // Setup real-time form updates
    setupRealTimeUpdates() {
        const inputs = ['commodityName', 'oldPrice', 'newPrice', 'priceUnit'];
        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', () => this.updateLivePreview());
            }
        });
    }

    // Setup image upload functionality
    setupImageUpload() {
        const fileInput = document.getElementById('productImage');
        const preview = document.getElementById('imagePreview');

        if (fileInput && preview) {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Product Image">`;
                        preview.classList.add('has-image');
                        this.updateLivePreview();
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Open edit form for specific price card
    openEditForm(index) {
        this.currentEditingIndex = index;
        const price = this.currentPrices[index];
        
        // Show the form section
        const formSection = document.querySelector('.price-edit-form-section');
        if (formSection) {
            formSection.style.display = 'block';
        }

        // Update form header
        const cardNumber = document.getElementById('editingCardNumber');
        if (cardNumber) {
            cardNumber.textContent = index + 1;
        }

        // Populate form fields
        this.populateForm(price);
        
        // Update live preview
        this.updateLivePreview();

        // Scroll to form
        formSection.scrollIntoView({ behavior: 'smooth' });
    }

    // Close edit form
    closeEditForm() {
        const formSection = document.querySelector('.price-edit-form-section');
        if (formSection) {
            formSection.style.display = 'none';
        }
        this.currentEditingIndex = -1;
        this.resetForm();
    }

    // Populate form with price data
    populateForm(price) {
        const fields = {
            'commodityName': price.name,
            'oldPrice': price.oldPrice,
            'newPrice': price.newPrice,
            'priceUnit': price.unit
        };

        Object.entries(fields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value;
            }
        });

        // Handle image preview
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview && price.image) {
            imagePreview.innerHTML = `<img src="${price.image}" alt="Product Image">`;
            imagePreview.classList.add('has-image');
        }
    }

    // Reset form to empty state
    resetForm() {
        const form = document.getElementById('priceEditForm');
        if (form) {
            form.reset();
        }

        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview) {
            imagePreview.innerHTML = '<i class="fas fa-image"></i><span>Upload Image</span>';
            imagePreview.classList.remove('has-image');
        }
    }

    // Update live preview in form
    updateLivePreview() {
        const name = document.getElementById('commodityName')?.value || 'Product Name';
        const oldPrice = parseFloat(document.getElementById('oldPrice')?.value) || 0;
        const newPrice = parseFloat(document.getElementById('newPrice')?.value) || 0;
        const unit = document.getElementById('priceUnit')?.value || 'kg';

        // Calculate savings
        const savingsAmount = oldPrice - newPrice;
        const savingsPercentage = oldPrice > 0 ? Math.round((savingsAmount / oldPrice) * 100) : 0;

        // Update savings display
        const savingsAmountEl = document.getElementById('savingsAmount');
        const savingsPercentageEl = document.getElementById('savingsPercentage');
        
        if (savingsAmountEl) savingsAmountEl.textContent = `₱${savingsAmount.toFixed(2)}`;
        if (savingsPercentageEl) savingsPercentageEl.textContent = `${savingsPercentage}%`;

        // Update live preview card
        const previewName = document.getElementById('previewProductName');
        const previewOldPrice = document.getElementById('previewOldPrice');
        const previewNewPrice = document.getElementById('previewNewPrice');
        const previewSavings = document.getElementById('previewSavings');
        const previewImage = document.getElementById('previewImage');

        if (previewName) previewName.textContent = name;
        if (previewOldPrice) previewOldPrice.textContent = `₱${oldPrice}/${unit}`;
        if (previewNewPrice) previewNewPrice.textContent = `₱${newPrice}/${unit}`;
        if (previewSavings) previewSavings.textContent = `Save ${savingsPercentage}%`;

        // Update preview image if there's one in the image preview
        const imagePreview = document.getElementById('imagePreview');
        const imageElement = imagePreview?.querySelector('img');
        if (imageElement && previewImage) {
            previewImage.src = imageElement.src;
        }
    }

    // Handle form submission
    handleFormSubmit(e) {
        e.preventDefault();
        
        if (this.currentEditingIndex === -1) return;

        // Get form data
        const formData = this.getFormData();
        
        // Validate form data using the data manager
        const validation = this.dataManager.validatePriceData(formData);
        if (!validation.isValid) {
            alert('Validation errors:\n' + validation.errors.join('\n'));
            return;
        }

        // Update the price data
        this.currentPrices[this.currentEditingIndex] = {
            ...this.currentPrices[this.currentEditingIndex],
            ...formData
        };
        
        // Save using the shared data manager (this will trigger updates automatically)
        const success = this.savePricesToStorage();
        
        if (success) {
            // Update preview cards
            this.updateAllPreviews();
            
            // Close form
            this.closeEditForm();
            
            // Show success message
            this.showNotification('Price card updated successfully! Changes are now live on the website.', 'success');
        } else {
            this.showNotification('Failed to save price changes. Please try again.', 'error');
        }
    }

    // Get form data
    getFormData() {
        const imagePreview = document.getElementById('imagePreview');
        const imageElement = imagePreview?.querySelector('img');
        
        return {
            name: document.getElementById('commodityName').value.trim(),
            oldPrice: parseFloat(document.getElementById('oldPrice').value),
            newPrice: parseFloat(document.getElementById('newPrice').value),
            unit: document.getElementById('priceUnit').value,
            image: imageElement ? imageElement.src : this.currentPrices[this.currentEditingIndex]?.image
        };
    }

    // Get formatted savings display
    getSavingsDisplay(oldPrice, newPrice) {
        const amount = this.dataManager.calculateSavingsAmount(oldPrice, newPrice);
        const percentage = this.dataManager.calculateSavings(oldPrice, newPrice);
        return { amount, percentage };
    }

    // Update all preview cards
    updateAllPreviews() {
        this.currentPrices.forEach((price, index) => {
            this.updatePreviewCard(index, price);
        });
    }

    // Update specific preview card
    updatePreviewCard(index, price) {
        const card = document.querySelector(`[data-index="${index}"]`);
        if (!card) return;

        const savingsData = this.getSavingsDisplay(price.oldPrice, price.newPrice);

        // Update image
        const img = card.querySelector('.preview-icon');
        if (img) img.src = price.image;

        // Update text content
        const name = card.querySelector('.preview-name');
        if (name) name.textContent = price.name;

        const oldPrice = card.querySelector('.preview-old-price');
        if (oldPrice) oldPrice.textContent = this.dataManager.formatPrice(price.oldPrice, price.unit);

        const newPrice = card.querySelector('.preview-new-price');
        if (newPrice) newPrice.textContent = this.dataManager.formatPrice(price.newPrice, price.unit);

        const savings = card.querySelector('.preview-savings');
        if (savings) savings.textContent = `Save ${savingsData.percentage}%`;
    }

    // Update landing page (cross-document communication)
    updateLandingPage() {
        // Store updated prices in localStorage for landing page to pick up
        localStorage.setItem('animatedPricesUpdated', 'true');
        
        // If we can access the landing page window (same origin), update it directly
        try {
            const landingWindow = window.parent;
            if (landingWindow && landingWindow.updateAnimatedPrices) {
                landingWindow.updateAnimatedPrices(this.currentPrices);
            }
        } catch (e) {
            console.log('Cross-window communication not available, using localStorage method');
        }
    }

    // Show notification message
    showNotification(message, type = 'success') {
        const colors = {
            success: '#27ae60',
            error: '#e74c3c',
            info: '#3498db',
            warning: '#f39c12'
        };

        // Create a temporary notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type] || colors.success};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            z-index: 10000;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        if (!document.head.querySelector('style[data-notifications]')) {
            style.setAttribute('data-notifications', 'true');
            document.head.appendChild(style);
        }
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Navigation handling for price management section
function setupPriceManagementNavigation() {
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    const contentSections = document.querySelectorAll('.content-section');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            const targetId = link.getAttribute('href').substring(1) + '-section';
            
            // Remove active class from all nav items and sections
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            contentSections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked nav item
            link.parentElement.classList.add('active');
            
            // Show target section
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Setup navigation
    setupPriceManagementNavigation();
    
    // Initialize price manager
    window.priceManager = new PriceManager();
});