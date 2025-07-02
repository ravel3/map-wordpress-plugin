<?php
/**
 * Uninstall Script for Medal Map Database System
 */

// Jeśli nie wywołano z WordPress, zakończ
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Sprawdź czy użytkownik chce usunąć dane
if (get_option('medal_map_delete_data_on_uninstall', false)) {
    global $wpdb;

    // Lista tabel do usunięcia
    $tables = array(
        $wpdb->prefix . 'medal_history',
        $wpdb->prefix . 'medal_medal_status',
        $wpdb->prefix . 'medal_medals',
        $wpdb->prefix . 'medal_maps'
    );

    // Usuń tabele
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    // Usuń opcje
    delete_option('medal_map_db_version');
    delete_option('medal_map_delete_data_on_uninstall');

    // Wyczyść cache
    wp_cache_flush();
}
?>