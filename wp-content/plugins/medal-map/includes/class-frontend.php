<?php
if (!defined('ABSPATH')) {
    exit;
}

class Medal_Map_Frontend {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_styles'));
    }

    public function enqueue_scripts() {
        // Leaflet CSS
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );
        // Leaflet Fullscreen
        wp_enqueue_style(
            'leaflet-fullscreen-css',
            'https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css',
            array(),
            '1.0.1'
        );

        // Plugin CSS
        wp_enqueue_style(
            'medal-map-css',
            MEDAL_MAP_PLUGIN_URL . 'assets/css/medal-map.css',
            array('leaflet-css'),
            MEDAL_MAP_VERSION
        );

        // Leaflet JS
        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        wp_enqueue_script(
            'leaflet-fullscreen-js',
            'https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js',
            array(),
            '1.0.1',
            true
        );

        // Plugin JS
        wp_enqueue_script(
            'medal-map-js',
            MEDAL_MAP_PLUGIN_URL . 'assets/js/medal-map.js',
            array('jquery', 'leaflet-js'),
            MEDAL_MAP_VERSION,
            true
        );


        wp_localize_script('medal-map-js', 'medalMapAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('medal_map_nonce'),
            'plugin_url' => MEDAL_MAP_PLUGIN_URL,
            'messages' => array(
                'loading' => __('Ładowanie...', 'medal-map'),
                'error' => __('Wystąpił błąd', 'medal-map'),
                'success' => __('Sukces!', 'medal-map'),
                'medal_taken' => __('Medal został zabrany!', 'medal-map'),
                'medal_unavailable' => __('Medal nie jest dostępny', 'medal-map'),
                'already_taken' => __('Już zabrałeś ten medal', 'medal-map'),
                'confirm_take' => __('Czy na pewno chcesz zabrać ten medal?', 'medal-map')
            )
        ));
    }

    public function add_custom_styles() {
        ?>
        <?php
    }
}
?>