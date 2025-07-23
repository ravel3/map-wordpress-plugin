/**
 * Medal Map System - Frontend JavaScript
 */

class MedalMapSystem {
    constructor(options) {
        this.options = {
            containerId: '',
            mapId: '',
            autoZoom: true,
            ...options
        };

        this.container = document.getElementById(this.options.containerId);
        this.mapElement = document.getElementById(this.options.mapId);
        this.leafletMap = null;
        this.currentMapData = null;
        this.medalMarkers = [];
        this.imageBounds = null;
        this.medals = null;
        this.init();
    }

    init() {
        this.loadMap(this.options.selectedMapId);
    }


    showLoading() {
        const loading = this.container.querySelector('.medal-map-loading');
        if (loading) loading.style.display = 'block';

        if (this.mapElement) this.mapElement.style.display = 'none';
    }

    hideLoading() {
        const loading = this.container.querySelector('.medal-map-loading');
        if (loading) loading.style.display = 'none';
    }



    hideError() {
        const errorDiv = this.container.querySelector('.medal-map-error');
        if (errorDiv) errorDiv.style.display = 'none';
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
                    this.reloadMedalsOnMap();
                    this.updateMedalTable()
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
            scrollWheelZoom: true,
            zoomSnap: this.options.snapZoom,
            zoomDelta: this.options.deltaZoom,
            fullscreenControl: this.options.fullscreenControl
        });

        this.leafletMap._medalMapSystem = this

        // utility method to get medals coordinates - output in a browser console
        this.leafletMap.on('click', function (e) {
            console.log('x: ' + e.latlng.lng + ', y: ' + e.latlng.lat + ',');
        });

        // Dodaj obraz jako podkład
        this.imageBounds = [[0, 0], [this.currentMapData.map.image_height, this.currentMapData.map.image_width]];
        L.imageOverlay(map.image_url, this.imageBounds).addTo(this.leafletMap);

        // Ustaw widok
        this.leafletMap.setMaxBounds(this.imageBounds);
        this.leafletMap.setView([map.image_height / 2,  map.image_width / 2], map.default_zoom);

        // Pokaż mapę
        this.mapElement.style.display = 'block';

        // Invalidate size po pokazaniu
        setTimeout(() => {
            this.leafletMap.invalidateSize();
        }, 100);
    }

    reloadMedalsOnMap() {
        // Wyczyść istniejące markery
        this.medalMarkers.forEach(marker => {
            this.leafletMap.removeLayer(marker);
        });
        this.medalMarkers = [];

        this.medals = this.currentMapData.medals;

        this.medals.forEach(medal => {
            const marker = this.createMedalMarker(medal);
            marker.addTo(this.leafletMap);
            this.medalMarkers.push(marker);
        });
    }

    createMedalMarker(medal) {
        const marker = L.marker([medal.y_coordinate, medal.x_coordinate]);
        const popupContent = this.createPopupContent(medal);

        marker.bindPopup(popupContent);

        const imageBounds = this.imageBounds
        marker.on('popupclose', function () {
            this._map.setMaxBounds(imageBounds);
        });
        marker.on('popupopen', function () {
            this._map.setMaxBounds(null);

            const btn = document.getElementById(`take-medal-${medal.id}`);
            if (btn) {
                btn.addEventListener('click', () => {
                    this._map._medalMapSystem.takeMedal(medal.id, marker);
                });
            }
        });

        return marker;
    }

    createPopupContent(medal) {
        let button = "";

        if (medal.available_medals > 0) {
            button = `<button 
                    id="take-medal-${medal.id}" 
                    data-medal-id="${medal.id}" 
                    class="take-medal-btn"
                    class="take-medal-btn"
                    style="background: #28a745; color: white; border: none; 
                           padding: 10px 20px; border-radius: 5px; cursor: pointer; 
                           font-weight: bold; font-size: 14px;">
                    🏅 Zabieram medal
                  </button>`;
        }
        let lastTakenAt = medal.last_taken_at ? medal.last_taken_at : 'Nigdy'

        return `
        <div style="text-align: center; min-width: 200px;">
            <h3 style="margin: 0 0 10px 0; color: #2c5aa0;">🏅 ${medal.name}</h3>
            <p style="margin: 5px 0; color: #666;">${medal.description}</p>
            <p style="margin: 10px 0 15px 0; font-weight: bold; color: #28a745;">
                Dostępne: ${medal.available_medals}/${medal.total_medals} medali
            </p>
            <p style="margin: 5px 0; color: #666;">Ostatnie zabranie: ${lastTakenAt}</p>
            ${button}
        </div>
    `;
    }


    showMessage(type, message) {
        const mapContainer = this.mapElement;
        if (!mapContainer) return;

        // Ensure relative positioning for absolute overlay
        mapContainer.style.position = 'relative';

        const existing = mapContainer.querySelector(`.medal-map-${type}`);
        if (existing) existing.remove();

        const messageDiv = document.createElement('div');
        messageDiv.className = `medal-map-message medal-map-${type}`;
        messageDiv.innerHTML = `
        <span class="message-text">${message}</span>
        <button class="close-btn" aria-label="Close">&times;</button>
    `;

        mapContainer.appendChild(messageDiv);

        // Close on click
        messageDiv.querySelector('.close-btn').addEventListener('click', () => {
            messageDiv.remove();
        });

        // Auto-hide after 5s
        setTimeout(() => {
            if (messageDiv.parentElement) {
                messageDiv.remove();
            }
        }, 5000);
    }

