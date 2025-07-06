<?php
if (!defined('ABSPATH')) {
    exit;
}

class Medal_Map_Database {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();


        $table_maps = $wpdb->prefix . 'medal_maps';
        $sql_maps = "CREATE TABLE $table_maps (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            image_url varchar(500),
            image_width int(11),
            image_height int(11),
            min_zoom DECIMAL(5,1) DEFAULT -2.0,
            max_zoom DECIMAL(5,1) DEFAULT 4.0,
            default_zoom DECIMAL(5,1) DEFAULT 0.0,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";


        $table_medals = $wpdb->prefix . 'medal_medals';
        $sql_medals = "CREATE TABLE $table_medals (
            id int(11) NOT NULL AUTO_INCREMENT,
            map_id int(11) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            pk_no varchar(20),
            x_coordinate int(11) NOT NULL,
            y_coordinate int(11) NOT NULL,
            total_medals int(11) DEFAULT 1,
            available_medals int(11) DEFAULT 1,
            last_taken_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (map_id) REFERENCES $table_maps(id) ON DELETE CASCADE
        ) $charset_collate;";


        $table_medal_history = $wpdb->prefix . 'medal_history';
        $sql_medal_history = "CREATE TABLE $table_medal_history (
            id int(11) NOT NULL AUTO_INCREMENT,
            medal_id int(11) NOT NULL,
            taken_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            FOREIGN KEY (medal_id) REFERENCES $table_medals(id) ON DELETE CASCADE,
            INDEX idx_taken_at (taken_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_maps);
        dbDelta($sql_medals);
        dbDelta($sql_medal_history);

        // Dodanie opcji wersji bazy danych
        update_option('medal_map_db_version', MEDAL_MAP_VERSION);
    }

    /**
     * Usuwanie tabel bazy danych
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'medal_history',
            $wpdb->prefix . 'medal_medals',
            $wpdb->prefix . 'medal_maps'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('medal_map_db_version');
    }

    public static function get_maps() {
        global $wpdb;

        $table_maps = $wpdb->prefix . 'medal_maps';
        return $wpdb->get_results("SELECT * FROM $table_maps WHERE status = 'active' ORDER BY name");
    }

    public static function get_map($map_id) {
        global $wpdb;

        $table_maps = $wpdb->prefix . 'medal_maps';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_maps WHERE id = %d", $map_id));
    }

    public static function get_medals_for_map($map_id) {
        global $wpdb;

        $table_medals = $wpdb->prefix . 'medal_medals';

        $sql = "SELECT m.* 
                FROM $table_medals m 
                WHERE m.map_id = %d 
                ORDER BY m.name";

        return $wpdb->get_results($wpdb->prepare($sql, $map_id));
    }

    public static function get_medal($medal_id) {
        global $wpdb;

        $table_medals = $wpdb->prefix . 'medal_medals';

        $sql = "SELECT m.* 
                FROM $table_medals m 
                WHERE m.id = %d";

        return $wpdb->get_row($wpdb->prepare($sql, $medal_id));
    }

    public static function take_medal($medal_id) {
        global $wpdb;

        $table_medals = $wpdb->prefix . 'medal_medals';
        $table_history = $wpdb->prefix . 'medal_history';

        // Rozpocznij transakcję
        $wpdb->query('START TRANSACTION');

        try {
            // Sprawdź dostępność medalu
            $medal = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_medals WHERE id = %d FOR UPDATE",
                $medal_id
            ));

            if (!$medal || $medal->available_medals <= 0) {
                $wpdb->query('ROLLBACK');
                return array('success' => false, 'message' => 'Medal nie jest dostępny');
            }

            // Zmniejsz liczbę dostępnych medali
            $updated = $wpdb->update(
                $table_medals,
                array(
                    'available_medals' => $medal->available_medals - 1,
                    'last_taken_at' => current_time('mysql')
                ),
                array('id' => $medal_id),
                array('%d', '%s'),
                array('%d')
            );

            if ($updated === false) {
                $wpdb->query('ROLLBACK');
                return array('success' => false, 'message' => 'Błąd aktualizacji bazy danych');
            }

            // Dodaj wpis do historii
            $history_inserted = $wpdb->insert(
                $table_history,
                array(
                    'medal_id' => $medal_id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ),
                array('%d', '%s', '%s')
            );

            if ($history_inserted === false) {
                $wpdb->query('ROLLBACK');
                return array('success' => false, 'message' => 'Błąd zapisu historii');
            }

            $wpdb->query('COMMIT');

            return array(
                'success' => true,
                'available_medals' => $medal->available_medals - 1,
                'last_taken_at' => current_time('mysql')
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return array('success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage());
        }
    }
}
?>