// Market Price Analytics functionality
document.addEventListener('DOMContentLoaded', function() {
    setupMarketPriceAnalytics();
});

// Setup Market Price Analytics functionality
function setupMarketPriceAnalytics() {
    const timeRangeFilter = document.getElementById('timeRangeFilter');
    if (!timeRangeFilter) return;
    
    // Current date display
    document.getElementById('priceDate').textContent = 'September 26, 2025';
    
    // Sample data for different time periods with historical price data
    const priceData = {
        week: {
            produce: { 
                current: '$3.50/lb', 
                previous: '$3.40/lb', 
                change: '+3%',
                history: [3.42, 3.45, 3.48, 3.49, 3.50]
            },
            meat: { 
                current: '$8.25/lb', 
                previous: '$8.15/lb', 
                change: '+1%',
                history: [8.15, 8.18, 8.22, 8.24, 8.25]
            },
            seafood: { 
                current: '$12.00/lb', 
                previous: '$11.75/lb', 
                change: '+2%',
                history: [11.75, 11.80, 11.85, 11.95, 12.00]
            },
            dairy: { 
                current: '$4.10/lb', 
                previous: '$4.25/lb', 
                change: '-4%',
                history: [4.25, 4.20, 4.18, 4.15, 4.10]
            }
        },
        month: {
            produce: { 
                current: '$3.75/lb', 
                previous: '$3.00/lb', 
                change: '+25%',
                history: [3.00, 3.25, 3.45, 3.60, 3.75]
            },
            meat: { 
                current: '$8.50/lb', 
                previous: '$8.00/lb', 
                change: '+6%',
                history: [8.00, 8.10, 8.25, 8.40, 8.50]
            },
            seafood: { 
                current: '$12.25/lb', 
                previous: '$13.50/lb', 
                change: '-9%',
                history: [13.50, 13.25, 12.90, 12.40, 12.25]
            },
            dairy: { 
                current: '$4.25/lb', 
                previous: '$3.85/lb', 
                change: '+10%',
                history: [3.85, 3.95, 4.05, 4.15, 4.25]
            }
        },
        quarter: {
            produce: { 
                current: '$3.75/lb', 
                previous: '$2.80/lb', 
                change: '+34%',
                history: [2.80, 3.15, 3.35, 3.55, 3.75]
            },
            meat: { 
                current: '$8.50/lb', 
                previous: '$7.25/lb', 
                change: '+17%',
                history: [7.25, 7.65, 8.00, 8.30, 8.50]
            },
            seafood: { 
                current: '$12.25/lb', 
                previous: '$10.50/lb', 
                change: '+17%',
                history: [10.50, 11.00, 11.50, 12.00, 12.25]
            },
            dairy: { 
                current: '$4.25/lb', 
                previous: '$3.50/lb', 
                change: '+21%',
                history: [3.50, 3.75, 3.90, 4.10, 4.25]
            }
        },
        year: {
            produce: { 
                current: '$3.75/lb', 
                previous: '$2.50/lb', 
                change: '+50%',
                history: [2.50, 2.80, 3.20, 3.50, 3.75]
            },
            meat: { 
                current: '$8.50/lb', 
                previous: '$6.75/lb', 
                change: '+26%',
                history: [6.75, 7.25, 7.75, 8.25, 8.50]
            },
            seafood: { 
                current: '$12.25/lb', 
                previous: '$9.50/lb', 
                change: '+29%',
                history: [9.50, 10.25, 11.00, 11.75, 12.25]
            },
            dairy: { 
                current: '$4.25/lb', 
                previous: '$3.25/lb', 
                change: '+31%',
                history: [3.25, 3.50, 3.75, 4.00, 4.25]
            }
        }
    };
    
    // Function to draw line chart
    function drawLineChart(canvas, data, color) {
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);
        
        // Find min and max values for scaling
        const minValue = Math.min(...data);
        const maxValue = Math.max(...data);
        const valueRange = maxValue - minValue;
        
        // Calculate points
        const points = [];
        const segmentWidth = width / (data.length - 1);
        
        for (let i = 0; i < data.length; i++) {
            // Add some padding at the top and bottom (10%)
            const normalizedValue = (data[i] - minValue) / (valueRange || 1);
            const y = height - (normalizedValue * height * 0.8 + height * 0.1);
            points.push({
                x: i * segmentWidth,
                y: y
            });
        }
        
        // Draw grid lines
        ctx.strokeStyle = '#e0e0e0';
        ctx.lineWidth = 0.5;
        
        // Horizontal grid lines
        for (let i = 0; i <= 4; i++) {
            const y = height * i / 4;
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(width, y);
            ctx.stroke();
        }
        
        // Draw line
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        
        for (let i = 1; i < points.length; i++) {
            ctx.lineTo(points[i].x, points[i].y);
        }
        ctx.stroke();
        
        // Draw points
        ctx.fillStyle = color;
        points.forEach(point => {
            ctx.beginPath();
            ctx.arc(point.x, point.y, 3, 0, Math.PI * 2);
            ctx.fill();
        });
    }
    
    // Function to update chart data
    function updateCharts(timeRange) {
        const data = priceData[timeRange];
        
        // Update date ranges based on the selected time range
        updateDateRanges(timeRange);
        
        // Update Produce Chart
        updateChart('produceChart', data.produce);
        
        // Update Meat Chart
        updateChart('meatChart', data.meat);
        
        // Update Seafood Chart
        updateChart('seafoodChart', data.seafood);
        
        // Update Dairy Chart
        updateChart('dairyChart', data.dairy);
    }
    
    // Function to update date ranges displayed under charts
    function updateDateRanges(timeRange) {
        const dateSets = document.querySelectorAll('.chart-dates');
        const today = new Date(2025, 8, 26); // September 26, 2025
        
        dateSets.forEach(dateSet => {
            const spans = dateSet.querySelectorAll('span');
            let dates = [];
            
            switch(timeRange) {
                case 'week':
                    // Generate dates for the last 5 days
                    for (let i = 4; i >= 0; i--) {
                        const date = new Date(today);
                        date.setDate(today.getDate() - i);
                        dates.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                    }
                    break;
                case 'month':
                    // Generate dates for the last month (weekly intervals)
                    dates = ['Aug 27', 'Sep 5', 'Sep 12', 'Sep 19', 'Sep 26'];
                    break;
                case 'quarter':
                    // Generate dates for the last quarter (monthly intervals)
                    dates = ['Jun 26', 'Jul 26', 'Aug 12', 'Sep 5', 'Sep 26'];
                    break;
                case 'year':
                    // Generate dates for the last year (quarterly intervals)
                    dates = ['Sep 26, 24', 'Dec 26', 'Mar 26', 'Jun 26', 'Sep 26'];
                    break;
            }
            
            // Update the date labels
            spans.forEach((span, i) => {
                span.textContent = dates[i] || '';
            });
        });
    }
    
    // Function to update individual chart
    function updateChart(chartId, data) {
        const chart = document.getElementById(chartId);
        const priceChange = chart.querySelector('.price-change');
        const priceCurrent = chart.querySelector('.price-current');
        const canvas = chart.querySelector('.line-chart');
        
        // Set canvas dimensions to match its display size
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        
        // Update price display
        priceCurrent.textContent = data.current;
        priceChange.textContent = data.change;
        
        if (data.change.includes('+')) {
            priceChange.classList.remove('decrease');
            priceChange.classList.add('increase');
        } else {
            priceChange.classList.remove('increase');
            priceChange.classList.add('decrease');
        }
        
        // Draw the line chart
        drawLineChart(canvas, data.history, 
            data.change.includes('+') ? '#27ae60' : '#e74c3c');
    }
    
    // Initialize charts with default (month) data
    updateCharts('month');
    
    // Add event listener for time range filter
    timeRangeFilter.addEventListener('change', function() {
        updateCharts(this.value);
    });
    
    // Make sure charts are responsive on window resize
    window.addEventListener('resize', function() {
        updateCharts(timeRangeFilter.value);
    });
    
    // View detailed report button
    const viewReportBtn = document.querySelector('.view-detailed-report');
    if (viewReportBtn) {
        viewReportBtn.addEventListener('click', function() {
            alert('Detailed market price report will be generated. Feature coming soon!');
        });
    }
}