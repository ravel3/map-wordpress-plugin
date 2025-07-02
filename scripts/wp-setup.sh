#!/bin/bash

# WordPress Setup Script for Medal Map System
echo "🔧 Konfiguracja WordPress dla Medal Map System..."

# Oczekiwanie na WordPress
wait_for_wordpress() {
    echo "⏳ Oczekiwanie na dostępność WordPress..."
    until wp core is-installed --allow-root 2>/dev/null; do
        echo "   💤 WordPress jeszcze nie jest gotowy, próba instalacji..."

        # Próba instalacji WordPress jeśli nie jest zainstalowany
        wp core install \
            --url="http://localhost:8080" \
            --title="Medal Map System" \
            --admin_user="admin" \
            --admin_password="admin_password" \
            --admin_email="admin@example.com" \
            --skip-email \
            --allow-root 2>/dev/null || true

        sleep 3
    done
    echo "✅ WordPress jest dostępny"
}

# Instalacja i konfiguracja
setup_wordpress() {
    echo "⚙️  Konfiguracja podstawowa..."

    # Aktywacja pluginu
    wp plugin activate medal-map --allow-root

    if [ $? -eq 0 ]; then
        echo "✅ Plugin Medal Map został aktywowany"
    else
        echo "⚠️  Ostrzeżenie: Problem z aktywacją pluginu"
    fi

    # Konfiguracja języka polskiego
    wp language core install pl_PL --allow-root
    wp site switch-language pl_PL --allow-root

    # Ustawienia podstawowe
    wp option update blogname "System Map Medalów" --allow-root
    wp option update blogdescription "Interaktywne mapy medalów z bazą danych" --allow-root
    wp option update timezone_string "Europe/Warsaw" --allow-root
    wp option update date_format "d.m.Y" --allow-root
    wp option update time_format "H:i" --allow-root

    # Ustawienia permalinków
    wp rewrite structure "/%postname%/" --allow-root
    wp rewrite flush --allow-root

    echo "✅ Konfiguracja podstawowa zakończona"
}

# Tworzenie przykładowej strony
create_demo_page() {
    echo "📄 Tworzenie przykładowej strony z mapą..."

    # Usuń istniejącą stronę jeśli istnieje
    wp post delete $(wp post list --post_type=page --name="mapa-medali" --format=ids --allow-root) --force --allow-root 2>/dev/null || true

    # Utwórz nową stronę
    wp post create --post_type=page \
        --post_title="Mapa Medali" \
        --post_name="mapa-medali" \
        --post_status=publish \
        --post_content="<h2>Interaktywna Mapa Medalów</h2>
<p>Witaj w systemie map medalów! Wybierz mapę i klikaj w pinezki aby zobaczyć dostępne medale.</p>
[medal_map]
<h3>Instrukcje</h3>
<ul>
<li>Wybierz mapę z listy rozwijanej</li>
<li>Kliknij w pinezki na mapie aby zobaczyć szczegóły medalu</li>
<li>Podaj swój adres e-mail przy pierwszym użyciu</li>
<li>Kliknij "Zabrałem medal" aby odebrać medal</li>
</ul>" \
        --allow-root

    if [ $? -eq 0 ]; then
        echo "✅ Strona przykładowa została utworzona"
    else
        echo "⚠️  Problem z tworzeniem strony przykładowej"
    fi
}

# Główna funkcja
main() {
    wait_for_wordpress
    setup_wordpress
    create_demo_page

    echo ""
    echo "🎉 Konfiguracja WordPress zakończona!"
    echo "🔗 Strona z mapą: http://localhost:8080/mapa-medali/"
}

# Uruchom konfigurację
main
