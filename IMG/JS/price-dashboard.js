// Commodity Price Dashboard JavaScript

// Sample data for all commodities across all stores with images
const commodityData = {
    fruits: [
        {
            name: "Apple (Red Delicious)",
            category: "fruits",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 180.00,
                savemore: 175.00,
                "public-market": 160.00,
                puregold: 185.00
            }
        },
        {
            name: "Banana (Saba)",
            category: "fruits",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1603833665858-e61d17a86224?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 45.00,
                savemore: 42.00,
                "public-market": 35.00,
                puregold: 48.00
            }
        },
        {
            name: "Orange (Valencia)",
            category: "fruits",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 120.00,
                savemore: 115.00,
                "public-market": 100.00,
                puregold: 125.00
            }
        },
        {
            name: "Mango (Carabao)",
            category: "fruits",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1605711285791-0219e80e43a3?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 200.00,
                savemore: 195.00,
                "public-market": 180.00,
                puregold: 210.00
            }
        },
        {
            name: "Grapes (Green)",
            category: "fruits",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1515694346937-94d85e41e6f0?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 280.00,
                savemore: 275.00,
                "public-market": 250.00,
                puregold: 290.00
            }
        },
        {
            name: "Pineapple",
            category: "fruits",
            unit: "per piece",
            image: "https://images.unsplash.com/photo-1550258987-190a2d41a8ba?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 85.00,
                savemore: 80.00,
                "public-market": 70.00,
                puregold: 90.00
            }
        }
    ],
    vegetables: [
        {
            name: "Tomato",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1546470427-e5d5d68b9368?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 80.00,
                savemore: 75.00,
                "public-market": 65.00,
                puregold: 85.00
            }
        },
        {
            name: "Onion (Red)",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1508747703725-719777637510?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 150.00,
                savemore: 145.00,
                "public-market": 130.00,
                puregold: 155.00
            }
        },
        {
            name: "Potato",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 90.00,
                savemore: 85.00,
                "public-market": 75.00,
                puregold: 95.00
            }
        },
        {
            name: "Carrot",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1445282768818-728615cc910a?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 110.00,
                savemore: 105.00,
                "public-market": 95.00,
                puregold: 115.00
            }
        },
        {
            name: "Cabbage",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 70.00,
                savemore: 65.00,
                "public-market": 55.00,
                puregold: 75.00
            }
        },
        {
            name: "Eggplant",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1659261200833-ec8761558af7?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 85.00,
                savemore: 80.00,
                "public-market": 70.00,
                puregold: 90.00
            }
        },
        {
            name: "Bell Pepper (Green)",
            category: "vegetables",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1563565375-f3fdfdbefa83?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 180.00,
                savemore: 175.00,
                "public-market": 160.00,
                puregold: 185.00
            }
        }
    ],
    meats: [
        {
            name: "Pork (Liempo)",
            category: "meats",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1602470520998-f4a52199a3d6?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 320.00,
                savemore: 315.00,
                "public-market": 300.00,
                puregold: 325.00
            }
        },
        {
            name: "Beef (Chuck)",
            category: "meats",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1588347818481-711a2bb1b5b1?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 450.00,
                savemore: 445.00,
                "public-market": 420.00,
                puregold: 455.00
            }
        },
        {
            name: "Pork (Kasim)",
            category: "meats",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 280.00,
                savemore: 275.00,
                "public-market": 260.00,
                puregold: 285.00
            }
        },
        {
            name: "Beef (Ground)",
            category: "meats",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1559561853-08451507cbe7?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 380.00,
                savemore: 375.00,
                "public-market": 350.00,
                puregold: 385.00
            }
        },
        {
            name: "Pork (Spare Ribs)",
            category: "meats",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 350.00,
                savemore: 345.00,
                "public-market": 330.00,
                puregold: 355.00
            }
        }
    ],
    rice: [
        {
            name: "Rice (Premium)",
            category: "rice",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1586201375761-83865001e31c?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 65.00,
                savemore: 62.00,
                "public-market": 58.00,
                puregold: 68.00
            }
        },
        {
            name: "Rice (Regular)",
            category: "rice",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1506976785307-8732e854ad03?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 52.00,
                savemore: 50.00,
                "public-market": 45.00,
                puregold: 55.00
            }
        },
        {
            name: "Rice (Jasmine)",
            category: "rice",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 80.00,
                savemore: 78.00,
                "public-market": 72.00,
                puregold: 85.00
            }
        },
        {
            name: "Brown Rice",
            category: "rice",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1627662168675-de13de095d23?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 95.00,
                savemore: 92.00,
                "public-market": 85.00,
                puregold: 100.00
            }
        }
    ],
    poultry: [
        {
            name: "Chicken (Whole)",
            category: "poultry",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 180.00,
                savemore: 175.00,
                "public-market": 165.00,
                puregold: 185.00
            }
        },
        {
            name: "Chicken (Breast)",
            category: "poultry",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1585769411658-afe0b180de83?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 220.00,
                savemore: 215.00,
                "public-market": 200.00,
                puregold: 225.00
            }
        },
        {
            name: "Chicken (Thigh)",
            category: "poultry",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1598511726623-d2e9996892f0?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 200.00,
                savemore: 195.00,
                "public-market": 185.00,
                puregold: 205.00
            }
        },
        {
            name: "Eggs (Large)",
            category: "poultry",
            unit: "per dozen",
            image: "https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 85.00,
                savemore: 82.00,
                "public-market": 78.00,
                puregold: 88.00
            }
        },
        {
            name: "Duck",
            category: "poultry",
            unit: "per kg",
            image: "https://images.unsplash.com/photo-1626885930480-aa38bd5c7fa5?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 250.00,
                savemore: 245.00,
                "public-market": 230.00,
                puregold: 255.00
            }
        }
    ],
    dairy: [
        {
            name: "Fresh Milk (1L)",
            category: "dairy",
            unit: "per liter",
            image: "https://images.unsplash.com/photo-1550583724-b2692b85b150?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 95.00,
                savemore: 92.00,
                "public-market": 85.00,
                puregold: 98.00
            }
        },
        {
            name: "Cheese (Cheddar)",
            category: "dairy",
            unit: "per 200g",
            image: "https://images.unsplash.com/photo-1452195100486-9cc805987862?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 180.00,
                savemore: 175.00,
                "public-market": 165.00,
                puregold: 185.00
            }
        },
        {
            name: "Butter",
            category: "dairy",
            unit: "per 200g",
            image: "https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 120.00,
                savemore: 115.00,
                "public-market": 105.00,
                puregold: 125.00
            }
        },
        {
            name: "Yogurt (Plain)",
            category: "dairy",
            unit: "per 500ml",
            image: "https://images.unsplash.com/photo-1488477181946-6428a0291777?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 65.00,
                savemore: 62.00,
                "public-market": 55.00,
                puregold: 68.00
            }
        },
        {
            name: "Ice Cream (1L)",
            category: "dairy",
            unit: "per liter",
            image: "https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=100&h=100&fit=crop&crop=center",
            prices: {
                alfamart: 250.00,
                savemore: 245.00,
                "public-market": 220.00,
                puregold: 255.00
            }
        }
    ]
};

