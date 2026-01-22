#!/usr/bin/env python3
"""
Command-line interface for the Tanza Price Tracking System.
"""
import sys
from datetime import datetime
from typing import Optional
from database import Database


class TanzaPriceTracker:
    """Main CLI application for the Tanza Price Tracking System."""

    def __init__(self, db_path: str = "tanza_prices.db"):
        """Initialize the price tracker."""
        self.db = Database(db_path)

    def add_product(self, name: str, category: str, unit: str, 
                   description: Optional[str] = None):
        """Add a new product."""
        try:
            # Check if product already exists
            existing = self.db.get_product_by_name(name)
            if existing:
                print(f"Error: Product '{name}' already exists.")
                return False
            
            product_id = self.db.add_product(name, category, unit, description)
            print(f"✓ Product added successfully (ID: {product_id})")
            print(f"  Name: {name}")
            print(f"  Category: {category}")
            print(f"  Unit: {unit}")
            return True
        except Exception as e:
            print(f"Error adding product: {e}")
            return False

    def add_price(self, product_name: str, price: float, 
                 vendor: Optional[str] = None,
                 notes: Optional[str] = None):
        """Add a price entry for a product."""
        try:
            product = self.db.get_product_by_name(product_name)
            if not product:
                print(f"Error: Product '{product_name}' not found.")
                print("Use 'list-products' to see available products.")
                return False
            
            entry_id = self.db.add_price_entry(
                product.id, price, 
                vendor=vendor, 
                notes=notes
            )
            print(f"✓ Price recorded successfully (ID: {entry_id})")
            print(f"  Product: {product.name}")
            print(f"  Price: ₱{price:.2f}")
            print(f"  Date: {datetime.now().strftime('%Y-%m-%d %H:%M')}")
            if vendor:
                print(f"  Vendor: {vendor}")
            return True
        except Exception as e:
            print(f"Error adding price: {e}")
            return False

    def list_products(self, category: Optional[str] = None):
        """List all products."""
        try:
            products = self.db.list_products(category)
            if not products:
                if category:
                    print(f"No products found in category '{category}'.")
                else:
                    print("No products in the system.")
                return
            
            print("\n" + "=" * 80)
            print("PRODUCTS")
            print("=" * 80)
            
            current_category = None
            for product in products:
                if product.category != current_category:
                    current_category = product.category
                    print(f"\n{current_category}:")
                    print("-" * 80)
                
                latest_price = self.db.get_latest_price(product.id)
                price_str = f"₱{latest_price.price:.2f}" if latest_price else "No price data"
                
                print(f"  {product.name} ({product.unit})")
                print(f"    Latest Price: {price_str}")
                if product.description:
                    print(f"    Description: {product.description}")
            
            print("=" * 80 + "\n")
        except Exception as e:
            print(f"Error listing products: {e}")

    def show_price_history(self, product_name: str, limit: int = 10):
        """Show price history for a product."""
        try:
            product = self.db.get_product_by_name(product_name)
            if not product:
                print(f"Error: Product '{product_name}' not found.")
                return
            
            history = self.db.get_price_history(product.id, limit)
            if not history:
                print(f"No price history for '{product.name}'.")
                return
            
            print("\n" + "=" * 80)
            print(f"PRICE HISTORY: {product.name} ({product.unit})")
            print("=" * 80)
            
            for entry in history:
                print(f"\n  Date: {entry.date.strftime('%Y-%m-%d %H:%M')}")
                print(f"  Price: ₱{entry.price:.2f}")
                if entry.vendor:
                    print(f"  Vendor: {entry.vendor}")
                if entry.notes:
                    print(f"  Notes: {entry.notes}")
                print("  " + "-" * 76)
            
            # Show statistics
            stats = self.db.get_price_statistics(product.id)
            if stats:
                print(f"\nSTATISTICS (all time):")
                print(f"  Records: {stats['count']}")
                print(f"  Average: ₱{stats['average']:.2f}")
                print(f"  Minimum: ₱{stats['minimum']:.2f}")
                print(f"  Maximum: ₱{stats['maximum']:.2f}")
            
            print("=" * 80 + "\n")
        except Exception as e:
            print(f"Error showing price history: {e}")

    def search_products(self, search_term: str):
        """Search for products."""
        try:
            products = self.db.search_products(search_term)
            if not products:
                print(f"No products found matching '{search_term}'.")
                return
            
            print(f"\nSearch results for '{search_term}':")
            print("-" * 80)
            for product in products:
                latest_price = self.db.get_latest_price(product.id)
                price_str = f"₱{latest_price.price:.2f}" if latest_price else "No price"
                print(f"  {product.name} ({product.unit}) - {product.category}")
                print(f"    Latest Price: {price_str}")
            print("-" * 80 + "\n")
        except Exception as e:
            print(f"Error searching products: {e}")

    def show_help(self):
        """Display help information."""
        help_text = """
Tanza Price Tracking System
============================

Usage: python cli.py <command> [arguments]

Commands:

  add-product <name> <category> <unit> [description]
      Add a new product to the system
      Example: python cli.py add-product "Tomato" "Vegetables" "kg" "Fresh tomatoes"

  add-price <product_name> <price> [vendor] [notes]
      Record a price for a product
      Example: python cli.py add-price "Tomato" 45.50 "Vendor A" "Good quality"

  list-products [category]
      List all products, optionally filtered by category
      Example: python cli.py list-products
      Example: python cli.py list-products "Vegetables"

  price-history <product_name> [limit]
      Show price history for a product
      Example: python cli.py price-history "Tomato" 20

  search <search_term>
      Search for products by name or category
      Example: python cli.py search "tom"

  help
      Show this help message

Examples:
  # Add a product
  python cli.py add-product "Rice" "Grains" "kg" "White rice"
  
  # Record a price
  python cli.py add-price "Rice" 50.00 "Market Vendor"
  
  # View all products
  python cli.py list-products
  
  # View price history
  python cli.py price-history "Rice"
"""
        print(help_text)

    def close(self):
        """Close the database connection."""
        self.db.close()


