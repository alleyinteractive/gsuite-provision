<?php
// This file needs to exist as a standalone redirect target for the OAuth response.

// These requires pull in the WP function stack and the Google API from composer.
require_once( '../../../../wp-load.php' );
require_once( '../vendor/autoload.php' );

// We use PHP session storage here because the access token is an associative array with
// a pretty long set of hashes -- it would be unwieldy to serialize it and pass it as a query arg.
session_start();

$client = new Google_Client();
$client->setAuthConfig( gsuite_provision_settings()->auth_config() );
$client->addScope( Google_Service_Oauth2::USERINFO_EMAIL );

if ( ! isset( $_GET['code'] ) ) {
	$auth_url = $client->createAuthUrl();
	wp_redirect( $auth_url );
	die();
} else {
	$client->authenticate( sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
	$_SESSION['access_token'] = $client->getAccessToken();
	wp_redirect( '/wp-login.php?action=gsuite' );
	die();
}
