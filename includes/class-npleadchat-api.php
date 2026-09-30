<?php
/**
 * REST API class for NP Lead Chatbot.
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NPLEADCHAT_API {
    const MIN_SUBMIT_SECONDS = 3;
    const DUPLICATE_WINDOW   = 10 * MINUTE_IN_SECONDS;
    const MAX_MESSAGE_LENGTH = 5000;

    public static function npleadchat_init() {
        add_action( 'rest_api_init', array( __CLASS__, 'npleadchat_register_routes' ) );
    }

    public static function npleadchat_register_routes() {
        register_rest_route( 'npleadchat/v1', '/lead', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'npleadchat_handle_lead' ),
            /*
             * Public lead form: any visitor may submit, so no capability is required.
             * For logged-in users WordPress core still verifies the X-WP-Nonce header
             * (rest_cookie_check_errors). For visitors a nonce adds no protection, and
             * requiring one breaks the form on cached pages once the nonce expires.
             * Abuse is limited by the honeypot, timing check, rate limits and field limits.
             */
            'permission_callback' => '__return_true',
            'args'                => array(
                'name'            => array( 'type' => 'string', 'maxLength' => 191 ),
                'email'           => array( 'type' => 'string', 'maxLength' => 191 ),
                'phone'           => array( 'type' => 'string', 'maxLength' => 50 ),
                'message'         => array( 'type' => 'string', 'maxLength' => self::MAX_MESSAGE_LENGTH ),
                'source_url'      => array( 'type' => 'string', 'maxLength' => 2048 ),
                'website'         => array( 'type' => 'string', 'maxLength' => 255 ),
                'form_started_at' => array( 'type' => 'integer', 'minimum' => 0 ),
            ),
        ) );
    }

    private static function npleadchat_success_message() {
        $options = NPLEADCHAT_Admin::npleadchat_get_options();

        return $options['success_message'];
    }

    private static function npleadchat_get_visitor_ip() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        return rest_is_ip_address( $ip ) ? $ip : 'unknown';
    }

    /**
     * Only keep source URLs that point at this site, so stored links cannot be
     * used to plant arbitrary external URLs in the admin leads table.
     *
     * @param string $url Submitted page URL.
     * @return string
     */
    private static function npleadchat_sanitize_source_url( $url ) {
        $url = esc_url_raw( $url, array( 'http', 'https' ) );

        if ( '' === $url ) {
            return '';
        }

        $url_host  = wp_parse_url( $url, PHP_URL_HOST );
        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );

        if ( ! $url_host || strtolower( $url_host ) !== strtolower( (string) $site_host ) ) {
            return '';
        }

        return $url;
    }

    private static function npleadchat_is_too_fast( $form_started_at ) {
        $form_started_at = absint( $form_started_at );

        if ( empty( $form_started_at ) ) {
            return true;
        }

        return ( time() - $form_started_at ) < self::MIN_SUBMIT_SECONDS;
    }

    /**
     * Rate limit keys: one per visitor IP and one per email address, so changing
     * the email (or the IP) alone is not enough to bypass the cooldown.
     *
     * @param string $email Lead email.
     * @return string[]
     */
    private static function npleadchat_rate_limit_keys( $email ) {
        return array(
            'npleadchat_rate_ip_' . md5( self::npleadchat_get_visitor_ip() ),
            'npleadchat_rate_em_' . md5( strtolower( $email ) ),
        );
    }

    private static function npleadchat_duplicate_key( array $data ) {
        return 'npleadchat_dup_' . md5(
            strtolower( $data['email'] ) . '|' .
            strtolower( $data['name'] ) . '|' .
            strtolower( $data['message'] ) . '|' .
            strtolower( $data['source_url'] )
        );
    }

    /**
     * IMPROVEMENT: Validate phone number format
     * Allows digits, spaces, hyphens, plus signs, parentheses, and periods (common phone formats)
     *
     * @param string $phone Phone number to validate
     * @return bool True if valid or empty, false otherwise
     */
    private static function npleadchat_validate_phone( $phone ) {
        if ( empty( $phone ) ) {
            return true; // Phone is optional
        }

        // Allow digits, spaces, hyphens, plus signs, parentheses, and periods
        if ( ! preg_match( '/^[\d\s\-\+\(\)\.x]+$/i', $phone ) ) {
            return false;
        }

        return true;
    }

    private static function npleadchat_send_notification( array $data, $lead_id ) {
        $options = NPLEADCHAT_Admin::npleadchat_get_options();

        if ( empty( $options['enable_notifications'] ) || empty( $options['notification_email'] ) || ! is_email( $options['notification_email'] ) ) {
            return;
        }

        $site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $subject   = sprintf(
            /* translators: %s: site name */
            __( 'New lead from %s', 'np-lead-chatbot' ),
            $site_name
        );

        // IMPROVED: Better sanitization of user input in email body
        $body = sprintf(
            "%s\n\n%s\n%s\n%s\n%s\n%s\n%s\n",
            __( 'A new lead was captured by Lead Capture Chat.', 'np-lead-chatbot' ),
            sprintf(
                /* translators: %s: lead name */
                __( 'Name: %s', 'np-lead-chatbot' ),
                sanitize_text_field( $data['name'] )
            ),
            sprintf(
                /* translators: %s: lead email */
                __( 'Email: %s', 'np-lead-chatbot' ),
                sanitize_email( $data['email'] )
            ),
            sprintf(
                /* translators: %s: lead phone */
                __( 'Phone: %s', 'np-lead-chatbot' ),
                sanitize_text_field( $data['phone'] )
            ),
            sprintf(
                /* translators: %s: lead message */
                __( 'Message: %s', 'np-lead-chatbot' ),
                sanitize_textarea_field( $data['message'] )
            ),
            sprintf(
                /* translators: %s: source URL */
                __( 'Source: %s', 'np-lead-chatbot' ),
                esc_url_raw( $data['source_url'] )
            ),
            sprintf(
                /* translators: %s: admin leads URL */
                __( 'View lead: %s', 'np-lead-chatbot' ),
                admin_url( 'admin.php?page=npleadchat-leads&lead_id=' . absint( $lead_id ) )
            )
        );

        wp_mail( sanitize_email( $options['notification_email'] ), $subject, $body );
    }

    public static function npleadchat_handle_lead( $request ) {
        // Types and lengths are already validated by the route schema.
        $name            = sanitize_text_field( (string) $request->get_param( 'name' ) );
        $email           = sanitize_email( (string) $request->get_param( 'email' ) );
        $phone           = sanitize_text_field( (string) $request->get_param( 'phone' ) );
        $message         = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
        $source_url      = self::npleadchat_sanitize_source_url( (string) $request->get_param( 'source_url' ) );
        $honeypot        = sanitize_text_field( (string) $request->get_param( 'website' ) );
        $form_started_at = absint( $request->get_param( 'form_started_at' ) );
        $options         = NPLEADCHAT_Admin::npleadchat_get_options();

        if ( ! empty( $honeypot ) ) {
            return rest_ensure_response( array( 'success' => true, 'message' => self::npleadchat_success_message() ) );
        }

        if ( self::npleadchat_is_too_fast( $form_started_at ) ) {
            return rest_ensure_response( array( 'success' => true, 'message' => self::npleadchat_success_message() ) );
        }

        if ( empty( $name ) || empty( $email ) ) {
            return rest_ensure_response( array( 'success' => false, 'message' => __( 'Name and email are required.', 'np-lead-chatbot' ) ) );
        }

        if ( ! is_email( $email ) ) {
            return rest_ensure_response( array( 'success' => false, 'message' => __( 'Please enter a valid email address.', 'np-lead-chatbot' ) ) );
        }

        // IMPROVEMENT: Validate phone format
        if ( ! self::npleadchat_validate_phone( $phone ) ) {
            return rest_ensure_response( array( 'success' => false, 'message' => __( 'Please enter a valid phone number.', 'np-lead-chatbot' ) ) );
        }

        $rate_limit_keys = self::npleadchat_rate_limit_keys( $email );
        foreach ( $rate_limit_keys as $rate_limit_key ) {
            if ( get_transient( $rate_limit_key ) ) {
                return rest_ensure_response( array( 'success' => false, 'message' => __( 'Please wait a moment before sending another message.', 'np-lead-chatbot' ) ) );
            }
        }

        $data = array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'source_url' => $source_url,
            'date' => current_time( 'mysql' ),
        );

        $duplicate_key = self::npleadchat_duplicate_key( $data );
        if ( get_transient( $duplicate_key ) ) {
            return rest_ensure_response( array( 'success' => true, 'message' => self::npleadchat_success_message() ) );
        }

        $id = NPLEADCHAT_DB::npleadchat_insert_lead( $data );

        if ( $id ) {
            foreach ( $rate_limit_keys as $rate_limit_key ) {
                set_transient( $rate_limit_key, 1, max( 10, absint( $options['rate_limit_seconds'] ) ) );
            }
            set_transient( $duplicate_key, 1, self::DUPLICATE_WINDOW );
            self::npleadchat_send_notification( $data, $id );

            return rest_ensure_response( array( 'success' => true, 'message' => self::npleadchat_success_message() ) );
        }

        return rest_ensure_response( array( 'success' => false, 'message' => __( 'Could not save lead', 'np-lead-chatbot' ) ) );
    }
}
