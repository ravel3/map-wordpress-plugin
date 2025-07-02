#!/bin/bash

# Medal Map Database System - Instalator
echo "🗺️  Medal Map Database System - Instalator"
echo "=========================================="

# Sprawdź wymagania
check_requirements() {
    echo "📋 Sprawdzanie wymagań systemowych..."

    if ! command -v docker &> /dev/null; then
        echo "❌ Docker nie jest zainstalowany. Proszę zainstalować Docker przed kontynuowaniem."
        echo "   Instrukcje: https://docs.docker.com/get-docker/"
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        echo "❌ Docker Compose nie jest zainstalowany. Proszę zainstalować Docker Compose przed kontynuowaniem."
        echo "   Instrukcje: https://docs.docker.com/compose/install/"
        exit 1
    fi

    echo "✅ Docker i Docker Compose są zainstalowane"
}

# Generowanie przykładowych obrazów map
generate_sample_images() {
    echo "🖼️  Generowanie przykładowych obrazów map..."

    mkdir -p images

    # Sprawdź czy Python jest dostępny dla generowania obrazów
    if command -v python3 &> /dev/null; then
        python3 scripts/create_sample_images.py
    else
        echo "⚠️  Python3 nie jest dostępny. Kopiowanie obrazów zastępczych..."
        # Skopiuj obrazy zastępcze jeśli Python nie jest dostępny
        cp scripts/placeholder-*.jpg images/ 2>/dev/null || true
    fi

    echo "✅ Obrazy przykładowe zostały przygotowane"
}

# Uruchomienie kontenerów
start_containers() {
    echo "🐳 Uruchamianie kontenerów Docker..."

    # Użyj docker compose lub docker-compose w zależności od dostępności
    if docker compose version &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker compose"
    else
        DOCKER_COMPOSE_CMD="docker-compose"
    fi

    $DOCKER_COMPOSE_CMD up -d

    if [ $? -eq 0 ]; then
        echo "✅ Kontenery zostały uruchomione"
    else
        echo "❌ Błąd podczas uruchamiania kontenerów"
        exit 1
    fi
}

# Oczekiwanie na uruchomienie usług
wait_for_services() {
    echo "⏳ Oczekiwanie na uruchomienie usług..."

    # Oczekuj na MySQL
    echo "   📊 Oczekiwanie na bazę danych..."
    until docker-compose exec -T db mysqladmin ping -h localhost --silent; do
        sleep 2
    done

    # Oczekuj na WordPress
    echo "   🌐 Oczekiwanie na WordPress..."
    until curl -s http://localhost:8080 > /dev/null; do
        sleep 2
    done

    # Dodatkowe oczekiwanie na pełne uruchomienie
    sleep 10

    echo "✅ Usługi są gotowe"
}

# Konfiguracja WordPress
setup_wordpress() {
    echo "⚙️  Konfiguracja WordPress..."

    # Użyj docker compose lub docker-compose
    if docker compose version &> /dev/null; then
        DOCKER_COMPOSE_CMD="docker compose"
    else
        DOCKER_COMPOSE_CMD="docker-compose"
    fi

    # Uruchom skrypt konfiguracji WordPress
    $DOCKER_COMPOSE_CMD exec -T wp-cli bash /scripts/wp-setup.sh

    if [ $? -eq 0 ]; then
        echo "✅ WordPress został skonfigurowany"
    else
        echo "⚠️  Ostrzeżenie: Mogły wystąpić problemy z konfiguracją WordPress"
    fi
}

# Główna funkcja
main() {
    echo "🚀 Rozpoczynanie instalacji..."

    check_requirements
    generate_sample_images
    start_containers
    wait_for_services
    setup_wordpress

    echo ""
    echo "🎉 Instalacja zakończona pomyślnie!"
    echo ""
    echo "📌 Dostępne adresy:"
    echo "   🌐 Strona główna:        http://localhost:8080"
    echo "   🗺️  Mapa medalów:        http://localhost:8080/mapa-medali/"
    echo "   👤 Panel administracyjny: http://localhost:8080/wp-admin/"
    echo "   📧 Login:                admin"
    echo "   🔑 Hasło:                admin_password"
    echo ""
    echo "📚 Dokumentacja i pomoc:"
    echo "   📖 Shortcode:            [medal_map]"
    echo "   🔧 Panel Admin:          Mapy Medalów w menu WP Admin"
    echo "   📋 Logi:                 docker-compose logs wordpress"
    echo ""
    echo "🛑 Aby zatrzymać system:   docker-compose down"
    echo "🔄 Aby zrestartować:       docker-compose restart"
}

# Uruchom główną funkcję
main
