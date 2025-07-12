<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Medal_List_Table extends WP_List_Table {

    private $map_id;

    public function __construct($map_id) {
        parent::__construct(array(
            'singular' => 'medal',
            'plural'   => 'medals',
            'ajax'     => false
        ));
        $this->map_id = $map_id;
    }

    public function get_columns() {
        return array(
            'name' => __('Nazwa', 'medal-map'),
            'description' => __('Opis', 'medal-map'),
            'pk_no' => __('Numer PK', 'medal-map'),
            'coordinates' => __('Współrzędne', 'medal-map'),
            'total_medals' => __('Łączna liczba', 'medal-map'),
            'available_medals' => __('Dostępne', 'medal-map'),
            'last_taken_at' => __('Ostatnio wzięty', 'medal-map'),
            'actions' => __('Akcje', 'medal-map')
        );
    }

    public function get_sortable_columns() {
        return array(
            'name' => array('name', true),
            'pk_no' => array('pk_no', false),
            'total_medals' => array('total_medals', false),
            'available_medals' => array('available_medals', false),
            'last_taken_at' => array('last_taken_at', false)
        );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'description':
                return sprintf(
                    '<span class="medal-description" data-medal-id="%d">%s</span>',
                    $item->id,
                    wp_trim_words($item->description, 10)
                );
            case 'pk_no':
                return sprintf(
                    '<span class="medal-pk-no" data-medal-id="%d">%s</span>',
                    $item->id,
                    esc_html($item->pk_no)
                );
            case 'coordinates':
                return sprintf(
                    '<span class="medal-coordinates" data-medal-id="%d">X: %d, Y: %d</span>',
                    $item->id,
                    $item->x_coordinate,
                    $item->y_coordinate
                );
            case 'total_medals':
                return sprintf(
                    '<span class="medal-total" data-medal-id="%d">%d</span>',
                    $item->id,
                    $item->total_medals
                );
            case 'available_medals':
                return sprintf(
                    '<span class="medal-available" data-medal-id="%d">%d</span>',
                    $item->id,
                    $item->available_medals
                );
            case 'last_taken_at':
                return sprintf(
                    '<span class="medal-last-taken" data-medal-id="%d">%s</span>',
                    $item->id,
                    $item->last_taken_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->last_taken_at)) : __('Nigdy', 'medal-map')
                );
            default:
                return sprintf(
                    '<span class="medal-%s" data-medal-id="%d">%s</span>',
                    $column_name,
                    $item->id,
                    esc_html($item->$column_name)
                );
        }
    }

    public function column_name($item) {
        return sprintf(
            '<span class="medal-name" data-medal-id="%d">%s</span>',
            $item->id,
            esc_html($item->name)
        );
    }



    public function column_actions($item) {
        return sprintf(
            '<div class="medal-actions" data-medal-id="%d">
                <a href="#" class="button button-small edit-medal" data-id="%d">%s</a>
                <a href="#" class="button button-small save-medal" data-id="%d" style="display:none;">%s</a>
                <a href="#" class="button button-small cancel-edit" data-id="%d" style="display:none;">%s</a>
                <a href="%s" class="button button-small delete-medal" data-id="%d" onclick="return confirm(\'%s\')">%s</a>
            </div>',
            $item->id,
            $item->id,
            __('Edytuj', 'medal-map'),
            $item->id,
            __('Zapisz', 'medal-map'),
            $item->id,
            __('Anuluj', 'medal-map'),
            wp_nonce_url(admin_url('admin.php?page=medal-map-medals&action=medal_delete&id=' . $item->id . '&map_id=' . $this->map_id), 'delete_medal_' . $item->id),
            $item->id,
            __('Czy na pewno chcesz usunąć ten medal?', 'medal-map'),
            __('Usuń', 'medal-map')
        );
    }

    public function prepare_items() {
        global $wpdb;

        $table_medals = $wpdb->prefix . 'medal_medals';
        
        // Set column headers
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );

        // Build query
        $sql = "SELECT * FROM $table_medals WHERE map_id = %d";
        $sql_args = array($this->map_id);

        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        if ($search) {
            $sql .= " AND (name LIKE %s OR description LIKE %s OR pk_no LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $sql_args[] = $search_term;
            $sql_args[] = $search_term;
            $sql_args[] = $search_term;
        }

        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'name';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'ASC';
        
        if (!in_array($orderby, array('name', 'pk_no', 'total_medals', 'available_medals', 'last_taken_at'))) {
            $orderby = 'name';
        }
        
        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = 'ASC';
        }
        
        $sql .= " ORDER BY $orderby $order";

        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Get total items for pagination
        $total_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_medals WHERE map_id = %d",
            $this->map_id
        ));

        $sql .= " LIMIT %d OFFSET %d";
        $sql_args[] = $per_page;
        $sql_args[] = $offset;

        // Get items
        $this->items = $wpdb->get_results($wpdb->prepare($sql, $sql_args));

        // Set pagination args
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }


}