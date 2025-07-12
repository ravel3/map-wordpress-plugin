<?php
if (!defined('ABSPATH')) {
    exit;
}

class Medal_Map_Ajax {

    public function __construct() {
        add_action('wp_ajax_medal_map_get_maps', array($this, 'get_maps'));
        add_action('wp_ajax_nopriv_medal_map_get_maps', array($this, 'get_maps'));

        add_action('wp_ajax_medal_map_get_medals', array($this, 'get_medals'));
        add_action('wp_ajax_nopriv_medal_map_get_medals', array($this, 'get_medals'));

        add_action('wp_ajax_medal_map_get_medal_info', array($this, 'get_medal_info'));
        add_action('wp_ajax_nopriv_medal_map_get_medal_info', array($this, 'get_medal_info'));

        add_action('wp_ajax_medal_map_take_medal', array($this, 'take_medal'));
        add_action('wp_ajax_nopriv_medal_map_take_medal', array($this, 'take_medal'));

        // Admin AJAX actions for medal management
        add_action('wp_ajax_medal_map_add_medal', array($this, 'add_medal'));
        add_action('wp_ajax_medal_map_edit_medal', array($this, 'edit_medal'));
        add_action('wp_ajax_medal_map_delete_medal', array($this, 'delete_medal'));
        add_action('wp_ajax_medal_map_get_medal_for_edit', array($this, 'get_medal_for_edit'));
    }

    public function get_maps() {
        // Sprawdź nonce
        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_nonce')) {
            wp_die('Nieprawidłowy token bezpieczeństwa');
        }

        $maps = Medal_Map_Database::get_maps();

        if ($maps) {
            wp_send_json_success($maps);
        } else {
            wp_send_json_error('Nie znaleziono map');
        }
    }

    public function get_medals() {
        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_nonce')) {
            wp_die('Nieprawidłowy token bezpieczeństwa');
        }

        $map_id = intval($_POST['map_id']);

        if (!$map_id) {
            wp_send_json_error('Nieprawidłowy ID mapy');
        }

        $map = Medal_Map_Database::get_map($map_id);
        if (!$map) {
            wp_send_json_error('Mapa nie została znaleziona');
        }

        $medals = Medal_Map_Database::get_medals_for_map($map_id);

        wp_send_json_success(array(
            'map' => $map,
            'medals' => $medals
        ));
    }

    public function get_medal_info() {
        // Sprawdź nonce
        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_nonce')) {
            wp_die('Nieprawidłowy token bezpieczeństwa');
        }

        $medal_id = intval($_POST['medal_id']);

        if (!$medal_id) {
            wp_send_json_error('Nieprawidłowy ID medalu');
        }

        $medal = Medal_Map_Database::get_medal($medal_id);

        if ($medal) {
            wp_send_json_success($medal);
        } else {
            wp_send_json_error('Medal nie został znaleziony');
        }
    }

    public function take_medal() {
        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_nonce')) {
            wp_die('Nieprawidłowy token bezpieczeństwa');
        }

        $medal_id = intval($_POST['medal_id']);

        if (!$medal_id) {
            wp_send_json_error('Nieprawidłowy ID medalu');
        }

        $medal = Medal_Map_Database::get_medal($medal_id);
        if (!$medal) {
            wp_send_json_error('Medal nie został znaleziony');
        }

        if ($medal->available_medals <= 0) {
            wp_send_json_error('Medal nie jest dostępny');
        }

        $result = Medal_Map_Database::take_medal($medal_id);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Medal został pomyślnie zabrany!',
                'available_medals' => $result['available_medals'],
                'last_taken_at' => $result['last_taken_at'],
                'medal_name' => $medal->name
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Add new medal
     */
    public function add_medal() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
        }

        $map_id = intval($_POST['map_id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $pk_no = sanitize_text_field($_POST['pk_no']);
        $x_coordinate = intval($_POST['x_coordinate']);
        $y_coordinate = intval($_POST['y_coordinate']);
        $total_medals = intval($_POST['total_medals']);
        $available_medals = intval($_POST['available_medals']);

        if (!$map_id || !$name || $x_coordinate < 0 || $y_coordinate < 0) {
            wp_send_json_error("Nieprawidłowe dane - map_id: $map_id, name: '$name', x: $x_coordinate, y: $y_coordinate");
        }

        global $wpdb;
        $table_medals = $wpdb->prefix . 'medal_medals';

        $result = $wpdb->insert($table_medals, array(
            'map_id' => $map_id,
            'name' => $name,
            'description' => $description,
            'pk_no' => $pk_no,
            'x_coordinate' => $x_coordinate,
            'y_coordinate' => $y_coordinate,
            'total_medals' => $total_medals,
            'available_medals' => $available_medals
        ));

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Medal został pomyślnie dodany',
                'medal_id' => $wpdb->insert_id
            ));
        } else {
            wp_send_json_error('Błąd podczas dodawania medalu');
        }
    }

    /**
     * Edit medal
     */
    public function edit_medal() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
        }

        $medal_id = intval($_POST['medal_id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $pk_no = sanitize_text_field($_POST['pk_no']);
        $x_coordinate = intval($_POST['x_coordinate']);
        $y_coordinate = intval($_POST['y_coordinate']);
        $total_medals = intval($_POST['total_medals']);
        $available_medals = intval($_POST['available_medals']);

        if (!$medal_id || !$name || $x_coordinate < 0 || $y_coordinate < 0) {
            wp_send_json_error("Nieprawidłowe dane - medal_id: $medal_id, name: '$name', x: $x_coordinate, y: $y_coordinate");
        }

        global $wpdb;
        $table_medals = $wpdb->prefix . 'medal_medals';

        $result = $wpdb->update($table_medals, array(
            'name' => $name,
            'description' => $description,
            'pk_no' => $pk_no,
            'x_coordinate' => $x_coordinate,
            'y_coordinate' => $y_coordinate,
            'total_medals' => $total_medals,
            'available_medals' => $available_medals
        ), array('id' => $medal_id));

        if ($result !== false) {
            wp_send_json_success('Medal został pomyślnie zaktualizowany');
        } else {
            wp_send_json_error('Błąd podczas aktualizacji medalu');
        }
    }

    /**
     * Delete medal
     */
    public function delete_medal() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
        }

        $medal_id = intval($_POST['medal_id']);

        if (!$medal_id) {
            wp_send_json_error('Nieprawidłowy ID medalu');
        }

        global $wpdb;
        $table_medals = $wpdb->prefix . 'medal_medals';

        $result = $wpdb->delete($table_medals, array('id' => $medal_id));

        if ($result) {
            wp_send_json_success('Medal został pomyślnie usunięty');
        } else {
            wp_send_json_error('Błąd podczas usuwania medalu');
        }
    }

    /**
     * Get medal data for editing
     */
    public function get_medal_for_edit() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'medal_map_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
        }

        $medal_id = intval($_POST['medal_id']);

        if (!$medal_id) {
            wp_send_json_error('Nieprawidłowy ID medalu');
        }

        $medal = Medal_Map_Database::get_medal($medal_id);

        if ($medal) {
            wp_send_json_success($medal);
        } else {
            wp_send_json_error('Medal nie został znaleziony');
        }
    }
}
?>