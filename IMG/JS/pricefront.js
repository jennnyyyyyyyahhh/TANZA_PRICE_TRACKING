// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (navMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        // Close mobile menu when clicking on nav links
        const navLinks = navMenu.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenuToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                if (navMenu.classList.contains('active')) {
                    mobileMenuToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.body.style.overflow = '';
            if (mobileMenuToggle && navMenu) {
                mobileMenuToggle.classList.remove('active');
                navMenu.classList.remove('active');
            }
        }
    });
});

// Category selection function
function selectCategory(category) {
    // Add loading animation
    const categoryCard = event.currentTarget;
    categoryCard.classList.add('loading');
    
    // Simulate loading and navigation
    setTimeout(() => {
        categoryCard.classList.remove('loading');
        
        // Navigate to specific category pages
        const categoryPages = {
            'vegetables': 'vegetables.html',
            'fruits': 'fruits.html',
            'meat-poultry': 'meat-poultry.html',
            'fish': 'fish.html',
            'rice-grains': 'rice-grains.html',
            'other': 'other.html'
        };

        if (categoryPages[category]) {
            window.location.href = categoryPages[category];
            return;
        }
        
        // Fallback for unknown categories
        alert(`Category not found!`);
        
    }, 800);
}

// Keyboard navigation for category cards
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
});

// Notification dropdown functionality
const notificationBell = document.getElementById('notificationBell');
const notificationDropdown = document.getElementById('notificationDropdown');

if (notificationBell && notificationDropdown) {
    notificationBell.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('show');
        }
    });

    // Mark all as read functionality
    const markReadBtn = notificationDropdown.querySelector('.mark-read');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function() {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.style.display = 'none';
            }
            notificationDropdown.classList.remove('show');
        });
    }
}

// Update notification badge (example)
setTimeout(() => {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.textContent = '3';
    }
}, 3000);

console.log('🛒 Commodity Price Dashboard loaded successfully!');

// ============================================
// PDF Download Functionality
// ============================================
const downloadPdfBtn = document.getElementById('downloadPdfBtn');

