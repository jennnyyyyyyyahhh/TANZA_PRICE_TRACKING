/**
 * Survey Form JavaScript
 * This file handles the functionality for the commodity survey form
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("Survey form script loaded");
    
    // Initialize survey form functionality
    initSurveyForm();
    
    // Special handling for fruits and vegetables buttons
    const fruitButton = document.querySelector('button[data-category="fruits"]');
    const vegetableButton = document.querySelector('button[data-category="vegetables"]');
    
    if (fruitButton) {
        console.log("Found fruits button:", fruitButton);
    } else {
        console.log("Fruits button not found!");
    }
    
    if (vegetableButton) {
        console.log("Found vegetables button:", vegetableButton);
    } else {
        console.log("Vegetables button not found!");
    }
});

/**
 * Initialize the Survey Form
 */
function initSurveyForm() {
    // Add click events for category tabs if implemented
    setupCategoryTabs();
    
    // Setup add item functionality for each commodity category
    setupAddItemButtons();
    
    // Setup explicit handling for fruits and vegetables
    setupSpecificCategoryButtons();
    
    // Setup remove item functionality
    setupRemoveButtons();
    
    // Setup add category functionality
    setupAddCategoryButton();
    
    // Setup form submission
    setupFormSubmission();
}

/**
 * Setup explicit handlers for fruits and vegetables buttons
 */
function setupSpecificCategoryButtons() {
    // Try different selectors for the fruit button
    const fruitSelectors = [
        'button[data-category="fruits"]',
        '.add-commodity-item[data-category="fruits"]',
        'button.add-item-btn[data-category="fruits"]',
        '#fruitItems + button', // Button right after fruitItems
        '.commodity-category:nth-child(2) button' // Second category button (often fruits)
    ];
    
    // Try different selectors for the vegetable button
    const vegetableSelectors = [
        'button[data-category="vegetables"]',
        '.add-commodity-item[data-category="vegetables"]',
        'button.add-item-btn[data-category="vegetables"]',
        '#vegetableItems + button', // Button right after vegetableItems
        '.commodity-category:nth-child(3) button' // Third category button (often vegetables)
    ];
    
    // Find and set up fruit button
    let addFruitBtn = null;
    for (let selector of fruitSelectors) {
        addFruitBtn = document.querySelector(selector);
        if (addFruitBtn) break;
    }
    
    if (addFruitBtn) {
        console.log("Found fruit button using selector:", addFruitBtn);
        addFruitBtn.addEventListener('click', function() {
            console.log("Fruit button clicked");
            addItemToCategory('fruits', 'fruitItems');
        });
    } else {
        console.error("Could not find fruit button with any selector");
    }
    
    // Find and set up vegetable button
    let addVegetableBtn = null;
    for (let selector of vegetableSelectors) {
        addVegetableBtn = document.querySelector(selector);
        if (addVegetableBtn) break;
    }
    
    if (addVegetableBtn) {
        console.log("Found vegetable button using selector:", addVegetableBtn);
        addVegetableBtn.addEventListener('click', function() {
            console.log("Vegetable button clicked");
            addItemToCategory('vegetables', 'vegetableItems');
        });
    } else {
        console.error("Could not find vegetable button with any selector");
    }
}

/**
 * Add item to a specific category
 * @param {string} category - The category name
 * @param {string} containerId - The ID of the container for items
 */
function addItemToCategory(category, containerId) {
    const itemsContainer = document.getElementById(containerId);
    if (!itemsContainer) {
        console.error(`Container #${containerId} not found`);
        return;
    }
    
    // Clone the first item in this category as a template
    const template = itemsContainer.querySelector('.commodity-item');
    if (!template) {
        console.error(`No template found in #${containerId}`);
        return;
    }
    
    const newItem = template.cloneNode(true);
    
    // Clear input values in the cloned item
    newItem.querySelectorAll('input').forEach(input => {
        input.value = '';
    });
    
    // Reset selects to first option
    newItem.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
    
    // Clear textarea
    const textarea = newItem.querySelector('textarea');
    if (textarea) textarea.value = '';
    
    // Add the new item to the container
    itemsContainer.appendChild(newItem);
    
    // Setup remove button for the new item
    setupRemoveButton(newItem.querySelector('.remove-btn'));
    
    // Scroll to the new item for better UX
    newItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Add a highlight effect to the new item
    newItem.classList.add('highlight-new');
    setTimeout(() => {
        newItem.classList.remove('highlight-new');
    }, 1000);
    
    console.log(`Successfully added new ${category} item`);
}

/**
 * Setup the category tabs if implemented
 */
