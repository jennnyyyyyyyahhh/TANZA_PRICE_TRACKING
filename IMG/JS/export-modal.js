document.addEventListener('DOMContentLoaded', function () {
    const exportModal = document.getElementById('exportModal');
    const exportDataBtn = document.getElementById('exportDataBtn');
    const exportModalClose = document.getElementById('exportModalClose');
    const fileFormatOptions = document.querySelectorAll('.file-format-option');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    // Set default dates (last month)
    function setDefaultDates() {
        const today = new Date();
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
        
        startDateInput.value = lastMonth.toISOString().split('T')[0];
        endDateInput.value = today.toISOString().split('T')[0];
        
        // Set max date to today
        endDateInput.max = today.toISOString().split('T')[0];
    }

    // Show the modal
    exportDataBtn.addEventListener('click', () => {
        setDefaultDates();
        exportModal.style.display = 'block';
    });

    // Hide the modal
    exportModalClose.addEventListener('click', () => {
        exportModal.style.display = 'none';
    });

    // Hide modal on outside click
    window.addEventListener('click', (event) => {
        if (event.target === exportModal) {
            exportModal.style.display = 'none';
        }
    });

    // Validate date range
    startDateInput.addEventListener('change', () => {
        if (startDateInput.value && endDateInput.value) {
            if (new Date(startDateInput.value) > new Date(endDateInput.value)) {
                alert('Start date cannot be after end date');
                startDateInput.value = '';
            }
        }
    });

    endDateInput.addEventListener('change', () => {
        if (startDateInput.value && endDateInput.value) {
            if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                alert('End date cannot be before start date');
                endDateInput.value = '';
            }
        }
    });

    // Handle file format selection
    fileFormatOptions.forEach(option => {
        option.addEventListener('click', () => {
            fileFormatOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });

    // Handle report generation
    generateReportBtn.addEventListener('click', () => {
        // Validate dates
        if (!startDateInput.value || !endDateInput.value) {
            alert('Please select both start and end dates');
            return;
        }

        // Get selected format
        const selectedFormat = document.querySelector('.file-format-option.selected');
        if (!selectedFormat) {
            alert('Please select a file format');
            return;
        }

        // Get selected options
        const selectedOptions = Array.from(document.querySelectorAll('input[name="include"]:checked'))
            .map(cb => cb.value);

        alert(`Generating report from ${startDateInput.value} to ${endDateInput.value} in ${selectedFormat.dataset.format.toUpperCase()} format...`);
        exportModal.style.display = 'none';
    });
});
