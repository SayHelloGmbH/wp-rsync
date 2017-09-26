<?php
/*
Plugin Name: WP rsync
Plugin URI: https://github.com/nico-martin/wp-rsync
Description: This plugin lets you push Plugins Themes and Medias via rsync
Author: Nico Martin
Version: 0.2.7
Author URI: https://nicomartin.ch
Text Domain: wprsync
Domain Path: /languages
 */

global $wp_version;
if ( version_compare( $wp_version, '4.7', '<' ) || version_compare( PHP_VERSION, '5.4', '<' ) ) {
	function wprsync_compatability_warning() {
		echo '<div class="error"><p>';
		// translators: Dependency waring
		echo sprintf( __( '“%1$s” requires PHP %2$s (or newer) and WordPress %3$s (or newer) to function properly. Your site is using PHP %4$s and WordPress %5$s. Please upgrade. The plugin has been automatically deactivated.', 'wprsync' ), 'Advanced WPPerformance', '5.3', '4.7', PHP_VERSION, $GLOBALS['wp_version'] );
		echo '</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	add_action( 'admin_notices', 'wprsync_compatability_warning' );

	function wprsync_deactivate_self() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	add_action( 'admin_init', 'wprsync_deactivate_self' );

	return;

} else {

	require_once 'inc/funcs.php';
	require_once 'Classes/class-plugin.php';

	function wprsync_get_instance() {
		return nicomartin\WPrsync\Plugin::get_instance( __FILE__ );
	}

	wprsync_get_instance();

	require_once 'Classes/class-init.php';
	wprsync_get_instance()->Init = new nicomartin\WPrsync\Init();
	wprsync_get_instance()->Init->run();

	require_once 'Classes/class-settings.php';
	wprsync_get_instance()->Settings = new nicomartin\WPrsync\Settings();
	wprsync_get_instance()->Settings->run();

	require_once 'Classes/class-sync.php';
	wprsync_get_instance()->Sync = new nicomartin\WPrsync\Sync();
	wprsync_get_instance()->Sync->run();

} // End if().
