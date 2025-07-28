<?php

if (!defined('ABSPATH')) {
    exit;
}

class Medal_Map_Shortcode {

    public function __construct() {
        add_shortcode('medal_map', array($this, 'render_shortcode'));
    }

    /**
     * Renderowanie shortcode
     * 
     * Użycie:
     * [medal_map]                         - wyświetla selektor map i mapę
     * [medal_map map_id="1"]              - wyświetla konkretną mapę
     * [medal_map height="600px"]          - ustala wysokość mapy
     * [medal_map show_selector="false"]   - ukrywa selektor map
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'map_id' => '',
            'height' => '500px',
            'width' => '100%',
            'auto_zoom' => 'true',
            'snap_zoom' => 0.25,
            'delta_zoom' => 0.25,
            'marker_radius' => 25,
            'marker_fill_color' => '#ff0000', //red
            'marker_fill_opacity' => 0.0, // values 0-1, 0: transparent, 1: full color

            'class' => ''
        ), $atts, 'medal_map');

        $container_id = 'medal-map-' . uniqid();
        $map_id = 'leaflet-map-' . uniqid();

        ob_start();
        ?>
        <div class="medal-map-container <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($container_id); ?>">

            <?php if ($atts['show_selector'] === 'true' && empty($atts['map_id'])): ?>
            <div class="medal-map-controls">
                <label for="map-selector-<?php echo esc_attr($container_id); ?>">
                    <?php _e('Wybierz mapę:', 'medal-map'); ?>
                </label>
                <select id="map-selector-<?php echo esc_attr($container_id); ?>" class="medal-map-select">
                    <option value=""><?php _e('-- Wybierz mapę --', 'medal-map'); ?></option>
                </select>
            </div>
            <?php endif; ?>

            <div class="medal-map-loading" id="loading-<?php echo esc_attr($container_id); ?>">
                <?php _e('Ładowanie mapy...', 'medal-map'); ?>
            </div>

            <div id="<?php echo esc_attr($map_id); ?>" 
                 style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; display: none;  background-color: transparent !important; margin: 0 auto;">
            </div>

        </div>

        <div class="medals-table-container" style="width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="medals-table-title">📋 Lista medali</div>
            <div class="table-wrapper">
                <table id="medalsTable">
                    <thead>
                    <tr>
                        <th>Nazwa PK</th>
                        <th>Liczba dostępnych medali</th>
                    </tr>
                    </thead>
                    <tbody id="medalsTableBody">
                    <!-- Wiersze generowane przez JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                var medalMapInstance = new MedalMapSystem({
                    containerId: '<?php echo esc_js($container_id); ?>',
                    mapId: '<?php echo esc_js($map_id); ?>',
                    selectedMapId: <?php echo $atts['map_id'] ? intval($atts['map_id']) : 'null'; ?>,
                    autoZoom: <?php echo $atts['auto_zoom'] === 'true' ? 'true' : 'false'; ?>,
                    snapZoom: <?php echo $atts['snap_zoom']; ?>,
                    deltaZoom: <?php echo $atts['delta_zoom']; ?>,
                    fullscreenControl: <?php echo $atts['fullscreen_control'] ? boolval($atts['fullscreen_control']) : 'true'; ?>,
                    markerRadius: <?php echo $atts['marker_radius']; ?>,
                    markerFillColor: '<?php echo $atts['marker_fill_color']; ?>',
                    markerFillOpacity: <?php echo $atts['marker_fill_opacity']; ?>
                });
            });
        </script>

        <?php
        return ob_get_clean();
    }
}
?>