// Store information
const stores = {
    alfamart: { name: "Alfamart", icon: "fas fa-store" },
    savemore: { name: "SaveMore", icon: "fas fa-store" },
    "public-market": { name: "Public Market", icon: "fas fa-store-alt" },
    puregold: { name: "Puregold", icon: "fas fa-store" }
};

// Current filters
let currentFilters = {
    store: 'all',
    category: 'all',
    search: ''
};

// Pagination variables
let currentPage = 1;
let itemsPerPage = 12;
let totalItems = 0;
let totalPages = 0;
let allCommodityCards = [];

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', function() {
    renderAllCommodities();
    updateStats();
    setupEventListeners();
    updateLastUpdated();
    setupNotifications();
    setupSidebar();
    updateSidebarStats();
    setupPdfDownload(); // Initialize PDF download functionality
    
    // Initialize pagination after a short delay to ensure all commodities are rendered
    setTimeout(() => {
        setupPagination(); // Initialize pagination
    }, 100);
});

// Setup event listeners
function setupEventListeners() {
    // Store filter buttons
    document.querySelectorAll('[data-store]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all store buttons
            document.querySelectorAll('[data-store]').forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            // Update filter
            currentFilters.store = this.dataset.store;
            filterCommodities();
        });
    });

    // Category filter buttons
    document.querySelectorAll('[data-category]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all category buttons
            document.querySelectorAll('[data-category]').forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            // Update filter
            currentFilters.category = this.dataset.category;
            filterCommodities();
        });
    });

    // Search input
    document.getElementById('searchInput').addEventListener('input', function() {
        currentFilters.search = this.value.toLowerCase();
        filterCommodities();
    });

    // Modal close button
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('comparisonModal').style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('comparisonModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Setup sidebar functionality
function setupSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Mobile sidebar toggle
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Sidebar navigation links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all sidebar links
            document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');
            
            // Get target section
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                // Smooth scroll to section
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Close sidebar on mobile
                if (window.innerWidth <= 1024) {
                    setTimeout(() => closeSidebar(), 500);
                }
            }
        });
    });
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
    document.body.style.overflow = '';
}

// Update sidebar statistics
function updateSidebarStats() {
    const allCommodities = getAllCommodities();
    let bestDealsCount = 0;
    let totalSavings = 0;
    
    allCommodities.forEach(commodity => {
        const prices = Object.values(commodity.prices);
        const minPrice = Math.min(...prices);
        const maxPrice = Math.max(...prices);
        const savings = maxPrice - minPrice;
        
        if (savings > 0) {
            bestDealsCount++;
            totalSavings += savings;
        }
    });
    
    const avgSavings = bestDealsCount > 0 ? totalSavings / bestDealsCount : 0;
    
    // Update sidebar stats
    const bestDealsElement = document.getElementById('bestDealsCount');
    const avgSavingsElement = document.getElementById('avgSavings');
    
    if (bestDealsElement) {
        bestDealsElement.textContent = bestDealsCount;
    }
    
    if (avgSavingsElement) {
        avgSavingsElement.textContent = `₱${avgSavings.toFixed(0)}`;
    }
}

// Setup notification functionality (from landing page)
function setupNotifications() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationBell && notificationDropdown) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            
            // Add click animation
            notificationBell.style.transform = 'scale(1.2)';
            setTimeout(() => {
                notificationBell.style.transform = 'scale(1)';
            }, 150);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });

        // Mark all as read functionality
        const markReadBtn = document.querySelector('.mark-read');
        if (markReadBtn) {
            markReadBtn.addEventListener('click', function() {
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    badge.style.display = 'none';
                }
                
                // Apply visual feedback for read notifications
                const notificationItems = document.querySelectorAll('.notification-item');
                notificationItems.forEach(item => {
                    item.style.opacity = '0.6';
                });
                
                // Show confirmation toast (if the function exists)
                if (typeof showToast === 'function') {
                    showToast('All notifications marked as read!', 'success');
                } else if (typeof showNotification === 'function') {
                    showNotification('All notifications marked as read!', 'success');
                }
            });
        }
        
        // Individual notification clicks
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach((item, index) => {
            item.addEventListener('click', function() {
                // Visual fade effect
                item.style.opacity = '0.6';
                
                // Optional: Decrease badge count
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent);
                    if (currentCount > 0) {
                        badge.textContent = currentCount - 1;
                        
                        // Hide badge if no notifications left
                        if (currentCount - 1 <= 0) {
                            badge.style.display = 'none';
                        }
                    }
                }
                
                // Show feedback
                if (typeof showToast === 'function') {
                    showToast('Notification dismissed', 'info');
                } else if (typeof showNotification === 'function') {
                    showNotification('Notification dismissed', 'info');
                }
            });
        });
    }
}

