<?php
/**
 * Plugin Name:       Lead Capture Chat
 * Plugin URI:        https://wordpress.org/plugins/np-lead-chatbot/
 * Description:       Convert visitors into leads with a chat-based form. Easy WordPress chatbot plugin for higher conversions.
 * Version:           2.4.4
 * Author:            NitsPatel
 * Author URI:        https://github.com/NiteshPatel-1988
 * Text Domain:       np-lead-chatbot
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Tested up to:      7.1
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package NP_Lead_Chatbot
 */

/* Exit if accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NPLEADCHAT_PLUGIN_FILE', __FILE__ );
define( 'NPLEADCHAT_VERSION',     '2.4.4' );
define( 'NPLEADCHAT_DIR',         plugin_dir_path( __FILE__ ) );
define( 'NPLEADCHAT_URL',         plugin_dir_url( __FILE__ ) );


// ── Core files ────────────────────────────────────────────────────────────────
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-activator.php';
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-deactivator.php';
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-admin.php';
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-frontend.php';
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-api.php';
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-db.php';
require_once NPLEADCHAT_DIR . 'includes/class-npleadchat-privacy.php';

// ── PRO upsell (free version only) ───────────────────────────────────────────
if ( ! defined( 'NPLEADCHAT_PRO_VERSION' ) ) {
	require_once NPLEADCHAT_DIR . 'admin/class-npleadchat-upsell.php';
}

// ── Activation / deactivation hooks ──────────────────────────────────────────
register_activation_hook( __FILE__,   array( 'NPLEADCHAT_Activator',   'npleadchat_activate' ) );
register_deactivation_hook( __FILE__, array( 'NPLEADCHAT_Deactivator', 'npleadchat_deactivate' ) );

// ── Bootstrap ─────────────────────────────────────────────────────────────────
add_action( 'init', function () {
	NPLEADCHAT_DB::npleadchat_maybe_upgrade();

	if ( is_admin() ) {
		NPLEADCHAT_Admin::npleadchat_init();

		if ( ! defined( 'NPLEADCHAT_PRO_VERSION' ) ) {
			NPLEADCHAT_Upsell::npleadchat_upsell_init();
		}
	}

	NPLEADCHAT_Frontend::npleadchat_init();
	NPLEADCHAT_API::npleadchat_init();
} );
