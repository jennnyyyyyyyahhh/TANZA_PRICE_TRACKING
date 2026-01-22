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
    currentPageSpan.textContent = currentPage;
    totalPagesSpan.textContent = totalPages;
    showProductsForPage(currentPage);
    initializeHamburgerMenu();
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
        
        const navLinks = navMenu.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
        
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
    searchInput.addEventListener('input', handleSearch);
    searchBtn.addEventListener('click', handleSearch);
    
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSearch();
        }
    });

    productCards.forEach(card => {
        card.addEventListener('click', function() {
            handleProductClick(this);
        });
        
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleProductClick(this);
            }
        });
        
        card.setAttribute('tabindex', '0');
    });

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
}

// Handle search functionality
function handleSearch() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        filteredProducts = Array.from(productCards);
    } else {
        filteredProducts = Array.from(productCards).filter(card => {
            const productName = card.querySelector('.product-name').textContent.toLowerCase();
            const productData = card.dataset.product.toLowerCase();
            return productName.includes(searchTerm) || productData.includes(searchTerm);
        });
    }
    
    currentPage = 1;
    totalPages = Math.ceil(filteredProducts.length / itemsPerPage) || 1;
    
    productCards.forEach(card => {
        card.style.display = 'none';
    });
    
    if (filteredProducts.length === 0) {
        showNoResults();
    } else {
        hideNoResults();
        showProductsForPage(currentPage);
    }
    
    updatePagination();
}

// Show products for current page
function showProductsForPage(page) {
    productCards.forEach(card => {
        card.style.display = 'none';
    });
    
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    
    const productsToShow = filteredProducts.slice(startIndex, endIndex);
    productsToShow.forEach(card => {
        card.style.display = 'block';
    });
    
    productsGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Update pagination controls
function updatePagination() {
    currentPageSpan.textContent = currentPage;
    totalPagesSpan.textContent = totalPages;
    
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    
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

// Price data for fish and seafood
const fishPrices = {
    tilapia: {
        name: 'Fresh Tilapia',
        image: '../IMG/tilapia.jpg',
        prices: {
            'public-market': { price: 180 },
            'puregold': { price: 200 },
            'sm-store': { price: 220 },
            'savemore': { price: 210 },
            'alfamart': { price: 205 }
        }
    },
    bangus: {
        name: 'Fresh Bangus (Milkfish)',
        image: '../IMG/bangus.jpg',
        prices: {
            'public-market': { price: 160 },
            'puregold': { price: 175 },
            'sm-store': { price: 195 },
            'savemore': { price: 185 },
            'alfamart': { price: 180 }
        }
    },
    galunggong: {
        name: 'Fresh Galunggong (Round Scad)',
        image: '../IMG/galunggong.jpg',
        prices: {
            'public-market': { price: 140 },
            'puregold': { price: 155 },
            'sm-store': { price: 175 },
            'savemore': { price: 165 },
            'alfamart': { price: 160 }
        }
    },
    salmon: {
        name: 'Fresh Salmon',
        image: '../IMG/salmon.jpg',
        prices: {
            'public-market': { price: 450 },
            'puregold': { price: 480 },
            'sm-store': { price: 520 },
            'savemore': { price: 500 },
            'alfamart': { price: 495 }
        }
    },
    shrimp: {
        name: 'Fresh Shrimp',
        image: '../IMG/shrimp.jpg',
        prices: {
            'public-market': { price: 280 },
            'puregold': { price: 320 },
            'sm-store': { price: 350 },
            'savemore': { price: 335 },
            'alfamart': { price: 330 }
        }
    },
    crab: {
        name: 'Fresh Crab',
        image: '../IMG/crab.jpg',
        prices: {
            'public-market': { price: 350 },
            'puregold': { price: 380 },
            'sm-store': { price: 420 },
            'savemore': { price: 400 },
            'alfamart': { price: 395 }
        }
    },
    squid: {
        name: 'Fresh Squid',
        image: '../IMG/squid.jpg',
        prices: {
            'public-market': { price: 220 },
            'puregold': { price: 240 },
            'sm-store': { price: 265 },
            'savemore': { price: 255 },
            'alfamart': { price: 250 }
        }
    },
    tuna: {
        name: 'Fresh Tuna',
        image: '../IMG/tuna.jpg',
        prices: {
            'public-market': { price: 380 },
            'puregold': { price: 420 },
            'sm-store': { price: 450 },
            'savemore': { price: 435 },
            'alfamart': { price: 430 }
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
    const productData = card.dataset.product;
    showPriceModal(productData);
}

// Show price modal
function showPriceModal(productKey) {
    const product = fishPrices[productKey];
    if (!product) return;

    const modal = document.getElementById('priceModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalImage = document.getElementById('modalProductImage');
    const priceCardsContainer = document.getElementById('priceCardsContainer');
    const bestDealStore = document.getElementById('bestDealStore');
    const bestDealPrice = document.getElementById('bestDealPrice');
    const priceRange = document.getElementById('priceRange');

    modalTitle.textContent = product.name;
    modalImage.src = product.image;
    modalImage.alt = product.name;

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

    bestDealStore.textContent = bestStore;
    bestDealPrice.textContent = `₱${bestPrice}/kg`;
    priceRange.textContent = `₱${minPrice} - ₱${maxPrice} per kg`;

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

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close modal functionality
function closePriceModal() {
    const modal = document.getElementById('priceModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Modal event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('priceModal');
    const closeModalBtn = document.getElementById('closeModal');
    const closeModalFooterBtn = document.getElementById('closeModalBtn');
    const viewStoreLocationBtn = document.getElementById('viewStoreLocation');

    if (closeModalBtn) closeModalBtn.addEventListener('click', closePriceModal);
    if (closeModalFooterBtn) closeModalFooterBtn.addEventListener('click', closePriceModal);
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePriceModal();
            }
        });
    }

    if (viewStoreLocationBtn) {
        viewStoreLocationBtn.addEventListener('click', function() {
            alert('Store locations feature will be available soon!');
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
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
                <h3>No fish or seafood found</h3>
                <p>Try searching with a different name</p>
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

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && searchInput.value.trim() !== '') {
        clearSearch();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
    }
    
    if (e.key === 'ArrowLeft' && !prevBtn.disabled) {
        e.preventDefault();
        prevBtn.click();
    }
    
    if (e.key === 'ArrowRight' && !nextBtn.disabled) {
        e.preventDefault();
        nextBtn.click();
    }
});
