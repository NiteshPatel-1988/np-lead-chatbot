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
            date datetime NOT NULL,
            PRIMARY KEY  (id)
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
    private static function npleadchat_search_where( $search ) {
        global $wpdb;

        if ( '' === (string) $search ) {
            return '';
        }

        $like = '%' . $wpdb->esc_like( $search ) . '%';

        return $wpdb->prepare(
            ' WHERE name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s OR source_url LIKE %s',
            $like,
            $like,
            $like,
            $like,
            $like
        );
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
    public static function npleadchat_get_leads( $orderby = 'date', $order = 'DESC', $search = '', $per_page = 0, $page = 1 ) {
        global $wpdb;

        $allowed_orderby = array( 'name', 'date' );
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date';
        }
        $order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

        $table = $wpdb->prefix . 'npleadchat_leads';
        $where = self::npleadchat_search_where( $search );
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
    public static function npleadchat_count_leads( $search = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'npleadchat_leads';
        $where = self::npleadchat_search_where( $search );

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
