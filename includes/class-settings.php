<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */
class TrustedLogin_Example_Settings_Page {

	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		// add_action('admin_init', array($this, 'page_init'));
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {

		// This page will be under "Settings"
		add_options_page(
			'TrustedLogin Demo',
			'TrustedLogin Demo',
			'manage_options',
			'trustedlogin-settings',
			array( $this, 'create_admin_page' )
		);

		// This page will be under "Users"
		add_users_page(
			'TrustedLogin Demo',
			'TrustedLogin Demo',
			'manage_options',
			'trustedlogin-users',
			array( $this, 'create_admin_page' )
		);

		// This page will be under "Tools"
		add_management_page(
			'TrustedLogin Demo',
			'TrustedLogin Demo',
			'manage_options',
			'trustedlogin-tools',
			array( $this, 'create_admin_page' )
		);

		// add top level menu page
		add_menu_page(
			'TrustedLogin Demo',
			'TrustedLogin Demo',
			'manage_options',
			'trustedlogin-admin',
			array( $this, 'create_admin_page' ),
			'dashicons-lock'
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		?>
	<div class="wrap">
		<h1>TrustedLogin Demo</h1>
		<div class="about-wrap full-width-layout">
            <h2>Output a TrustedLogin button</h2>
		<p class="description">Examples of using the TrustedLogin button generator:</p>
		<pre lang="php">$TL = new TrustedLogin;
echo $TL->get_button( 'size=normal&class=button-secondary' );
</pre>

		<div class="has-2-columns is-fullwidth">
			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=hero</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=hero'); ?>
			</div>

			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=hero&class=button-secondary</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=hero&class=button-secondary'); ?>
			</div>
		</div>

		<hr />

		<div class="has-2-columns is-fullwidth">
			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=large</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=large'); ?>
			</div>

			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=large&class=button-secondary</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=large&class=button-secondary'); ?>
			</div>
		</div>

		<hr />

		<div class="has-2-columns is-fullwidth">
			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=normal</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=normal'); ?>
			</div>

			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=normal&class=button-secondary</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=normal&class=button-secondary'); ?>
			</div>
		</div>

		<hr />

		<div class="has-2-columns is-fullwidth">
			<div class="column">
			<h3 style="font-weight: normal;">Attributes: <code>size=small</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=small'); ?>
			</div>

			<div class="column">
				<h3 style="font-weight: normal;">Attributes: <code>size=small&class=button-secondary</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=small&class=button-secondary'); ?>
			</div>
		</div>

		<hr />

		<div class="has-2-columns is-fullwidth">
			<div class="column">
			<h3 style="font-weight: normal;">Attributes: <code>size=&class=&powered_by=</code></h3>
				<?php do_action( 'trustedlogin/gravityview/button', 'size=&class=&powered_by=', false); ?>
			</div>
		</div>

		</div>

        <hr>

        <div class="about-wrap full-width-layout">
            <h2>Output a table of users</h2>
            <p class="description">To include a table of your active support users created with TrustedLogin:</p>
            <pre lang="php">do_action( 'trustedlogin_users_table' );</pre>
            <?php
                do_action( 'trustedlogin/gravityview/users_table' );
            ?>
        </div>
	</div>
<?php
	}

}

add_action( 'plugins_loaded', function() {
	new TrustedLogin_Example_Settings_Page;
});
