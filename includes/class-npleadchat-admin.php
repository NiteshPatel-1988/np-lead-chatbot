<?php
/**
 * Admin class for NP Lead Chatbot.
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NPLEADCHAT_Admin {

    public static function npleadchat_init() {
        add_action( 'admin_menu',               array( __CLASS__, 'npleadchat_admin_menu' ) );
        add_action( 'admin_enqueue_scripts',    array( __CLASS__, 'npleadchat_enqueue' ) );
        add_action( 'admin_init',               array( __CLASS__, 'npleadchat_register_settings' ) );
        add_action( 'admin_post_npleadchat_export_leads', array( __CLASS__, 'npleadchat_export_csv' ) );
    }

    public static function npleadchat_enqueue( $hook ) {
        // Only load on our own admin page.
        if ( ! in_array( $hook, array( 'toplevel_page_npleadchat-leads', 'npleadchat-leads_page_npleadchat-settings' ), true ) ) {
            return;
        }
        wp_enqueue_style(
            'npleadchat-admin',
            NPLEADCHAT_URL . 'assets/css/chatbot.css',
            array(),
            NPLEADCHAT_VERSION
        );
    }

    public static function npleadchat_admin_menu() {
        add_menu_page(
            esc_html__( 'NP Lead Chatbot', 'np-lead-chatbot' ),
            esc_html__( 'NP Lead Chatbot', 'np-lead-chatbot' ),
            'manage_options',
            'npleadchat-leads',
            array( __CLASS__, 'npleadchat_render_page' ),
            'dashicons-format-chat'
        );

        add_submenu_page(
            'npleadchat-leads',
            esc_html__( 'Settings', 'np-lead-chatbot' ),
            esc_html__( 'Settings', 'np-lead-chatbot' ),
            'manage_options',
            'npleadchat-settings',
            array( __CLASS__, 'npleadchat_render_settings_page' )
        );
    }

    public static function npleadchat_default_options() {
        return array(
            'enable_floating_widget' => 1,
            'enable_notifications'    => 1,
            'notification_email'       => get_option( 'admin_email' ),
            'rate_limit_seconds'       => 60,
            'widget_title'           => __( 'Chat With Us', 'np-lead-chatbot' ),
            'widget_subtitle'        => __( 'Fill in the form and we\'ll get back to you shortly.', 'np-lead-chatbot' ),
            'online_status'          => __( 'We\'re online', 'np-lead-chatbot' ),
            'success_message'        => __( 'Thanks! We\'ll be in touch soon.', 'np-lead-chatbot' ),
        );
    }

    public static function npleadchat_get_options() {
        $options = get_option( 'npleadchat_options', array() );

        if ( ! is_array( $options ) ) {
            $options = array();
        }

        return wp_parse_args( $options, self::npleadchat_default_options() );
    }

    public static function npleadchat_register_settings() {
        register_setting(
            'npleadchat_settings',
            'npleadchat_options',
            array(
                'sanitize_callback' => array( __CLASS__, 'npleadchat_sanitize_options' ),
                'default'           => self::npleadchat_default_options(),
            )
        );
    }

    public static function npleadchat_sanitize_options( $input ) {
        $defaults = self::npleadchat_default_options();
        $input    = is_array( $input ) ? $input : array();

        return array(
            'enable_floating_widget' => empty( $input['enable_floating_widget'] ) ? 0 : 1,
            'enable_notifications'    => empty( $input['enable_notifications'] ) ? 0 : 1,
            'notification_email'       => isset( $input['notification_email'] ) && is_email( $input['notification_email'] ) ? sanitize_email( $input['notification_email'] ) : $defaults['notification_email'],
            'rate_limit_seconds'       => isset( $input['rate_limit_seconds'] ) ? max( 10, min( 3600, absint( $input['rate_limit_seconds'] ) ) ) : $defaults['rate_limit_seconds'],
            'widget_title'           => isset( $input['widget_title'] ) ? sanitize_text_field( $input['widget_title'] ) : $defaults['widget_title'],
            'widget_subtitle'        => isset( $input['widget_subtitle'] ) ? sanitize_text_field( $input['widget_subtitle'] ) : $defaults['widget_subtitle'],
            'online_status'          => isset( $input['online_status'] ) ? sanitize_text_field( $input['online_status'] ) : $defaults['online_status'],
            'success_message'        => isset( $input['success_message'] ) ? sanitize_text_field( $input['success_message'] ) : $defaults['success_message'],
        );
    }

    public static function npleadchat_render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'np-lead-chatbot' ) );
        }

        require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-leads-table.php';

        $table = new NPLEADCHAT_Leads_Table();
        $table->prepare_items();
        ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e( 'Leads', 'np-lead-chatbot' ); ?>
            </h1>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                <input type="hidden" name="action" value="npleadchat_export_leads" />
                <?php wp_nonce_field( 'npleadchat_export_nonce', 'npleadchat_export_nonce_field' ); ?>
                <input type="submit" class="page-title-action" value="<?php esc_attr_e( 'Export Leads (CSV)', 'np-lead-chatbot' ); ?>" />
            </form>

            <hr class="wp-header-end">

            <form method="post">
                <input type="hidden" name="page" value="npleadchat-leads" />
                <?php
                // WP_List_Table::display() prints the bulk-leads nonce field itself.
                $table->search_box( esc_html__( 'Search Leads', 'np-lead-chatbot' ), 'npleadchat-search' );
                $table->display();
                ?>
            </form>
        </div>

        <?php
    }

    public static function npleadchat_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'np-lead-chatbot' ) );
        }

        $options = self::npleadchat_get_options();
        ?>

        <div class="wrap">
            <h1><?php esc_html_e( 'NP Lead Chatbot Settings', 'np-lead-chatbot' ); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'npleadchat_settings' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Floating Widget', 'np-lead-chatbot' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="npleadchat_options[enable_floating_widget]" value="1" <?php checked( 1, $options['enable_floating_widget'] ); ?> />
                                <?php esc_html_e( 'Show the floating chat widget on the site', 'np-lead-chatbot' ); ?>
                            </label>
                            <p class="description"><?php esc_html_e( 'The shortcode form still works when this is turned off.', 'np-lead-chatbot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="npleadchat-widget-title"><?php esc_html_e( 'Widget Title', 'np-lead-chatbot' ); ?></label></th>
                        <td>
                            <input type="text" id="npleadchat-widget-title" name="npleadchat_options[widget_title]" class="regular-text" value="<?php echo esc_attr( $options['widget_title'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="npleadchat-widget-subtitle"><?php esc_html_e( 'Inline Subtitle', 'np-lead-chatbot' ); ?></label></th>
                        <td>
                            <input type="text" id="npleadchat-widget-subtitle" name="npleadchat_options[widget_subtitle]" class="regular-text" value="<?php echo esc_attr( $options['widget_subtitle'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="npleadchat-online-status"><?php esc_html_e( 'Online Status Text', 'np-lead-chatbot' ); ?></label></th>
                        <td>
                            <input type="text" id="npleadchat-online-status" name="npleadchat_options[online_status]" class="regular-text" value="<?php echo esc_attr( $options['online_status'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="npleadchat-success-message"><?php esc_html_e( 'Success Message', 'np-lead-chatbot' ); ?></label></th>
                        <td>
                            <input type="text" id="npleadchat-success-message" name="npleadchat_options[success_message]" class="regular-text" value="<?php echo esc_attr( $options['success_message'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email Notifications', 'np-lead-chatbot' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="npleadchat_options[enable_notifications]" value="1" <?php checked( 1, $options['enable_notifications'] ); ?> />
                                <?php esc_html_e( 'Send an email when a new lead is captured', 'np-lead-chatbot' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="npleadchat-notification-email"><?php esc_html_e( 'Notification Email', 'np-lead-chatbot' ); ?></label></th>
                        <td>
                            <input type="email" id="npleadchat-notification-email" name="npleadchat_options[notification_email]" class="regular-text" value="<?php echo esc_attr( $options['notification_email'] ); ?>" />
                            <p class="description"><?php esc_html_e( 'Advanced templates and visitor auto-replies are available in PRO.', 'np-lead-chatbot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="npleadchat-rate-limit-seconds"><?php esc_html_e( 'Spam Rate Limit', 'np-lead-chatbot' ); ?></label></th>
                        <td>
                            <input type="number" id="npleadchat-rate-limit-seconds" name="npleadchat_options[rate_limit_seconds]" class="small-text" min="10" max="3600" step="1" value="<?php echo esc_attr( $options['rate_limit_seconds'] ); ?>" />
                            <?php esc_html_e( 'seconds between submissions from the same visitor/email.', 'np-lead-chatbot' ); ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <?php
    }

    private static function npleadchat_prepare_csv_cell( $value ) {
        $value = (string) $value;

        if ( '' !== $value && preg_match( '/^[=\-+@\t\r]/', $value ) ) {
            return "'" . $value;
        }

        return $value;
    }

    private static function npleadchat_prepare_csv_row( array $row ) {
        return array_map( array( __CLASS__, 'npleadchat_prepare_csv_cell' ), $row );
    }

    public static function npleadchat_export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'np-lead-chatbot' ) );
        }

        if (
            ! isset( $_POST['npleadchat_export_nonce_field'] ) ||
            ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['npleadchat_export_nonce_field'] ) ),
                'npleadchat_export_nonce'
            )
        ) {
            wp_die( esc_html__( 'Nonce verification failed.', 'np-lead-chatbot' ) );
        }

        $leads = NPLEADCHAT_DB::npleadchat_get_leads();

        if ( empty( $leads ) ) {
            wp_die( esc_html__( 'No leads found.', 'np-lead-chatbot' ) );
        }

        // Convert stdClass objects to arrays.
        $leads = array_map( function( $lead ) {
            return (array) $lead;
        }, $leads );

        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=npleadchat-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'X-Frame-Options: DENY' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array_keys( $leads[0] ) );

        foreach ( $leads as $lead ) {
            fputcsv( $output, self::npleadchat_prepare_csv_row( $lead ) );
        }

        fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        exit;
    }
}
