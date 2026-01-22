// DOM Elements
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const productsGrid = document.getElementById('productsGrid');
const productCards = document.querySelectorAll('.product-card');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const currentPageSpan = document.querySelector('.current-page');
const totalPagesSpan = document.querySelector('.total-pages');

// Variables
let currentPage = 1;
let totalPages = 2;
let filteredProducts = Array.from(productCards);
const itemsPerPage = 8;

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
    setupEventListeners();
    updatePagination();
});

// Initialize page functionality
function initializePage() {
    // Set initial pagination
    currentPageSpan.textContent = currentPage;
    totalPagesSpan.textContent = totalPages;
    
    // Show initial products
    showProductsForPage(currentPage);
    
    // Initialize hamburger menu
    initializeHamburgerMenu();
    
    console.log('🍖 Meat & Poultry page loaded successfully!');
}

// Hamburger Menu Functionality
function initializeHamburgerMenu() {
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const navMenu = document.getElementById('navMenu');
    
    if (hamburgerMenu && navMenu) {
        hamburgerMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            hamburgerMenu.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Close menu when clicking on a nav link
        const navLinks = navMenu.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navMenu.contains(e.target) && !hamburgerMenu.contains(e.target)) {
                hamburgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    }
}

// Setup all event listeners
function setupEventListeners() {
    // Search functionality
    searchInput.addEventListener('input', handleSearch);
    searchBtn.addEventListener('click', handleSearch);
    
    // Enter key for search
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSearch();
        }
    });

    // Product card clicks
    productCards.forEach(card => {
        card.addEventListener('click', function() {
            handleProductClick(this);
        });
        
        // Keyboard navigation for accessibility
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleProductClick(this);
            }
        });
        
        // Make cards focusable
        card.setAttribute('tabindex', '0');
    });

    // Pagination controls
    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            showProductsForPage(currentPage);
            updatePagination();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            showProductsForPage(currentPage);
            updatePagination();
        }
    });

    // Notification bell (if exists)
    const notificationBell = document.getElementById('notificationBell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            // Add notification functionality here
            console.log('Notifications clicked');
        });
    }
}

// Handle search functionality
function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        // Show all products if search is empty
        filteredProducts = Array.from(productCards);
    } else {
        // Filter products based on search term
        filteredProducts = Array.from(productCards).filter(card => {
            const productName = card.querySelector('.product-name').textContent.toLowerCase();
            const productData = card.dataset.product.toLowerCase();
            return productName.includes(searchTerm) || productData.includes(searchTerm);
        });
    }
    
    // Reset pagination
    currentPage = 1;
    totalPages = Math.ceil(filteredProducts.length / itemsPerPage) || 1;
    
    // Hide all products first
    productCards.forEach(card => {
        card.style.display = 'none';
    });
    
    // Show filtered results
    if (filteredProducts.length === 0) {
        showNoResults();
    } else {
        hideNoResults();
        showProductsForPage(currentPage);
    }
    
    updatePagination();
    
    // Add search animation
    productsGrid.style.opacity = '0.7';
    setTimeout(() => {
        productsGrid.style.opacity = '1';
    }, 300);
}

