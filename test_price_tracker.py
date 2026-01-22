"""
Unit tests for the Tanza Price Tracking System.
"""
import os
import pytest
from datetime import datetime, timedelta
from database import Database
from models import Product, PriceEntry


@pytest.fixture
def test_db():
    """Create a temporary test database."""
    db_path = "test_tanza.db"
    db = Database(db_path)
    yield db
    db.close()
    if os.path.exists(db_path):
        os.remove(db_path)


def test_create_tables(test_db):
    """Test that tables are created successfully."""
    cursor = test_db.conn.cursor()
    
    # Check products table exists
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='products'")
    assert cursor.fetchone() is not None
    
    # Check price_entries table exists
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='price_entries'")
    assert cursor.fetchone() is not None


def test_add_product(test_db):
    """Test adding a product."""
    product_id = test_db.add_product("Tomato", "Vegetables", "kg", "Fresh tomatoes")
    assert product_id > 0
    
    product = test_db.get_product(product_id)
    assert product is not None
    assert product.name == "Tomato"
    assert product.category == "Vegetables"
    assert product.unit == "kg"
    assert product.description == "Fresh tomatoes"


def test_get_product_by_name(test_db):
    """Test getting a product by name."""
    test_db.add_product("Rice", "Grains", "kg")
    product = test_db.get_product_by_name("Rice")
    
    assert product is not None
    assert product.name == "Rice"
    assert product.category == "Grains"
    
    # Test case insensitive search
    product = test_db.get_product_by_name("rice")
    assert product is not None
    assert product.name == "Rice"


def test_list_products(test_db):
    """Test listing products."""
    test_db.add_product("Tomato", "Vegetables", "kg")
    test_db.add_product("Rice", "Grains", "kg")
    test_db.add_product("Onion", "Vegetables", "kg")
    
    # List all products
    products = test_db.list_products()
    assert len(products) == 3
    
    # List by category
    vegetables = test_db.list_products("Vegetables")
    assert len(vegetables) == 2
    assert all(p.category == "Vegetables" for p in vegetables)


def test_add_price_entry(test_db):
    """Test adding a price entry."""
    product_id = test_db.add_product("Tomato", "Vegetables", "kg")
    
    entry_id = test_db.add_price_entry(product_id, 45.50, vendor="Vendor A")
    assert entry_id > 0
    
    entry = test_db.get_latest_price(product_id)
    assert entry is not None
    assert entry.price == 45.50
    assert entry.product_id == product_id
    assert entry.vendor == "Vendor A"


def test_price_history(test_db):
    """Test getting price history."""
    product_id = test_db.add_product("Rice", "Grains", "kg")
    
    # Add multiple price entries
    now = datetime.now()
    test_db.add_price_entry(product_id, 50.00, date=now - timedelta(days=2))
    test_db.add_price_entry(product_id, 52.00, date=now - timedelta(days=1))
    test_db.add_price_entry(product_id, 51.50, date=now)
    
    history = test_db.get_price_history(product_id)
    assert len(history) == 3
    
    # Should be in descending order by date
    assert history[0].price == 51.50
    assert history[1].price == 52.00
    assert history[2].price == 50.00
    
    # Test with limit
    limited_history = test_db.get_price_history(product_id, limit=2)
    assert len(limited_history) == 2


def test_latest_price(test_db):
    """Test getting the latest price."""
    product_id = test_db.add_product("Onion", "Vegetables", "kg")
    
    # No price yet
    assert test_db.get_latest_price(product_id) is None
    
    # Add prices
    test_db.add_price_entry(product_id, 30.00)
    test_db.add_price_entry(product_id, 35.00)
    
    latest = test_db.get_latest_price(product_id)
    assert latest is not None
    assert latest.price == 35.00


def test_price_statistics(test_db):
    """Test price statistics calculation."""
    product_id = test_db.add_product("Garlic", "Vegetables", "kg")
    
    # No statistics without data
    stats = test_db.get_price_statistics(product_id)
    assert stats is None
    
    # Add price entries
    test_db.add_price_entry(product_id, 100.00)
    test_db.add_price_entry(product_id, 120.00)
    test_db.add_price_entry(product_id, 110.00)
    
    stats = test_db.get_price_statistics(product_id)
    assert stats is not None
    assert stats['count'] == 3
    assert stats['average'] == 110.00
    assert stats['minimum'] == 100.00
    assert stats['maximum'] == 120.00


def test_search_products(test_db):
    """Test product search functionality."""
    test_db.add_product("Tomato", "Vegetables", "kg")
    test_db.add_product("Potato", "Vegetables", "kg")
    test_db.add_product("Rice", "Grains", "kg")
    
    # Search by name
    results = test_db.search_products("tom")
    assert len(results) == 1
    assert results[0].name == "Tomato"
    
    # Search by category
    results = test_db.search_products("Vegetables")
    assert len(results) == 2
    
    # Partial search
    results = test_db.search_products("ato")
    assert len(results) == 2  # Tomato and Potato


def test_product_nonexistent(test_db):
    """Test getting a non-existent product."""
    product = test_db.get_product(999)
    assert product is None
    
    product = test_db.get_product_by_name("NonExistent")
    assert product is None
