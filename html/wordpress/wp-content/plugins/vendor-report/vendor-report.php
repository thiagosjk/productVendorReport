<?php
/**
 * Plugin Name: plugin_vendor_report
 * Plugin URI: http://www.pandoapps.com.br
 * Description: A plugin for woocommerce product vendor saying who bought each item
 * Version: 1.0
 * Author: Thiago Ferreira - Pandô APPs
 * Author URI: http://www.pandoapps.com.br
 * License: GPL2
 */

	/** Step 2 (from text above). */
	add_action( 'admin_menu', 'plugin_vendor_report' );

	/** Step 1. */
	function plugin_vendor_report() {
		add_options_page( 'Vendor Report', 'Vendor Report', 'manage_options', 'plugin-vendor-report', 'plugin_vendor_report_options' );
	}

	/** Step 3. */
	function plugin_vendor_report_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<p>Here is where the form would go if I actually had options.</p>';
		echo '</div>';
	}
?>