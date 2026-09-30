<?php
/**
 * Frontend class for NP Lead Chatbot.
 *
 * @package NP_Lead_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class NPLEADCHAT_Frontend {
    public static function npleadchat_init() {
        add_shortcode( 'npleadchat', array( __CLASS__, 'npleadchat_bot_ui' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'npleadchat_enqueue' ) );
        add_action( 'wp_footer', array( __CLASS__, 'npleadfloating_widget' ) );
    }

    /**
     * Register assets on every page, but only enqueue them when the floating
     * widget is enabled. The shortcode enqueues them itself when it is used.
     */
    public static function npleadchat_enqueue() {
        $options = NPLEADCHAT_Admin::npleadchat_get_options();

        wp_register_style(
            'npleadchat-frontend',
            NPLEADCHAT_URL . 'assets/css/chatbot.css',
            array(),
            NPLEADCHAT_VERSION
        );
        wp_register_script(
            'npleadchat-frontend-js',
            NPLEADCHAT_URL . 'assets/js/chatbot.js',
            array( 'jquery' ),
            NPLEADCHAT_VERSION,
            true
        );
        wp_localize_script( 'npleadchat-frontend-js', 'npleadchat_api', array(
            'url'            => esc_url_raw( rest_url( 'npleadchat/v1/lead' ) ),
            // Only logged-in users need the REST nonce. A visitor's nonce gets cached
            // with the page, and once it expires WordPress rejects every submission.
            'nonce'          => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
            'successMessage' => $options['success_message'],
            'i18n'           => array(
                'nameRequired'    => __( 'Please enter your name.', 'np-lead-chatbot' ),
                'emailRequired'   => __( 'Please enter your email address.', 'np-lead-chatbot' ),
                'emailInvalid'    => __( 'Please enter a valid email address.', 'np-lead-chatbot' ),
                'phoneRequired'   => __( 'Please enter your phone number.', 'np-lead-chatbot' ),
                'messageRequired' => __( 'Please enter a message.', 'np-lead-chatbot' ),
                'fallbackSuccess' => __( 'Thanks! We\'ll be in touch soon.', 'np-lead-chatbot' ),
                'fallbackError'   => __( 'Something went wrong. Please try again.', 'np-lead-chatbot' ),
            ),
        ) );

        if ( ! empty( $options['enable_floating_widget'] ) ) {
            self::npleadchat_enqueue_assets();
        }
    }

    /**
     * Enqueue the registered frontend assets.
     */
    private static function npleadchat_enqueue_assets() {
        wp_enqueue_style( 'npleadchat-frontend' );
        wp_enqueue_script( 'npleadchat-frontend-js' );
    }

    /**
     * Shortcode output — inline embed with its own header.
     */
    public static function npleadchat_bot_ui() {
        $options = NPLEADCHAT_Admin::npleadchat_get_options();

        self::npleadchat_enqueue_assets();

        ob_start(); ?>

        <div class="nlc-chatbot nlc-inline-mode">

            <div class="nlc-inline-header">
                <h3><?php echo esc_html( $options['widget_title'] ); ?></h3>
                <p><?php echo esc_html( $options['widget_subtitle'] ); ?></p>
            </div>

            <div class="nlc-form-body">
                <?php echo self::npleadchat_form_fields(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every dynamic value is escaped inside npleadchat_form_fields(). ?>
            </div>

        </div>

        <?php return ob_get_clean();
    }

    /**
     * Floating widget injected into footer.
     */
    public static function npleadfloating_widget() {
        $options = NPLEADCHAT_Admin::npleadchat_get_options();

        if ( empty( $options['enable_floating_widget'] ) ) {
            return;
        }

        /* Floating trigger button */
        echo '<div id="wlc-floating-btn" role="button" aria-label="' . esc_attr__( 'Open chat', 'np-lead-chatbot' ) . '" aria-controls="wlc-chat-popup" aria-expanded="false" tabindex="0">';

        /* Chat icon */
        echo '<svg class="nlc-icon nlc-icon-chat" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">';
        echo '<path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
        echo '</svg>';

        /* Close icon */
        echo '<svg class="nlc-icon nlc-icon-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">';
        echo '<path d="M6 18L18 6M6 6l12 12" stroke="white" stroke-width="2.5" stroke-linecap="round"/>';
        echo '</svg>';

        echo '</div>';

        /* Popup */
        echo '<div id="wlc-chat-popup" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Contact form', 'np-lead-chatbot' ) . '">';

            /* Gradient header */
            echo '<div class="nlc-popup-header">';
                echo '<div class="nlc-header-top">';

                    /* Avatar */
                    echo '<div class="nlc-header-avatar">';
                    echo '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
                    echo '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';
                    echo '</svg>';
                    echo '</div>';

                    /* Title + status */
                    echo '<div class="nlc-header-info">';
                    echo '<h3 class="nlc-header-title">' . esc_html( $options['widget_title'] ) . '</h3>';
                    echo '<div class="nlc-header-status">';
                    echo '<span class="nlc-status-dot"></span>';
                    echo '<span class="nlc-status-text">' . esc_html( $options['online_status'] ) . '</span>';
                    echo '</div>';
                    echo '</div>';

                    /* Close button */
                    echo '<button type="button" id="wlc-chat-close" aria-label="' . esc_attr__( 'Close chat', 'np-lead-chatbot' ) . '">&times;</button>';

                echo '</div>';
            echo '</div>';

            /* Form */
            echo '<div class="nlc-chatbot">';
            echo self::npleadchat_form_fields(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every dynamic value is escaped inside npleadchat_form_fields().
            echo '</div>';

            /* Footer */
            echo '<div class="nlc-popup-footer">' . esc_html__( 'Powered by NP Lead Chatbot', 'np-lead-chatbot' ) . '</div>';

        echo '</div>';
    }

    /**
     * Shared form fields HTML (used in both shortcode and floating widget).
     */
    private static function npleadchat_form_fields() {
        static $instance = 0;

        $instance++;
        $prefix = 'wlc-' . $instance . '-';

        ob_start(); ?>

        <div class="nlc-field">
            <label class="nlc-label" for="<?php echo esc_attr( $prefix . 'name' ); ?>"><?php esc_html_e( 'Your Name', 'np-lead-chatbot' ); ?></label>
            <input type="text" id="<?php echo esc_attr( $prefix . 'name' ); ?>" data-nlc-field="name" placeholder="<?php esc_attr_e( 'Jane Smith', 'np-lead-chatbot' ); ?>" autocomplete="name">
            <span class="wlc-error" data-nlc-error="name" role="alert"></span>
        </div>

        <div class="nlc-field">
            <label class="nlc-label" for="<?php echo esc_attr( $prefix . 'email' ); ?>"><?php esc_html_e( 'Email Address', 'np-lead-chatbot' ); ?></label>
            <input type="email" id="<?php echo esc_attr( $prefix . 'email' ); ?>" data-nlc-field="email" placeholder="<?php esc_attr_e( 'jane@example.com', 'np-lead-chatbot' ); ?>" autocomplete="email">
            <span class="wlc-error" data-nlc-error="email" role="alert"></span>
        </div>

        <div class="nlc-field">
            <label class="nlc-label" for="<?php echo esc_attr( $prefix . 'phone' ); ?>"><?php esc_html_e( 'Phone Number', 'np-lead-chatbot' ); ?></label>
            <input type="text" id="<?php echo esc_attr( $prefix . 'phone' ); ?>" data-nlc-field="phone" placeholder="<?php esc_attr_e( '+1 (555) 000-0000', 'np-lead-chatbot' ); ?>" autocomplete="tel">
            <span class="wlc-error" data-nlc-error="phone" role="alert"></span>
        </div>

        <div class="nlc-field">
            <label class="nlc-label" for="<?php echo esc_attr( $prefix . 'message' ); ?>"><?php esc_html_e( 'Message', 'np-lead-chatbot' ); ?></label>
            <textarea id="<?php echo esc_attr( $prefix . 'message' ); ?>" data-nlc-field="message" placeholder="<?php esc_attr_e( 'How can we help you?', 'np-lead-chatbot' ); ?>"></textarea>
            <span class="wlc-error" data-nlc-error="message" role="alert"></span>
        </div>

        <div class="nlc-hp-field" aria-hidden="true">
            <label for="<?php echo esc_attr( $prefix . 'website' ); ?>"><?php esc_html_e( 'Website', 'np-lead-chatbot' ); ?></label>
            <input type="text" id="<?php echo esc_attr( $prefix . 'website' ); ?>" data-nlc-field="website" tabindex="-1" autocomplete="off">
        </div>

        <input type="hidden" data-nlc-field="form_started_at" value="<?php echo esc_attr( time() ); ?>">

        <button type="button" class="wlc-submit">
            <span class="nlc-btn-text">
                <svg width="15" height="15" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
                    <path d="M3 10l14-7-7 14-2-5-5-2z" fill="white"/>
                </svg>
                <?php esc_html_e( 'Send Message', 'np-lead-chatbot' ); ?>
            </span>
            <span class="nlc-spinner" aria-hidden="true"></span>
        </button>

        <div class="wlc-response" role="status" aria-live="polite"></div>

        <?php return ob_get_clean();
    }
}
