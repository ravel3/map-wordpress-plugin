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
                 style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; display: none;">
            </div>

        </div>

        <!-- Modal do wprowadzania e-maila -->
        <div id="email-modal-<?php echo esc_attr($container_id); ?>" class="medal-map-modal" style="display: none;">
            <div class="medal-map-modal-content">
                <span class="medal-map-modal-close">&times;</span>
                <h3><?php _e('Podaj swój adres e-mail', 'medal-map'); ?></h3>
                <p><?php _e('Aby zabrać medal, musisz podać swój adres e-mail. Zostanie on zapisany i nie będziesz musiał go podawać ponownie.', 'medal-map'); ?></p>
                <form id="email-form-<?php echo esc_attr($container_id); ?>">
                    <input type="email" 
                           id="user-email-<?php echo esc_attr($container_id); ?>" 
                           placeholder="<?php _e('Twój adres e-mail', 'medal-map'); ?>" 
                           required 
                           style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="submit" 
                            style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        <?php _e('Zapisz i kontynuuj', 'medal-map'); ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal do informacji o medalu -->
        <div id="medal-modal-<?php echo esc_attr($container_id); ?>" class="medal-map-modal" style="display: none;">
            <div class="medal-map-modal-content">
                <span class="medal-map-modal-close">&times;</span>
                <h3 id="medal-title-<?php echo esc_attr($container_id); ?>"></h3>
                <div id="medal-content-<?php echo esc_attr($container_id); ?>"></div>
                <div id="medal-actions-<?php echo esc_attr($container_id); ?>"></div>
            </div>
        </div>

        <div class="medals-table-container">
            <div class="medals-table-title">📋 Lista medali</div>
            <div class="table-wrapper">
                <table id="medalsTable">
                    <thead>
                    <tr>
                        <th>Nazwa PK</th>
                        <th>Medale</th>
                    </tr>
                    </thead>
                    <tbody id="medalsTableBody">
                    <!-- Wiersze generowane przez JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var medalMapInstance = new MedalMapSystem({
                containerId: '<?php echo esc_js($container_id); ?>',
                mapId: '<?php echo esc_js($map_id); ?>',
                autoZoom: <?php echo $atts['auto_zoom'] === 'true' ? 'true' : 'false'; ?>,
                snapZoom: <?php echo $atts['snap_zoom']; ?>,
                deltaZoom: <?php echo $atts['delta_zoom']; ?>
        });
        });
        </script>

        <?php
        return ob_get_clean();
    }
}
?>