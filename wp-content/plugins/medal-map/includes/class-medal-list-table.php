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
                return wp_trim_words($item->description, 10);
            case 'coordinates':
                return sprintf('X: %d, Y: %d', $item->x_coordinate, $item->y_coordinate);
            case 'last_taken_at':
                return $item->last_taken_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->last_taken_at)) : __('Nigdy', 'medal-map');
            default:
                return $item->$column_name;
        }
    }

    public function column_name($item) {
        $actions = array(
            'edit' => sprintf(
                '<a href="#" class="edit-medal" data-id="%d">%s</a>',
                $item->id,
                __('Edytuj', 'medal-map')
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=medal-map-medals&action=medal_delete&id=' . $item->id . '&map_id=' . $this->map_id), 'delete_medal_' . $item->id),
                __('Czy na pewno chcesz usunąć ten medal?', 'medal-map'),
                __('Usuń', 'medal-map')
            )
        );

        return sprintf(
            '<strong>%1$s</strong> %2$s',
            esc_html($item->name),
            $this->row_actions($actions)
        );
    }



    public function column_actions($item) {
        return sprintf(
            '<a href="#" class="button button-small edit-medal" data-id="%d">%s</a>',
            $item->id,
            __('Edytuj', 'medal-map')
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