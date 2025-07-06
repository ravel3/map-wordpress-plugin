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
        $user_email = sanitize_email($_POST['user_email']);

        if (!$medal_id) {
            wp_send_json_error('Nieprawidłowy ID medalu');
        }

        if (!is_email($user_email)) {
            wp_send_json_error('Nieprawidłowy adres e-mail');
        }

        $medal = Medal_Map_Database::get_medal($medal_id);
        if (!$medal) {
            wp_send_json_error('Medal nie został znaleziony');
        }

        if ($medal->available_medals <= 0) {
            wp_send_json_error('Medal nie jest dostępny');
        }

        // Opcjonalne: sprawdź czy użytkownik już nie zabrał tego medalu
        //TODO: consider to enable once providing e-mail is required
//        global $wpdb;
//        $table_history = $wpdb->prefix . 'medal_history';
//        $already_taken = $wpdb->get_var($wpdb->prepare(
//            "SELECT COUNT(*) FROM $table_history WHERE medal_id = %d AND user_email = %s",
//            $medal_id, $user_email
//        ));
//
//        if ($already_taken > 0) {
//            wp_send_json_error('Już zabrałeś ten medal');
//        }

        // Zabierz medal
        $result = Medal_Map_Database::take_medal($medal_id, $user_email);

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
     * Sprawdzenie czy użytkownik już zabrał medal
     */
    private function user_already_took_medal($medal_id, $user_email) {
        global $wpdb;

        $table_history = $wpdb->prefix . 'medal_history';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_history WHERE medal_id = %d AND user_email = %s",
            $medal_id, $user_email
        ));

        return $count > 0;
    }
}
?>