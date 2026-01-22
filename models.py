"""
Data models for the Tanza Price Tracking System.
"""
from dataclasses import dataclass
from datetime import datetime
from typing import Optional


@dataclass
class Product:
    """Represents a product in the Tanza market."""
    id: int
    name: str
    category: str
    unit: str  # e.g., "kg", "piece", "liter"
    description: Optional[str] = None

    def __str__(self):
        return f"{self.name} ({self.unit})"


@dataclass
class PriceEntry:
    """Represents a price record for a product."""
    id: int
    product_id: int
    price: float
    date: datetime
    vendor: Optional[str] = None
    location: Optional[str] = "Tanza Market"
    notes: Optional[str] = None

    def __str__(self):
        return f"₱{self.price:.2f} on {self.date.strftime('%Y-%m-%d')}"