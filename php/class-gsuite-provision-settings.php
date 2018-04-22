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
		add_action( 'admin_menu', [ self::$instance, 'admin_menu' ] );
	}

	/**
	 * Registers and adds settings.
	 */
	public function admin_menu() {
		register_setting( 'general', 'gsuite_domain', [ 'type' => 'string' ] );
		register_setting( 'general', 'gsuite_role', [ 'type' => 'string' ] );
		register_setting( 'general', 'gsuite_client_id', [ 'type' => 'string' ] );
		register_setting( 'general', 'gsuite_client_secret', [ 'type' => 'string' ] );

		add_settings_section( 'gsuite', esc_html__( 'GSuite Login Settings', 'gsuite_provision' ), [ self::$instance, 'setting_section_header' ], 'general' );

		add_settings_field( 'gsuite_domain', esc_html__( 'Authorized Domain', 'gsuite_provision' ), [ self::$instance, 'domain_setting' ], 'general', 'gsuite' );
		add_settings_field( 'gsuite_role', esc_html__( 'Role for New Users', 'gsuite_provision' ), [ self::$instance, 'role_setting' ], 'general', 'gsuite' );
		add_settings_field( 'gsuite_client_id', esc_html__( 'OAuth Client ID', 'gsuite_provision' ), [ self::$instance, 'client_id_setting' ], 'general', 'gsuite' );
		add_settings_field( 'gsuite_client_secret', esc_html__( 'OAuth Client Secret', 'gsuite_provision' ), [ self::$instance, 'client_secret_setting' ], 'general', 'gsuite' );
	}

	/**
	 * Outputs settings section header message.
	 */
	public function setting_section_header() {
		?><p><?php esc_html_e( 'These settings apply to the GSuite Provisioning plugin. Logging via GSuite will only be available if all settings are valid.', 'gsuite_provision' ); ?></p><?php
	}

	/**
	 * Outputs domain setting control.
	 */
	public function domain_setting() {
		?><input type="text" class="regular-text" name="gsuite_domain" value="<?php echo esc_attr( get_option( 'gsuite_domain' ) ); ?>"><?php
	}

	/**
	 * Outputs role setting control.
	 */
	public function role_setting() {
		?><select name="gsuite_role">
			<?php wp_dropdown_roles( get_option( 'gsuite_role' ) ); ?>
		</select><?php
	}

	/**
	 * Outputs client ID setting control.
	 */
	public function client_id_setting() {
		?><input type="text" class="large-text" name="gsuite_client_id" value="<?php echo esc_attr( get_option( 'gsuite_client_id' ) ); ?>"><?php
	}

	/**
	 * Outputs cient secret setting control.
	 */
	public function client_secret_setting() {
		?><input type="text" class="regular-text" name="gsuite_client_secret" value="<?php echo esc_attr( get_option( 'gsuite_client_secret' ) ); ?>"><?php
	}

	/**
	 * Returns an assembled configuration array suitable for passing to the Google OAuth `setAuthConfig` method.
	 * @return array
	 */
	public function auth_config() {
		return [
			'web' => [
				'client_id' => get_option( 'gsuite_client_id' ),
				'client_secret' => get_option( 'gsuite_client_secret' ),
				'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
				'token_uri' => 'https://accounts.google.com/o/oauth2/token',
				'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
				'redirect_uris' => [
					GSUITE_PROVISION_URL . 'lib/auth.php'
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