// Show products for current page
function showProductsForPage(page) {
    // Hide all products
    productCards.forEach(card => {
        card.style.display = 'none';
    });
    
    // Calculate range for current page
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    
    // Show products for current page
    const productsToShow = filteredProducts.slice(startIndex, endIndex);
    productsToShow.forEach(card => {
        card.style.display = 'block';
    });
    
    // Scroll to top of products grid
    productsGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Update pagination controls
function updatePagination() {
    currentPageSpan.textContent = currentPage;
    totalPagesSpan.textContent = totalPages;
    
    // Update button states
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    
    // Update button styles
    if (prevBtn.disabled) {
        prevBtn.style.opacity = '0.5';
        prevBtn.style.cursor = 'not-allowed';
    } else {
        prevBtn.style.opacity = '1';
        prevBtn.style.cursor = 'pointer';
    }
    
    if (nextBtn.disabled) {
        nextBtn.style.opacity = '0.5';
        nextBtn.style.cursor = 'not-allowed';
    } else {
        nextBtn.style.opacity = '1';
        nextBtn.style.cursor = 'pointer';
    }
}

// Price data for different meat and poultry and stores
const meatPrices = {
    'chicken-breast': {
        name: 'Fresh Chicken Breast',
        image: '../IMG/chicken-breast.jpg',
        prices: {
            'public-market': { price: 260 },
            'puregold': { price: 285 },
            'sm-store': { price: 320 },
            'savemore': { price: 300 },
            'alfamart': { price: 295 }
        }
    },
    'pork-chops': {
        name: 'Fresh Pork Chops',
        image: '../IMG/pork-chops.jpg',
        prices: {
            'public-market': { price: 320 },
            'puregold': { price: 350 },
            'sm-store': { price: 380 },
            'savemore': { price: 365 },
            'alfamart': { price: 360 }
        }
    },
    'ground-beef': {
        name: 'Fresh Ground Beef',
        image: '../IMG/ground-beef.jpg',
        prices: {
            'public-market': { price: 380 },
            'puregold': { price: 420 },
            'sm-store': { price: 450 },
            'savemore': { price: 435 },
            'alfamart': { price: 430 }
        }
    },
    'chicken-leg': {
        name: 'Fresh Chicken Leg',
        image: '../IMG/chicken-leg.jpg',
        prices: {
            'public-market': { price: 180 },
            'puregold': { price: 200 },
            'sm-store': { price: 225 },
            'savemore': { price: 210 },
            'alfamart': { price: 205 }
        }
    },
    'pork-belly': {
        name: 'Fresh Pork Belly',
        image: '../IMG/pork-belly.jpg',
        prices: {
            'public-market': { price: 280 },
            'puregold': { price: 310 },
            'sm-store': { price: 340 },
            'savemore': { price: 325 },
            'alfamart': { price: 320 }
        }
    },
    'chicken-wings': {
        name: 'Fresh Chicken Wings',
        image: '../IMG/chicken-wings.jpg',
        prices: {
            'public-market': { price: 220 },
            'puregold': { price: 240 },
            'sm-store': { price: 265 },
            'savemore': { price: 255 },
            'alfamart': { price: 250 }
        }
    },
    'beef-steak': {
        name: 'Fresh Beef Steak',
        image: '../IMG/beef-steak.jpg',
        prices: {
            'public-market': { price: 450 },
            'puregold': { price: 480 },
            'sm-store': { price: 520 },
            'savemore': { price: 500 },
            'alfamart': { price: 495 }
        }
    },
    'whole-chicken': {
        name: 'Fresh Whole Chicken',
        image: '../IMG/whole-chicken.jpg',
        prices: {
            'public-market': { price: 150 },
            'puregold': { price: 165 },
            'sm-store': { price: 185 },
            'savemore': { price: 175 },
            'alfamart': { price: 170 }
        }
    }
};

// Store information
const storeInfo = {
    'public-market': { name: 'Public Market', icon: 'fas fa-store' },
    'puregold': { name: 'Puregold', icon: 'fas fa-medal' },
    'sm-store': { name: 'SM Store', icon: 'fas fa-shopping-cart' },
    'savemore': { name: 'Save More', icon: 'fas fa-piggy-bank' },
    'alfamart': { name: 'Alfamart', icon: 'fas fa-leaf' }
};

// Handle product card clicks
function handleProductClick(card) {
    const productName = card.querySelector('.product-name').textContent;
    const productData = card.dataset.product;
    
    // Add loading animation
    card.classList.add('loading');
    
    // Simulate loading
    setTimeout(() => {
        card.classList.remove('loading');
        showPriceModal(productData);
    }, 500);
}

// Show price modal
function showPriceModal(productKey) {
    const product = meatPrices[productKey];
    if (!product) return;

    const modal = document.getElementById('priceModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalImage = document.getElementById('modalProductImage');
    const priceCardsContainer = document.getElementById('priceCardsContainer');
    const bestDealStore = document.getElementById('bestDealStore');
    const bestDealPrice = document.getElementById('bestDealPrice');
    const priceRange = document.getElementById('priceRange');

    // Set product info
    modalTitle.textContent = product.name;
    modalImage.src = product.image;
    modalImage.alt = product.name;

    // Find best price
    let bestPrice = Infinity;
    let bestStore = '';
    let minPrice = Infinity;
    let maxPrice = 0;

    Object.entries(product.prices).forEach(([storeKey, data]) => {
        if (data.price < bestPrice) {
            bestPrice = data.price;
            bestStore = storeInfo[storeKey].name;
        }
        if (data.price < minPrice) minPrice = data.price;
        if (data.price > maxPrice) maxPrice = data.price;
    });

    // Update summary
    bestDealStore.textContent = bestStore;
    bestDealPrice.textContent = `₱${bestPrice}/kg`;
    priceRange.textContent = `₱${minPrice} - ₱${maxPrice} per kg`;

    // Create price cards
    priceCardsContainer.innerHTML = '';
    Object.entries(product.prices).forEach(([storeKey, data]) => {
        const isBest = data.price === bestPrice;
        const priceCard = document.createElement('div');
        priceCard.className = `price-card ${isBest ? 'best-price' : ''}`;

        priceCard.innerHTML = `
            <div class="store-name">${storeInfo[storeKey].name}</div>
            <div class="store-price">₱${data.price}/kg</div>
        `;
        priceCardsContainer.appendChild(priceCard);
    });

    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close modal functionality
function closePriceModal() {
    const modal = document.getElementById('priceModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Add modal event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('priceModal');
    const closeModalBtn = document.getElementById('closeModal');
    const closeModalFooterBtn = document.getElementById('closeModalBtn');
    const viewStoreLocationBtn = document.getElementById('viewStoreLocation');

    // Close modal events
    closeModalBtn.addEventListener('click', closePriceModal);
    closeModalFooterBtn.addEventListener('click', closePriceModal);
    
    // Close modal when clicking overlay
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePriceModal();
        }
    });

    // View store locations
    viewStoreLocationBtn.addEventListener('click', function() {
        alert('Store locations feature will be available soon!');
    });

    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closePriceModal();
        }
    });
});

