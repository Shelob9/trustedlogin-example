<?php
/**
 * Class TrustedLoginUsersTest
 *
 * @package Trustedlogin_Button
 */

/**
 * Sample test case.
 */
class TrustedLoginUsersTest extends WP_UnitTestCase {

	/**
	 * @var TrustedLogin
	 */
	private $TrustedLogin;

	/**
	 * @var ReflectionClass
	 */
	private $TrustedLoginReflection;

	private $config = array();

	/**
	 * SampleTest constructor.
	 */
	public function __construct() {

		$this->config = array(
			'role'             => array(
				'editor' => 'Support needs to be able to access your site as an administrator to debug issues effectively.',
			),
			'extra_caps'       => array(
				'manage_options' => 'we need this to make things work real gud',
				'edit_posts' => 'Access the posts that you created',
				'delete_users' => 'In order to manage the users that we thought you would want us to.',
			),
			'webhook_url' => '...',
			'auth' => array(
				'api_key' => '9946ca31be6aa948', // Public key for encrypting the securedKey
			),
			'decay' => WEEK_IN_SECONDS,
			'vendor' => array(
				'namespace' => 'gravityview',
				'title' => 'GravityView',
				'first_name' => 'Floaty',
				'last_name' => 'the Astronaut',
				'email' => 'support@gravityview.co',
				'website' => 'https://gravityview.co',
				'support_url' => 'https://gravityview.co/support/', // Backup to redirect users if TL is down/etc
				'logo_url' => '', // Displayed in the authentication modal
			),
			'reassign_posts' => true,
		);

		$this->TrustedLogin = new TrustedLogin( $this->config );
		$this->TrustedLoginReflection = new ReflectionClass('TrustedLogin' );
	}

	private function _get_public_property( $name ) {

		$prop = $this->TrustedLoginReflection->getProperty( $name );
		$prop->setAccessible( true );

		return $prop;
	}

	/**
	 * @covers TrustedLogin::support_user_create_role
	 */
	public function test_support_user_create_role() {
		$this->_test_cloned_cap( 'administrator' );
		$this->_test_cloned_cap( 'editor' );
		$this->_test_cloned_cap( 'author' );
		$this->_test_cloned_cap( 'contributor' );
		$this->_test_cloned_cap( 'subscriber' );

		$this->assertFalse( $this->TrustedLogin->support_user_create_role( '', 'administrator' ), 'empty new role' );
		$this->assertFalse( $this->TrustedLogin->support_user_create_role( microtime(), '' ), 'empty clone role' );
		$this->assertFalse( $this->TrustedLogin->support_user_create_role( microtime(), 'DOES NOT EXIST' ) );

		$this->assertTrue( $this->TrustedLogin->support_user_create_role( 'administrator', '1' ), 'role already exists' );
	}

	/**
	 * @param $role
	 */
	private function _test_cloned_cap( $role ) {

		$new_role = microtime();

		$result = $this->TrustedLogin->support_user_create_role( $new_role, $role );

		$this->assertTrue( $result );

		$remove_caps = array(
			'create_users',
			'delete_users',
			'edit_users',
			'promote_users',
			'delete_site',
			'remove_users',
		);

		$new_role_caps = get_role( $new_role )->capabilities;
		$cloned_caps = get_role( $role )->capabilities;

		foreach ( $remove_caps as $remove_cap ) {
			$this->assertFalse( in_array( $remove_cap, get_role( $new_role )->capabilities, true ) );
			unset( $cloned_caps[ $remove_cap ] );
		}

		$extra_caps = $this->TrustedLogin->get_setting('extra_caps' );

		foreach ( $extra_caps as $extra_cap => $reason ) {

			// The caps that were requested to be added are not allowed
			if ( in_array( $extra_cap, $remove_caps, true ) ) {
				$this->assertFalse( in_array( $extra_cap, array_keys( $new_role_caps ), true ), 'restricted caps were added, but should not have been' );
			} else {
				$this->assertTrue( in_array( $extra_cap, array_keys( $new_role_caps ), true ), $extra_cap . ' was not added, but should have been (for ' . $role .' role)' );
				$cloned_caps[ $extra_cap ] = true;
			}

		}

		$this->assertEquals( $new_role_caps, $cloned_caps );
	}

