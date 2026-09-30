<?php
/**
 * Pro upsell screen for Lead Capture Chat.
 *
 * Registers an "Upgrade to PRO" submenu page and a one-time dismissible
 * admin notice. All output is escaped per WordPress.org coding standards.
 *
 * @package NP_Lead_Chatbot
 * @since   2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NPLEADCHAT_Upsell
 */
class NPLEADCHAT_Upsell {

	/**
	 * Option key used to track notice dismissal.
	 *
	 * @var string
	 */
	const NOTICE_DISMISSED_OPTION = 'npleadchat_upsell_notice_dismissed';

	/**
	 * Freemius checkout URL (PRO plan).
	 *
	 * @var string
	 */
	const UPGRADE_URL = 'https://checkout.freemius.com/plugin/29207/plan/48049/';

	/**
	 * Live-preview / demo URL.
	 *
	 * @var string
	 */
	const PREVIEW_URL = 'https://leadcapturechat.netlify.app/';

	/**
	 * Boot all hooks.
	 *
	 * @return void
	 */
	public static function npleadchat_upsell_init() {
		add_action( 'admin_menu',    array( __CLASS__, 'npleadchat_add_submenu' ) );
		add_action( 'admin_notices', array( __CLASS__, 'npleadchat_admin_notice' ) );
		add_action( 'admin_init',    array( __CLASS__, 'npleadchat_handle_notice_dismiss' ) );
	}

	// -------------------------------------------------------------------------
	// Submenu
	// -------------------------------------------------------------------------

	/**
	 * Register the "Upgrade to PRO" submenu under the plugin's top-level menu.
	 *
	 * @return void
	 */
	public static function npleadchat_add_submenu() {
		add_submenu_page(
			'npleadchat-leads',
			esc_html__( 'Upgrade to PRO - Lead Capture Chat', 'np-lead-chatbot' ),
			/* translators: menu label — the rocket emoji is decorative */
			esc_html__( 'Upgrade to PRO 🚀', 'np-lead-chatbot' ),
			'manage_options',
			'npleadchat-upgrade',
			array( __CLASS__, 'npleadchat_render_page' )
		);
	}