// Render all commodities
function renderAllCommodities() {
    Object.keys(commodityData).forEach(category => {
        renderCategoryGrid(category, commodityData[category]);
    });
}

// Render commodity grid for a specific category
function renderCategoryGrid(category, items) {
    const grid = document.getElementById(`${category}-grid`);
    grid.innerHTML = '';

    items.forEach(item => {
        const card = createCommodityCard(item);
        grid.appendChild(card);
    });
}

// Create a commodity card
function createCommodityCard(commodity) {
    const card = document.createElement('div');
    card.className = 'commodity-card';
    card.dataset.category = commodity.category;
    
    // Get only Public Market price
    const publicMarketPrice = commodity.prices['public-market'];
    const storeInfo = stores['public-market'];

    card.innerHTML = `
        <div class="commodity-header">
            <img src="${commodity.image}" alt="${commodity.name}" class="commodity-image" 
                 onerror="this.src='https://via.placeholder.com/120x120/e8f5e8/2d5016?text=N/A';">
            <div class="commodity-info">
                <div class="commodity-name">${commodity.name}</div>
                <div class="commodity-category">${commodity.category}</div>
            </div>
        </div>
        <div class="commodity-prices">
            <div class="store-price" data-store="public-market">
                <span class="store-name">
                    <i class="${storeInfo.icon}"></i>
                    ${storeInfo.name}
                </span>
                <span class="price">
                    ₱${publicMarketPrice.toFixed(2)}
                    <span class="unit">${commodity.unit}</span>
                </span>
            </div>
        </div>
    `;

    // Add click event for price comparison (still shows all stores in modal)
    card.addEventListener('click', () => showPriceComparison(commodity));

    return card;
}

// Filter commodities based on current filters
function filterCommodities() {
    // Filter by category
    document.querySelectorAll('.category-section').forEach(section => {
        const category = section.dataset.category;
        if (currentFilters.category === 'all' || currentFilters.category === category) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    });

    // Filter by search
    document.querySelectorAll('.commodity-card').forEach(card => {
        const commodityName = card.querySelector('.commodity-name').textContent.toLowerCase();
        
        // Check search filter
        const matchesSearch = currentFilters.search === '' || 
                            commodityName.includes(currentFilters.search);

        // Since we only show Public Market prices, store filter is not applicable to individual cards
        // But we can still use it for statistical purposes
        
        // Show/hide card based on search filter
        if (matchesSearch) {
            card.style.display = 'block';
            
            // Highlight search terms
            if (currentFilters.search) {
                highlightSearchTerm(card, currentFilters.search);
            } else {
                removeHighlight(card);
            }
        } else {
            card.style.display = 'none';
        }
    });

    updateStats();
    updateSidebarStats();
    updatePaginationAfterFilter(); // Update pagination after filtering
}

// Highlight search term
function highlightSearchTerm(card, term) {
    const nameElement = card.querySelector('.commodity-name');
    const originalText = nameElement.textContent;
    const highlightedText = originalText.replace(
        new RegExp(term, 'gi'), 
        `<span class="highlight">$&</span>`
    );
    nameElement.innerHTML = highlightedText;
}

// Remove highlight
function removeHighlight(card) {
    const nameElement = card.querySelector('.commodity-name');
    nameElement.innerHTML = nameElement.textContent;
}

// Show price comparison modal
function showPriceComparison(commodity) {
    const modal = document.getElementById('comparisonModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');

    modalTitle.textContent = `Price Comparison: ${commodity.name}`;

    // Sort stores by price (lowest to highest)
    const sortedPrices = Object.entries(commodity.prices)
        .sort(([,a], [,b]) => a - b);

    let tableHTML = `
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Store</th>
                    <th>Price ${commodity.unit}</th>
                    <th>Difference from Lowest</th>
                </tr>
            </thead>
            <tbody>
    `;

    const lowestPrice = sortedPrices[0][1];

    sortedPrices.forEach(([store, price], index) => {
        const storeInfo = stores[store];
        const difference = price - lowestPrice;
        const badge = index === 0 ? '<span style="color: #28a745;">★ Best Price</span>' : 
                     index === sortedPrices.length - 1 ? '<span style="color: #dc3545;">Most Expensive</span>' : '';

        tableHTML += `
            <tr>
                <td>
                    <i class="${storeInfo.icon}"></i> ${storeInfo.name}
                    ${badge}
                </td>
                <td>₱${price.toFixed(2)}</td>
                <td>${difference === 0 ? '-' : '+₱' + difference.toFixed(2)}</td>
            </tr>
        `;
    });

    tableHTML += `
            </tbody>
        </table>
    `;

    modalBody.innerHTML = tableHTML;
    modal.style.display = 'block';
}

