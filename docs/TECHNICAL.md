# Medal Map Database System - Dokumentacja Techniczna

## Architektura systemu

System Medal Map Database został zaprojektowany jako modularny plugin WordPress wykorzystujący nowoczesne technologie webowe i relacyjną bazę danych.

### Komponenty systemu

#### 1. Backend PHP (WordPress Plugin)
- **Główny plik**: `medal-map.php` - inicjalizacja pluginu
- **Klasa bazy danych**: `class-database.php` - zarządzanie tabelami i danymi
- **Obsługa AJAX**: `class-ajax.php` - API endpoints dla frontendu
- **Frontend**: `class-frontend.php` - ładowanie zasobów
- **Shortcode**: `class-shortcode.php` - renderowanie map na stronach
- **Panel admin**: `class-admin.php` - interfejs administracyjny

#### 2. Frontend JavaScript
- **Leaflet.js**: Biblioteka map interaktywnych
- **MedalMapSystem**: Główna klasa JavaScript obsługująca interfejs użytkownika
- **jQuery**: Obsługa AJAX i manipulacja DOM

#### 3. Baza danych
- **wp_medal_maps**: Tabela map (dane niezmienne)
- **wp_medal_medals**: Tabela medali (konfiguracja)
- **wp_medal_medal_status**: Tabela stanu medali (dane zmienne)
- **wp_medal_history**: Historia pobrań medali

### Schemat bazy danych

```sql
-- Tabela map
CREATE TABLE wp_medal_maps (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    description text,
    image_url varchar(500),
    image_width int(11) DEFAULT 1000,
    image_height int(11) DEFAULT 1000,
    min_zoom int(11) DEFAULT 0,
    max_zoom int(11) DEFAULT 3,
    default_zoom int(11) DEFAULT 1,
    status enum('active','inactive') DEFAULT 'active',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Tabela medali
CREATE TABLE wp_medal_medals (
    id int(11) NOT NULL AUTO_INCREMENT,
    map_id int(11) NOT NULL,
    name varchar(255) NOT NULL,
    description text,
    x_coordinate int(11) NOT NULL,
    y_coordinate int(11) NOT NULL,
    radius int(11) DEFAULT 10,
    total_medals int(11) DEFAULT 1,
    color varchar(7) DEFAULT '#ff0000',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (map_id) REFERENCES wp_medal_maps(id) ON DELETE CASCADE
);
```

### API Endpoints (AJAX)

#### 1. Pobieranie map
- **Action**: `medal_map_get_maps`
- **Metoda**: POST
- **Parametry**: nonce
- **Zwraca**: Lista dostępnych map

#### 2. Pobieranie medali
- **Action**: `medal_map_get_medals`
- **Metoda**: POST
- **Parametry**: map_id, nonce
- **Zwraca**: Dane mapy i lista medali

#### 3. Zabranie medalu
- **Action**: `medal_map_take_medal`
- **Metoda**: POST
- **Parametry**: medal_id, user_email, nonce
- **Zwraca**: Status operacji

### Bezpieczeństwo

1. **Nonce verification**: Wszystkie żądania AJAX są zabezpieczone nonce
2. **Sanityzacja danych**: Wszystkie dane wejściowe są sanityzowane
3. **Walidacja**: Walidacja adresów e-mail i parametrów
4. **Transakcje**: Atomowe operacje na bazie danych
5. **Escape output**: Wszystkie dane wyjściowe są escapowane

### Rozszerzalność

System jest zaprojektowany z myślą o rozszerzalności:

1. **Hooki WordPress**: Plugin wykorzystuje standardowe hooki WordPress
2. **Filtry**: Dodawanie custom filtrów dla dostosowywania
3. **Actions**: Możliwość podpięcia dodatkowej funkcjonalności
4. **Database API**: Abstrakcja bazy danych umożliwiająca łatwe modyfikacje

### Wydajność

1. **Cachowanie**: Wykorzystanie cache WordPress
2. **Lazy loading**: Ładowanie danych tylko gdy potrzebne
3. **Optymalizacja zapytań**: Efektywne zapytania SQL z joinami
4. **Indeksy bazy danych**: Właściwe indeksowanie tabel

### Kompatybilność

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- Nowoczesne przeglądarki wspierające ES6

### Instalacja w środowisku produkcyjnym

1. Skopiuj katalog `medal-map` do `wp-content/plugins/`
2. Aktywuj plugin w panelu administracyjnym
3. Skonfiguruj mapy przez panel "Mapy Medalów"
4. Dodaj shortcode `[medal_map]` na wybranej stronie

### Konfiguracja zaawansowana

#### Dostosowywanie stylów
Edytuj plik `assets/css/medal-map.css` lub dodaj custom CSS przez WordPress Customizer.

#### Modyfikacja zachowania
Wykorzystaj filtry WordPress:

```php
// Modyfikacja danych medalu przed wyświetleniem
add_filter('medal_map_medal_data', function($medal) {
    // Twoje modyfikacje
    return $medal;
});
```

### Rozwiązywanie problemów

#### Debug mode
Włącz debug w `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Logi błędów
Sprawdź logi w `/wp-content/debug.log`

#### Resetowanie danych
```sql
TRUNCATE TABLE wp_medal_history;
UPDATE wp_medal_medal_status SET available_medals = (
    SELECT total_medals FROM wp_medal_medals 
    WHERE wp_medal_medals.id = wp_medal_medal_status.medal_id
);
```
