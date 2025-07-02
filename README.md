# Medal Map Database System

System interaktywnych map medalów z wykorzystaniem bazy danych WordPress.

## Opis

Medal Map Database System to kompletne rozwiązanie dla zarządzania interaktywnymi mapami z medalami, które można umieszczać na stronach WordPress. System wykorzystuje bazę danych WordPress do przechowywania informacji o mapach, medalach i ich dostępności.

### Kluczowe funkcjonalności

- Obsługa wielu map z unikalnym ID dla każdej mapy
- Zarządzanie medalami poprzez bazę danych WordPress
- Interaktywna mapa z wykorzystaniem Leaflet.js
- System pinezek z kolorami i promieniami
- Popup z informacjami o medalu
- Śledzenie historii pobrań medali
- Walidacja adresów e-mail użytkowników
- Panel administracyjny do zarządzania mapami i medalami

## Wymagania

- Docker i Docker Compose
- Dostęp do portu 8080 na hoście

## Instalacja

1. Rozpakuj archiwum ZIP
2. Otwórz terminal i przejdź do katalogu z rozpakowanymi plikami
3. Nadaj uprawnienia wykonywania dla skryptu instalacyjnego:
   ```
   chmod +x install.sh
   ```
4. Uruchom skrypt instalacyjny:
   ```
   ./install.sh
   ```

Skrypt automatycznie uruchomi kontenery Docker, zainstaluje WordPress i skonfiguruje plugin.

## Dostęp do systemu

Po zakończeniu instalacji będą dostępne następujące adresy:

- **Strona główna**: http://localhost:8080
- **Mapa medalów**: http://localhost:8080/mapa-medali/
- **Panel administracyjny**: http://localhost:8080/wp-admin/
  - Login: admin
  - Hasło: admin_password

## Korzystanie z systemu

### Dodawanie map na stronach

Aby dodać mapę na dowolnej stronie WordPress, użyj shortcode:

```
[medal_map]
```

Parametry opcjonalne:
- `map_id="1"` - wyświetla konkretną mapę bez selektora
- `height="600px"` - ustala wysokość mapy
- `show_selector="false"` - ukrywa selektor map
- `auto_zoom="false"` - wyłącza automatyczne dopasowanie zoomu

Przykład:
```
[medal_map map_id="2" height="700px"]
```

### Zarządzanie mapami i medalami

W panelu administracyjnym WordPress dostępna jest sekcja "Mapy Medalów", która umożliwia:
- Przeglądanie i edycję istniejących map
- Dodawanie nowych map
- Zarządzanie medalami dla każdej mapy
- Przeglądanie historii pobrań

## Struktura bazy danych

System wykorzystuje następujące tabele w bazie danych WordPress:

- `wp_medal_maps` - przechowuje informacje o mapach
- `wp_medal_medals` - przechowuje stałe informacje o medalach
- `wp_medal_medal_status` - przechowuje aktualny stan medali
- `wp_medal_history` - przechowuje historię pobrań medali

## Rozwiązywanie problemów

### Resetowanie systemu

Aby zresetować system i rozpocząć od nowa:

```
docker-compose down -v
./install.sh
```

### Logi

Aby sprawdzić logi:

```
docker-compose logs wordpress
```

## Licencja

System jest objęty licencją GPL v2 lub nowszą.

## Pomoc techniczna

W przypadku problemów lub pytań, skontaktuj się z autorem systemu.
