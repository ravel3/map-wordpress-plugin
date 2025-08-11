<?php
/**
 * Plugin Name: Hawran Medal Map
 * Version: 1.0.0
 * Author: Artur Flis
 * Text Domain: medal-map
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

// Stałe pluginu
define('MEDAL_MAP_VERSION', '1.0.2');
define('MEDAL_MAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MEDAL_MAP_PLUGIN_PATH', plugin_dir_path(__FILE__));
require_once MEDAL_MAP_PLUGIN_PATH . 'includes/class-database.php';


/**
 * Główna klasa pluginu Medal Map
 */
class MedalMapPlugin
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    public function init()
    {
        // Ładowanie plików językowych
        load_plugin_textdomain('medal-map', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Ładowanie klas
        $this->includes();

        // Inicjalizacja komponentów
        if (is_admin()) {
            new Medal_Map_Admin();
        }

        new Medal_Map_Frontend();
        new Medal_Map_Ajax();
        new Medal_Map_Shortcode();
    }

    private function includes()
    {
        require_once MEDAL_MAP_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once MEDAL_MAP_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once MEDAL_MAP_PLUGIN_PATH . 'includes/class-shortcode.php';

        if (is_admin()) {
            require_once MEDAL_MAP_PLUGIN_PATH . 'admin/class-admin.php';
            require_once MEDAL_MAP_PLUGIN_PATH . 'includes/class-medal-list-table.php';
        }
    }

    public function activate()
    {
        // Tworzenie tabel bazy danych
        Medal_Map_Database::create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public static function uninstall()
    {
        // Usuwanie tabel (opcjonalne)
        if (get_option('medal_map_delete_data_on_uninstall', false)) {
            Medal_Map_Database::drop_tables();
        }
    }
}

// Inicjalizacja pluginu
add_action('plugins_loaded', array('MedalMapPlugin', 'get_instance'));
// Create and return plugin instance
$medal_map_plugin = MedalMapPlugin::get_instance();

// Hook activation, deactivation, uninstall
register_activation_hook(__FILE__, array($medal_map_plugin, 'activate'));
register_deactivation_hook(__FILE__, array($medal_map_plugin, 'deactivate'));
register_uninstall_hook(__FILE__, array('MedalMapPlugin', 'uninstall'));
?>