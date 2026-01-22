// Shared Price Data Management
// This module handles price data synchronization between admin dashboard and landing page

class PriceDataManager {
    constructor() {
        this.storageKey = 'farmfresh_animated_prices';
        this.updateEventKey = 'farmfresh_price_updated';
        this.initializeDefaultData();
    }

    // Initialize with default price data
    initializeDefaultData() {
        if (!this.getStoredPrices()) {
            const defaultPrices = [
                {
                    id: 1,
                    name: 'Farm Tomatoes',
                    oldPrice: 85,
                    newPrice: 65,
                    unit: 'kg',
                    image: '../IMG/tomato-icon.png',
                    lastUpdated: new Date().toISOString()
                },
                {
                    id: 2,
                    name: 'Fresh Lettuce',
                    oldPrice: 55,
                    newPrice: 40,
                    unit: 'kg',
                    image: '../IMG/lettuce-icon.png',
                    lastUpdated: new Date().toISOString()
                },
                {
                    id: 3,
                    name: 'Organic Carrots',
                    oldPrice: 65,
                    newPrice: 50,
                    unit: 'kg',
                    image: '../IMG/carrot-icon.jpg',
                    lastUpdated: new Date().toISOString()
                }
            ];
            this.savePrices(defaultPrices);
        }
    }

    // Get prices from localStorage
    getStoredPrices() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : null;
        } catch (error) {
            console.error('Error loading stored prices:', error);
            return null;
        }
    }

    // Save prices to localStorage
    savePrices(prices) {
        try {
            // Add timestamp to each price
            const timestampedPrices = prices.map(price => ({
                ...price,
                lastUpdated: new Date().toISOString()
            }));
            
            localStorage.setItem(this.storageKey, JSON.stringify(timestampedPrices));
            
            // Trigger update event for cross-window communication
            this.triggerUpdateEvent();
            
            return true;
        } catch (error) {
            console.error('Error saving prices:', error);
            return false;
        }
    }

    // Update a single price by index
    updatePrice(index, priceData) {
        const prices = this.getStoredPrices();
        if (prices && prices[index]) {
            prices[index] = {
                ...prices[index],
                ...priceData,
                lastUpdated: new Date().toISOString()
            };
            return this.savePrices(prices);
        }
        return false;
    }

    // Calculate savings percentage
    calculateSavings(oldPrice, newPrice) {
        if (oldPrice && newPrice && oldPrice > 0) {
            return Math.round(((oldPrice - newPrice) / oldPrice) * 100);
        }
        return 0;
    }

    // Calculate savings amount
    calculateSavingsAmount(oldPrice, newPrice) {
        if (oldPrice && newPrice) {
            return Math.max(0, oldPrice - newPrice);
        }
        return 0;
    }

    // Trigger update event for real-time synchronization
    triggerUpdateEvent() {
        // Use localStorage to communicate between windows/tabs
        localStorage.setItem(this.updateEventKey, Date.now().toString());
        
        // Dispatch custom event for same-window updates
        window.dispatchEvent(new CustomEvent('priceDataUpdated', {
            detail: { prices: this.getStoredPrices() }
        }));
    }

    // Listen for price updates from other windows/tabs
    onPriceUpdate(callback) {
        window.addEventListener('storage', (e) => {
            if (e.key === this.updateEventKey) {
                callback(this.getStoredPrices());
            }
        });

        // Also listen for same-window updates
        window.addEventListener('priceDataUpdated', (e) => {
            callback(e.detail.prices);
        });
    }

    // Validate price data
    validatePriceData(priceData) {
        const requiredFields = ['name', 'oldPrice', 'newPrice', 'unit'];
        const errors = [];

        requiredFields.forEach(field => {
            if (!priceData[field]) {
                errors.push(`${field} is required`);
            }
        });

        if (priceData.oldPrice && priceData.newPrice) {
            if (priceData.oldPrice < 0 || priceData.newPrice < 0) {
                errors.push('Prices must be positive numbers');
            }
            if (priceData.newPrice > priceData.oldPrice) {
                errors.push('New price should be less than old price for a discount');
            }
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    // Get formatted price display
    formatPrice(price, unit) {
        return `₱${parseFloat(price).toFixed(2)}/${unit}`;
    }

    // Get last update timestamp
    getLastUpdateTime() {
        const prices = this.getStoredPrices();
        if (prices && prices.length > 0) {
            const timestamps = prices.map(p => new Date(p.lastUpdated)).filter(d => !isNaN(d));
            if (timestamps.length > 0) {
                return new Date(Math.max(...timestamps));
            }
        }
        return new Date();
    }

    // Export prices data (for admin dashboard)
    exportPrices() {
        const prices = this.getStoredPrices();
        const dataStr = JSON.stringify(prices, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportFileDefaultName = `farmfresh_prices_${new Date().toISOString().split('T')[0]}.json`;
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
    }

    // Import prices data (for admin dashboard)
    importPrices(jsonFile, callback) {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const importedPrices = JSON.parse(e.target.result);
                if (Array.isArray(importedPrices) && importedPrices.length > 0) {
                    const success = this.savePrices(importedPrices);
                    callback(success, success ? 'Prices imported successfully' : 'Failed to save imported prices');
                } else {
                    callback(false, 'Invalid price data format');
                }
            } catch (error) {
                callback(false, 'Error parsing JSON file: ' + error.message);
            }
        };
        reader.readAsText(jsonFile);
    }
}

// Create global instance
window.priceDataManager = window.priceDataManager || new PriceDataManager();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PriceDataManager;
}