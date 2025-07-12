<?php
/**
 * Klasa obsługująca panel administracyjny systemu map medalów
 */

if (!defined('ABSPATH')) {
    exit;
}

class Medal_Map_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('admin_init', array($this, 'handle_medal_delete'));
    }

    /**
     * Dodanie menu w panelu administracyjnym
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Mapy Medali', 'medal-map'),
            __('Mapy Medali', 'medal-map'),
            'manage_options',
            'medal-map',
            array($this, 'admin_page'),
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'medal-map',
            __('Wszystkie Mapy', 'medal-map'),
            __('Wszystkie Mapy', 'medal-map'),
            'manage_options',
            'medal-map',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'medal-map',
            __('Dodaj Mapę', 'medal-map'),
            __('Dodaj Mapę', 'medal-map'),
            'manage_options',
            'medal-map-add',
            array($this, 'add_map_page')
        );

        add_submenu_page(
            'medal-map',
            __('Medale', 'medal-map'),
            __('Medale', 'medal-map'),
            'manage_options',
            'medal-map-medals',
            array($this, 'medals_page')
        );

        add_submenu_page(
            'medal-map',
            __('Historia', 'medal-map'),
            __('Historia', 'medal-map'),
            'manage_options',
            'medal-map-history',
            array($this, 'history_page')
        );

    }

    /**
     * Ładowanie skryptów administratora
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'medal-map') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('medal-map-admin-css', MEDAL_MAP_PLUGIN_URL . 'admin/admin.css', array(), MEDAL_MAP_VERSION);
        wp_enqueue_script('medal-map-admin-js', MEDAL_MAP_PLUGIN_URL . 'admin/admin.js', array('jquery', 'jquery-ui-sortable'), MEDAL_MAP_VERSION, true);
        
        wp_localize_script('medal-map-admin-js', 'medalMapAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('medal_map_admin_nonce'),
            'plugin_url' => MEDAL_MAP_PLUGIN_URL . 'admin/'
        ));
    }

    /**
     * Obsługa usuwania medalu - osobny handler
     */
    public function handle_medal_delete() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'medal_delete' && isset($_GET['id']) && isset($_GET['map_id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_medal_' . $_GET['id'])) {
                $this->handle_delete_medal($_GET['id'], $_GET['map_id']);
            }
        }
    }

    /**
     * Obsługa akcji administratora
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Dodawanie mapy
        if (isset($_POST['add_map']) && wp_verify_nonce($_POST['_wpnonce'], 'add_map')) {
            $this->handle_add_map();
        }

        // Edycja mapy
        if (isset($_POST['edit_map']) && wp_verify_nonce($_POST['_wpnonce'], 'edit_map')) {
            $this->handle_edit_map();
        }

        // Usuwanie mapy
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && !isset($_GET['map_id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_map_' . $_GET['id'])) {
                $this->handle_delete_map($_GET['id']);
            }
        }

        // Dodawanie medalu
        if (isset($_POST['add_medal']) && wp_verify_nonce($_POST['_wpnonce'], 'add_medal')) {
            $this->handle_add_medal();
        }

        // Edycja medalu
        if (isset($_POST['edit_medal']) && wp_verify_nonce($_POST['_wpnonce'], 'edit_medal')) {
            $this->handle_edit_medal();
        }
    }

    /**
     * Strona główna - lista map
     */
    public function admin_page() {
        $maps = Medal_Map_Database::get_maps();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Mapy Medalów', 'medal-map'); ?>
                <a href="<?php echo admin_url('admin.php?page=medal-map-add'); ?>" class="page-title-action">
                    <?php _e('Dodaj Nową', 'medal-map'); ?>
                </a>
            </h1>

            <?php $this->show_admin_notices(); ?>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <button type="button" class="button" onclick="location.reload()">
                        <?php _e('Odśwież', 'medal-map'); ?>
                    </button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-name column-primary">
                            <?php _e('Nazwa', 'medal-map'); ?>
                        </th>
                        <th scope="col" class="manage-column">
                            <?php _e('Opis', 'medal-map'); ?>
                        </th>
                        <th scope="col" class="manage-column">
                            <?php _e('Liczba Medali', 'medal-map'); ?>
                        </th>
                        <th scope="col" class="manage-column">
                            <?php _e('Status', 'medal-map'); ?>
                        </th>
                        <th scope="col" class="manage-column">
                            <?php _e('Data Utworzenia', 'medal-map'); ?>
                        </th>
                        <th scope="col" class="manage-column">
                            <?php _e('Akcje', 'medal-map'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($maps): ?>
                        <?php foreach ($maps as $map): ?>
                            <?php $medals_count = $this->get_medals_count($map->id); ?>
                            <tr>
                                <td class="name column-name has-row-actions column-primary">
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=medal-map-add&action=edit&id=' . $map->id); ?>">
                                            <?php echo esc_html($map->name); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=medal-map-add&action=edit&id=' . $map->id); ?>">
                                                <?php _e('Edytuj', 'medal-map'); ?>
                                            </a> |
                                        </span>
                                        <span class="view">
                                            <a href="<?php echo admin_url('admin.php?page=medal-map-medals&map_id=' . $map->id); ?>">
                                                <?php _e('Medale', 'medal-map'); ?>
                                            </a> |
                                        </span>
                                        <span class="trash">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=medal-map&action=delete&id=' . $map->id), 'delete_map_' . $map->id); ?>" 
                                               onclick="return confirm('<?php _e('Czy na pewno chcesz usunąć tę mapę?', 'medal-map'); ?>')">
                                                <?php _e('Usuń', 'medal-map'); ?>
                                            </a>
                                        </span>
                                    </div>
                                    <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Pokaż więcej szczegółów', 'medal-map'); ?></span></button>
                                </td>
                                <td class="desc column-desc">
                                    <?php echo esc_html(wp_trim_words($map->description, 10)); ?>
                                </td>
                                <td class="medals column-medals">
                                    <?php echo $medals_count; ?>
                                </td>
                                <td class="status column-status">
                                    <span class="status-<?php echo esc_attr($map->status); ?>">
                                        <?php echo $map->status === 'active' ? __('Aktywna', 'medal-map') : __('Nieaktywna', 'medal-map'); ?>
                                    </span>
                                </td>
                                <td class="date column-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($map->created_at)); ?>
                                </td>
                                <td class="actions column-actions">
                                    <a href="<?php echo admin_url('admin.php?page=medal-map-add&action=edit&id=' . $map->id); ?>" class="button button-small">
                                        <?php _e('Edytuj', 'medal-map'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-items">
                                <?php _e('Nie znaleziono map. ', 'medal-map'); ?>
                                <a href="<?php echo admin_url('admin.php?page=medal-map-add'); ?>">
                                    <?php _e('Dodaj pierwszą mapę', 'medal-map'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Strona dodawania/edycji mapy
     */
    public function add_map_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'add';
        $map = null;

        if ($action === 'edit' && isset($_GET['id'])) {
            $map = Medal_Map_Database::get_map($_GET['id']);
            if (!$map) {
                wp_die(__('Mapa nie została znaleziona.', 'medal-map'));
            }
        }

        $title = $action === 'edit' ? __('Edytuj Mapę', 'medal-map') : __('Dodaj Nową Mapę', 'medal-map');
        ?>
        <div class="wrap">
            <h1><?php echo $title; ?></h1>

            <?php $this->show_admin_notices(); ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field($action === 'edit' ? 'edit_map' : 'add_map'); ?>

                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="map_id" value="<?php echo esc_attr($map->id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="map_name"><?php _e('Nazwa Mapy', 'medal-map'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="map_name" 
                                   name="map_name" 
                                   value="<?php echo $map ? esc_attr($map->name) : ''; ?>" 
                                   class="regular-text" 
                                   required>
                            <p class="description"><?php _e('Wprowadź nazwę mapy', 'medal-map'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="map_description"><?php _e('Opis', 'medal-map'); ?></label>
                        </th>
                        <td>
                            <textarea id="map_description" 
                                      name="map_description" 
                                      rows="3" 
                                      class="large-text"><?php echo $map ? esc_textarea($map->description) : ''; ?></textarea>
                            <p class="description"><?php _e('Opcjonalny opis mapy', 'medal-map'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="map_image"><?php _e('Obraz Mapy', 'medal-map'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="map_image" 
                                   name="map_image" 
                                   value="<?php echo $map ? esc_attr($map->image_url) : ''; ?>" 
                                   class="regular-text">
                            <button type="button" class="button" id="upload_image_button">
                                <?php _e('Wybierz Obraz', 'medal-map'); ?>
                            </button>
                            <p class="description"><?php _e('URL obrazu mapy lub wybierz z biblioteki mediów', 'medal-map'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Wymiary Obrazu', 'medal-map'); ?></th>
                        <td>
                            <label for="image_width"><?php _e('Szerokość:', 'medal-map'); ?></label>
                            <input type="number" 
                                   id="image_width" 
                                   name="image_width" 
                                   value="<?php echo $map ? esc_attr($map->image_width) : '1000'; ?>" 
                                   class="small-text" 
                                   min="100" 
                                   max="5000"> px

                            <label for="image_height" style="margin-left: 20px;"><?php _e('Wysokość:', 'medal-map'); ?></label>
                            <input type="number" 
                                   id="image_height" 
                                   name="image_height" 
                                   value="<?php echo $map ? esc_attr($map->image_height) : '800'; ?>" 
                                   class="small-text" 
                                   min="100" 
                                   max="5000"> px
                            <p class="description"><?php _e('Rzeczywiste wymiary obrazu w pikselach', 'medal-map'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Ustawienia Zoomu', 'medal-map'); ?></th>
                        <td>
                            <label for="min_zoom"><?php _e('Min zoom:', 'medal-map'); ?></label>
                            <input type="number" 
                                   id="min_zoom" 
                                   name="min_zoom" 
                                   value="<?php echo $map ? esc_attr($map->min_zoom) : '0'; ?>" 
                                   class="small-text" 
                                   min="-5"
                                   max="5">

                            <label for="max_zoom" style="margin-left: 20px;"><?php _e('Max zoom:', 'medal-map'); ?></label>
                            <input type="number" 
                                   id="max_zoom" 
                                   name="max_zoom" 
                                   value="<?php echo $map ? esc_attr($map->max_zoom) : '3'; ?>" 
                                   class="small-text" 
                                   min="0" 
                                   max="10">

                            <label for="default_zoom" style="margin-left: 20px;"><?php _e('Domyślny zoom:', 'medal-map'); ?></label>
                            <input type="number" 
                                   id="default_zoom" 
                                   name="default_zoom" 
                                   value="<?php echo $map ? esc_attr($map->default_zoom) : '1'; ?>" 
                                   class="small-text" 
                                   min="-5"
                                   max="10">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="map_status"><?php _e('Status', 'medal-map'); ?></label>
                        </th>
                        <td>
                            <select id="map_status" name="map_status">
                                <option value="active" <?php selected($map ? $map->status : 'active', 'active'); ?>>
                                    <?php _e('Aktywna', 'medal-map'); ?>
                                </option>
                                <option value="inactive" <?php selected($map ? $map->status : '', 'inactive'); ?>>
                                    <?php _e('Nieaktywna', 'medal-map'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" 
                           name="<?php echo $action === 'edit' ? 'edit_map' : 'add_map'; ?>" 
                           class="button-primary" 
                           value="<?php echo $action === 'edit' ? __('Zaktualizuj Mapę', 'medal-map') : __('Dodaj Mapę', 'medal-map'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=medal-map'); ?>" class="button">
                        <?php _e('Anuluj', 'medal-map'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Strona medali
     */
    public function medals_page() {
        // If this is a delete action, don't render the page
        if (isset($_GET['action']) && $_GET['action'] === 'medal_delete') {
            return;
        }
        
        $map_id = isset($_GET['map_id']) ? intval($_GET['map_id']) : 1;
        
        // Get map info
        $map = Medal_Map_Database::get_map($map_id);
        if (!$map) {
            wp_die(__('Mapa nie została znaleziona.', 'medal-map'));
        }
        ?>
        <div class="wrap">
            <h1>
                <?php printf(__('Zarządzanie Medalami - %s', 'medal-map'), esc_html($map->name)); ?>
                <a href="<?php echo admin_url('admin.php?page=medal-map'); ?>" class="page-title-action">
                    <?php _e('Powrót do Map', 'medal-map'); ?>
                </a>
            </h1>

            <?php $this->show_admin_notices(); ?>

            <div class="medal-controls">
                <button id="add-medal" class="button button-primary">
                    <?php _e('Dodaj Medal', 'medal-map'); ?>
                </button>
            </div>

            <?php
            require_once(MEDAL_MAP_PLUGIN_PATH . 'includes/class-medal-list-table.php');
            $medal_table = new Medal_List_Table($map_id);
            $medal_table->prepare_items();
            $medal_table->display();
            ?>
        </div>
        <?php
    }

    /**
     * Strona historii
     */
    public function history_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Historia Pobrań Medali', 'medal-map'); ?></h1>
            <p><?php _e('Historia pobrań medali będzie dostępna w pełnej wersji.', 'medal-map'); ?></p>
        </div>
        <?php
    }
    /**
     * Obsługa dodawania mapy
     */
    private function handle_add_map() {
        global $wpdb;

        $table_maps = $wpdb->prefix . 'medal_maps';

        $data = array(
            'name' => sanitize_text_field($_POST['map_name']),
            'description' => sanitize_textarea_field($_POST['map_description']),
            'image_url' => esc_url($_POST['map_image']),
            'image_width' => intval($_POST['image_width']),
            'image_height' => intval($_POST['image_height']),
            'min_zoom' => doubleval($_POST['min_zoom']),
            'max_zoom' => doubleval($_POST['max_zoom']),
            'default_zoom' => doubleval($_POST['default_zoom']),
            'status' => sanitize_text_field($_POST['map_status'])
        );

        $result = $wpdb->insert($table_maps, $data);

        if ($result) {
            $this->add_admin_notice(__('Mapa została pomyślnie dodana.', 'medal-map'), 'success');
        } else {
            $this->add_admin_notice(__('Błąd podczas dodawania mapy.', 'medal-map'), 'error');
        }
    }

    /**
     * Obsługa edycji mapy
     */
    private function handle_edit_map() {
        global $wpdb;

        $table_maps = $wpdb->prefix . 'medal_maps';
        $map_id = intval($_POST['map_id']);

        $data = array(
            'name' => sanitize_text_field($_POST['map_name']),
            'description' => sanitize_textarea_field($_POST['map_description']),
            'image_url' => esc_url($_POST['map_image']),
            'image_width' => intval($_POST['image_width']),
            'image_height' => intval($_POST['image_height']),
            'min_zoom' => intval($_POST['min_zoom']),
            'max_zoom' => intval($_POST['max_zoom']),
            'default_zoom' => intval($_POST['default_zoom']),
            'status' => sanitize_text_field($_POST['map_status'])
        );

        $result = $wpdb->update($table_maps, $data, array('id' => $map_id));

        if ($result !== false) {
            $this->add_admin_notice(__('Mapa została pomyślnie zaktualizowana.', 'medal-map'), 'success');
        } else {
            $this->add_admin_notice(__('Błąd podczas aktualizacji mapy.', 'medal-map'), 'error');
        }
    }

    /**
     * Obsługa usuwania mapy
     */
    private function handle_delete_map($map_id) {
        global $wpdb;

        $table_maps = $wpdb->prefix . 'medal_maps';
        $result = $wpdb->delete($table_maps, array('id' => intval($map_id)));

        if ($result) {
            $this->add_admin_notice(__('Mapa została pomyślnie usunięta.', 'medal-map'), 'success');
        } else {
            $this->add_admin_notice(__('Błąd podczas usuwania mapy.', 'medal-map'), 'error');
        }

        wp_redirect(admin_url('admin.php?page=medal-map'));
        exit;
    }

    /**
     * Obsługa usuwania medalu
     */
    private function handle_delete_medal($medal_id, $map_id) {
        global $wpdb;

        $table_medals = $wpdb->prefix . 'medal_medals';
        $result = $wpdb->delete($table_medals, array('id' => intval($medal_id)));

        if ($result) {
            $this->add_admin_notice(__('Medal został pomyślnie usunięty.', 'medal-map'), 'success');
        } else {
            $this->add_admin_notice(__('Błąd podczas usuwania medalu.', 'medal-map'), 'error');
        }

        wp_redirect(admin_url('admin.php?page=medal-map-medals&map_id=' . $map_id));
        exit;
    }

    private function get_medals_count($map_id) {
        global $wpdb;
        $table_medals = $wpdb->prefix . 'medal_medals';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_medals WHERE map_id = %d", $map_id));
    }

    private function add_admin_notice($message, $type = 'info') {
        set_transient('medal_map_admin_notice', array('message' => $message, 'type' => $type), 30);
    }

    private function show_admin_notices() {
        $notice = get_transient('medal_map_admin_notice');
        if ($notice) {
            delete_transient('medal_map_admin_notice');
            ?>
            <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                <p><?php echo esc_html($notice['message']); ?></p>
            </div>
            <?php
        }
    }
}
?>