// Update statistics
function updateStats() {
    const visibleCards = document.querySelectorAll('.commodity-card:not([style*="display: none"])');
    const totalCommodities = visibleCards.length;
    
    // Calculate average price from visible Public Market prices
    let totalPrice = 0;
    let priceCount = 0;
    
    visibleCards.forEach(card => {
        const priceElement = card.querySelector('.price');
        if (priceElement) {
            const priceText = priceElement.textContent.replace('₱', '').split(' ')[0];
            totalPrice += parseFloat(priceText);
            priceCount++;
        }
    });

    const avgPrice = priceCount > 0 ? totalPrice / priceCount : 0;

    document.getElementById('totalCommodities').textContent = totalCommodities;
    document.getElementById('avgPrice').textContent = `₱${avgPrice.toFixed(2)}`;
}

// Update last updated timestamp
function updateLastUpdated() {
    const now = new Date();
    const dateString = now.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    document.getElementById('lastUpdated').textContent = dateString;
}

// Utility function to get all commodities as flat array
function getAllCommodities() {
    const allCommodities = [];
    Object.values(commodityData).forEach(categoryItems => {
        allCommodities.push(...categoryItems);
    });
    return allCommodities;
}

// Export functions for potential future use
window.DashboardAPI = {
    commodityData,
    stores,
    getAllCommodities,
    updateStats,
    filterCommodities
};

// Setup pagination functionality
function setupPagination() {
    // Get all commodity cards
    collectAllCommodityCards();
    
    // Show pagination container if we have items
    const paginationContainer = document.getElementById('paginationContainer');
    if (paginationContainer && totalItems > itemsPerPage) {
        paginationContainer.style.display = 'flex';
    } else if (paginationContainer) {
        paginationContainer.style.display = totalItems > 0 ? 'flex' : 'none';
    }
    
    // Setup pagination event listeners
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                updatePagination();
            }
        });
    }
    
    // Initial pagination setup
    updatePagination();
}

// Collect all commodity cards for pagination
function collectAllCommodityCards() {
    allCommodityCards = Array.from(document.querySelectorAll('.commodity-card'));
    totalItems = allCommodityCards.length;
    totalPages = Math.ceil(totalItems / itemsPerPage);
    
    console.log(`Pagination: Found ${totalItems} items, ${totalPages} pages, ${itemsPerPage} items per page`);
}

// Update pagination display and show current page items
function updatePagination() {
    // Re-collect commodity cards in case they changed
    collectAllCommodityCards();
    
    // If no items or only one page, hide pagination
    if (totalItems <= itemsPerPage) {
        document.getElementById('paginationContainer').style.display = 'none';
        // Show all items
        allCommodityCards.forEach(card => {
            card.classList.add('current-page');
        });
        return;
    }
    
    // Show pagination
    document.getElementById('paginationContainer').style.display = 'flex';
    
    // Enable pagination mode
    document.body.classList.add('pagination-active');
    
    // Hide all cards first
    allCommodityCards.forEach(card => {
        card.classList.remove('current-page');
    });
    
    // Calculate start and end indices for current page
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    
    // Show cards for current page
    for (let i = startIndex; i < endIndex; i++) {
        if (allCommodityCards[i]) {
            allCommodityCards[i].classList.add('current-page');
        }
    }
    
    // Update pagination controls
    updatePaginationControls();
    updatePaginationInfo();
    
    // Scroll to top of dashboard
    const dashboard = document.querySelector('.dashboard');
    if (dashboard) {
        dashboard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Update pagination control buttons and numbers
function updatePaginationControls() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const paginationNumbers = document.getElementById('paginationNumbers');
    
    // Update prev/next buttons
    if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
    }
    
    if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages || totalPages <= 1;
    }
    
    // Generate page numbers
    if (paginationNumbers && totalPages > 1) {
        paginationNumbers.innerHTML = generatePageNumbers();
    } else if (paginationNumbers) {
        paginationNumbers.innerHTML = '';
    }
}

// Generate page number buttons (Google-style)
function generatePageNumbers() {
    let html = '';
    const maxVisiblePages = 7; // Maximum number of page buttons to show
    
    if (totalPages <= maxVisiblePages) {
        // Show all pages if total pages is small
        for (let i = 1; i <= totalPages; i++) {
            html += createPageButton(i);
        }
    } else {
        // Show first page
        html += createPageButton(1);
        
        if (currentPage > 4) {
            html += '<span class="page-number dots">...</span>';
        }
        
        // Show pages around current page
        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);
        
        for (let i = start; i <= end; i++) {
            html += createPageButton(i);
        }
        
        if (currentPage < totalPages - 3) {
            html += '<span class="page-number dots">...</span>';
        }
        
        // Show last page
        if (totalPages > 1) {
            html += createPageButton(totalPages);
        }
    }
    
    return html;
}

// Create individual page button
function createPageButton(pageNum) {
    const isActive = pageNum === currentPage ? 'active' : '';
    return `
        <button class="page-number ${isActive}" onclick="goToPage(${pageNum})">
            ${pageNum}
        </button>
    `;
}

// Navigate to specific page
function goToPage(pageNum) {
    if (pageNum >= 1 && pageNum <= totalPages && pageNum !== currentPage) {
        currentPage = pageNum;
        updatePagination();
    }
}

// Update pagination info text
function updatePaginationInfo() {
    const paginationInfo = document.getElementById('paginationInfo');
    if (paginationInfo) {
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);
        paginationInfo.textContent = `Showing ${startItem}-${endItem} of ${totalItems} commodities`;
    }
}

// Update pagination when filtering
function updatePaginationAfterFilter() {
    // Collect visible cards after filtering
    const visibleCards = Array.from(document.querySelectorAll('.commodity-card:not([style*="display: none"])'));
    allCommodityCards = visibleCards;
    totalItems = allCommodityCards.length;
    totalPages = Math.ceil(totalItems / itemsPerPage);
    
    // Reset to first page
    currentPage = 1;
    
    // Update pagination
    if (totalItems > 0) {
        updatePagination();
        document.getElementById('paginationContainer').style.display = 'flex';
    } else {
        document.getElementById('paginationContainer').style.display = 'none';
    }
}

