<?php
/**
 * Uninstall script for NP Lead Chatbot
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove the plugin's table and options for the current site.
 */
function npleadchat_uninstall_site() {
    global $wpdb;

    $npleadchat_table = $wpdb->prefix . 'npleadchat_leads';
    $wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $npleadchat_table ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

    delete_option( 'npleadchat_options' );
    delete_option( 'npleadchat_db_version' );
    delete_option( 'npleadchat_upsell_notice_dismissed' );
}

if ( is_multisite() ) {
    foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $npleadchat_site_id ) {
        switch_to_blog( $npleadchat_site_id );
        npleadchat_uninstall_site();
        restore_current_blog();
    }
} else {
    npleadchat_uninstall_site();
}