// Show no results message
function showNoResults() {
    let noResultsMessage = document.getElementById('noResults');
    
    if (!noResultsMessage) {
        noResultsMessage = document.createElement('div');
        noResultsMessage.id = 'noResults';
        noResultsMessage.className = 'no-results';
        noResultsMessage.innerHTML = `
            <div class="no-results-content">
                <i class="fas fa-search"></i>
                <h3>No vegetables found</h3>
                <p>Try searching for a different vegetable name</p>
                <button class="clear-search-btn" onclick="clearSearch()">Clear Search</button>
            </div>
        `;
        productsGrid.appendChild(noResultsMessage);
    }
    
    noResultsMessage.style.display = 'block';
}

// Hide no results message
function hideNoResults() {
    const noResultsMessage = document.getElementById('noResults');
    if (noResultsMessage) {
        noResultsMessage.style.display = 'none';
    }
}

// Clear search function
function clearSearch() {
    searchInput.value = '';
    handleSearch();
    searchInput.focus();
}

// Keyboard navigation for the page
document.addEventListener('keydown', function(e) {
    // ESC key to clear search
    if (e.key === 'Escape') {
        if (searchInput.value.trim() !== '') {
            clearSearch();
        }
    }
    
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
    }
    
    // Arrow keys for pagination
    if (e.key === 'ArrowLeft' && !prevBtn.disabled) {
        e.preventDefault();
        prevBtn.click();
    }
    
    if (e.key === 'ArrowRight' && !nextBtn.disabled) {
        e.preventDefault();
        nextBtn.click();
    }
});

// Search input enhancements
searchInput.addEventListener('focus', function() {
    this.parentElement.style.transform = 'scale(1.02)';
});

searchInput.addEventListener('blur', function() {
    this.parentElement.style.transform = 'scale(1)';
});

// Smooth scroll for better UX
function smoothScrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Auto-hide notification badge after some time
setTimeout(() => {
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge) {
        const currentCount = parseInt(notificationBadge.textContent);
        if (currentCount > 0) {
            notificationBadge.textContent = Math.max(0, currentCount - 1);
            if (notificationBadge.textContent === '0') {
                notificationBadge.style.display = 'none';
            }
        }
    }
}, 5000);

// Add dynamic product loading simulation
function simulateProductUpdate() {
    const randomCard = productCards[Math.floor(Math.random() * productCards.length)];
    if (randomCard && randomCard.style.display !== 'none') {
        randomCard.style.transform = 'scale(1.05)';
        randomCard.style.boxShadow = '0 8px 25px rgba(39, 174, 96, 0.3)';
        
        setTimeout(() => {
            randomCard.style.transform = '';
            randomCard.style.boxShadow = '';
        }, 1000);
    }
}

// Simulate periodic product updates (every 30 seconds)
setInterval(simulateProductUpdate, 30000);

// Add CSS for no results styling
const noResultsCSS = `
    .no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin: 2rem 0;
    }
    
    .no-results-content {
        max-width: 400px;
        margin: 0 auto;
    }
    
    .no-results-content i {
        font-size: 3rem;
        color: #ccc;
        margin-bottom: 1rem;
    }
    
    .no-results-content h3 {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 0.5rem;
    }
    
    .no-results-content p {
        color: #666;
        margin-bottom: 1.5rem;
    }
    
    .clear-search-btn {
        background: #27ae60;
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .clear-search-btn:hover {
        background: #219a52;
        transform: translateY(-2px);
    }
`;

// Inject no results CSS
const style = document.createElement('style');
style.textContent = noResultsCSS;
document.head.appendChild(style);

// Performance optimization: Lazy loading for images
const observeImages = () => {
    const images = document.querySelectorAll('.product-image img');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.src; // Trigger actual loading
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
};

// Initialize lazy loading
if ('IntersectionObserver' in window) {
    observeImages();
}