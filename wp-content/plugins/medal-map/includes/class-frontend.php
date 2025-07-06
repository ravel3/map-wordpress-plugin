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
                'email_required' => __('Proszę podać adres e-mail', 'medal-map'),
                'invalid_email' => __('Proszę podać prawidłowy adres e-mail', 'medal-map'),
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
        <style>
        .medal-map-container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .medal-map-controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .medal-map-select {
            width: 100%;
            max-width: 300px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .medal-map-loading {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }

        .medal-map-error {
            padding: 15px;
            background: #ffe6e6;
            border: 1px solid #ff9999;
            border-radius: 4px;
            color: #cc0000;
            margin: 10px 0;
        }

        </style>
        <?php
    }
}
?>