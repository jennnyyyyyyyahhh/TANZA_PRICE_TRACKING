// Specific JavaScript for survey-form.html

document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill today's date in survey form
    const surveyDateInput = document.getElementById('surveyDate');
    if (surveyDateInput) {
        const today = new Date().toISOString().split('T')[0];
        surveyDateInput.value = today;
    }
    
    // Add logic for dynamic commodity item management (if any was in the original vendor-dashboard.js)
    // Since the original vendor-dashboard.js was not provided, we assume the core functionality
    // is contained within the shared script and the commodity management logic is self-contained 
    // or relies on external files (survey-form.js) which we will link.
});
