<?php
/**
 * Database helper for NP Lead Chatbot.
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NPLEADCHAT_DB {

    /**
     * Allowed lead statuses (slug => label).
     *
     * @return array<string,string>
     */
    public static function npleadchat_get_statuses() {
        return array(
            'new'       => __( 'New', 'np-lead-chatbot' ),
            'contacted' => __( 'Contacted', 'np-lead-chatbot' ),
            'closed'    => __( 'Closed', 'np-lead-chatbot' ),
        );
    }

    public static function npleadchat_is_valid_status( $status ) {
        return is_string( $status ) && array_key_exists( $status, self::npleadchat_get_statuses() );
    }

    /**
     * Update the status of a single lead.
     *
     * @param int    $id     Lead ID.
     * @param string $status One of the whitelisted statuses.
     * @return bool
     */
    public static function npleadchat_update_status( $id, $status ) {
        global $wpdb;

        $id = absint( $id );
        if ( ! $id || ! self::npleadchat_is_valid_status( $status ) ) {
            return false;
        }

        $updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table.
            $wpdb->prefix . 'npleadchat_leads',
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );

        return false !== $updated;
    }

    /**
     * Lead counts keyed by status.
     *
     * @return array<string,int>
     */
    public static function npleadchat_count_by_status() {
        global $wpdb;

        $table = $wpdb->prefix . 'npleadchat_leads';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $rows   = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM `{$table}` GROUP BY status" );
        $counts = array_fill_keys( array_keys( self::npleadchat_get_statuses() ), 0 );

        foreach ( (array) $rows as $row ) {
            if ( isset( $counts[ $row->status ] ) ) {
                $counts[ $row->status ] = (int) $row->total;
            }
        }

        return $counts;
    }

    public static function npleadchat_create_table() {
        global $wpdb;
        $table           = $wpdb->prefix . 'npleadchat_leads';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            email varchar(191) NOT NULL,
            phone varchar(50) NOT NULL,
            message text NOT NULL,
            source_url text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'new',
            date datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        update_option( 'npleadchat_db_version', NPLEADCHAT_VERSION );
    }

    public static function npleadchat_maybe_upgrade() {
        $db_version = get_option( 'npleadchat_db_version', '' );

        if ( NPLEADCHAT_VERSION !== $db_version ) {
            self::npleadchat_create_table();
        }
    }

    public static function npleadchat_insert_lead( $data = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'npleadchat_leads';
        $data  = wp_parse_args(
            $data,
            array(
                'name'       => '',
                'email'      => '',
                'phone'      => '',
                'message'    => '',
                'source_url' => '',
                'date'       => current_time( 'mysql' ),
            )
        );

        $inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table.
            $table,
            array(
                'name'       => $data['name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'message'    => $data['message'],
                'source_url' => $data['source_url'],
                'date'       => $data['date'],
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        return false === $inserted ? 0 : (int) $wpdb->insert_id;
    }

    /**
     * Build the WHERE clause for an optional search term.
     *
     * @param string $search Search term.
     * @return string Prepared SQL fragment (empty when no search).
     */
    private static function npleadchat_search_where( $search, $status = '' ) {
        global $wpdb;

        $clauses = array();

        if ( '' !== (string) $search ) {
            $like      = '%' . $wpdb->esc_like( $search ) . '%';
            $clauses[] = $wpdb->prepare(
                '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s OR source_url LIKE %s)',
                $like,
                $like,
                $like,
                $like,
                $like
            );
        }

        if ( self::npleadchat_is_valid_status( $status ) ) {
            $clauses[] = $wpdb->prepare( 'status = %s', $status );
        }

        return empty( $clauses ) ? '' : ' WHERE ' . implode( ' AND ', $clauses );
    }

    /**
     * Fetch leads.
     *
     * @param string $orderby  Column to sort by (name|date).
     * @param string $order    ASC|DESC.
     * @param string $search   Optional search term.
     * @param int    $per_page Rows per page, 0 for all rows (CSV export).
     * @param int    $page     1-based page number.
     * @return object[]
     */
    public static function npleadchat_get_leads( $orderby = 'date', $order = 'DESC', $search = '', $per_page = 0, $page = 1, $status = '' ) {
        global $wpdb;

        $allowed_orderby = array( 'name', 'date' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date';
        }
        $order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

        $table = $wpdb->prefix . 'npleadchat_leads';
        $where = self::npleadchat_search_where( $search, $status );
        $limit = '';

        if ( $per_page > 0 ) {
            $limit = $wpdb->prepare( ' LIMIT %d OFFSET %d', absint( $per_page ), absint( $per_page ) * ( max( 1, absint( $page ) ) - 1 ) );
        }

        // $table is built from $wpdb->prefix, $where/$limit are prepared above and orderby/order are whitelisted.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results( "SELECT * FROM `{$table}`{$where} ORDER BY {$orderby} {$order}, id {$order}{$limit}" );
    }

    /**
     * Count leads, optionally filtered by a search term.
     *
     * @param string $search Optional search term.
     * @return int
     */
    public static function npleadchat_count_leads( $search = '', $status = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'npleadchat_leads';
        $where = self::npleadchat_search_where( $search, $status );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`{$where}" );
    }

    public static function npleadchat_delete_leads( array $ids ) {
        global $wpdb;

        if ( empty( $ids ) ) {
            return;
        }

        // All IDs are already cast to int by the caller — build safe placeholders.
        $ids          = array_map( 'absint', $ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $table        = $wpdb->prefix . 'npleadchat_leads';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                ...$ids
            )
        );
    }
}