if (downloadPdfBtn) {
    downloadPdfBtn.addEventListener('click', async function() {
        try {
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Generating PDF...</span>';
            
            // Sample price data (in production, fetch from API)
            const priceData = {
                reportDate: new Date().toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                }),
                categories: [
                    {
                        name: 'Rice & Grains',
                        items: [
                            { name: 'White Rice (Regular)', price: '₱48.00/kg', market: 'Cavite Public Market' },
                            { name: 'Brown Rice', price: '₱65.00/kg', market: 'Cavite Public Market' },
                            { name: 'Jasmine Rice', price: '₱75.00/kg', market: 'Cavite Public Market' }
                        ]
                    },
                    {
                        name: 'Vegetables',
                        items: [
                            { name: 'Tomatoes', price: '₱35.00/kg', market: 'Morning Market' },
                            { name: 'Onions', price: '₱45.00/kg', market: 'Morning Market' },
                            { name: 'Cabbage', price: '₱40.00/kg', market: 'Morning Market' }
                        ]
                    },
                    {
                        name: 'Fruits',
                        items: [
                            { name: 'Bananas', price: '₱60.00/kg', market: 'Central Market' },
                            { name: 'Mangoes', price: '₱85.00/kg', market: 'Central Market' },
                            { name: 'Papaya', price: '₱50.00/kg', market: 'Central Market' }
                        ]
                    },
                    {
                        name: 'Meat & Poultry',
                        items: [
                            { name: 'Chicken (Whole)', price: '₱180.00/kg', market: 'Public Market' },
                            { name: 'Pork (Belly)', price: '₱280.00/kg', market: 'Public Market' },
                            { name: 'Beef (Ground)', price: '₱350.00/kg', market: 'Public Market' }
                        ]
                    },
                    {
                        name: 'Fish & Seafood',
                        items: [
                            { name: 'Tilapia', price: '₱120.00/kg', market: 'Coastal Market' },
                            { name: 'Bangus (Milkfish)', price: '₱140.00/kg', market: 'Coastal Market' },
                            { name: 'Shrimp', price: '₱450.00/kg', market: 'Coastal Market' }
                        ]
                    }
                ]
            };
            
            // Generate PDF using jsPDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Title
            doc.setFontSize(20);
            doc.setTextColor(45, 80, 22);
            doc.text('Tanza Public Market', 105, 20, { align: 'center' });
            
            doc.setFontSize(16);
            doc.text('Commodity Price Report', 105, 30, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setTextColor(102, 102, 102);
            doc.text(`Report Date: ${priceData.reportDate}`, 105, 38, { align: 'center' });
            
            // Starting Y position
            let yPos = 50;
            
            // Loop through categories
            priceData.categories.forEach((category, index) => {
                // Check if we need a new page
                if (yPos > 250) {
                    doc.addPage();
                    yPos = 20;
                }
                
                // Category header
                doc.setFontSize(14);
                doc.setTextColor(76, 175, 80);
                doc.text(category.name, 20, yPos);
                yPos += 8;
                
                // Draw line
                doc.setDrawColor(200, 200, 200);
                doc.line(20, yPos, 190, yPos);
                yPos += 8;
                
                // Items
                doc.setFontSize(10);
                doc.setTextColor(51, 51, 51);
                
                category.items.forEach(item => {
                    if (yPos > 270) {
                        doc.addPage();
                        yPos = 20;
                    }
                    
                    doc.text(`• ${item.name}`, 25, yPos);
                    doc.text(item.price, 120, yPos);
                    doc.setTextColor(102, 102, 102);
                    doc.setFontSize(9);
                    doc.text(item.market, 25, yPos + 5);
                    doc.setFontSize(10);
                    doc.setTextColor(51, 51, 51);
                    yPos += 12;
                });
                
                yPos += 5;
            });
            
            // Footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(150, 150, 150);
                doc.text(`Page ${i} of ${pageCount}`, 105, 290, { align: 'center' });
                doc.text('Generated by Tanza Public Market System', 105, 285, { align: 'center' });
            }
            
            // Save PDF
            doc.save(`Tanza_Public_Market_Price_Report_${new Date().toISOString().split('T')[0]}.pdf`);
            
            // Reset button
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-file-pdf"></i><span>Download Price Report (PDF)</span>';
            
            // Show success message
            alert('✅ PDF downloaded successfully!');
            
        } catch (error) {
            console.error('PDF generation error:', error);
            alert('❌ Error generating PDF. Please try again.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-file-pdf"></i><span>Download Price Report (PDF)</span>';
        }
    });
}

// ============================================
// Report Overpricing Modal Functionality
// ============================================

// Modal elements
const reportModal = document.getElementById('reportModal');
const reportOverpriceBtn = document.getElementById('reportOverpriceBtn');
const modalClose = document.getElementById('modalClose');

// Form steps
const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');
const successStep = document.getElementById('successStep');

// Forms
const emailForm = document.getElementById('emailForm');
const otpForm = document.getElementById('otpForm');
const reportForm = document.getElementById('reportForm');

// Store user data
let userData = {
    email: '',
    otp: '',
    verified: false
};

// Open modal
if (reportOverpriceBtn) {
    reportOverpriceBtn.addEventListener('click', function() {
        reportModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });
}

// Close modal
function closeModal() {
    reportModal.classList.remove('show');
    document.body.style.overflow = '';
    // Reset form
    resetModal();
}

if (modalClose) {
    modalClose.addEventListener('click', closeModal);
}

// Close on outside click
reportModal.addEventListener('click', function(e) {
    if (e.target === reportModal) {
        closeModal();
    }
});

// Reset modal to initial state
function resetModal() {
    step1.classList.remove('hidden');
    step2.classList.add('hidden');
    step3.classList.add('hidden');
    successStep.classList.add('hidden');
    emailForm.reset();
    otpForm.reset();
    reportForm.reset();
    userData = { email: '', otp: '', verified: false };
}

