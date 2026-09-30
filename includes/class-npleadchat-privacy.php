<?php
/**
 * GDPR Privacy Integration for NP Lead Chatbot
 *
 * Registers personal data exporter/eraser and suggested privacy policy text.
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NPLEADCHAT_Privacy {

    /**
     * Initialize privacy hooks
     */
    public static function npleadchat_init_privacy() {
        add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'npleadchat_register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'npleadchat_register_eraser' ) );
        add_action( 'admin_init', array( __CLASS__, 'npleadchat_privacy_policy_content' ) );
    }

    /**
     * Suggest privacy policy text under Settings > Privacy > Policy Guide.
     */
    public static function npleadchat_privacy_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }

        $content = '<p>' . esc_html__( 'When you submit the chat contact form, we store the name, email address, phone number and message you enter, the page you sent it from and the date. This data is kept in this website\'s database so we can reply to you, and may be sent to the site administrator by email. It is not shared with third-party services by this plugin.', 'np-lead-chatbot' ) . '</p>';

        wp_add_privacy_policy_content( __( 'Lead Capture Chat', 'np-lead-chatbot' ), wp_kses_post( $content ) );
    }

    /**
     * Register data exporter for privacy requests
     *
     * @param array $exporters Array of registered exporters.
     * @return array
     */
    public static function npleadchat_register_exporter( $exporters ) {
        $exporters['npleadchat'] = array(
            'exporter_friendly_name' => __( 'NP Lead Chatbot', 'np-lead-chatbot' ),
            'callback'               => array( __CLASS__, 'npleadchat_exporter' ),
        );
        return $exporters;
    }

    /**
     * Register data eraser for privacy requests
     *
     * @param array $erasers Array of registered erasers.
     * @return array
     */
    public static function npleadchat_register_eraser( $erasers ) {
        $erasers['npleadchat'] = array(
            'eraser_friendly_name' => __( 'NP Lead Chatbot', 'np-lead-chatbot' ),
            'callback'             => array( __CLASS__, 'npleadchat_eraser' ),
        );
        return $erasers;
    }

    /**
     * Export personal data for a user
     *
     * @param string $email_address The user's email address.
     * @param int    $page         Current page number.
     * @return array
     */
    public static function npleadchat_exporter( $email_address = '', $page = 1 ) {
        $export_items = array();

        if ( empty( $email_address ) ) {
            return array(
                'data' => $export_items,
                'done' => true,
            );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'npleadchat_leads';

        // Get leads associated with this email. $table is built from $wpdb->prefix.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $leads = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$table}` WHERE email = %s ORDER BY date DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $email_address
            )
        );

        if ( ! empty( $leads ) ) {
            $group_id = 0;

            foreach ( $leads as $lead ) {
                $group_id++;

                $export_items[] = array(
                    'group_id'    => 'npleadchat-leads-' . $group_id,
                    'group_label' => sprintf(
                        /* translators: %s: Lead capture date */
                        __( 'Lead Captured: %s', 'np-lead-chatbot' ),
                        $lead->date
                    ),
                    'item_id'     => 'lead-' . $lead->id,
                    'data'        => array(
                        array(
                            'name'  => __( 'Name', 'np-lead-chatbot' ),
                            'value' => $lead->name,
                        ),
                        array(
                            'name'  => __( 'Email', 'np-lead-chatbot' ),
                            'value' => $lead->email,
                        ),
                        array(
                            'name'  => __( 'Phone', 'np-lead-chatbot' ),
                            'value' => $lead->phone,
                        ),
                        array(
                            'name'  => __( 'Message', 'np-lead-chatbot' ),
                            'value' => $lead->message,
                        ),
                        array(
                            'name'  => __( 'Source Page', 'np-lead-chatbot' ),
                            'value' => $lead->source_url,
                        ),
                        array(
                            'name'  => __( 'Submission Date', 'np-lead-chatbot' ),
                            'value' => $lead->date,
                        ),
                    ),
                );
            }
        }

        return array(
            'data' => $export_items,
            'done' => true,
        );
    }

    /**
     * Erase personal data for a user
     *
     * @param string $email_address The user's email address.
     * @param int    $page         Current page number.
     * @return array
     */
    public static function npleadchat_eraser( $email_address = '', $page = 1 ) {
        if ( empty( $email_address ) ) {
            return array(
                'items_removed'  => false,
                'items_retained' => false,
                'messages'       => array(),
                'done'           => true,
            );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'npleadchat_leads';

        // Delete every lead for this email. $table is built from $wpdb->prefix.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$table}` WHERE email = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $email_address
            )
        );

        return array(
            'items_removed'  => $deleted > 0,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => true,
        );
    }
}

// Initialize privacy hooks when WordPress loads
add_action( 'init', array( 'NPLEADCHAT_Privacy', 'npleadchat_init_privacy' ) );
