// Specific JavaScript for cleaning.html

document.addEventListener('DOMContentLoaded', function() {
    // Placeholder for cleaning request form submission logic
    const cleaningForm = document.querySelector('.cleaning-request-form');
    const resetBtn = document.getElementById('resetCleaningForm');
    const submitBtn = document.getElementById('submitCleaningRequest');
    const preferredDateInput = document.getElementById('preferredDate');

    // Set minimum date for preferred date input to today
    if (preferredDateInput) {
        const today = new Date().toISOString().split('T')[0];
        preferredDateInput.setAttribute('min', today);
    }

    if (cleaningForm) {
        cleaningForm.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Cleaning Request Submitted! (Placeholder functionality)');
            // In a real application, this would send data to a server
            cleaningForm.reset();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            cleaningForm.reset();
            // Re-set the min date after reset
            if (preferredDateInput) {
                const today = new Date().toISOString().split('T')[0];
                preferredDateInput.setAttribute('min', today);
            }
        });
    }
});
