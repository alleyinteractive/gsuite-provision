<?php

class GSuite_Provision {

	/**
	 * @var object
	 */
	private static $instance;

	/**
	 * Instance generator.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new GSuite_Provision;
			self::setup();
		}
		return self::$instance;
	}

	/**
	 * Adds relevant action and filter hooks.
	 */
	private static function setup() {
		if (
			! get_option( 'gsuite_domain' ) ||
			! get_option( 'gsuite_client_id' ) ||
			! get_option( 'gsuite_client_secret' ) ||
			// This is a security firewall, since setting this to 'gmail.com' would grant automatic access to the public.
			'gmail.com' === get_option( 'gsuite_domain' )
		) {
			return;
		}

		add_action( 'login_form', [ self::$instance, 'login_form' ] );
		add_action( 'login_head', [ self::$instance, 'login_head' ] );
		add_action( 'login_footer', [ self::$instance, 'login_footer' ] );
		add_action( 'login_form_gsuite', [ self::$instance, 'login_form_gsuite' ] );

		add_filter( 'login_message', [ self::$instance, 'login_message' ] );
	}

	/**
	 * Adds JS to the footer to hide default elements of the login form.
	 */
	public function login_footer() {
		if ( empty( $_GET['gsuite'] ) || 'disable' !== $_GET['gsuite'] ) {
		?>
			<script>
				document.querySelector('#loginform').action = '<?php echo esc_js( GSUITE_PROVISION_URL . '/lib/auth.php' ); ?>'
				var items = document.querySelector('#loginform').querySelectorAll('p:not(.gsuite)');
				items.forEach((item) => {
					item.style.display = "none";
				});
				document.querySelector('#nav').style.display = "none";
			</script>
		<?php }
	}

	/**
	 * Adds supplemental styles to the header.
	 */
	public function login_head() {
		?>
			<style type="text/css">
				#login form p.submit {
					margin-top: 10px;
					text-align: center;
				}

				.login .button-primary.center {
					float: none;
				}

				.gsuite.center {
					text-align: center;
					margin-top: 20px;
				}
			</style>
		<?php
	}

	/**
	 * Provides the alternative login form if GSuite is enabled.
	 */
	public function login_form() {
		if ( empty( $_GET['gsuite'] ) || 'disable' !== $_GET['gsuite'] ) { ?>
			<p class="gsuite"><?php esc_html_e( 'This site allows you to log in with your GSuite credentials. If you do not have an account yet, one will be created for you using your GSuite account information.', 'gsuite_provision' ); ?></p>
			<p class="gsuite submit">
				<input type="submit" class="center button button-primary button-large" value="<?php esc_attr_e( 'Log in with GSuite', 'gsuite_provision' ); ?>">
			</p>
			<p class="gsuite center"><a href="/wp-login.php?gsuite=disable"><?php esc_html_e( 'Log in with username and password instead.', 'gsuite_provision' ); ?></a></p>
		<?php }
	}

	/**
	 * Processes GSuite access tokens and accepts or rejects the user based on their email domain.
	 */
	public function login_form_gsuite() {
		require_once( GSUITE_PROVISION_PATH . 'vendor/autoload.php' );
		session_start();
		$client = new Google_Client();
		$client->setAuthConfig( gsuite_provision_settings()->auth_config() );
		$client->addScope( Google_Service_Oauth2::USERINFO_EMAIL );

		if ( isset( $_SESSION['access_token'] ) && $_SESSION['access_token'] ) {
			$client->setAccessToken( $_SESSION['access_token'] );

			if ( $client->isAccessTokenExpired() ) {
				wp_redirect( GSUITE_PROVISION_URL . '/lib/auth.php' );
				return;
			}

			$service = new Google_Service_Oauth2( $client );
			$userinfo = $service->userinfo->get();

			$loc = $this->process_userinfo( $userinfo );
			wp_redirect( $loc );
			return;
		}
	}

	/**
	 * Maps error values to error messages.
	 */
	public function login_message() {
		if ( isset( $_GET['gsuite'] ) && 'invalid' === $_GET['gsuite'] ) {
			return '<div id="login_error">' . esc_html__( 'Your email domain is not authorized to access this site.', 'gsuite_provision' ) . '</div>';
		}

		if ( isset( $_GET['gsuite'] ) && 'error' === $_GET['gsuite'] ) {
			return '<div id="login_error">' . esc_html__( 'We encountered an error obtaining your information from GSuite. Please contact the site administrator.', 'gsuite_provision' ) . '</div>';
		}

		return '';
	}

	/**
	 * Checks validity of userinfo object from the API, then logs the user info (creating it necessary).
	 * Returns the relative URL to redirect to upon processing.
	 * @param Google_Service_Oauth2_Userinfoplus $userinfo info object from the API
	 * @return string
	 */
	public function process_userinfo( $userinfo ) {
		if ( ! $userinfo ) {
			return '/wp-login.php?gsuite=error';
		}

		if ( ! $userinfo->hd || get_option( 'gsuite_domain' ) !== $userinfo->hd ) {
			return '/wp-login.php?gsuite=invalid';
		}

		$user = $this->get_or_create_user( $userinfo->email, $userinfo->givenName, $userinfo->familyName, $userinfo->name, $userinfo->id );
		wp_set_auth_cookie( $user->ID, true );
		wp_set_current_user( $user->ID );
		return '/';
	}

	/**
	 * Returns or creates and returns a user object using information from GSuite.
	 * @param string $email User's email address
	 * @param string $first_name User's first name
	 * @param string $last_name User's last name
	 * @param string $display_name User's full name
	 * @param int $gsuite_id User's gsuite ID
	 * @return user
	 */
	public function get_or_create_user( $email, $first_name, $last_name, $display_name, $gsuite_id ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			$email_parts = explode( '@', $email );
			$username = sanitize_text_field( array_shift( $email_parts ) );
			$password = wp_generate_password();
			$user_id = wp_create_user( $username, $password, sanitize_text_field( $email ) );
			$user = get_user_by( 'ID', $user_id );

			$role = get_option( 'gsuite_role', 'subscriber' );

			wp_update_user( [
				'ID' => $user->ID,
				'role' => $role,
				'first_name' => sanitize_text_field( $first_name ),
				'last_name' => sanitize_text_field( $last_name ),
				'display_name' => sanitize_text_field( $display_name ),
				'nickname' => sanitize_text_field( $first_name ),
			] );

			update_user_meta( $user->ID, 'gsuite-profile-id', $gsuite_id );
		}

		return $user;
	}
}
