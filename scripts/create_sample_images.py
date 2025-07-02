#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Generator przykładowych obrazów map dla Medal Map System
"""

import os
import sys
import random
from datetime import datetime

try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    print("Biblioteka PIL/Pillow nie jest zainstalowana. Instalowanie...")
    try:
        import pip
        pip.main(['install', 'Pillow'])
        from PIL import Image, ImageDraw, ImageFont
    except:
        print("Nie można zainstalować Pillow. Używanie obrazów zastępczych.")
        sys.exit(1)

def create_simple_map(filename, width, height, title, bg_color=(230, 240, 255), 
                     text_color=(0, 0, 0), markers=None):
    """Tworzy prosty obraz mapy z tytułem i opcjonalnymi markerami."""

    # Utwórz obraz
    img = Image.new('RGB', (width, height), bg_color)
    draw = ImageDraw.Draw(img)

    # Dodaj siatkę
    grid_step = 50
    grid_color = (200, 210, 230)

    for x in range(0, width, grid_step):
        draw.line([(x, 0), (x, height)], fill=grid_color, width=1)

    for y in range(0, height, grid_step):
        draw.line([(0, y), (width, y)], fill=grid_color, width=1)

    # Dodaj losowe elementy "mapy"
    for _ in range(20):
        x1 = random.randint(0, width)
        y1 = random.randint(0, height)
        x2 = x1 + random.randint(50, 150)
        y2 = y1 + random.randint(50, 150)

        element_color = (
            random.randint(160, 200),
            random.randint(160, 200),
            random.randint(160, 200)
        )

        shape_type = random.choice(['rectangle', 'ellipse', 'line'])

        if shape_type == 'rectangle':
            draw.rectangle([x1, y1, x2, y2], fill=element_color, outline=(0, 0, 0))
        elif shape_type == 'ellipse':
            draw.ellipse([x1, y1, x2, y2], fill=element_color, outline=(0, 0, 0))
        else:
            draw.line([x1, y1, x2, y2], fill=element_color, width=3)

    # Dodaj tytuł
    try:
        # Spróbuj załadować czcionkę (możliwe tylko jeśli jest dostępna w systemie)
        font = ImageFont.truetype("Arial", 24)
    except:
        # Użyj domyślnej czcionki
        font = ImageFont.load_default()

    text_width = len(title) * 12  # Przybliżona szerokość tekstu
    text_x = (width - text_width) // 2
    draw.text((text_x, 20), title, fill=text_color, font=font)

    # Dodaj legendę
    legend_y = height - 40
    draw.rectangle([20, legend_y, 180, legend_y + 30], fill=(255, 255, 255), outline=(0, 0, 0))
    draw.text((25, legend_y + 5), "Przykładowa mapa", fill=text_color, font=font)

    # Dodaj znaczniki
    if markers:
        for marker in markers:
            x, y, color, size = marker
            if 0 <= x < width and 0 <= y < height:
                draw.ellipse([x-size, y-size, x+size, y+size], fill=color, outline=(0, 0, 0))

    # Zapisz obraz
    img.save(filename)
    print(f"Utworzono obraz: {filename}")

def main():
    """Funkcja główna generująca przykładowe obrazy map."""

    # Sprawdź czy istnieje katalog na obrazy
    images_dir = "../images"
    if not os.path.exists(images_dir):
        os.makedirs(images_dir)

    # Generuj obrazy dla trzech przykładowych map
    maps_data = [
        {
            "name": "beskidy-map.jpg",
            "title": "Mapa Beskidów",
            "width": 1200,
            "height": 800,
            "bg_color": (220, 240, 220),
            "markers": [
                (300, 200, (255, 0, 0), 15),
                (450, 300, (0, 255, 0), 12),
                (600, 250, (0, 0, 255), 13)
            ]
        },
        {
            "name": "malopolska-map.jpg",
            "title": "Mapa Małopolski",
            "width": 1000,
            "height": 800,
            "bg_color": (240, 230, 210),
            "markers": [
                (400, 350, (255, 0, 255), 20),
                (420, 380, (255, 255, 0), 18),
                (500, 400, (0, 255, 255), 14)
            ]
        },
        {
            "name": "tatry-map.jpg",
            "title": "Mapa Tatr",
            "width": 1100,
            "height": 700,
            "bg_color": (210, 220, 240),
            "markers": [
                (550, 150, (255, 128, 0), 16),
                (400, 200, (128, 0, 255), 14),
                (500, 300, (0, 128, 255), 12)
            ]
        }
    ]

    for map_data in maps_data:
        create_simple_map(
            os.path.join(images_dir, map_data["name"]),
            map_data["width"],
            map_data["height"],
            map_data["title"],
            map_data.get("bg_color", (230, 240, 255)),
            (0, 0, 0),
            map_data.get("markers", [])
        )

    # Utwórz również obrazy zastępcze do bezpośredniego kopiowania
    placeholder_dir = "."
    for map_data in maps_data:
        create_simple_map(
            os.path.join(placeholder_dir, "placeholder-" + map_data["name"]),
            map_data["width"],
            map_data["height"],
            map_data["title"],
            map_data.get("bg_color", (230, 240, 255)),
            (0, 0, 0),
            map_data.get("markers", [])
        )

    print("Wszystkie obrazy zostały wygenerowane pomyślnie!")

if __name__ == "__main__":
    main()
