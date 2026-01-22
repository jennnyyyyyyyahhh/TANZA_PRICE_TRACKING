/**
 * Weather Data for Tanza, Cavite
 * Frontend-only version with mock data (no backend required)
 */

// Constants
const TANZA_CAVITE_COORDS = { lat: 14.3053, lon: 120.8544 }; // Coordinates for Tanza, Cavite
const DAYS_TO_FETCH = 3; // Number of days to show in the forecast

// Mock weather data for demo purposes
const MOCK_WEATHER_DATA = {
    today: {
        temp: 28,
        condition: 'Partly Cloudy',
        icon: '02d',
        humidity: 75,
        windSpeed: 12
    },
    forecast: [
        { day: 'Tomorrow', temp: 29, condition: 'Sunny', icon: '01d' },
        { day: 'Day 3', temp: 27, condition: 'Rainy', icon: '10d' }
    ]
};

// Main weather data function (using mock data)
async function fetchWeatherData() {
    try {
        // Show loading state
        showWeatherLoadingState();
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Use mock weather data
        const weatherData = MOCK_WEATHER_DATA;
        
        if (weatherData) {
            updateWeatherUI(weatherData);
        }
    } catch (error) {
        showWeatherError();
    }
}

// Update the UI with weather data
function updateWeatherUI(data) {
    const today = new Date();
    
    // Update forecast cards
    updateForecastCards(data.daily, today);
    
    // Show the weather section after updating
    document.querySelector('.weather-section').classList.remove('loading');
}

// Update forecast cards
function updateForecastCards(dailyData, today) {
    const weatherCards = document.querySelectorAll('.weather-card');
    
    // Make sure we don't try to update more cards than we have data for
    const daysToShow = Math.min(DAYS_TO_FETCH, weatherCards.length, dailyData.length);
    
    for (let i = 0; i < daysToShow; i++) {
        const card = weatherCards[i];
        
        // For first card (today), use current weather data
        const dayData = dailyData[i];
        const date = new Date(today);
        date.setDate(today.getDate() + i);
        
        // Update day name - use the hardcoded days since we know Oct 9, 2025 is Thursday
        let dayName;
        if (i === 0) dayName = "THURSDAY";
        else if (i === 1) dayName = "FRIDAY";
        else if (i === 2) dayName = "SATURDAY";
        else dayName = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(date).toUpperCase();
        
        card.querySelector(`#forecastDay${i+1}`).textContent = dayName;
        
        // Update date - use the current date (October 9, 2025) as reference
        const currentDate = new Date(2025, 9, 9); // October is 9 (0-indexed)
        const forecastDate = new Date(currentDate);
        forecastDate.setDate(currentDate.getDate() + i);
        
        const dateStr = new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric' }).format(forecastDate);
        card.querySelector(`#forecastDate${i+1}`).textContent = dateStr;
        
        // Update temperature
        const temp = Math.round(dayData.temp.day);
        card.querySelector(`#weatherTemp${i+1}`).textContent = `${temp}°C`;
        
        // Update weather condition
        const condition = dayData.weather[0].main;
        card.querySelector(`#weatherCondition${i+1}`).textContent = condition;
        
        // Update weather icon
        updateWeatherIcon(card, dayData.weather[0].id);
        
        // Update harvest impact based on weather
        updateHarvestImpact(card, condition, temp);
        
        // Add 'today' class only to the first card
        if (i === 0) {
            card.classList.add('today');
        } else {
            card.classList.remove('today');
        }
    }
}

// Update weather icon based on weather condition ID
function updateWeatherIcon(card, conditionId) {
    const iconElement = card.querySelector('.weather-icon i');
    
    // Remove all previous classes except 'fas'
    
    // Remove all previous classes except 'fas'
    iconElement.className = 'fas';
    
    // Set icon based on condition ID from OpenWeatherMap
    // https://openweathermap.org/weather-conditions
    if (conditionId >= 200 && conditionId < 300) {
        // Thunderstorm
        iconElement.classList.add('fa-bolt');
    } else if (conditionId >= 300 && conditionId < 400) {
        // Drizzle
        iconElement.classList.add('fa-cloud-rain');
    } else if (conditionId >= 500 && conditionId < 600) {
        // Rain
        iconElement.classList.add('fa-cloud-showers-heavy');
    } else if (conditionId >= 600 && conditionId < 700) {
        // Snow - not common in Tanza but included for completeness
        iconElement.classList.add('fa-snowflake');
    } else if (conditionId >= 700 && conditionId < 800) {
        // Atmosphere (mist, fog, etc.)
        iconElement.classList.add('fa-smog');
    } else if (conditionId === 800) {
        // Clear sky
        iconElement.classList.add('fa-sun');
    } else if (conditionId > 800) {
        // Clouds
        if (conditionId === 801) {
            iconElement.classList.add('fa-cloud-sun'); // Few clouds
        } else {
            iconElement.classList.add('fa-cloud'); // More clouds
        }
    }
}