def main():
    """Main entry point for the CLI."""
    if len(sys.argv) < 2:
        print("Error: No command specified.")
        print("Use 'python cli.py help' for usage information.")
        sys.exit(1)

    command = sys.argv[1].lower()
    tracker = TanzaPriceTracker()

    try:
        if command == "add-product":
            if len(sys.argv) < 5:
                print("Error: add-product requires: <name> <category> <unit> [description]")
                sys.exit(1)
            name = sys.argv[2]
            category = sys.argv[3]
            unit = sys.argv[4]
            description = sys.argv[5] if len(sys.argv) > 5 else None
            tracker.add_product(name, category, unit, description)

        elif command == "add-price":
            if len(sys.argv) < 4:
                print("Error: add-price requires: <product_name> <price> [vendor] [notes]")
                sys.exit(1)
            product_name = sys.argv[2]
            try:
                price = float(sys.argv[3])
            except ValueError:
                print("Error: Price must be a valid number.")
                sys.exit(1)
            vendor = sys.argv[4] if len(sys.argv) > 4 else None
            notes = sys.argv[5] if len(sys.argv) > 5 else None
            tracker.add_price(product_name, price, vendor, notes)

        elif command == "list-products":
            category = sys.argv[2] if len(sys.argv) > 2 else None
            tracker.list_products(category)

        elif command == "price-history":
            if len(sys.argv) < 3:
                print("Error: price-history requires: <product_name> [limit]")
                sys.exit(1)
            product_name = sys.argv[2]
            limit = 10
            if len(sys.argv) > 3:
                try:
                    limit = int(sys.argv[3])
                except ValueError:
                    print("Error: Limit must be a valid integer.")
                    sys.exit(1)
            tracker.show_price_history(product_name, limit)

        elif command == "search":
            if len(sys.argv) < 3:
                print("Error: search requires: <search_term>")
                sys.exit(1)
            search_term = sys.argv[2]
            tracker.search_products(search_term)

        elif command == "help":
            tracker.show_help()

        else:
            print(f"Error: Unknown command '{command}'")
            print("Use 'python cli.py help' for usage information.")
            sys.exit(1)

    finally:
        tracker.close()


if __name__ == "__main__":
    main()