function setupCategoryTabs() {
    const categoryTabs = document.querySelectorAll('.category-tab');
    if (categoryTabs.length > 0) {
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                categoryTabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all form sections
                const formSections = document.querySelectorAll('.form-section');
                formSections.forEach(section => section.classList.remove('active'));
                
                // Show the selected form section
                const targetSection = document.querySelector(this.dataset.target);
                if (targetSection) targetSection.classList.add('active');
            });
        });
        
        // Activate the first tab by default
        if (categoryTabs[0]) {
            categoryTabs[0].click();
        }
    }
}

/**
 * Setup add item buttons for each commodity category
 */
function setupAddItemButtons() {
    // Target both old class (.add-item-btn) and the class from HTML (.add-commodity-item)
    const addButtons = document.querySelectorAll('.add-item-btn, .add-commodity-item');
    
    addButtons.forEach(button => {
        // Skip if this button already has a click handler
        if (button.hasAttribute('data-initialized')) {
            return;
        }
        
        button.setAttribute('data-initialized', 'true');
        
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            // Try both ID formats - with and without "Items" suffix
            let itemsContainer = document.getElementById(`${category}Items`);
            
            // If not found, try the direct selector based on category attribute
            if (!itemsContainer) {
                const categoryElement = document.querySelector(`#${category}`);
                if (categoryElement) {
                    itemsContainer = categoryElement.querySelector('.commodity-items');
                }
            }
            
            if (itemsContainer) {
                // Clone the first item in this category as a template
                const template = itemsContainer.querySelector('.commodity-item');
                if (template) {
                    const newItem = template.cloneNode(true);
                    
                    // Clear input values in the cloned item
                    newItem.querySelectorAll('input').forEach(input => {
                        input.value = '';
                    });
                    
                    // Reset selects to first option
                    newItem.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });
                    
                    // Clear textarea
                    const textarea = newItem.querySelector('textarea');
                    if (textarea) textarea.value = '';
                    
                    // Add the new item to the container
                    itemsContainer.appendChild(newItem);
                    
                    // Setup remove button for the new item
                    setupRemoveButton(newItem.querySelector('.remove-btn'));
                    
                    // Scroll to the new item for better UX
                    newItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Add a highlight effect to the new item
                    newItem.classList.add('highlight-new');
                    setTimeout(() => {
                        newItem.classList.remove('highlight-new');
                    }, 1000);
                    
                    // Log success message
                    console.log(`Added new ${category} item`);
                } else {
                    console.error(`No template found for ${category}`);
                }
            } else {
                console.error(`Container not found for ${category}`);
            }
        });
    });
}

/**
 * Setup the Add Category button functionality
 */
function setupAddCategoryButton() {
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    if (!addCategoryBtn) return;
    
    addCategoryBtn.addEventListener('click', function() {
        showAddCategoryModal();
    });
}

/**
 * Show the modal for adding a new category
 */
