// Carousel functionality for the landing page
document.addEventListener('DOMContentLoaded', function() {
    initializeCarousel();
});

function initializeCarousel() {
    const carouselSlides = document.querySelectorAll('.carousel-slide');
    const nextBtn = document.querySelector('.next-btn');
    
    // Check if carousel exists on the page
    if (!carouselSlides.length) {
        return;
    }
    
    let currentSlide = 0;
    const totalSlides = carouselSlides.length;
    
    // Auto-advance carousel
    let carouselInterval = setInterval(nextSlide, 5000);
    
    // Initialize carousel
    function updateCarousel() {
        // Reset all slides
        carouselSlides.forEach(slide => slide.classList.remove('active'));
        
        // Set current slide as active
        carouselSlides[currentSlide].classList.add('active');
    }
    
    // Next slide function
    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        updateCarousel();
    }
    
    // Previous slide function
    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }
    
    // Event listeners for carousel navigation buttons
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearInterval(carouselInterval);
            nextSlide();
            carouselInterval = setInterval(nextSlide, 5000);
        });
    }
    
    // Pause carousel on hover
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', function() {
            clearInterval(carouselInterval);
        });
        
        carouselContainer.addEventListener('mouseleave', function() {
            carouselInterval = setInterval(nextSlide, 5000);
        });
    }
    
    // Initialize the carousel with the first slide active
    updateCarousel();
}