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

		if ( ! wprsync_check_phpexec() ) {
			add_action( 'admin_notices', [ $this, 'exec_warning' ] );
		}

		if ( ! wprsync_check_rsync() ) {
			add_action( 'admin_notices', [ $this, 'rsync_warning' ] );
		}

		if ( wprsync_check_phpexec() && wprsync_check_rsync() ) {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'wprsync_menupage', [ $this, 'debug_information' ], 100 );
		}
	}

	public function add_menu_page() {
		add_menu_page( wprsync_get_instance()->name, wprsync_get_instance()->name, $this->capability, $this->menu_page, [ $this, 'register_menu_page' ], 'dashicons-update' );
		add_submenu_page( $this->menu_page, __( 'Settings', 'wprsync' ), __( 'Settings', 'wprsync' ), $this->capability, $this->menu_page_settings, [ $this, 'register_settings_page' ] );
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
				settings_fields( $this->settings_group );
				do_settings_sections( $this->menu_page_settings );
				?>
				<div class="about-text">
					<p>
						<?php
						// translators: This Plugin was created by ...
						printf( __( 'This Plugin was created by %s.', 'wprsync' ), '<a href="https://nicomartin.ch" target="_blank">Nico Martin</a> - <a href="https://sayhello.ch" target="_blank">Say Hello GmbH</a>' );
						?>
					</p>
				</div>
				<?php
				submit_button();
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
		$test = $this->test_rsync();
		echo '<div class="notice notice-' . $test['status'] . '"><p>';
		echo $test['message'];
		echo '</p></div>';
	}

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

	public function test_rsync() {

		$msg = '';

		if ( '' == $this->options['user'] || '' == $this->options['host'] || '' == $this->options['dest'] ) {

			$msg = __( 'Please insert your remote server setings below', 'wprsync' );

			return [
				'message' => $msg,
				'status'  => 'warning',
			];

		} else {

			$connection = $this->options['user'] . '@' . $this->options['host'] . ':' . $this->options['dest'];
			$exec       = shell_exec( 'rsync --list-only ' . $connection );
			$stat       = 'error';

			if ( is_null( $exec ) ) {

				$msg .= '<b>' . __( 'connection failed', 'wprsync' ) . '</b>:<br>';
				$msg .= __( 'Please make sure the remote server configurations are correct and your SSH Key is configured properly', 'wprsync' );
				$msg .= '<br><br><code>' . $connection . '</code>';

			} elseif ( strpos( $exec, 'wp-config.php' ) === false ) {

				$msg = __( 'Connection was successfull but wp-config.php could not be found. Please define the absolute path to the remote WordPress root folder.', 'wprsync' );

			} else {

				$msg  = __( 'Connection successful!', 'wprsync' );
				$stat = 'success';

			}

			return [
				'message' => $msg,
				'status'  => $stat,
			];
		} // End if().
	}
}
