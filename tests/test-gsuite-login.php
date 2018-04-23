<?php

/**
 * Tests the login flow from the point of receiving a valid auth response from GSuite.
 */
class Test_GSuite_Provision_Login extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$this->valid_userinfo = new stdClass;
		$this->valid_userinfo->email = 'testuser@example.com';
		$this->valid_userinfo->familyName = 'Test';
		$this->valid_userinfo->givenName = 'User';
		$this->valid_userinfo->name = 'Test User';
		$this->valid_userinfo->hd = 'example.com';
		$this->valid_userinfo->verifiedEmail = 1;
		$this->valid_userinfo->id = rand(1,10000000000000);

		$this->existing_userinfo = new stdClass;
		$this->existing_userinfo->email = 'existinguser@example.com';
		$this->existing_userinfo->familyName = 'Existing';
		$this->existing_userinfo->givenName = 'User';
		$this->existing_userinfo->name = 'Existing User';
		$this->existing_userinfo->hd = 'example.com';
		$this->existing_userinfo->verifiedEmail = 1;
		$this->existing_userinfo->id = rand(1,10000000000000);

		$this->invalid_userinfo = new stdClass;
		$this->invalid_userinfo->email = 'malicioususer@fake.com';
		$this->invalid_userinfo->familyName = 'Malicious';
		$this->invalid_userinfo->givenName = 'User';
		$this->invalid_userinfo->name = 'Malicious User';
		$this->invalid_userinfo->hd = 'fake.com';
		$this->invalid_userinfo->verifiedEmail = 1;
		$this->invalid_userinfo->id = rand(1,10000000000000);

		$this->gmail_userinfo = new stdClass;
		$this->gmail_userinfo->email = 'anyone@gmail.com';
		$this->gmail_userinfo->familyName = 'Gmail';
		$this->gmail_userinfo->givenName = 'User';
		$this->gmail_userinfo->name = 'Gmail User';
		$this->gmail_userinfo->hd = 'gmail.com';
		$this->gmail_userinfo->verifiedEmail = 1;
		$this->gmail_userinfo->id = rand(1,10000000000000);

		update_option( 'gsuite_domain', 'example.com' );
		update_option( 'gsuite_role', 'author' );

		$this->gsuite = gsuite_provision();
		$this->existing_user = $this->factory->user->create_and_get( [ 'user_email' => 'existinguser@example.com' ] );
	}

	public function test_new_valid_user_login() {
		$loc = $this->gsuite->process_userinfo( $this->valid_userinfo );

		$new_user = get_user_by( 'email', $this->valid_userinfo->email );
		$current_user = wp_get_current_user();

		$this->assertObjectHasAttribute( 'user_login', $new_user->data );
		$this->assertEquals( 'testuser', $new_user->user_login );
		$this->assertEquals( 'Test User', $new_user->display_name );
		$this->assertEquals( $new_user->ID, $current_user->ID );
		$this->assertEquals( '/', $loc );
	}

	public function test_existing_user_login() {
		$loc = $this->gsuite->process_userinfo( $this->existing_userinfo );

		$current_user = wp_get_current_user();

		$this->assertEquals( $current_user->ID, $this->existing_user->ID );
		$this->assertEquals( '/', $loc );
	}

	public function test_invalid_user_rejection() {
		$loc = $this->gsuite->process_userinfo( $this->invalid_userinfo );

		$new_user = get_user_by( 'email', $this->invalid_userinfo->email );
		$current_user = wp_get_current_user();

		$this->assertEquals( $current_user->ID, 0 );
		$this->assertFalse( $new_user );
		$this->assertEquals( '/wp-login.php?gsuite=invalid', $loc );
	}

	public function test_gmail_user_rejection() {
		// Even in a hypothetical scenario where the front-facing forms don't bail out properly.
		update_option( 'gsuite_domain', 'gmail.com' );

		$loc = $this->gsuite->process_userinfo( $this->gmail_userinfo );

		$new_user = get_user_by( 'email', $this->valid_userinfo->email );
		$current_user = wp_get_current_user();

		$this->assertEquals( $current_user->ID, 0 );
		$this->assertFalse( $new_user );
		$this->assertEquals( '/wp-login.php?gsuite=invalid', $loc );
	}
}