	/**
	 * @covers TrustedLogin::create_support_user
	 * @covers TrustedLogin::support_user_create_role
	 */
	public function test_create_support_user() {

		$user_id = $this->TrustedLogin->create_support_user();

		$this->assertNotFalse( $user_id );

		$support_user = new WP_User( $user_id );

		if ( get_option( 'link_manager_enabled' ) ) {
			$support_user->add_cap( 'manage_links' );
		}

		$support_role_key = $this->_get_public_property( 'support_role' )->getValue( $this->TrustedLogin );
		$support_role = ( new WP_Roles )->get_role( $support_role_key );

		$this->assertTrue( in_array( $support_role_key, $support_user->roles, true ) );

		foreach( $support_role->capabilities as $expected_cap => $enabled ) {

			$expect = true;

			// manage_links is magical.
			if ( 'manage_links' === $expected_cap ) {
				$expect = ! empty( get_option( 'link_manager_enabled' ) );
			}

			/**
			 * This cap requires `delete_users` for normal admins, or is_super_admin() for MS, which we aren't testing
			 * @see map_meta_cap():393
			 */
			if( 'unfiltered_html' === $expected_cap ) {
				$expect = ! is_multisite();
			}

			$this->assertSame( $expect, $support_user->has_cap( $expected_cap ), 'Did not have ' . $expected_cap .', which was set to ' . var_export( $enabled, true ) );
		}

		$username = sprintf( esc_html__( '%s Support', 'trustedlogin' ), $this->TrustedLogin->get_setting( 'vendor/title' ) );

		$this->assertSame( $this->TrustedLogin->get_setting('vendor/first_name'), $support_user->first_name );
		$this->assertSame( $this->TrustedLogin->get_setting('vendor/last_name'), $support_user->last_name );
		$this->assertSame( $this->TrustedLogin->get_setting('vendor/email'), $support_user->user_email );
		$this->assertSame( $this->TrustedLogin->get_setting('vendor/website'), $support_user->user_url );
		$this->assertSame( sanitize_user( $username ), $support_user->user_login );

		###
		###
		### Test error messages
		###
		###

		$this->_reset_roles();

		$duplicate_user = $this->TrustedLogin->create_support_user();
		$this->assertWPError( $duplicate_user );
		$this->assertSame( 'username_exists', $duplicate_user->get_error_code() );

		$this->_reset_roles();

		$config_with_new_title = $this->config;
		$config_with_new_title['vendor']['title'] = microtime();
		$TL_with_new_title = new TrustedLogin( $config_with_new_title );

		$should_be_dupe_email = $TL_with_new_title->create_support_user();
		$this->assertWPError( $should_be_dupe_email );
		$this->assertSame( 'user_email_exists', $should_be_dupe_email->get_error_code() );

		$this->_reset_roles();

		$config_with_bad_role = $this->config;
		$config_with_bad_role['vendor']['title'] = microtime();
		$config_with_bad_role['vendor']['namespace'] = microtime();
		$config_with_bad_role['vendor']['email'] = microtime() . '@example.com';
		$config_with_bad_role['role'] = array( 'madeuprole' => 'We do not need this; it is made-up!');
		$TL_config_with_bad_role = new TrustedLogin( $config_with_bad_role );

		$should_be_missing_role = $TL_config_with_bad_role->create_support_user();
		$this->assertWPError( $should_be_missing_role );
		$this->assertSame( 'role_not_created', $should_be_missing_role->get_error_code() );


		$valid_config = $this->config;
		$valid_config['vendor']['title'] = microtime();
		$valid_config['vendor']['namespace'] = microtime();
		$valid_config['vendor']['email'] = microtime() . '@example.com';
		$TL_valid_config = new TrustedLogin( $valid_config );

		// Check to see what happens when an error is returned during wp_insert_user()
		add_filter( 'pre_user_login', '__return_empty_string' );

		$should_be_empty_login = $TL_valid_config->create_support_user();
		$this->assertWPError( $should_be_empty_login );
		$this->assertSame( 'empty_user_login', $should_be_empty_login->get_error_code() );

		remove_filter( 'pre_user_login', '__return_empty_string' );
	}

	private function _reset_roles() {
		global $wp_roles;

		unset( $wp_roles );

		wp_roles();
	}
}