// Convenience wrappers
    showSuccess(message) {
        this.showMessage('success', message);
    }

    showError(message) {
        this.hideLoading();
        this.showMessage('error', message);
    }


    takeMedalByUser(medalId) {
            jQuery.ajax({
                url: medalMapAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'medal_map_take_medal',
                    medal_id: medalId,
                    nonce: medalMapAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(`Medal "${response.data.medal_name}" został pomyślnie zabrany!`);
                        this.updateMedalOnMapWithResponse(response.data, medalId);
                        this.reloadMedalsOnMap()
                        this.updateMedalTable()
                    } else {
                        this.showError(response.data || medalMapAjax.messages.error);
                    }
                },
                error: () => {
                    this.showError(medalMapAjax.messages.error);
                }
            });
    }

    updateMedalOnMapWithResponse(medalResult, medalId) {
        const medal = this.medals.find(m => m.id === medalId);
        if (!medal) return;

        medal.available_medals = medalResult.available_medals
        medal.last_taken_at = medalResult.last_taken_at
    }

    resetAllMedals() {
        if (confirm('Czy na pewno chcesz zresetować wszystkie medale?')) {
            localStorage.removeItem('takenMedals');
            // initializeMap(updateCounters, updateMedalTable);
            // this.loadMap(this.map) //TODO: getMapID somehow
        }
    }

    takeMedal(medalId, marker) {
        const medal = this.medals.find(m => m.id === medalId);
        if (!medal) return;

        this.takeMedalByUser(medalId);
        marker.closePopup();
    }

    updateMedalTable() {
        const tableBody = document.getElementById('medalsTableBody');
        tableBody.innerHTML = '';

        this.medals.sort((a, b) => a.id - b.id)
            .forEach(medal => {
                const row = document.createElement('tr');
                row.innerHTML = `<td data-label="Nazwa Medalu">${medal.pk_no} ${medal.name}</td>
                        <td data-label="Medale" class="${medal.available_medals > 0 ? 'medal-count-available' : 'medal-count-zero'}">
                        ${medal.available_medals}/${medal.total_medals} </td>`;

                tableBody.appendChild(row);
            });
    }
}

// Inicjalizacja gdy DOM jest gotowy
jQuery(document).ready(function($) {
    // Global exposure for manual initialization
    window.MedalMapSystem = MedalMapSystem;
});