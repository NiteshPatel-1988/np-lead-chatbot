<?php
/**
 * Leads list table for NP Lead Chatbot.
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class NPLEADCHAT_Leads_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'lead',
            'plural'   => 'leads',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'      => '<input type="checkbox" />',
            'name'    => __( 'Name',    'np-lead-chatbot' ),
            'email'   => __( 'Email',   'np-lead-chatbot' ),
            'phone'   => __( 'Phone',   'np-lead-chatbot' ),
            'message' => __( 'Message', 'np-lead-chatbot' ),
            'source_url' => __( 'Source Page', 'np-lead-chatbot' ),
            'status'  => __( 'Status',  'np-lead-chatbot' ),
            'date'    => __( 'Date',    'np-lead-chatbot' ),
        );
    }

    public function get_sortable_columns() {
        return array(
            'name' => array( 'name', false ),
            'date' => array( 'date', true ),
        );
    }

    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="lead_ids[]" value="%d" />',
            absint( $item->id )
        );
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => __( 'Delete', 'np-lead-chatbot' ),
        );
    }

    public function process_bulk_action() {
        if ( 'delete' !== $this->current_action() ) {
            return;
        }

        // Verify nonce before processing.
        check_admin_referer( 'bulk-' . $this->_args['plural'] );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'np-lead-chatbot' ) );
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $raw_ids = isset( $_POST['lead_ids'] ) ? wp_unslash( $_POST['lead_ids'] ) : array();

        if ( empty( $raw_ids ) || ! is_array( $raw_ids ) ) {
            return;
        }

        $ids = array_map( 'absint', $raw_ids );
        $ids = array_filter( $ids ); // Remove any 0s.

        if ( empty( $ids ) ) {
            return;
        }

        NPLEADCHAT_DB::npleadchat_delete_leads( $ids );
    }

    public function prepare_items() {
        $this->process_bulk_action();

        $per_page = 10;
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order   = isset( $_GET['order'] )   ? sanitize_text_field( wp_unslash( $_GET['order'] ) )   : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search  = isset( $_REQUEST['s'] )   ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) )   : '';     // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $status = self::npleadchat_current_status();

        $current_page = $this->get_pagenum();
        $total_items  = NPLEADCHAT_DB::npleadchat_count_leads( $search, $status );

        $this->items = NPLEADCHAT_DB::npleadchat_get_leads( $orderby, $order, $search, $per_page, $current_page, $status );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ) );
    }

    /**
     * Currently requested status filter, validated against the whitelist.
     *
     * @return string Empty string for "All".
     */
    private static function npleadchat_current_status() {
        $status = isset( $_GET['lead_status'] ) ? sanitize_key( wp_unslash( $_GET['lead_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        return NPLEADCHAT_DB::npleadchat_is_valid_status( $status ) ? $status : '';
    }

    protected function get_views() {
        $current = self::npleadchat_current_status();
        $counts  = NPLEADCHAT_DB::npleadchat_count_by_status();
        $base    = admin_url( 'admin.php?page=npleadchat-leads' );
        $views   = array();

        $views['all'] = sprintf(
            '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
            esc_url( $base ),
            '' === $current ? ' class="current" aria-current="page"' : '',
            esc_html__( 'All', 'np-lead-chatbot' ),
            array_sum( $counts )
        );

        foreach ( NPLEADCHAT_DB::npleadchat_get_statuses() as $slug => $label ) {
            $views[ $slug ] = sprintf(
                '<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
                esc_url( add_query_arg( 'lead_status', $slug, $base ) ),
                $slug === $current ? ' class="current" aria-current="page"' : '',
                esc_html( $label ),
                (int) $counts[ $slug ]
            );
        }

        return $views;
    }

    public function column_status( $item ) {
        $current = NPLEADCHAT_DB::npleadchat_is_valid_status( $item->status ?? '' ) ? $item->status : 'new';
        $html    = sprintf(
            '<select class="npleadchat-status-select" data-lead-id="%d" data-previous="%s" aria-label="%s">',
            absint( $item->id ),
            esc_attr( $current ),
            esc_attr__( 'Lead status', 'np-lead-chatbot' )
        );

        foreach ( NPLEADCHAT_DB::npleadchat_get_statuses() as $slug => $label ) {
            $html .= sprintf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr( $slug ),
                selected( $current, $slug, false ),
                esc_html( $label )
            );
        }

        return $html . '</select>';
    }

    public function column_default( $item, $column_name ) {
        return esc_html( $item->$column_name ?? '' );
    }

    public function column_source_url( $item ) {
        if ( empty( $item->source_url ) ) {
            return '&mdash;';
        }

        return sprintf(
            '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
            esc_url( $item->source_url ),
            esc_html( wp_parse_url( $item->source_url, PHP_URL_PATH ) ?: $item->source_url )
        );
    }
}
