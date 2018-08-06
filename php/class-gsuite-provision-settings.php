<?php

class GSuite_Provision_Settings {
	/**
	 * @var object
	 */
	private static $instance;

	/**
	 * Adds action hook for menu setup.
	 */
	public static function setup() {
		if ( self::is_network_enabled() && is_network_admin() ) {
			add_action( 'network_admin_menu', [ self::$instance, 'network_admin_menu' ] );
			add_action( 'network_admin_edit_gsuite_provision', [ self::$instance, 'network_admin_menu_process' ] );
		} else if ( ! self::is_network_enabled() ) {
			// Do not show the individual site config if the plugin is network-enabled.
			add_action( 'admin_menu', [ self::$instance, 'admin_menu' ] );
		}
	}

	/**
	 * Helper to test if the plugin is network-enabled or not.
	 */
	public function is_network_enabled() {
		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if ( is_plugin_active_for_network( GSUITE_PROVISION_LOCAL_PATH ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Registers a submenu page in the network settings menu.
	 * Unfortunately, the network settings page does not support directly registering a settings section.
	 */
	public function network_admin_menu() {
		add_submenu_page(
			'settings.php',
			esc_html__( 'Network GSuite Login', 'gsuite_provision' ),
			esc_html__( 'Network Gsuite Login', 'gsuite_provision' ),
			'manage_network_options',
			'gsuite-provision-network',
			[ self::$instance, 'network_admin_page' ]
		);

		$this->admin_menu( 'gsuite-provision-network' );
	}

	/**
	 * Callback to render the network admin settings page,
	 */
	public function network_admin_page() {
		?>
			<form name="gsuite-provision" method="post" action="edit.php?action=gsuite_provision">
				<?php
					settings_fields( 'gsuite-provision-network' );
					do_settings_sections( 'gsuite-provision-network' );
					submit_button();
				?>
			</form>
		<?php
	}

	/**
	 * Intercept the data save, since the settings API doesn't help us here
	 */
	public function network_admin_menu_process() {
		check_admin_referer( 'gsuite-provision-network-options' );

		if ( ! $this->is_network_enabled() || ! is_main_site() ) {
			return;
		}

		global $new_whitelist_options;
		$options = $new_whitelist_options['gsuite-provision-network'];

		foreach ( $options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				update_site_option( $option, sanitize_text_field( wp_unslash( $_POST[ $option ] ) ) );
			} else {
				delete_site_option( $option );
			}
		}

		wp_redirect( add_query_arg(
			[
				'page' => 'gsuite-provision-network',
				'updated' => 'true',
			],
			network_admin_url( 'settings.php' )
		) );
		exit();
	}

	/**
	 * Registers and adds settings.
	 */
	public function admin_menu( $slug = 'general' ) {
		if ( empty( $slug ) || '' === $slug ) {
			$slug = 'general';
		}

		register_setting( $slug, 'gsuite_domain', [ 'type' => 'string' ] );
		register_setting( $slug, 'gsuite_role', [ 'type' => 'string' ] );
		register_setting( $slug, 'gsuite_client_id', [ 'type' => 'string' ] );
		register_setting( $slug, 'gsuite_client_secret', [ 'type' => 'string' ] );

		$section_title = is_network_admin() ? esc_html__( 'Network GSuite Login Settings', 'gsuite_provision' ) : esc_html__( 'GSuite Login Settings', 'gsuite_provision' );
		add_settings_section( 'gsuite', $section_title, [ self::$instance, 'setting_section_header' ], $slug );

		add_settings_field( 'gsuite_domain', esc_html__( 'Authorized Domain', 'gsuite_provision' ), [ self::$instance, 'domain_setting' ], $slug, 'gsuite' );
		add_settings_field( 'gsuite_role', esc_html__( 'Role for New Users', 'gsuite_provision' ), [ self::$instance, 'role_setting' ], $slug, 'gsuite' );
		add_settings_field( 'gsuite_client_id', esc_html__( 'OAuth Client ID', 'gsuite_provision' ), [ self::$instance, 'client_id_setting' ], $slug, 'gsuite' );
		add_settings_field( 'gsuite_client_secret', esc_html__( 'OAuth Client Secret', 'gsuite_provision' ), [ self::$instance, 'client_secret_setting' ], $slug, 'gsuite' );
	}

	/**
	 * Outputs settings section header message.
	 */
	public function setting_section_header() {
		?>
			<p><?php esc_html_e( 'These settings apply to the GSuite Provisioning plugin. Logging via GSuite will only be available if all settings are valid.', 'gsuite_provision' ); ?></p>
		<?php
	}

	/**
	 * Outputs domain setting control.
	 */
	public function domain_setting() {
		?>
			<input type="text" class="regular-text" name="gsuite_domain" value="<?php echo esc_attr( get_site_option( 'gsuite_domain' ) ); ?>">
		<?php
	}

	/**
	 * Outputs role setting control.
	 */
	public function role_setting() {
		?>
			<select name="gsuite_role">
				<?php wp_dropdown_roles( get_site_option( 'gsuite_role' ) ); ?>
			</select>
		<?php
	}

	/**
	 * Outputs client ID setting control.
	 */
	public function client_id_setting() {
		?>
			<input type="text" class="large-text" name="gsuite_client_id" value="<?php echo esc_attr( get_site_option( 'gsuite_client_id' ) ); ?>">
		<?php
	}

	/**
	 * Outputs cient secret setting control.
	 */
	public function client_secret_setting() {
		?>
			<input type="text" class="regular-text" name="gsuite_client_secret" value="<?php echo esc_attr( get_site_option( 'gsuite_client_secret' ) ); ?>">
		<?php
	}

	/**
	 * Returns an assembled configuration array suitable for passing to the Google OAuth `setAuthConfig` method.
	 * @return array
	 */
	public function auth_config() {
		return [
			'web' => [
				'client_id' => get_site_option( 'gsuite_client_id' ),
				'client_secret' => get_site_option( 'gsuite_client_secret' ),
				'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
				'token_uri' => 'https://accounts.google.com/o/oauth2/token',
				'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
				'redirect_uris' => [
					GSUITE_PROVISION_URL . 'lib/auth.php',
				],
			],
		];
	}

	/**
	 * Instance generator method.
	 * @return object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new GSuite_Provision_Settings;
			self::setup();
		}
		return self::$instance;
	}
}
