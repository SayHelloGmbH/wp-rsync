<?php

namespace nicomartin\WPrsync;

class Settings {

	public $capability = '';
	public $icon = '';
	public $settings_page = '';
	public $settings_group = '';
	public $settings_key = '';
	public $adminbar_id = '';

	private $options = '';

	public function __construct() {

		$this->capability       = 'administrator';
		$this->settings_page    = wprsync_get_instance()->prefix . '-settings';
		$this->settings_option  = wprsync_get_instance()->prefix . '-option';
		$this->settings_group   = $this->settings_key . '-group';
		$this->settings_section = $this->settings_key . '-section';
		$this->adminbar_id      = wprsync_get_instance()->prefix . '_adminbar';
		$this->options          = get_option( $this->settings_option );

	}

	public function run() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar' ], 90 );
	}

	public function add_menu_page() {
		add_submenu_page( 'options-general.php', wprsync_get_instance()->name, wprsync_get_instance()->name, $this->capability, $this->settings_page, [ $this, 'register_settings_page' ] );
	}

	public function register_settings_page() {
		?>
		<div class="wrap wprsync-settings-wrap">
			<h1><?php echo wprsync_get_instance()->name; ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( $this->settings_group );
				do_settings_sections( $this->settings_page );
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
		add_settings_section( $section, __( 'Settings', 'wprsync' ), [ $this, 'print_section_info' ], $this->settings_page );
		add_settings_field( 'scripts_to_footer', __( 'Move all scripts to footer', 'wprsync' ), [ $this, 'scripts_to_footer_callback' ], $this->settings_page, $section );
		add_settings_field( 'minify', __( 'Minify CSS and JS Files', 'wprsync' ), [ $this, 'minify_callback' ], $this->settings_page, $section );
		add_settings_field( 'loadcss', __( 'Load CSS async', 'wprsync' ), [ $this, 'loadcss_callback' ], $this->settings_page, $section );
	}

	public function sanitize( $input ) {

		$checkboxes = [ 'scripts_to_footer', 'defer_scripts', 'loadcss', 'minify' ];

		foreach ( $checkboxes as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$input[ $key ] = $input[ $key ];
			} else {
				$input[ $key ] = 'off';
			}
		}

		return $input;
	}

	public function print_section_info() {
	}

	public function scripts_to_footer_callback() {
		$key = 'scripts_to_footer';
		$val = $this->get_val( $key, 'on' );
		printf( '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />', $this->settings_option, $key, ( 'on' == $val ? 'checked' : '' ) );
	}

	public function minify_callback() {
		$key = 'minify';
		$val = $this->get_val( $key, 'on' );
		printf( '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />', $this->settings_option, $key, ( 'on' == $val ? 'checked' : '' ) );
	}

	public function loadcss_callback() {
		$key = 'loadcss';
		$val = $this->get_val( $key, 'on' );
		printf( '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />', $this->settings_option, $key, ( 'on' == $val ? 'checked' : '' ) );
	}

	public function add_toolbar( $wp_admin_bar ) {
		$args = [
			'id'    => $this->adminbar_id,
			'title' => str_replace( 'Advanced ', '', wprsync_get_instance()->name ),
			'href'  => admin_url( 'options-general.php?page=' . $this->settings_page ),
			'meta'  => [
				'class' => wprsync_get_instance()->prefix . '-adminbar',
			],
		];
		$wp_admin_bar->add_node( $args );
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
