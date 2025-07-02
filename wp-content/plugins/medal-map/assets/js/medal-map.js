/**
 * Medal Map System - Frontend JavaScript
 */

class MedalMapSystem {
    constructor(options) {
        this.options = {
            containerId: '',
            mapId: '',
            preselectedMapId: null,
            showSelector: true,
            autoZoom: true,
            ...options
        };

        this.container = document.getElementById(this.options.containerId);
        this.mapElement = document.getElementById(this.options.mapId);
        this.leafletMap = null;
        this.currentMapData = null;
        this.medalMarkers = [];
        this.userEmail = this.getUserEmail();

        this.init();
    }

    init() {
        if (this.options.showSelector) {
            this.loadMaps();
        } else if (this.options.preselectedMapId) {
            this.loadMap(this.options.preselectedMapId);
        }

        this.setupEventListeners();
    }

    setupEventListeners() {
        // Selektor map
        const mapSelector = this.container.querySelector('.medal-map-select');
        if (mapSelector) {
            mapSelector.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.loadMap(parseInt(e.target.value));
                }
            });
        }

        // Modalowe okna
        this.setupModalListeners();
    }

    setupModalListeners() {
        // Zamknij modal po kliknięciu X lub poza modal
        this.container.addEventListener('click', (e) => {
            if (e.target.classList.contains('medal-map-modal-close') || 
                e.target.classList.contains('medal-map-modal')) {
                this.closeModals();
            }
        });

        // Formularz e-mail
        const emailForm = this.container.querySelector(`#email-form-${this.options.containerId.split('-').pop()}`);
        if (emailForm) {
            emailForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveUserEmail();
            });
        }
    }

    showLoading() {
        const loading = this.container.querySelector('.medal-map-loading');
        if (loading) loading.style.display = 'block';

        if (this.mapElement) this.mapElement.style.display = 'none';

        const info = this.container.querySelector('.medal-map-info');
        if (info) info.style.display = 'none';
    }

    hideLoading() {
        const loading = this.container.querySelector('.medal-map-loading');
        if (loading) loading.style.display = 'none';
    }

    showError(message) {
        this.hideLoading();

        let errorDiv = this.container.querySelector('.medal-map-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'medal-map-error';
            this.container.appendChild(errorDiv);
        }

        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    hideError() {
        const errorDiv = this.container.querySelector('.medal-map-error');
        if (errorDiv) errorDiv.style.display = 'none';
    }

    loadMaps() {
        this.showLoading();

        jQuery.ajax({
            url: medalMapAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'medal_map_get_maps',
                nonce: medalMapAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.populateMapSelector(response.data);
                    this.hideLoading();
                } else {
                    this.showError(response.data || medalMapAjax.messages.error);
                }
            },
            error: () => {
                this.showError(medalMapAjax.messages.error);
            }
        });
    }

    populateMapSelector(maps) {
        const selector = this.container.querySelector('.medal-map-select');
        if (!selector) return;

        // Wyczyść istniejące opcje (zachowaj pierwszą)
        while (selector.children.length > 1) {
            selector.removeChild(selector.lastChild);
        }

        maps.forEach(map => {
            const option = document.createElement('option');
            option.value = map.id;
            option.textContent = map.name;
            selector.appendChild(option);
        });

        // Jeśli jest preselected map, ustaw ją
        if (this.options.preselectedMapId) {
            selector.value = this.options.preselectedMapId;
            this.loadMap(this.options.preselectedMapId);
        }
    }

    loadMap(mapId) {
        this.showLoading();
        this.hideError();

        jQuery.ajax({
            url: medalMapAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'medal_map_get_medals',
                map_id: mapId,
                nonce: medalMapAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.currentMapData = response.data;
                    this.initializeLeafletMap();
                    this.addMedalsToMap();
                    this.showMapInfo();
                    this.hideLoading();
                } else {
                    this.showError(response.data || medalMapAjax.messages.error);
                }
            },
            error: () => {
                this.showError(medalMapAjax.messages.error);
            }
        });
    }

    initializeLeafletMap() {
        if (this.leafletMap) {
            this.leafletMap.remove();
        }

        const map = this.currentMapData.map;

        // Inicjalizuj mapę
        this.leafletMap = L.map(this.options.mapId, {
            crs: L.CRS.Simple,
            minZoom: map.min_zoom,
            maxZoom: map.max_zoom,
            zoomControl: true,
            scrollWheelZoom: true
        });

        // Dodaj obraz jako podkład
        const imageBounds = [[0, 0], [map.image_height, map.image_width]];
        L.imageOverlay(map.image_url, imageBounds).addTo(this.leafletMap);

        // Ustaw widok
        this.leafletMap.fitBounds(imageBounds);
        this.leafletMap.setZoom(map.default_zoom);

        // Pokaż mapę
        this.mapElement.style.display = 'block';

        // Invalidate size po pokazaniu
        setTimeout(() => {
            this.leafletMap.invalidateSize();
        }, 100);
    }

    addMedalsToMap() {
        // Wyczyść istniejące markery
        this.medalMarkers.forEach(marker => {
            this.leafletMap.removeLayer(marker);
        });
        this.medalMarkers = [];

        const medals = this.currentMapData.medals;

        medals.forEach(medal => {
            const marker = this.createMedalMarker(medal);
            marker.addTo(this.leafletMap);
            this.medalMarkers.push(marker);
        });
    }

    createMedalMarker(medal) {
        const isAvailable = medal.available_medals > 0;
        const color = isAvailable ? medal.color : '#888888';
        const fillOpacity = isAvailable ? 0.7 : 0.3;

        const marker = L.circle([medal.y_coordinate, medal.x_coordinate], {
            radius: medal.radius,
            color: color,
            fillColor: color,
            fillOpacity: fillOpacity,
            weight: 2
        });

        // Popup podstawowy
        const popupContent = this.createPopupContent(medal);
        marker.bindPopup(popupContent, {
            className: 'medal-popup',
            maxWidth: 300
        });

        // Event listener dla szczegółowych informacji
        marker.on('click', () => {
            this.showMedalModal(medal);
        });

        return marker;
    }

    createPopupContent(medal) {
        const isAvailable = medal.available_medals > 0;
        const statusClass = isAvailable ? 'available' : 'unavailable';
        const statusText = isAvailable ? 
            `Dostępne: ${medal.available_medals}/${medal.total_medals}` :
            'Brak dostępnych medali';

        return `
            <div class="medal-popup">
                <h4>${medal.name}</h4>
                <p>${medal.description || ''}</p>
                <div class="medal-status ${statusClass}">
                    ${statusText}
                </div>
                <p><small>Kliknij aby zobaczyć szczegóły</small></p>
            </div>
        `;
    }

    showMedalModal(medal) {
        const modalId = `medal-modal-${this.options.containerId.split('-').pop()}`;
        const modal = this.container.querySelector(`#${modalId}`);
        if (!modal) return;

        const title = modal.querySelector(`#medal-title-${this.options.containerId.split('-').pop()}`);
        const content = modal.querySelector(`#medal-content-${this.options.containerId.split('-').pop()}`);
        const actions = modal.querySelector(`#medal-actions-${this.options.containerId.split('-').pop()}`);

        title.textContent = medal.name;

        const isAvailable = medal.available_medals > 0;
        const lastTakenText = medal.last_taken_at ? 
            new Date(medal.last_taken_at).toLocaleString('pl-PL') : 'Nigdy';

        content.innerHTML = `
            <div class="medal-info">
                ${medal.description ? `<p><strong>Opis:</strong> ${medal.description}</p>` : ''}
                <div class="medal-info-row">
                    <span class="medal-info-label">Dostępne medale:</span>
                    <span class="medal-info-value ${isAvailable ? 'medal-available' : 'medal-unavailable'}">
                        ${medal.available_medals} / ${medal.total_medals}
                    </span>
                </div>
                <div class="medal-info-row">
                    <span class="medal-info-label">Ostatnio zabrany:</span>
                    <span class="medal-info-value">${lastTakenText}</span>
                </div>
                ${medal.last_taken_by ? `
                <div class="medal-info-row">
                    <span class="medal-info-label">Zabrany przez:</span>
                    <span class="medal-info-value">${medal.last_taken_by}</span>
                </div>
                ` : ''}
            </div>
        `;

        actions.innerHTML = '';
        if (isAvailable) {
            const takeButton = document.createElement('button');
            takeButton.className = 'medal-action-button';
            takeButton.textContent = 'Zabrałem medal';
            takeButton.onclick = () => this.takeMedal(medal.id);
            actions.appendChild(takeButton);
        }

        modal.style.display = 'block';
    }

    takeMedal(medalId) {
        if (!this.userEmail) {
            this.showEmailModal(medalId);
            return;
        }

        if (!confirm(medalMapAjax.messages.confirm_take)) {
            return;
        }

        jQuery.ajax({
            url: medalMapAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'medal_map_take_medal',
                medal_id: medalId,
                user_email: this.userEmail,
                nonce: medalMapAjax.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess(`Medal "${response.data.medal_name}" został pomyślnie zabrany!`);
                    this.closeModals();
                    // Odśwież mapę
                    this.loadMap(this.currentMapData.map.id);
                } else {
                    this.showError(response.data || medalMapAjax.messages.error);
                }
            },
            error: () => {
                this.showError(medalMapAjax.messages.error);
            }
        });
    }

    showEmailModal(medalId) {
        const modalId = `email-modal-${this.options.containerId.split('-').pop()}`;
        const modal = this.container.querySelector(`#${modalId}`);
        if (!modal) return;

        modal.style.display = 'block';
        modal.dataset.medalId = medalId;

        const emailInput = modal.querySelector('input[type="email"]');
        if (emailInput) emailInput.focus();
    }

    saveUserEmail() {
        const modalId = `email-modal-${this.options.containerId.split('-').pop()}`;
        const modal = this.container.querySelector(`#${modalId}`);
        const emailInput = modal.querySelector('input[type="email"]');

        if (!emailInput.value || !this.isValidEmail(emailInput.value)) {
            this.showError(medalMapAjax.messages.invalid_email);
            return;
        }

        this.userEmail = emailInput.value;
        this.setUserEmail(this.userEmail);

        const medalId = modal.dataset.medalId;
        this.closeModals();

        if (medalId) {
            this.takeMedal(parseInt(medalId));
        }
    }

    showMapInfo() {
        const info = this.container.querySelector('.medal-map-info');
        if (!info) return;

        const description = info.querySelector(`#map-description-${this.options.containerId.split('-').pop()}`);
        const medalsCount = info.querySelector(`#medals-count-${this.options.containerId.split('-').pop()}`);

        const map = this.currentMapData.map;
        const medals = this.currentMapData.medals;
        const totalMedals = medals.reduce((sum, medal) => sum + medal.total_medals, 0);
        const availableMedals = medals.reduce((sum, medal) => sum + medal.available_medals, 0);

        if (description) {
            description.innerHTML = map.description || '';
        }

        if (medalsCount) {
            medalsCount.innerHTML = `
                <p><strong>Łącznie medali:</strong> ${totalMedals}</p>
                <p><strong>Dostępne medale:</strong> ${availableMedals}</p>
                <p><strong>Liczba punktów:</strong> ${medals.length}</p>
            `;
        }

        info.style.display = 'block';
    }

    showSuccess(message) {
        let successDiv = this.container.querySelector('.medal-map-success');
        if (!successDiv) {
            successDiv = document.createElement('div');
            successDiv.className = 'medal-map-success';
            this.container.appendChild(successDiv);
        }

        successDiv.textContent = message;
        successDiv.style.display = 'block';

        // Ukryj po 5 sekundach
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 5000);
    }

    closeModals() {
        const modals = this.container.querySelectorAll('.medal-map-modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }

    getUserEmail() {
        return this.getCookie('medal_map_user_email');
    }

    setUserEmail(email) {
        this.setCookie('medal_map_user_email', email, 30);
    }

    isValidEmail(email) {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(email);
    }

    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    setCookie(name, value, days) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = `${name}=${value}; expires=${expires}; path=/`;
    }
}

// Inicjalizacja gdy DOM jest gotowy
jQuery(document).ready(function($) {
    // Global exposure for manual initialization
    window.MedalMapSystem = MedalMapSystem;
});