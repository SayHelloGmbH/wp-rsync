<?php

namespace nicomartin\WPrsync;

class Settings {

	public $capability = '';
	public $icon = '';
	public $menu_page = '';
	public $menu_page_settings = '';
	public $settings_group = '';
	public $settings_key = '';
	public $adminbar_id = '';

	private $options = '';

	public function __construct() {

		$this->capability         = 'administrator';
		$this->menu_page          = wprsync_get_instance()->prefix;
		$this->menu_page_settings = wprsync_get_instance()->prefix . '-settings';
		$this->settings_option    = wprsync_get_instance()->prefix . '-option';
		$this->settings_group     = $this->settings_key . '-group';
		$this->settings_section   = $this->settings_key . '-section';
		$this->adminbar_id        = wprsync_get_instance()->prefix . '_adminbar';
		$this->options            = get_option( $this->settings_option );
	}

	public function run() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'wprsync_settingspage', [ $this, 'credits' ], 100 );
		add_action( 'wprsync_menupage', [ $this, 'credits' ], 100 );

		if ( ! wprsync_check_phpexec() ) {
			add_action( 'admin_notices', [ $this, 'exec_warning' ] );
		}

		if ( ! wprsync_check_rsync() ) {
			add_action( 'admin_notices', [ $this, 'rsync_warning' ] );
		}

		if ( wprsync_check_phpexec() && wprsync_check_rsync() ) {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'wprsync_settingspage', [ $this, 'debug_information' ], 90 );
		}
	}

	public function add_menu_page() {
		add_menu_page( wprsync_get_instance()->name, wprsync_get_instance()->name, $this->capability, $this->menu_page, [ $this, 'register_menu_page' ], 'dashicons-update' );
		add_submenu_page( $this->menu_page, __( 'Settings', 'wprsync' ), __( 'Settings', 'wprsync' ), $this->capability, $this->menu_page_settings, [ $this, 'register_settings_page' ] );
	}

	public function credits() {
		?>
		<div class="about-text _menupage-element">
			<p>
				<?php
				// translators: This Plugin was created by ...
				printf( __( 'Created by %s.', 'wprsync' ), '<a href="https://nicomartin.ch" target="_blank">Nico Martin</a> - <a href="https://sayhello.ch" target="_blank">Say Hello GmbH</a>' );
				?>
			</p>
		</div>
		<?php
	}

	public function exec_warning() {

		if ( get_current_screen()->parent_base != $this->menu_page ) {
			return;
		}

		$class   = 'notice notice-error';
		$message = __( 'Please enable php shell_exec() and make sure you have the right permissions.', 'wprsync' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), '<b>' . wprsync_get_instance()->name . ':</b> ' . esc_html( $message ) );
	}

	public function rsync_warning() {

		if ( get_current_screen()->parent_base != $this->menu_page ) {
			return;
		}

		$class   = 'notice notice-error';
		$message = __( 'Please install rsync on your server to use the automatic file transfer.', 'wprsync' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), '<b>' . wprsync_get_instance()->name . ':</b> ' . esc_html( $message ) );
	}

	public function register_menu_page() {
		?>
		<div class="wrap wprsync-menupage-wrap">
			<h1><?php echo wprsync_get_instance()->name; ?></h1>
			<div class="wprsync-menupage">
				<?php do_action( 'wprsync_menupage' ); ?>
			</div>
		</div>
		<?php
	}

	public function register_settings_page() {
		?>
		<div class="wrap wprsync-menupage-wrap">
			<h1><?php echo wprsync_get_instance()->name . ' ' . __( 'Settings', 'wprsync' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				echo '<div class="_menupage-element">';
				settings_fields( $this->settings_group );
				do_settings_sections( $this->menu_page_settings );
				submit_button();
				echo '</div>';
				do_action( 'wprsync_settingspage' );
				?>
			</form>
		</div>
		<?php
	}

	public function register_settings() {
		$section = $this->settings_section;
		register_setting( $this->settings_group, $this->settings_option, [ $this, 'sanitize' ] );

		add_settings_section( $section, __( 'remote server configuration', 'wprsync' ), [ $this, 'print_section_info' ], $this->menu_page_settings );
		add_settings_field( 'user', __( 'User', 'wprsync' ), [ $this, 'settings_user_callback' ], $this->menu_page_settings, $section );
		add_settings_field( 'host', __( 'Host', 'wprsync' ), [ $this, 'settings_host_callback' ], $this->menu_page_settings, $section );
		add_settings_field( 'dest', __( 'Path to WP root', 'wprsync' ), [ $this, 'settings_dest_callback' ], $this->menu_page_settings, $section );

		add_settings_section( $section . '-exclude', __( 'exclude folders', 'wprsync' ), [ $this, 'print_section_info_exclude' ], $this->menu_page_settings );
		add_settings_field( 'uploads', __( 'wp-content/uploads/', 'wprsync' ), [ $this, 'settings_exclude_uploads_callback' ], $this->menu_page_settings, $section . '-exclude' );
		//add_settings_field( 'host', __( 'Host', 'wprsync' ), [ $this, 'settings_host_callback' ], $this->menu_page_settings, $section );
		//add_settings_field( 'dest', __( 'Path to WP root', 'wprsync' ), [ $this, 'settings_dest_callback' ], $this->menu_page_settings, $section );
	}

	public function sanitize( $input ) {

		foreach ( $input as $key => $val ) {
			if ( 'dest' == $key ) {
				if ( substr( $val, - 1 ) != '/' ) {
					$val = $val . '/';
				}
				$input[ $key ] = $val;
			}
		}

		return $input;
	}

	public function print_section_info() {
		$test = wprsync_test_rsync();
		echo '<div class="notice notice-' . $test['status'] . '"><p>';
		echo $test['message'];
		//echo '<pre>';
		//print_r( $this->options );
		//echo '</pre>';
		echo '</p></div>';
	}

	public function print_section_info_exclude() {

	}

	/**
	 * Settings fields
	 */

	public function settings_user_callback() {
		$key = 'user';
		$val = $this->get_val( $key );
		printf( '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" />', $this->settings_option, $key, $val );
	}

	public function settings_host_callback() {
		$key = 'host';
		$val = $this->get_val( $key );
		printf( '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" />', $this->settings_option, $key, $val );
	}

	public function settings_dest_callback() {
		$key = 'dest';
		$val = $this->get_val( $key );
		printf( '<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" placeholder="' . ABSPATH . '" />', $this->settings_option, $key, $val );
	}

	public function settings_exclude_uploads_callback() {
		$folders                 = wprsync_get_uploads_subfolders();
		$elements                = [];
		$excluded_upload_folders = $this->options['exclude_uploads'];
		foreach ( $folders as $folder ) {
			$checked = false;
			if ( isset( $excluded_upload_folders[ $folder['path'] ] ) ) {
				$checked = true;
			}

			$elements[] = '<label><input type="checkbox" name="' . $this->settings_option . '[exclude_uploads][' . $folder['path'] . ']" ' . ( $checked ? 'checked' : '' ) . ' /> ' . $folder['name'] . '</label>';
		}
		echo implode( '<br>', $elements );
	}

	/**
	 *
	 */

	public function debug_information() {
		?>
		<div class="_debug _menupage-element">
			<h2><?php _e( 'Debug information', 'wprsync' ); ?></h2>
			<table class="wprsync-table">
				<tr>
					<td>rsync</td>
					<td><?php echo nl2br( shell_exec( 'rsync --version' ) ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Helpers
	 */
	public function get_val( $key, $default = '' ) {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		} else {
			return $default;
		}
	}
}
