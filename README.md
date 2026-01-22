# Tanza Price Tracking System

A command-line price tracking and management system for the Tanza market. This system allows you to track product prices over time, manage product information, and analyze price trends.

## Features

- **Product Management**: Add and manage products with categories, units, and descriptions
- **Price Tracking**: Record price entries with timestamps, vendor information, and notes
- **Price History**: View historical price data for any product
- **Statistics**: Calculate average, minimum, and maximum prices
- **Search**: Find products by name or category
- **Data Persistence**: All data is stored in a SQLite database

## Installation

1. Clone this repository:
```bash
git clone https://github.com/jennnyyyyyyyahhh/TANZA_PRICE_TRACKING.git
cd TANZA_PRICE_TRACKING
```

2. Install dependencies (optional, only needed for testing):
```bash
pip install -r requirements.txt
```

## Usage

### Adding a Product

```bash
python cli.py add-product <name> <category> <unit> [description]
```

Example:
```bash
python cli.py add-product "Tomato" "Vegetables" "kg" "Fresh tomatoes"
python cli.py add-product "Rice" "Grains" "kg" "White rice"
python cli.py add-product "Banana" "Fruits" "dozen"
```

### Recording a Price

```bash
python cli.py add-price <product_name> <price> [vendor] [notes]
```

Example:
```bash
python cli.py add-price "Tomato" 45.50
python cli.py add-price "Rice" 50.00 "Market Vendor A"
python cli.py add-price "Banana" 60.00 "Vendor B" "Good quality"
```

### Listing Products

```bash
# List all products
python cli.py list-products

# List products by category
python cli.py list-products "Vegetables"
```

### Viewing Price History

```bash
# View last 10 price entries (default)
python cli.py price-history "Tomato"

# View last 20 price entries
python cli.py price-history "Rice" 20
```

### Searching Products

```bash
python cli.py search "tom"
python cli.py search "Vegetables"
```

### Getting Help

```bash
python cli.py help
```

## Project Structure

```
TANZA_PRICE_TRACKING/
├── cli.py                  # Command-line interface
├── database.py             # Database operations
├── models.py               # Data models (Product, PriceEntry)
├── test_price_tracker.py   # Unit tests
├── requirements.txt        # Python dependencies
├── README.md              # This file
└── tanza_prices.db        # SQLite database (created automatically)
```

## Data Models

### Product
- **id**: Unique identifier
- **name**: Product name
- **category**: Product category (e.g., Vegetables, Fruits, Grains)
- **unit**: Unit of measurement (e.g., kg, piece, liter, dozen)
- **description**: Optional description

### Price Entry
- **id**: Unique identifier
- **product_id**: Reference to product
- **price**: Price in Philippine Pesos (₱)
- **date**: Date and time of the price entry
- **vendor**: Optional vendor/seller name
- **location**: Location (default: "Tanza Market")
- **notes**: Optional notes

## Testing

Run the test suite:

```bash
pytest test_price_tracker.py -v
```

Run tests with coverage:

```bash
pytest test_price_tracker.py --cov=. --cov-report=html
```

## Example Workflow

Here's a complete example of using the system:

```bash
# 1. Add some products
python cli.py add-product "Tomato" "Vegetables" "kg" "Fresh tomatoes"
python cli.py add-product "Onion" "Vegetables" "kg" "Red onions"
python cli.py add-product "Rice" "Grains" "kg" "White rice"

# 2. Record prices
python cli.py add-price "Tomato" 45.50 "Vendor A"
python cli.py add-price "Onion" 30.00 "Vendor B"
python cli.py add-price "Rice" 50.00 "Market Stall 1"

# 3. Record another price for the same product (price tracking over time)
python cli.py add-price "Tomato" 47.00 "Vendor A" "Price increased"

# 4. View all products
python cli.py list-products

# 5. View price history for tomatoes
python cli.py price-history "Tomato"

# 6. Search for products
python cli.py search "Vegetables"
```

## Currency

All prices are stored and displayed in Philippine Pesos (₱).

## Database

The system uses SQLite for data storage. The database file (`tanza_prices.db`) is created automatically when you first run the application. The database includes:

- **products** table: Stores product information
- **price_entries** table: Stores price records with timestamps

## Contributing

Contributions are welcome! Please feel free to submit issues or pull requests.

## License

This project is open source and available under the MIT License.