// PDF Download Functionality
function setupPdfDownload() {
    const downloadBtn = document.getElementById('downloadPdfBtn');
    const pdfModal = document.getElementById('pdfDownloadModal');
    const pdfModalClose = document.getElementById('pdfModalClose');
    
    if (downloadBtn && pdfModal) {
        // Open PDF modal when download button is clicked
        downloadBtn.addEventListener('click', function() {
            pdfModal.style.display = 'block';
        });
        
        // Close PDF modal
        pdfModalClose.addEventListener('click', function() {
            pdfModal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === pdfModal) {
                pdfModal.style.display = 'none';
            }
        });
    }
}

// Historical price data simulation
const historicalPriceData = {
    '3days': {
        date: '2025-09-19',
        priceMultiplier: 0.99 // Slightly lower prices
    },
    '7days': {
        date: '2025-09-15',
        priceMultiplier: 0.98 // Lower prices
    },
    '14days': {
        date: '2025-09-08', 
        priceMultiplier: 1.02 // Slightly higher prices
    },
    '30days': {
        date: '2025-08-23',
        priceMultiplier: 1.05 // Higher prices
    }
};

// Download price report as PDF
function downloadPriceReport(period) {
    try {
        // Show loading indicator
        showLoadingIndicator();
        
        // Get price data for the specified period
        const priceData = getPriceDataForPeriod(period);
        
        // Generate PDF content
        const pdfContent = generatePDFContent(priceData, period);
        
        // Create and download PDF
        createAndDownloadPDF(pdfContent, period);
        
        // Hide loading indicator
        hideLoadingIndicator();
        
        // Close the modal
        document.getElementById('pdfDownloadModal').style.display = 'none';
        
        // Show success message
        showNotification(`Price report for ${getPeriodLabel(period)} downloaded successfully!`, 'success');
        
    } catch (error) {
        console.error('Error downloading PDF:', error);
        hideLoadingIndicator();
        showNotification('Error downloading PDF report. Please try again.', 'error');
    }
}

// Get price data for specific period
function getPriceDataForPeriod(period) {
    if (period === 'today') {
        return {
            date: new Date().toLocaleDateString(),
            title: 'Current Commodity Prices',
            data: commodityData
        };
    }
    
    const historical = historicalPriceData[period];
    if (!historical) {
        throw new Error(`No historical data found for period: ${period}`);
    }
    
    // Simulate historical prices by adjusting current prices
    const adjustedData = {};
    Object.keys(commodityData).forEach(category => {
        adjustedData[category] = commodityData[category].map(commodity => ({
            ...commodity,
            prices: {
                alfamart: Math.round(commodity.prices.alfamart * historical.priceMultiplier),
                savemore: Math.round(commodity.prices.savemore * historical.priceMultiplier),
                "public-market": Math.round(commodity.prices["public-market"] * historical.priceMultiplier),
                puregold: Math.round(commodity.prices.puregold * historical.priceMultiplier)
            }
        }));
    });
    
    return {
        date: new Date(historical.date).toLocaleDateString(),
        title: `Commodity Prices - ${getPeriodLabel(period)}`,
        data: adjustedData
    };
}