function showAddCategoryModal() {
    // Create modal container
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'categoryModal';
    
    // Create modal content
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Commodity Category</h3>
                <button type="button" class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="categoryName">Category Name:</label>
                    <input type="text" id="categoryName" placeholder="Enter category name (e.g., Dairy, Meat)" required>
                </div>
                <div class="form-group">
                    <label for="categoryItems">Common Items:</label>
                    <textarea id="categoryItems" placeholder="Enter common items for this category, separated by commas (e.g., Milk, Cheese, Yogurt)" rows="4"></textarea>
                    <small>These will be used as options in the dropdown.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelCategoryBtn">Cancel</button>
                <button type="button" class="btn-primary" id="saveCategoryBtn">Add Category</button>
            </div>
        </div>
    `;
    
    // Add to document
    document.body.appendChild(modal);
    
    // Focus on input
    setTimeout(() => {
        document.getElementById('categoryName').focus();
    }, 100);
    
    // Setup event listeners
    const closeBtn = modal.querySelector('.close-modal-btn');
    const cancelBtn = document.getElementById('cancelCategoryBtn');
    const saveBtn = document.getElementById('saveCategoryBtn');
    
    const closeModal = () => {
        modal.classList.add('fade-out');
        setTimeout(() => {
            modal.remove();
        }, 200);
    };
    
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    
    // Save button functionality
    saveBtn.addEventListener('click', function() {
        const categoryName = document.getElementById('categoryName').value.trim();
        const categoryItems = document.getElementById('categoryItems').value.trim();
        
        if (categoryName) {
            addNewCategory(categoryName, categoryItems);
            closeModal();
        } else {
            // Show validation error
            document.getElementById('categoryName').classList.add('error');
            document.getElementById('categoryName').focus();
            // Remove error class after a while
            setTimeout(() => {
                document.getElementById('categoryName').classList.remove('error');
            }, 2000);
        }
    });
    
    // Allow pressing Enter to submit
    document.getElementById('categoryName').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            saveBtn.click();
        }
    });
}

/**
 * Add a new category to the survey form
 */
function addNewCategory(categoryName, itemsText) {
    // Create safe ID from category name
    const categoryId = categoryName.toLowerCase().replace(/[^a-z0-9]/g, '-');
    
    // Parse items from comma-separated list
    const items = itemsText ? itemsText.split(',').map(item => item.trim()).filter(item => item) : [];
    
    // Create the category HTML
    const categoryDiv = document.createElement('div');
    categoryDiv.className = 'commodity-category';
    
    categoryDiv.innerHTML = `
        <h3>${categoryName}</h3>
        <div class="commodity-items" id="${categoryId}Items">
            <div class="commodity-item">
                <div class="item-header">
                    <div class="item-name">
                        <select class="commodity-select">
                            <option value="">Select ${categoryName} Type</option>
                            ${items.map(item => `<option value="${item.toLowerCase().replace(/\s+/g, '-')}">${item}</option>`).join('')}
                        </select>
                    </div>
                    <div class="item-variant">
                        <input type="text" placeholder="Variant/Brand (optional)" class="variant-input">
                    </div>
                    <div class="remove-item">
                        <button type="button" class="remove-btn"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="item-details">
                    <div class="price-input">
                        <label>Price (₱):</label>
                        <input type="number" min="0" step="0.01" placeholder="0.00" class="price-field">
                    </div>
                    <div class="quantity-input">
                        <label>Quantity Unit:</label>
                        <select class="unit-select">
                            <option value="per-kilo">Per Kilo</option>
                            <option value="per-piece">Per Piece</option>
                            <option value="per-dozen">Per Dozen</option>
                            <option value="per-pack">Per Pack</option>
                        </select>
                    </div>
                    <div class="quality-input">
                        <label>Quality:</label>
                        <select class="quality-select">
                            <option value="excellent">Excellent</option>
                            <option value="good" selected>Good</option>
                            <option value="average">Average</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                </div>
                <div class="item-notes">
                    <label>Notes:</label>
                    <textarea placeholder="Additional notes about this item"></textarea>
                </div>
            </div>
        </div>
        <button type="button" class="add-item-btn" data-category="${categoryId}">
            <i class="fas fa-plus"></i> Add ${categoryName} Item
        </button>
    `;
    
    // Insert before the Add Category button
    const addCategoryContainer = document.querySelector('.add-category-container');
    const form = document.getElementById('marketPriceSurveyForm');
    form.insertBefore(categoryDiv, addCategoryContainer);
    
    // Setup the new remove buttons
    setupRemoveButtons();
    
    // Setup the new add item button
    setupAddItemButtons();
    
    // Highlight the new category
    categoryDiv.classList.add('highlight-new');
    setTimeout(() => {
        categoryDiv.classList.remove('highlight-new');
    }, 1000);
    
    // Scroll to new category
    categoryDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Setup remove buttons for all commodity items
 */
function setupRemoveButtons() {
    document.querySelectorAll('.remove-btn').forEach(button => {
        setupRemoveButton(button);
    });
}

/**
 * Setup a single remove button
 */
function setupRemoveButton(button) {
    if (!button) return;
    
    // Skip if already initialized
    if (button.hasAttribute('data-initialized')) {
        return;
    }
    
    button.setAttribute('data-initialized', 'true');
    
    button.addEventListener('click', function() {
        const item = this.closest('.commodity-item');
        if (!item) {
            console.error("Could not find parent commodity-item");
            return;
        }
        
        const container = item.parentElement;
        if (!container) {
            console.error("Could not find container for the item");
            return;
        }
        
        // Only remove if this is not the last item in the container
        if (container.querySelectorAll('.commodity-item').length > 1) {
            // Add fade-out animation
            item.style.opacity = '0';
            item.style.transform = 'scale(0.9)';
            item.style.transition = 'all 0.2s ease';
            
            setTimeout(() => {
                item.remove();
                console.log("Item removed");
            }, 200);
        } else {
            // If last item, just reset the values
            console.log("Resetting last item instead of removing");
            item.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
            item.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            const textarea = item.querySelector('textarea');
            if (textarea) textarea.value = '';
            
            // Show brief highlight to indicate reset
            item.style.backgroundColor = 'rgba(255, 193, 7, 0.2)';
            setTimeout(() => {
                item.style.transition = 'background-color 1s ease';
                item.style.backgroundColor = '';
            }, 500);
        }
    });
}

/**
 * Setup form submission
 */
function setupFormSubmission() {
    const form = document.getElementById('marketPriceSurveyForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate the form
            if (validateSurveyForm()) {
                // Collect form data
                const formData = collectFormData();
                
                // Here you would typically send the data to a server
                console.log('Survey form data:', formData);
                
                // Show success message
                showFormSubmissionResult(true, 'Survey data submitted successfully!');
            }
        });
    }
}

/**
 * Validate the survey form
 * @returns {boolean} - Whether the form is valid
 */
function validateSurveyForm() {
    const form = document.getElementById('marketPriceSurveyForm');
    let isValid = true;
    let errorMessages = [];
    
    // Check required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value) {
            field.classList.add('error');
            
            // Animate the error field
            field.style.animation = 'none';
            setTimeout(() => {
                field.style.animation = 'shake 0.4s linear';
            }, 10);
            
            const fieldLabel = field.previousElementSibling ? field.previousElementSibling.textContent.replace(':', '') : 'Field';
            errorMessages.push(`${fieldLabel} is required`);
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    // Specifically check fruits and vegetables
    const fruitCategory = document.getElementById('fruitItems')?.closest('.commodity-category');
    const vegetableCategory = document.getElementById('vegetableItems')?.closest('.commodity-category');
    
    // Array of special categories to validate
    const specialCategories = [
        { element: fruitCategory, name: 'Fruits', id: 'fruitItems' },
        { element: vegetableCategory, name: 'Vegetables', id: 'vegetableItems' }
    ];
    
    // Validate special categories first
    specialCategories.forEach(specialCat => {
        if (specialCat.element) {
            let categoryValid = validateCategory(specialCat.element, specialCat.name, errorMessages);
            if (!categoryValid) isValid = false;
        }
    });
    
    // For each other commodity category, check that there's at least one item with complete information
    const categories = document.querySelectorAll('.commodity-category');
    categories.forEach(category => {
        // Skip already validated special categories
        if (category === fruitCategory || category === vegetableCategory) return;
        
        const categoryName = category.querySelector('h3').textContent;
        let categoryValid = validateCategory(category, categoryName, errorMessages);
        if (!categoryValid) isValid = false;
    });
    
    // Validate numeric values
    const numericInputs = form.querySelectorAll('input[type="number"]');
    numericInputs.forEach(input => {
        if (input.value && (isNaN(parseFloat(input.value)) || parseFloat(input.value) < 0)) {
            input.classList.add('error');
            errorMessages.push('Price must be a positive number');
            isValid = false;
        }
    });
    
    /**
     * Helper function to validate a category
     * @param {Element} category - Category element to validate
     * @param {string} categoryName - Name of the category 
     * @param {Array} errorMessages - Array to collect error messages
     * @returns {boolean} - Is valid or not
     */
    function validateCategory(category, categoryName, errorMessages) {
        if (!category) return true;
        
        const items = category.querySelectorAll('.commodity-item');
        let categoryHasCompleteItem = false;
        let incompleteItems = [];
        
        items.forEach((item, index) => {
            const commoditySelect = item.querySelector('.commodity-select');
            const priceField = item.querySelector('.price-field');
            
            if (commoditySelect && priceField) {
                if (commoditySelect.value && priceField.value) {
                    categoryHasCompleteItem = true;
                    item.classList.remove('error');
                } else if (commoditySelect.value || priceField.value) {
                    // If partially filled, highlight as error
                    item.classList.add('error');
                    
                    // Highlight specific missing fields
                    if (!commoditySelect.value) {
                        commoditySelect.classList.add('error');
                    }
                    
                    if (!priceField.value) {
                        priceField.classList.add('error');
                    }
                    
                    incompleteItems.push(index + 1);
                }
            }
        });
        
        if (!categoryHasCompleteItem) {
            // Highlight the category
            category.classList.add('error');
            
            // Animate the error
            category.style.animation = 'none';
            setTimeout(() => {
                category.style.animation = 'shake 0.4s linear';
            }, 10);
            
            errorMessages.push(`Please complete at least one item in the ${categoryName} category`);
            
            if (incompleteItems.length > 0) {
                errorMessages.push(`Items ${incompleteItems.join(', ')} in ${categoryName} are incomplete`);
            }
            
            return false;
        } else {
            category.classList.remove('error');
            return true;
        }
    }
    
    // If not valid, show error message
    if (!isValid) {
        // Display a more specific error message if we have details
        if (errorMessages.length > 0) {
            // Show at most 3 errors
            const displayMessages = errorMessages.slice(0, 3);
            
            // If there are more, add a count
            if (errorMessages.length > 3) {
                displayMessages.push(`...and ${errorMessages.length - 3} more issues`);
            }
            
            showFormSubmissionResult(false, displayMessages.join('<br>'));
            
            // Scroll to first error element
            const firstError = form.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Focus if it's an input
                if (firstError.tagName === 'INPUT' || firstError.tagName === 'SELECT') {
                    firstError.focus();
                }
            }
        } else {
            showFormSubmissionResult(false, 'Please fill in all required fields and ensure at least one item in each category has both a type and price.');
        }
    }
    
    return isValid;
}

/**
 * Show a result message after form submission or action
 * @param {boolean} success - Whether the action was successful
 * @param {string} message - The message to display
 */
function showFormSubmissionResult(success, message) {
    // Remove any existing message
    const existingMessage = document.getElementById('formResultMessage');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create message element
    const resultMessage = document.createElement('div');
    resultMessage.id = 'formResultMessage';
    resultMessage.className = success ? 'form-result success' : 'form-result error';
    
    // Add icon based on success/error
    const icon = success ? 
        '<i class="fas fa-check-circle"></i>' : 
        '<i class="fas fa-exclamation-circle"></i>';
        
    resultMessage.innerHTML = `
        <div class="result-icon">${icon}</div>
        <div class="result-message">${message}</div>
        <button type="button" class="close-result">&times;</button>
    `;
    
    // Add to form
    const form = document.getElementById('marketPriceSurveyForm');
    form.appendChild(resultMessage);
    
    // Add close button functionality
    const closeBtn = resultMessage.querySelector('.close-result');
    closeBtn.addEventListener('click', () => {
        resultMessage.remove();
    });
    
    // Auto-remove success messages after 5 seconds
    if (success) {
        setTimeout(() => {
            if (resultMessage.parentNode) {
                resultMessage.classList.add('fade-out');
                setTimeout(() => {
                    if (resultMessage.parentNode) {
                        resultMessage.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
    
    // Scroll to message
    resultMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Collect all form data into a structured object
 * @returns {Object} - The collected form data
 */
function collectFormData() {
    const form = document.getElementById('marketPriceSurveyForm');
    const formData = {
        surveyDate: document.getElementById('surveyDate').value,
        marketLocation: document.getElementById('marketLocation').value,
        vendorName: document.getElementById('vendorName').value,
        surveyType: document.getElementById('surveyType').value,
        commodities: {}
    };
    
    // Collect data from each commodity category
    const categories = document.querySelectorAll('.commodity-category');
    categories.forEach(category => {
        const categoryName = category.querySelector('h3').textContent.toLowerCase();
        formData.commodities[categoryName] = [];
        
        // Collect data from each item in this category
        const items = category.querySelectorAll('.commodity-item');
        items.forEach(item => {
            const commoditySelect = item.querySelector('.commodity-select');
            const variantInput = item.querySelector('.variant-input');
            const priceField = item.querySelector('.price-field');
            const unitSelect = item.querySelector('.unit-select');
            const qualitySelect = item.querySelector('.quality-select');
            const notes = item.querySelector('textarea');
            
            // Only add items that have at least a commodity type
            if (commoditySelect && commoditySelect.value) {
                formData.commodities[categoryName].push({
                    type: commoditySelect.value,
                    variant: variantInput ? variantInput.value : '',
                    price: priceField ? priceField.value : '',
                    unit: unitSelect ? unitSelect.value : '',
                    quality: qualitySelect ? qualitySelect.value : '',
                    notes: notes ? notes.value : ''
                });
            }
        });
    });
    
    return formData;
}

/**
 * Show the form submission result
 * @param {boolean} success - Whether the submission was successful
 * @param {string} message - The message to display
 */
function showFormSubmissionResult(success, message) {
    // Remove any existing result messages
    const existingMessage = document.querySelector('.form-result-message');
    if (existingMessage) existingMessage.remove();
    
    // Create new result message
    const resultDiv = document.createElement('div');
    resultDiv.className = `form-result-message ${success ? 'success' : 'error'}`;
    resultDiv.textContent = message;
    
    // Add to the form
    const form = document.getElementById('marketPriceSurveyForm');
    form.appendChild(resultDiv);
    
    // Scroll to the message
    resultDiv.scrollIntoView({ behavior: 'smooth' });
    
    // Remove the message after a few seconds if it's a success message
    if (success) {
        setTimeout(() => {
            resultDiv.remove();
        }, 5000);
    }
}