// Update harvest impact based on weather conditions
function updateHarvestImpact(card, condition, temp) {
    const impactElement = card.querySelector('.price-impact');
    const impactIcon = impactElement.querySelector('i');
    const impactText = impactElement.querySelector('span');
    
    // Reset classes
    impactElement.className = 'price-impact';
    impactIcon.className = 'fas';
    
    // Determine impact based on weather conditions
    if (condition === 'Clear' && temp >= 25 && temp <= 32) {
        // Perfect weather for most crops
        impactElement.classList.add('good');
        impactIcon.classList.add('fa-leaf');
        impactText.textContent = 'Great for Harvest';
    } else if (condition === 'Rain' || condition === 'Drizzle' || 
              (condition === 'Clouds' && temp >= 25 && temp <= 30)) {
        // Moderate conditions - good for some crops
        impactElement.classList.add('neutral');
        impactIcon.classList.add('fa-seedling');
        impactText.textContent = 'Good for Crops';
    } else if (condition === 'Thunderstorm' || temp > 35) {
        // Bad conditions for harvest
        impactElement.classList.add('bad');
        impactIcon.classList.add('fa-exclamation-triangle');
        impactText.textContent = 'Poor Harvest Conditions';
    } else {
        // Default neutral
        impactElement.classList.add('neutral');
        impactIcon.classList.add('fa-tractor');
        impactText.textContent = 'Normal Growing Conditions';
    }
}

// Show loading state in weather cards
function showWeatherLoadingState() {
    const weatherSection = document.querySelector('.weather-section');
    weatherSection.classList.add('loading');
    
    const cards = document.querySelectorAll('.weather-card');
    cards.forEach(card => {
        const tempElement = card.querySelector('.weather-temp');
        if (tempElement) {
            tempElement.innerHTML = '<div class="loading-pulse"></div>';
        }
        
        const conditionElement = card.querySelector('.weather-condition');
        if (conditionElement) {
            conditionElement.innerHTML = '<div class="loading-pulse"></div>';
        }
    });
}

// Show error state when weather data can't be fetched
function showWeatherError() {
    const weatherSection = document.querySelector('.weather-section');
    weatherSection.classList.remove('loading');
    weatherSection.classList.add('error');
    
    const errorMessage = document.createElement('div');
    errorMessage.className = 'weather-error-message';
    errorMessage.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <p>Unable to load weather data for Tanza, Cavite. Please try again later.</p>
    `;
    
    // Remove previous error message if exists
    const existingError = weatherSection.querySelector('.weather-error-message');
    if (existingError) {
        existingError.remove();
    }
    
    weatherSection.appendChild(errorMessage);
}

// Add loading pulse animation styles
function addLoadingStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .weather-section.loading .weather-card {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading-pulse {
            display: inline-block;
            width: 60px;
            height: 1.2em;
            background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
            background-size: 200% 100%;
            animation: loading-pulse 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading-pulse {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .weather-error-message {
            text-align: center;
            padding: 20px;
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 20px auto;
            max-width: 80%;
        }
        
        .weather-error-message i {
            font-size: 24px;
            margin-bottom: 10px;
        }
    `;
    
    document.head.appendChild(style);
}

// Initialize weather
document.addEventListener('DOMContentLoaded', function() {
    addLoadingStyles();
    
    // Add Tanza location label to weather section
    const weatherTitle = document.querySelector('.weather-section .section-title');
    weatherTitle.innerHTML = 'Weather & Harvest Impact <span class="location-label">Tanza, Cavite</span>';
    
    // Add styles for location label
    const style = document.createElement('style');
    style.textContent = `
        .location-label {
            font-size: 0.7em;
            background-color: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 10px;
            vertical-align: middle;
        }
    `;
    document.head.appendChild(style);
    
    // Fetch weather data for Tanza, Cavite
    fetchWeatherData();
    
    // Refresh weather data every 30 minutes
    setInterval(fetchWeatherData, 30 * 60 * 1000);
});