	/**
	 * Render the upsell page.
	 *
	 * @return void
	 */
	public static function npleadchat_render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'np-lead-chatbot' ) );
		}

		?>
		<div class="wrap npleadchat-upsell-wrap">

			<h1>
				<?php esc_html_e( 'Upgrade to Lead Capture Chat PRO 🚀', 'np-lead-chatbot' ); ?>
			</h1>

			<p class="npleadchat-upsell-tagline">
				<?php esc_html_e( 'Unlock powerful lead generation features and grow your business faster.', 'np-lead-chatbot' ); ?>
			</p>

			<!-- ── Benefits card ─────────────────────────────────────────── -->
			<div class="npleadchat-upsell-card">

				<h2><?php esc_html_e( 'Why Upgrade?', 'np-lead-chatbot' ); ?></h2>

				<ul class="npleadchat-upsell-benefits">
					<li>&#x2705; <?php esc_html_e( 'Unlimited Leads', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Custom Field Builder', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Advanced Email Notifications', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Auto Replies', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Google reCAPTCHA v3 Spam Protection', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Full Appearance &amp; Branding Control', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Live Admin Preview', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Analytics Dashboard', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'CRM Dashboard', 'np-lead-chatbot' ); ?></li>
					<li>&#x2705; <?php esc_html_e( 'Priority Support', 'np-lead-chatbot' ); ?></li>
				</ul>

				<p class="npleadchat-upsell-context">
					<?php esc_html_e( 'Free already includes the floating widget, shortcode form, basic settings, basic email notifications, lead source tracking, CSV export, and honeypot spam protection. PRO is best when you need automation, deeper customization, CRM workflow, analytics, and stronger anti-spam controls.', 'np-lead-chatbot' ); ?>
				</p>

				<hr>

				<!-- ── Comparison table ──────────────────────────────────── -->
				<h2><?php esc_html_e( 'Free vs PRO', 'np-lead-chatbot' ); ?></h2>

				<table class="widefat striped npleadchat-compare-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Feature', 'np-lead-chatbot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Free', 'np-lead-chatbot' ); ?></th>
							<th scope="col"><?php esc_html_e( 'PRO', 'np-lead-chatbot' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$rows = array(
							array(
								esc_html__( 'Floating chat widget',      'np-lead-chatbot' ),
								'&#x2705;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Inline shortcode',      'np-lead-chatbot' ),
								'&#x2705;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Search & sort',      'np-lead-chatbot' ),
								'&#x2705;',
								'&#x2705;',
							),
							array(
								esc_html__( 'CSV Export',      'np-lead-chatbot' ),
								'&#x2705;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Basic settings',      'np-lead-chatbot' ),
								'&#x2705;',
								esc_html__( 'Advanced', 'np-lead-chatbot' ),
							),
							array(
								esc_html__( 'Lead source page tracking',      'np-lead-chatbot' ),
								'&#x2705;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Honeypot spam protection',       'np-lead-chatbot' ),
								'&#x2705;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Unlimited Leads',      'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Custom Fields',         'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Email Notifications',   'np-lead-chatbot' ),
								esc_html__( 'Basic', 'np-lead-chatbot' ),
								esc_html__( 'Advanced', 'np-lead-chatbot' ),
							),
							array(
								esc_html__( 'Auto Replies',          'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Google reCAPTCHA v3',       'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Appearance Control',    'np-lead-chatbot' ),
								esc_html__( 'Basic', 'np-lead-chatbot' ),
								esc_html__( 'Full',  'np-lead-chatbot' ),
							),
							array(
								esc_html__( 'Live admin preview',       'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Lead status pipeline',       'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Notes and activity log',       'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Analytics Dashboard',       'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'CRM Dashboard',      'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
							array(
								esc_html__( 'Priority Support',      'np-lead-chatbot' ),
								'&#x274C;',
								'&#x2705;',
							),
						);

						foreach ( $rows as $row ) {
							printf(
								'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
								wp_kses_post( $row[0] ),
								wp_kses_post( $row[1] ),
								wp_kses_post( $row[2] )
							);
						}
						?>
					</tbody>
				</table>

				<!-- ── CTAs ──────────────────────────────────────────────── -->
				<p class="npleadchat-upsell-actions">
					<a href="<?php echo esc_url( self::UPGRADE_URL ); ?>"
					   class="button button-primary button-hero"
					   target="_blank"
					   rel="noopener noreferrer">
						<?php esc_html_e( '🚀 Upgrade to PRO', 'np-lead-chatbot' ); ?>
					</a>

					<a href="<?php echo esc_url( self::PREVIEW_URL ); ?>"
					   class="button button-secondary button-hero"
					   target="_blank"
					   rel="noopener noreferrer">
						<?php esc_html_e( '🎨 Live Preview', 'np-lead-chatbot' ); ?>
					</a>
				</p>

				<p class="npleadchat-upsell-note">
					<?php esc_html_e( 'Plans start at just $3/month · 14-day free trial · 30-day money-back guarantee', 'np-lead-chatbot' ); ?>
				</p>

			</div><!-- .npleadchat-upsell-card -->

		</div><!-- .wrap -->

		<style>
		/* Scoped to this page only — no separate stylesheet needed */
		.npleadchat-upsell-wrap h1   { font-size: 1.8em; margin-bottom: .4em; }
		.npleadchat-upsell-tagline   { font-size: 1em; color: #555; margin-bottom: 1.5em; }
		.npleadchat-upsell-card      { background: #fff; padding: 28px 32px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.08); max-width: 860px; }
		.npleadchat-upsell-card hr   { margin: 24px 0; border: none; border-top: 1px solid #e5e5e5; }
		.npleadchat-upsell-benefits  { margin: 12px 0 0; padding: 0 0 0 4px; list-style: none; }
		.npleadchat-upsell-benefits li { font-size: .95em; line-height: 2; }
		.npleadchat-upsell-context   { margin: 18px 0 0; max-width: 760px; color: #555; line-height: 1.6; }
		.npleadchat-compare-table    { margin-top: 16px; }
		.npleadchat-compare-table th:first-child,
		.npleadchat-compare-table td:first-child { width: 48%; }
		.npleadchat-compare-table th:not(:first-child),
		.npleadchat-compare-table td:not(:first-child) { text-align: center; }
		.npleadchat-upsell-actions   { margin-top: 28px; }
		.npleadchat-upsell-actions .button { margin-right: 10px; }
		.npleadchat-upsell-note      { margin-top: 14px; color: #777; font-size: .85em; }
		</style>

		<?php
	}

	// -------------------------------------------------------------------------
	// Dismissible admin notice
	// -------------------------------------------------------------------------

	/**
	 * Show a one-time dismissible notice on all admin pages (admins only).
	 * The user can permanently dismiss it via a nonce-protected link.
	 *
	 * @return void
	 */
	public static function npleadchat_admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Already dismissed? Never show again.
		if ( get_option( self::NOTICE_DISMISSED_OPTION ) ) {
			return;
		}

		// Don't show on our own upsell page — redundant.
		$screen = get_current_screen();
		if ( $screen && 'npleadchat-leads_page_npleadchat-upgrade' === $screen->id ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'npleadchat_dismiss_notice', '1' ),
			'npleadchat_dismiss_notice_nonce'
		);

		$upgrade_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( self::UPGRADE_URL ),
			esc_html__( 'Upgrade Now →', 'np-lead-chatbot' )
		);

		?>
		<div class="notice notice-info" id="npleadchat-upsell-notice">
			<p>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: 1: opening <strong> tag, 2: closing </strong> tag, 3: upgrade link HTML */
						esc_html__( '%1$sLead Capture Chat PRO%2$s - unlock custom fields, advanced email notifications, Google reCAPTCHA v3, unlimited leads, analytics, and more. %3$s', 'np-lead-chatbot' ),
						'<strong>',
						'</strong>',
						$upgrade_link
					),
					array(
						'strong' => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				);
				?>
				&nbsp;&nbsp;<a href="<?php echo esc_url( $dismiss_url ); ?>" style="color:#999; text-decoration:none; font-size:.85em;">
					<?php esc_html_e( 'Dismiss', 'np-lead-chatbot' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle the dismiss-notice request and store the preference.
	 *
	 * @return void
	 */
	public static function npleadchat_handle_notice_dismiss() {
		if ( ! isset( $_GET['npleadchat_dismiss_notice'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'npleadchat_dismiss_notice_nonce' );

		update_option( self::NOTICE_DISMISSED_OPTION, true );

		// Redirect back without the query arg so the page looks clean.
		$redirect = remove_query_arg( array( 'npleadchat_dismiss_notice', '_wpnonce' ) );
		wp_safe_redirect( $redirect );
		exit;
	}
}
