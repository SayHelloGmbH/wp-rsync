<?php

namespace nicomartin\WPrsync;

class Init {

	public function __construct() {
	}

	public function run() {
		add_action( 'wp_enqueue_scripts', [ $this, 'add_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_assets' ] );
	}

	public function add_assets() {

		$script_version = wprsync_get_instance()->version;

		$min = true;
		if ( wprsync_get_instance()->debug && is_user_logged_in() ) {
			$min = false;
		}

		$dir_uri = plugin_dir_url( wprsync_get_instance()->file );

		//wp_enqueue_style( wprsync_get_instance()->prefix . '-style', $dir_uri . 'assets/styles/ui' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		//wp_enqueue_script( wprsync_get_instance()->prefix . '-script', $dir_uri . 'assets/scripts/ui' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
	}

	public function add_admin_assets() {

		$current_screen_base = get_current_screen()->id;
		$enqueue_on          = [
			'wp-rsync_page_wprsync-settings',
			'toplevel_page_wprsync',
		];

		if ( ! in_array( $current_screen_base, $enqueue_on ) ) {
			return;
		}

		$script_version = wprsync_get_instance()->version;

		$min = true;
		if ( wprsync_get_instance()->debug && is_user_logged_in() ) {
			$min = false;
		}

		$dir_uri = plugin_dir_url( wprsync_get_instance()->file );

		wp_enqueue_style( wprsync_get_instance()->prefix . '-admin-style', $dir_uri . 'assets/styles/admin' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		wp_enqueue_script( wprsync_get_instance()->prefix . '-admin-script', $dir_uri . 'assets/scripts/admin' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );

	}
}
