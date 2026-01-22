"""
Database management for the Tanza Price Tracking System.
"""
import sqlite3
from datetime import datetime
from typing import List, Optional, Tuple
from models import Product, PriceEntry


class Database:
    """Handles all database operations for the price tracking system."""

    def __init__(self, db_path: str = "tanza_prices.db"):
        """Initialize database connection and create tables if needed."""
        self.db_path = db_path
        self.conn = sqlite3.connect(db_path)
        self.conn.row_factory = sqlite3.Row
        self._create_tables()

    def _create_tables(self):
        """Create necessary database tables."""
        cursor = self.conn.cursor()
        
        # Products table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                unit TEXT NOT NULL,
                description TEXT
            )
        """)
        
        # Price entries table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS price_entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                price REAL NOT NULL,
                date TEXT NOT NULL,
                vendor TEXT,
                location TEXT DEFAULT 'Tanza Market',
                notes TEXT,
                FOREIGN KEY (product_id) REFERENCES products (id)
            )
        """)
        
        self.conn.commit()

    def add_product(self, name: str, category: str, unit: str, 
                   description: Optional[str] = None) -> int:
        """Add a new product to the database."""
        cursor = self.conn.cursor()
        cursor.execute(
            "INSERT INTO products (name, category, unit, description) VALUES (?, ?, ?, ?)",
            (name, category, unit, description)
        )
        self.conn.commit()
        return cursor.lastrowid

    def get_product(self, product_id: int) -> Optional[Product]:
        """Get a product by ID."""
        cursor = self.conn.cursor()
        cursor.execute("SELECT * FROM products WHERE id = ?", (product_id,))
        row = cursor.fetchone()
        if row:
            return Product(
                id=row['id'],
                name=row['name'],
                category=row['category'],
                unit=row['unit'],
                description=row['description']
            )
        return None

    def get_product_by_name(self, name: str) -> Optional[Product]:
        """Get a product by name."""
        cursor = self.conn.cursor()
        cursor.execute("SELECT * FROM products WHERE name = ? COLLATE NOCASE", (name,))
        row = cursor.fetchone()
        if row:
            return Product(
                id=row['id'],
                name=row['name'],
                category=row['category'],
                unit=row['unit'],
                description=row['description']
            )
        return None

    def list_products(self, category: Optional[str] = None) -> List[Product]:
        """List all products, optionally filtered by category."""
        cursor = self.conn.cursor()
        if category:
            cursor.execute("SELECT * FROM products WHERE category = ? COLLATE NOCASE ORDER BY name", 
                         (category,))
        else:
            cursor.execute("SELECT * FROM products ORDER BY category, name")
        
        return [
            Product(
                id=row['id'],
                name=row['name'],
                category=row['category'],
                unit=row['unit'],
                description=row['description']
            )
            for row in cursor.fetchall()
        ]

    def add_price_entry(self, product_id: int, price: float, 
                       date: Optional[datetime] = None,
                       vendor: Optional[str] = None,
                       location: str = "Tanza Market",
                       notes: Optional[str] = None) -> int:
        """Add a new price entry."""
        if date is None:
            date = datetime.now()
        
        cursor = self.conn.cursor()
        cursor.execute(
            """INSERT INTO price_entries 
               (product_id, price, date, vendor, location, notes) 
               VALUES (?, ?, ?, ?, ?, ?)""",
            (product_id, price, date.isoformat(), vendor, location, notes)
        )
        self.conn.commit()
        return cursor.lastrowid

    def get_price_history(self, product_id: int, 
                         limit: Optional[int] = None) -> List[PriceEntry]:
        """Get price history for a product."""
        cursor = self.conn.cursor()
        query = """
            SELECT * FROM price_entries 
            WHERE product_id = ? 
            ORDER BY date DESC
        """
        if limit:
            query += f" LIMIT {limit}"
        
        cursor.execute(query, (product_id,))
        
        return [
            PriceEntry(
                id=row['id'],
                product_id=row['product_id'],
                price=row['price'],
                date=datetime.fromisoformat(row['date']),
                vendor=row['vendor'],
                location=row['location'],
                notes=row['notes']
            )
            for row in cursor.fetchall()
        ]

    def get_latest_price(self, product_id: int) -> Optional[PriceEntry]:
        """Get the most recent price for a product."""
        history = self.get_price_history(product_id, limit=1)
        return history[0] if history else None

    def get_price_statistics(self, product_id: int, 
                            days: Optional[int] = None) -> Optional[dict]:
        """Get price statistics for a product."""
        cursor = self.conn.cursor()
        
        query = """
            SELECT 
                COUNT(*) as count,
                AVG(price) as avg_price,
                MIN(price) as min_price,
                MAX(price) as max_price
            FROM price_entries
            WHERE product_id = ?
        """
        params = [product_id]
        
        if days:
            query += " AND date >= datetime('now', ?)"
            params.append(f'-{days} days')
        
        cursor.execute(query, params)
        row = cursor.fetchone()
        
        if row and row['count'] > 0:
            return {
                'count': row['count'],
                'average': row['avg_price'],
                'minimum': row['min_price'],
                'maximum': row['max_price']
            }
        return None

    def search_products(self, search_term: str) -> List[Product]:
        """Search products by name or category."""
        cursor = self.conn.cursor()
        cursor.execute(
            """SELECT * FROM products 
               WHERE name LIKE ? OR category LIKE ?
               ORDER BY name""",
            (f'%{search_term}%', f'%{search_term}%')
        )
        
        return [
            Product(
                id=row['id'],
                name=row['name'],
                category=row['category'],
                unit=row['unit'],
                description=row['description']
            )
            for row in cursor.fetchall()
        ]

    def close(self):
        """Close the database connection."""
        self.conn.close()
