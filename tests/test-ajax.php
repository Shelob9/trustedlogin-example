<?php
/**
 * Class TrustedLoginAPITest
 *
 * @package Trustedlogin_Button
 */

/**
 * Sample test case.
 * @group ajax
 */
class TrustedLoginAJAXTest extends WP_Ajax_UnitTestCase {

	/**
	 * @var TrustedLogin
	 */
	private $TrustedLogin;

	/**
	 * @var ReflectionClass
	 */
	private $TrustedLoginReflection;

	/**
	 * @var array
	 */
	private $config;

	public function __construct() {

		$this->config = array(
			'role'           => array(
				'editor' => 'Support needs to be able to access your site as an administrator to debug issues effectively.',
			),
			'extra_caps'     => array(
				'manage_options' => 'we need this to make things work real gud',
				'edit_posts'     => 'Access the posts that you created',
				'delete_users'   => 'In order to manage the users that we thought you would want us to.',
			),
			'webhook_url'    => '...',
			'auth'           => array(
				'api_key'     => '9946ca31be6aa948', // Public key for encrypting the securedKey
				'license_key' => 'my custom key',
			),
			'decay'          => WEEK_IN_SECONDS,
			'vendor'         => array(
				'namespace'   => 'gravityview',
				'title'       => 'GravityView',
				'email'       => 'support@gravityview.co',
				'website'     => 'https://gravityview.co',
				'support_url' => 'https://gravityview.co/support/', // Backup to redirect users if TL is down/etc
				'logo_url'    => '', // Displayed in the authentication modal
			),
			'reassign_posts' => true,
		);

		$this->TrustedLogin = new TrustedLogin( $this->config );

		$this->TrustedLoginReflection = new ReflectionClass( 'TrustedLogin' );
	}

	/**
	 * Set a valid "tl_nonce-{user_id}" $_POST['_nonce'] value
	 * @see GravityView_Ajax::check_ajax_nonce()
	 */
	function _set_nonce( $user_id = null ) {

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$_POST['_nonce'] = wp_create_nonce( 'tl_nonce-' . $user_id );
	}


	/**
	 * @covers TrustedLogin::ajax_generate_support
	 */
	function test_ajax_generate_support() {

		$this->_setRole('administrator' );
		$current_user = wp_get_current_user();
		if ( function_exists( 'grant_super_admin' ) ) {
			grant_super_admin( $current_user->ID );
		}

		unset( $_POST['vendor'] );
		$this->_catchHandleAjax();
		$this->assertContains( 'Vendor not defined', $this->_last_response );
		$this->_last_response = '';

		$_POST['vendor'] = 'asdasd';
		$this->_catchHandleAjax();
		$this->assertSame( '', $this->_last_response, 'Vendor does not match config vendor.' );
		$this->_last_response = '';

		$_POST['vendor'] = $this->config['vendor']['namespace'];
		$this->_catchHandleAjax();
		$this->assertContains( 'Nonce not sent', $this->_last_response );
		$this->_last_response = '';

		$_POST['vendor'] = $this->config['vendor']['namespace'];
		$this->_set_nonce( 0 );
		$this->_catchHandleAjax();
		$this->assertContains( 'Verification Issue', $this->_last_response, 'Nonce set to 0; should not validate.' );
		$this->_set_nonce();
		$this->_last_response = '';
		$this->_delete_all_support_users();

		$this->_setRole('subscriber' );
		$this->_set_nonce();
		$this->_catchHandleAjax();
		$this->assertContains( 'Permissions Issue', $this->_last_response, 'User should not have permission to create users.' );
		$this->_last_response = '';
		$this->_delete_all_support_users();

		/**
		 * Create conflicting user name and try to create the user with the same username.
		 * Just wanting to make sure this step is tested, but:
		 * @see TrustedLoginUsersTest::test_create_support_user for full testing
		 */
		$user_name = sprintf( esc_html__( '%s Support', 'trustedlogin' ), $this->TrustedLogin->get_setting( 'vendor/title' ) );
		$existing_user = $this->factory->user->create_and_get( array( 'user_login' => $user_name ) );
		$this->assertTrue( is_a( $existing_user, 'WP_User' ) );
		$this->_setRole( 'administrator' );
		$current_user = wp_get_current_user();
		if ( function_exists( 'grant_super_admin' ) ) {
			grant_super_admin( $current_user->ID );
		}
		$this->_set_nonce();
		$this->_catchHandleAjax();
		$this->assertContains( 'already exists', $this->_last_response, 'User should not have permission to create users.' );
		$this->_last_response = '';
		$this->assertTrue( wp_delete_user( $existing_user->ID ) ); // Cleanup
		$this->_delete_all_support_users();


		// Cause support_user_setup() to fail to trigger an error.
		add_filter( 'get_user_metadata', $cause_error = function( $return = null, $object_id, $meta_key, $single ) {
			if ( false !== strpos( $meta_key, 'tl_' ) ) {
				return false;
			}

			return $return;
		}, 10, 4 );

		$this->_catchHandleAjax();
		$this->assertContains( 'Error updating user', $this->_last_response, 'When support_user_setup() returns an error' );
		$this->_last_response = '';
		remove_filter( 'get_user_metadata', $cause_error );
		$this->_delete_all_support_users();


		/**
		 * It doesn't matter if create_access() fails now, since we have properly checked everything else.
		 * Now we just want to make sure the return data array is correct
		 */
		$this->_catchHandleAjax();

		$last_response = $this->_last_response;

		$json = json_decode( $last_response, true );

		$this->assertArrayHasKey( 'data', $json );

		$data = $json['data'];

		$this->assertArrayHasKey( 'siteurl', $data );
		$this->assertArrayHasKey( 'endpoint', $data );
		$this->assertArrayHasKey( 'identifier', $data );
		$this->assertArrayHasKey( 'user_id', $data );
		$this->assertArrayHasKey( 'expiry', $data );
	}

	function _delete_all_support_users() {
		$users = $this->TrustedLogin->get_support_users();

		foreach ( $users as $user ) {
			wp_delete_user( $user->ID );
		}

		$user = get_user_by( 'email', $this->TrustedLogin->get_setting( 'vendor/email' ) );

		if( $user ) {
			wp_delete_user( $user->ID );
		}
	}

	/**
	 * Sets `_last_response` property
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	function _catchHandleAjax( $action = 'tl_gen_support' ) {
		try {
			$this->_handleAjax( $action );
		} catch ( Exception $e ) {

		}
	}
}