// Generate PDF content as HTML string
function generatePDFContent(priceData, period) {
    let html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>${priceData.title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; border-bottom: 3px solid #4caf50; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #2d5016; margin: 0; font-size: 2.2rem; }
                .header p { color: #666; margin: 8px 0 0 0; font-size: 1.1rem; }
                .period-badge { background: #4caf50; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; margin-top: 10px; display: inline-block; }
                .category { margin-bottom: 35px; }
                .category h2 { color: #4caf50; border-bottom: 2px solid #e8f5e8; padding-bottom: 8px; margin-bottom: 15px; font-size: 1.4rem; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                th, td { border: 1px solid #ddd; padding: 12px 8px; text-align: left; }
                th { background: linear-gradient(135deg, #f8f9fa, #e9ecef); font-weight: bold; color: #2d5016; }
                .price { font-weight: bold; color: #2e7d32; }
                .commodity-name { font-weight: 600; color: #333; }
                .unit { color: #666; font-style: italic; }
                .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; border-top: 2px solid #e8f5e8; padding-top: 20px; }
                .footer p { margin: 5px 0; }
                .price-highlight { background: #e8f5e8; }
                @media print { body { margin: 0; } .header { border-bottom-color: #333; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🌱 ${priceData.title}</h1>
                <p>Generated on: ${new Date().toLocaleDateString()} | Price Date: ${priceData.date}</p>
                <div class="period-badge">${getPeriodLabel(period)}</div>
                <p><strong>FarmFresh Market - Commodity Price Dashboard</strong></p>
            </div>
    `;
    
    // Add categories and commodities
    Object.keys(priceData.data).forEach(category => {
        const categoryName = category.charAt(0).toUpperCase() + category.slice(1);
        const categoryIcons = {
            fruits: '🍎',
            vegetables: '🥕',
            meats: '🥩',
            rice: '🌾',
            poultry: '🐔',
            dairy: '🥛'
        };
        
        html += `
            <div class="category">
                <h2>${categoryIcons[category] || '📦'} ${categoryName}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Commodity</th>
                            <th>Unit</th>
                            <th>Alfamart</th>
                            <th>SaveMore</th>
                            <th>Public Market</th>
                            <th>Puregold</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        priceData.data[category].forEach(commodity => {
            // Find the lowest price for highlighting
            const prices = [
                commodity.prices.alfamart,
                commodity.prices.savemore,
                commodity.prices["public-market"],
                commodity.prices.puregold
            ];
            const lowestPrice = Math.min(...prices);
            
            html += `
                <tr>
                    <td class="commodity-name">${commodity.name}</td>
                    <td class="unit">${commodity.unit}</td>
                    <td class="price ${commodity.prices.alfamart === lowestPrice ? 'price-highlight' : ''}">₱${commodity.prices.alfamart.toFixed(2)}</td>
                    <td class="price ${commodity.prices.savemore === lowestPrice ? 'price-highlight' : ''}">₱${commodity.prices.savemore.toFixed(2)}</td>
                    <td class="price ${commodity.prices["public-market"] === lowestPrice ? 'price-highlight' : ''}">₱${commodity.prices["public-market"].toFixed(2)}</td>
                    <td class="price ${commodity.prices.puregold === lowestPrice ? 'price-highlight' : ''}">₱${commodity.prices.puregold.toFixed(2)}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    });
    
    html += `
            <div class="footer">
                <p><strong>© 2025 FarmFresh Market. All rights reserved.</strong></p>
                <p>This report contains commodity price information for comparison purposes.</p>
                <p>Prices highlighted in green represent the best deals across all stores.</p>
                <p>Report generated on ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;
    
    return html;
}

// Create and download PDF
function createAndDownloadPDF(htmlContent, period) {
    // Create a blob with the HTML content
    const blob = new Blob([htmlContent], { type: 'text/html' });
    
    // Create download link
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `FarmFresh_Price_Report_${getPeriodLabel(period).replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.html`;
    
    // Trigger download
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Clean up the URL object
    URL.revokeObjectURL(url);
}

// Get period label for display
function getPeriodLabel(period) {
    const labels = {
        'today': 'Today',
        '3days': '3 Days Ago',
        '7days': '7 Days Ago',
        '14days': '14 Days Ago',
        '30days': '30 Days Ago'
    };
    return labels[period] || period;
}

// Show loading indicator
function showLoadingIndicator() {
    // Create loading overlay if it doesn't exist
    if (!document.getElementById('loadingOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loader"></div>
                <p>Generating PDF report...</p>
            </div>
        `;
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            color: white;
            text-align: center;
        `;
        document.body.appendChild(overlay);
    }
    document.getElementById('loadingOverlay').style.display = 'flex';
}

// Hide loading indicator
function hideLoadingIndicator() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Show notification
// Toast notification for smaller alerts
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Set icon based on type
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    if (type === 'warning') icon = 'fa-exclamation-triangle';
    if (type === 'error') icon = 'fa-times-circle';
    
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Style the toast
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        color: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#2196f3'};
        padding: 12px 20px;
        border-left: 4px solid ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#2196f3'};
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 10001;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    `;
    
    // Add toast styles
    const style = document.createElement('style');
    style.textContent = `
        .toast-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toast-content i {
            font-size: 1.2rem;
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Larger notifications for more important alerts
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 90px;
        right: 20px;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#2196f3'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 10001;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    `;
    
    // Add notification content styles
    const style = document.createElement('style');
    style.textContent = `
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notification-content i {
            font-size: 1.2rem;
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// User Account Management
let currentUser = null;

// Check if user is logged in from landing page
function checkUserLogin() {
    // Check localStorage for user data from landing page
    const userData = localStorage.getItem('farmfresh_user');
    if (userData) {
        currentUser = JSON.parse(userData);
        switchToUserAccount();
    }
    
    // Check URL parameters for immediate login status
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('logged_in') === 'true') {
        // Simulate user data if coming from landing page login
        if (!currentUser) {
            currentUser = {
                firstName: 'John',
                lastName: 'Doe',
                email: 'john.doe@email.com',
                userType: 'customer',
                memberSince: 'September 2025',
                reportsDownloaded: 0,
                favoriteStores: 'Public Market, Puregold'
            };
            localStorage.setItem('farmfresh_user', JSON.stringify(currentUser));
        }
        switchToUserAccount();
        
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// Switch from login button to user account
function switchToUserAccount() {
    const loginBtn = document.getElementById('loginBtn');
    const userAccountContainer = document.getElementById('userAccountContainer');
    
    if (loginBtn && userAccountContainer && currentUser) {
        loginBtn.style.display = 'none';
        userAccountContainer.style.display = 'block';
        
        // Update user info in UI
        updateUserInfo();
        
        // Initialize user account functionality
        initializeUserAccount();
    }
}

// Update user information in all relevant places
function updateUserInfo() {
    const fullName = `${currentUser.firstName} ${currentUser.lastName}`;
    
    // Update header button
    document.getElementById('userName').textContent = fullName;
    
    // Update dropdown
    document.getElementById('dropdownUserName').textContent = fullName;
    document.getElementById('userEmail').textContent = currentUser.email;
    document.getElementById('userType').textContent = currentUser.userType.charAt(0).toUpperCase() + currentUser.userType.slice(1);
    
    // Also update the account modal user info
    if (document.getElementById('modalUserName')) {
        document.getElementById('modalUserName').textContent = fullName;
        document.getElementById('modalUserEmail').textContent = currentUser.email;
        document.getElementById('modalUserType').textContent = currentUser.userType.charAt(0).toUpperCase() + currentUser.userType.slice(1);
    }
    
    // Update account modal
    document.getElementById('accountUserName').textContent = fullName;
    document.getElementById('accountUserEmail').textContent = currentUser.email;
    document.getElementById('accountUserType').textContent = currentUser.userType.charAt(0).toUpperCase() + currentUser.userType.slice(1);
    document.getElementById('memberSince').textContent = currentUser.memberSince;
    document.getElementById('reportsDownloaded').textContent = currentUser.reportsDownloaded || 0;
    document.getElementById('favoriteStores').textContent = currentUser.favoriteStores;
    
    // Update edit profile form
    document.getElementById('editFirstName').value = currentUser.firstName;
    document.getElementById('editLastName').value = currentUser.lastName;
    document.getElementById('editEmail').value = currentUser.email;
    document.getElementById('editUserType').value = currentUser.userType;
    if (currentUser.phone) document.getElementById('editPhone').value = currentUser.phone;
    if (currentUser.location) document.getElementById('editLocation').value = currentUser.location;
    if (currentUser.bio) document.getElementById('editBio').value = currentUser.bio;
}

// Initialize user account functionality
function initializeUserAccount() {
    // Initialize account modal
    const accountModal = document.getElementById('accountModal');
    const accountModalClose = document.getElementById('accountModalClose');
    
    // Setup user account button to toggle dropdown
    const userAccountBtn = document.getElementById('userAccountBtn');
    const userDropdown = document.getElementById('userDropdown');
    
    userAccountBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        // Toggle the dropdown
        userDropdown.classList.toggle('show');
        userAccountBtn.classList.toggle('active');
    });
    
    // Close modal when clicking the X
    if (accountModalClose) {
        accountModalClose.addEventListener('click', function() {
            accountModal.style.display = 'none';
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.user-account-container') && userDropdown.classList.contains('show')) {
            userDropdown.classList.remove('show');
            userAccountBtn.classList.remove('active');
        }
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === accountModal) {
            accountModal.style.display = 'none';
        }
    });
    
    // Account menu options
    const accountMenuBtn = document.getElementById('accountMenuBtn');
    if (accountMenuBtn) {
        accountMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Account option selected', 'info');
            accountModal.style.display = 'none';
        });
    }
    
    const editProfileMenuBtn = document.getElementById('editProfileMenuBtn');
    if (editProfileMenuBtn) {
        editProfileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Edit Profile option selected', 'info');
            accountModal.style.display = 'none';
        });
    }
    
    const settingsMenuBtn = document.getElementById('settingsMenuBtn');
    if (settingsMenuBtn) {
        settingsMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Settings option selected', 'info');
            accountModal.style.display = 'none';
        });
    }
    
    const reportMenuBtn = document.getElementById('reportMenuBtn');
    if (reportMenuBtn) {
        reportMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('File a Report option selected', 'info');
            accountModal.style.display = 'none';
        });
    }
    
    // Dropdown menu buttons
    // ACCOUNT button
    const accountBtn = document.getElementById('accountBtn');
    if (accountBtn) {
        accountBtn.addEventListener('click', function(e) {
            e.preventDefault();
            accountModal.style.display = 'block';
            userDropdown.classList.remove('show');
            userAccountBtn.classList.remove('active');
        });
    }
    
    // EDIT PROFILE button
    const editProfileBtn = document.getElementById('editProfileBtn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('editProfileModal').style.display = 'block';
            userDropdown.classList.remove('show');
            userAccountBtn.classList.remove('active');
        });
    }
    
    // SETTINGS button
    const settingsBtn = document.getElementById('settingsBtn');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('settingsModal').style.display = 'block';
            userDropdown.classList.remove('show');
            userAccountBtn.classList.remove('active');
        });
    }
    
    // FILE A REPORT button
    const reportBtn = document.getElementById('reportBtn');
    if (reportBtn) {
        reportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('reportModal').style.display = 'block';
            userDropdown.classList.remove('show');
            userAccountBtn.classList.remove('active');
        });
    }
    
    // Report Modal Close Button
    const reportModalClose = document.getElementById('reportModalClose');
    if (reportModalClose) {
        reportModalClose.addEventListener('click', function() {
            document.getElementById('reportModal').style.display = 'none';
        });
    }
    
    // Report Form File Upload Preview
    const reportImage = document.getElementById('reportImage');
    const filePreview = document.getElementById('filePreview');
    
    if (reportImage) {
        reportImage.addEventListener('change', function() {
            filePreview.innerHTML = '';
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    filePreview.appendChild(img);
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Report Form Submit
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const reportType = document.getElementById('reportType').value;
            const reportDescription = document.getElementById('reportDescription').value;
            
            // Here you would normally send the form data to the server
            // For demo purposes, we'll just show a notification
            showNotification(`Report submitted: ${reportType}`, 'success');
            
            // Reset form and close modal
            this.reset();
            filePreview.innerHTML = '';
            document.getElementById('reportModal').style.display = 'none';
        });
    }
    
    // Cancel Report Button
    const cancelReportBtn = document.getElementById('cancelReportBtn');
    if (cancelReportBtn) {
        cancelReportBtn.addEventListener('click', function() {
            document.getElementById('reportForm').reset();
            document.getElementById('filePreview').innerHTML = '';
            document.getElementById('reportModal').style.display = 'none';
        });
    }
    
    // LOG OUT button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification('Logging out...', 'info');
            setTimeout(function() {
                // Hide user account button and show login button
                document.getElementById('userAccountContainer').style.display = 'none';
                document.getElementById('loginBtn').style.display = 'block';
            }, 1000);
            userDropdown.classList.remove('show');
            userAccountBtn.classList.remove('active');
        });
    }
    
    const logoutMenuBtn = document.getElementById('logoutMenuBtn');
    if (logoutMenuBtn) {
        logoutMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleLogout();
            accountModal.style.display = 'none';
        });
    }
}

// Initialize user modals
function initializeUserModals() {
    // Account Modal
    const accountBtn = document.getElementById('accountBtn');
    const accountModal = document.getElementById('accountModal');
    const accountModalClose = document.getElementById('accountModalClose');
    
    accountBtn.addEventListener('click', function() {
        accountModal.style.display = 'block';
        document.getElementById('userDropdown').classList.remove('show');
        document.getElementById('userAccountBtn').classList.remove('active');
    });
    
    accountModalClose.addEventListener('click', function() {
        accountModal.style.display = 'none';
    });
    
    // Edit Profile Modal
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    const editProfileModalClose = document.getElementById('editProfileModalClose');
    const editProfileForm = document.getElementById('editProfileForm');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    editProfileBtn.addEventListener('click', function() {
        editProfileModal.style.display = 'block';
        document.getElementById('userDropdown').classList.remove('show');
        document.getElementById('userAccountBtn').classList.remove('active');
    });
    
    editProfileModalClose.addEventListener('click', function() {
        editProfileModal.style.display = 'none';
    });
    
    cancelEditBtn.addEventListener('click', function() {
        editProfileModal.style.display = 'none';
    });
    
    editProfileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveProfileChanges();
    });
    
    // Settings Modal
    const settingsBtn = document.getElementById('settingsBtn');
    const settingsModal = document.getElementById('settingsModal');
    const settingsModalClose = document.getElementById('settingsModalClose');
    
    settingsBtn.addEventListener('click', function() {
        settingsModal.style.display = 'block';
        document.getElementById('userDropdown').classList.remove('show');
        document.getElementById('userAccountBtn').classList.remove('active');
    });
    
    settingsModalClose.addEventListener('click', function() {
        settingsModal.style.display = 'none';
    });
    
    // Logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    logoutBtn.addEventListener('click', function() {
        handleLogout();
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === accountModal) {
            accountModal.style.display = 'none';
        }
        if (e.target === editProfileModal) {
            editProfileModal.style.display = 'none';
        }
        if (e.target === settingsModal) {
            settingsModal.style.display = 'none';
        }
    });
    
    // Initialize settings functionality
    initializeSettings();
}

// Save profile changes
function saveProfileChanges() {
    currentUser.firstName = document.getElementById('editFirstName').value;
    currentUser.lastName = document.getElementById('editLastName').value;
    currentUser.email = document.getElementById('editEmail').value;
    currentUser.userType = document.getElementById('editUserType').value;
    currentUser.phone = document.getElementById('editPhone').value;
    currentUser.location = document.getElementById('editLocation').value;
    currentUser.bio = document.getElementById('editBio').value;
    
    // Save to localStorage
    localStorage.setItem('farmfresh_user', JSON.stringify(currentUser));
    
    // Update UI
    updateUserInfo();
    
    // Close modal
    document.getElementById('editProfileModal').style.display = 'none';
    
    // Show success notification
    showNotification('Profile updated successfully!', 'success');
}

// Initialize settings functionality
function initializeSettings() {
    // Export data button
    const exportDataBtn = document.getElementById('exportDataBtn');
    exportDataBtn.addEventListener('click', function() {
        exportUserData();
    });
    
    // Clear data button
    const clearDataBtn = document.getElementById('clearDataBtn');
    clearDataBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear all local data? This action cannot be undone.')) {
            localStorage.clear();
            showNotification('Local data cleared successfully!', 'success');
        }
    });
    
    // Change password button
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    changePasswordBtn.addEventListener('click', function() {
        showNotification('Password change functionality would be implemented here.', 'info');
    });
    
    // Delete account button
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    deleteAccountBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            handleAccountDeletion();
        }
    });
}

// Export user data
function exportUserData() {
    const dataToExport = {
        user: currentUser,
        preferences: {
            priceAlerts: document.getElementById('priceAlerts').checked,
            weeklyReports: document.getElementById('weeklyReports').checked,
            newProducts: document.getElementById('newProducts').checked
        },
        settings: {
            emailNotifications: document.getElementById('emailNotifications').checked,
            pushNotifications: document.getElementById('pushNotifications').checked,
            smsAlerts: document.getElementById('smsAlerts').checked,
            publicProfile: document.getElementById('publicProfile').checked,
            shareActivity: document.getElementById('shareActivity').checked,
            theme: document.getElementById('themeSelect').value,
            language: document.getElementById('languageSelect').value
        },
        exportDate: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(dataToExport, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `FarmFresh_UserData_${currentUser.firstName}_${new Date().toISOString().split('T')[0]}.json`;
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
    
    showNotification('User data exported successfully!', 'success');
}

// Handle account deletion
function handleAccountDeletion() {
    localStorage.removeItem('farmfresh_user');
    currentUser = null;
    
    // Revert to login button
    document.getElementById('loginBtn').style.display = 'block';
    document.getElementById('userAccountContainer').style.display = 'none';
    
    // Close settings modal
    document.getElementById('settingsModal').style.display = 'none';
    
    showNotification('Account deleted successfully. You have been logged out.', 'success');
}

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('farmfresh_user');
        currentUser = null;
        
        // Revert to login button
        document.getElementById('loginBtn').style.display = 'block';
        document.getElementById('userAccountContainer').style.display = 'none';
        
        // Close dropdown
        document.getElementById('userDropdown').classList.remove('show');
        document.getElementById('userAccountBtn').classList.remove('active');
        
        // Close modal if it exists
        const accountModal = document.getElementById('accountModal');
        if (accountModal) {
            accountModal.style.display = 'none';
        }
        
        showNotification('You have been logged out successfully.', 'success');
    }
}

// Update the main initialization to include user check
document.addEventListener('DOMContentLoaded', function() {
    checkUserLogin();
    // ... rest of existing initialization
});