// Step 1: Email submission
emailForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('userEmail').value;
    
    // Check rate limit (1 report per day per email)
    const lastReport = localStorage.getItem(`lastReport_${email}`);
    if (lastReport) {
        const lastReportDate = new Date(lastReport);
        const now = new Date();
        const hoursDiff = (now - lastReportDate) / (1000 * 60 * 60);
        
        if (hoursDiff < 24) {
            const hoursLeft = Math.ceil(24 - hoursDiff);
            alert(`⚠️ You can only submit one report per day. Please try again in ${hoursLeft} hour(s).`);
            return;
        }
    }
    
    // Simulate sending OTP (in production, call backend API)
    const generatedOtp = Math.floor(100000 + Math.random() * 900000).toString();
    
    // Store OTP in sessionStorage (in production, verify on backend)
    sessionStorage.setItem('verificationOtp', generatedOtp);
    userData.email = email;
    
    // For demo purposes, show the OTP in console
    console.log('🔐 Verification OTP:', generatedOtp);
    
    // Show temporary alert with OTP (remove in production)
    alert(`📧 Verification code sent to ${email}\n\nDemo OTP: ${generatedOtp}\n(In production, this will be sent via email)`);
    
    // Move to step 2
    document.getElementById('emailDisplay').textContent = email;
    step1.classList.add('hidden');
    step2.classList.remove('hidden');
});

// Step 2: OTP verification
const otpInputs = document.querySelectorAll('.otp-input');

// Auto-focus next input
otpInputs.forEach((input, index) => {
    input.addEventListener('input', function() {
        if (this.value.length === 1 && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
        }
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value === '' && index > 0) {
            otpInputs[index - 1].focus();
        }
    });
});

otpForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Collect OTP
    let enteredOtp = '';
    otpInputs.forEach(input => {
        enteredOtp += input.value;
    });
    
    const storedOtp = sessionStorage.getItem('verificationOtp');
    
    if (enteredOtp === storedOtp) {
        userData.verified = true;
        step2.classList.add('hidden');
        step3.classList.remove('hidden');
    } else {
        alert('❌ Invalid verification code. Please try again.');
        otpInputs.forEach(input => input.value = '');
        otpInputs[0].focus();
    }
});

// Resend OTP
const resendOtp = document.getElementById('resendOtp');
if (resendOtp) {
    resendOtp.addEventListener('click', function(e) {
        e.preventDefault();
        const generatedOtp = Math.floor(100000 + Math.random() * 900000).toString();
        sessionStorage.setItem('verificationOtp', generatedOtp);
        console.log('🔐 New Verification OTP:', generatedOtp);
        alert(`📧 New verification code sent!\n\nDemo OTP: ${generatedOtp}`);
    });
}

// Step 3: Report submission
const evidencePhoto = document.getElementById('evidencePhoto');
const fileName = document.getElementById('fileName');
const imagePreview = document.getElementById('imagePreview');

// File upload preview
evidencePhoto.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        fileName.textContent = file.name;
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            imagePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});

reportForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!userData.verified) {
        alert('❌ Email verification required.');
        return;
    }
    
    // Collect form data
    const reportData = {
        email: userData.email,
        stallName: document.getElementById('stallName').value,
        itemName: document.getElementById('itemName').value,
        reportedPrice: document.getElementById('reportedPrice').value,
        expectedPrice: document.getElementById('expectedPrice').value,
        description: document.getElementById('description').value,
        evidencePhoto: evidencePhoto.files[0] ? evidencePhoto.files[0].name : null,
        timestamp: new Date().toISOString()
    };
    
    // In production, send to backend API
    console.log('📋 Report submitted:', reportData);
    
    // Store last report timestamp
    localStorage.setItem(`lastReport_${userData.email}`, new Date().toISOString());
    
    // Show success
    step3.classList.add('hidden');
    successStep.classList.remove('hidden');
});

// Close success and modal
const closeSuccess = document.getElementById('closeSuccess');
if (closeSuccess) {
    closeSuccess.addEventListener('click', closeModal);
}