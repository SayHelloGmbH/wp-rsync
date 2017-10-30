<?php

namespace nicomartin\WPrsync;

class Sync {


	public $disabled_path = '';
	public $excluded = [];

	public function __construct() {
		$this->disabled_path = [
			ABSPATH . 'wp-content/plugins/wp-rsync/',
		];
		$options             = get_option( wprsync_get_instance()->Settings->settings_option );
		if ( isset( $options['exclude_uploads'] ) ) {
			foreach ( $options['exclude_uploads'] as $path => $val ) {
				$this->disabled_path[] = $path;
			}
		}
		if ( isset( $options['exclude_folders'] ) ) {
			foreach ( $options['exclude_folders'] as $path => $val ) {
				$this->disabled_path[] = $path;
			}
		}
		$this->excluded = [];
	}

	public function run() {
		add_action( 'wprsync_menupage', [ $this, 'core' ] );
		add_action( 'wprsync_menupage', [ $this, 'list_themes' ] );
		add_action( 'wprsync_menupage', [ $this, 'list_plugins' ] );
		add_action( 'wprsync_menupage', [ $this, 'list_uploads' ] );
		add_action( 'wp_ajax_wprsync_ajax_sync', [ $this, 'sync_window' ] );
		add_action( 'wp_ajax_wprsync_ajax_run_sync', [ $this, 'do_sync' ] );
		add_filter( 'wprsync_excludes', [ $this, 'exclude_build_files' ] );
	}

	public function core() {
		?>
		<div class="about-text _menupage-element">
			<h2><?php _e( 'WordPress', 'wprsync' ); ?></h2>
			<table class="wprsync-synctable wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e( 'Modul', 'wprsync' ); ?></th>
					<th><?php _e( 'Version', 'wprsync' ); ?></th>
					<th><?php _e( 'Last sync', 'wprsync' ); ?></th>
					<th></th>
				</tr>
				</thead>
				<?php

				$path = ABSPATH;
				global $wp_version;

				$args = [
					'category' => __( 'WordPress', 'wprsync' ),
					'name'     => __( 'Core', 'wprsync' ),
					'add_info' => '',
					'version'  => $wp_version,
				];

				echo $this->get_rsync_element( $path, $args );
				?>
			</table>
		</div>
		<?php
	}

	public function list_themes() {
		?>
		<div class="about-text _menupage-element">
			<h2><?php _e( 'Themes', 'wprsync' ); ?></h2>
			<table class="wprsync-synctable wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e( 'Theme', 'wprsync' ); ?></th>
					<th><?php _e( 'Version', 'wprsync' ); ?></th>
					<th><?php _e( 'Last sync', 'wprsync' ); ?></th>
					<th></th>
				</tr>
				</thead>
				<?php
				$themes_folder = get_theme_root() . '/';
				$themes        = wp_get_themes();
				foreach ( $themes as $folder => $val ) {
					$path  = $themes_folder . $folder . '/';
					$theme = wp_get_theme( $folder );

					$author     = $theme->get( 'Author' );
					$author_uri = $theme->get( 'AuthorURI' );
					if ( '' != $author_uri ) {
						$author = '<a target="_blank" href="' . $author_uri . '">' . $author . '</a>';
					}

					$args = [
						'category' => __( 'Theme', 'wprsync' ),
						'name'     => $theme->get( 'Name' ),
						// translators: Who created the theme?
						'add_info' => sprintf( __( 'By %s', 'wprsync' ), $author ),
						'version'  => $theme->get( 'Version' ),
					];

					echo $this->get_rsync_element( $path, $args );
				} // End foreach().
				?>
			</table>
		</div>
		<?php
	}

	public function list_plugins() {
		?>
		<div class="about-text _menupage-element">
			<h2><?php _e( 'Plugins', 'wprsync' ); ?></h2>
			<table class="wprsync-synctable wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e( 'Plugin', 'wprsync' ); ?></th>
					<th><?php _e( 'Version', 'wprsync' ); ?></th>
					<th><?php _e( 'Last sync', 'wprsync' ); ?></th>
					<th></th>
				</tr>
				</thead>
				<?php
				$plugins_folder = ABSPATH . 'wp-content/plugins/';
				$plugins        = get_plugins();
				foreach ( $plugins as $file => $val ) {
					$folder = explode( '/', $file )[0];
					$path   = $plugins_folder . $folder . '/';

					$author     = $val['Author'];
					$author_uri = $val['AuthorURI'];
					if ( '' != $author_uri ) {
						$author = '<a target="_blank" href="' . $author_uri . '">' . $author . '</a>';
					}

					$args = [
						'category' => __( 'Plugin', 'wprsync' ),
						'name'     => $val['Name'],
						// translators: Who created the plugin?
						'add_info' => sprintf( __( 'By %s', 'wprsync' ), $author ),
						'version'  => $val['Version'],
					];

					echo $this->get_rsync_element( $path, $args );
				} // End foreach().
				?>
			</table>
		</div>
		<?php
	}

	public function list_uploads() {
		?>
		<div class="about-text _menupage-element">
			<h2><?php _e( 'Uploads', 'wprsync' ); ?></h2>
			<table class="wprsync-synctable wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th><?php _e( 'Folder', 'wprsync' ); ?></th>
					<th><?php _e( 'File count', 'wprsync' ); ?></th>
					<th><?php _e( 'Last sync', 'wprsync' ); ?></th>
					<th></th>
				</tr>
				</thead>
				<?php

				$folders = wprsync_get_uploads_subfolders();

				foreach ( $folders as $key => $folder ) {

					$count = '0';
					//print_r( $this->get_dir_files( $path ) );
					if ( ! empty( $this->get_dir_files( $folder['path'] ) ) ) {
						$count = count( $this->get_dir_files( $folder['path'] ) );
					}

					$args = [
						'category' => __( 'Uploads', 'wprsync' ),
						'name'     => $folder['name'],
						'add_info' => '',
						'version'  => $count,
					];

					echo $this->get_rsync_element( $folder['path'], $args );
				} // End foreach().

				?>
			</table>
		</div>
		<?php
	}

	public function sync_window() {

		$data = shortcode_atts( [
			'path'     => '',
			'version'  => false,
			'name'     => false,
			'category' => false,
		], $_GET );

		$errors       = [];
		$data['path'] = stripslashes( $data['path'] );

		// check path
		if ( ! is_dir( $data['path'] ) || ( strpos( ABSPATH, $data['path'] ) !== false && ABSPATH != $data['path'] ) ) {
			// translators: check if path is correct
			$errors[] = sprintf( __( 'The given path "%s" is not a directory or not inside the WordPress installation', 'wprsync' ), $data['path'] );
		}

		// check system
		if ( ! wprsync_check_rsync() || ! wprsync_check_phpexec() ) {
			$errors[] = __( 'Please check your system requirements. php shell_exec has to be enabled and rsync has to be installed.', 'wprsync' );
		}

		// check data
		$options = get_option( wprsync_get_instance()->Settings->settings_option );
		if ( '' == $options['dest'] ) {
			$errors[] = __( 'Please add a Destination.', 'wprsync' );
		}

		if ( empty( $errors ) ) {

			$cmd = $this->get_cmd( $data['path'] );

			$exec = shell_exec( $cmd );
			if ( is_null( $exec ) ) {
				// translators: shell exec error
				$errors[] = sprintf( __( 'The following command caused an undefined error: "%s"', 'wprsync' ), $cmd );
			}
		}

		if ( ! empty( $errors ) ) {
			echo '<div id="wprsync-popup" class="mfp-white-popup">';
			foreach ( $errors as $error ) {
				echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
			}
			echo '</div>';
			exit();
		}

		echo '<div id="wprsync-popup" class="mfp-white-popup">';

		$parsed_exec = $this->parse_rsync_response( $exec );

		$files_conut = count( $parsed_exec['files'] );
		// translators: x files are/is ready to be synced
		$title = $data['name'];
		if ( $data['category'] && '' != $data['category'] ) {
			$title = $data['category'] . ': ' . $title;
		}

		$subtitle = str_replace( ABSPATH, '', $data['path'] );

		echo "<h3>$title</h3>";
		echo "<p class='_subtitle'><small>$subtitle</small></p>";

		if ( 0 == $files_conut ) {

			$message = __( 'All files are already up to date', 'wprsync' );
			echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
			//echo '<pre>' . print_r( $parsed_exec ) . '</pre>';

		} else {

			// translators: x file(s) are/is ready to be synced
			$message = sprintf( _n( '%s File is ready to be synced', '%s Files are ready to be synced', $files_conut, 'wprsync' ), $files_conut );
			echo '<div id="message" class="notice notice-info"><p>' . $message . '</p></div>';

			echo '<ul class="file-list">';
			foreach ( $parsed_exec['files'] as $key => $file ) {
				echo '<li id="' . $key . '">';
				echo $file['file'];
				echo '<span class="_add">';
				echo $file['add'];
				echo '</span>';
				echo '</li>';
			}
			echo '</ul>';

			echo '<button id="sync-now" class="button button-primary">' . __( 'Sync now', 'wprsync' ) . '</button>';

			echo '<p>';
			echo '<a id="toggle_exec">';
			echo '<span class="_show">' . __( 'show plain answer', 'wprsync' ) . '</span>';
			echo '<span class="_hide" style="display: none;">' . __( 'hide plain answer', 'wprsync' ) . '</span>';
			echo '</a>';
			echo '</p>';

			echo '<div class="plain-answer" style="display: none;">';
			echo '<span class="_command"><b>' . __( 'cmd', 'wprsync' ) . ':</b><code>' . $cmd . '</code></span>';
			echo '<span class="_answer"><b>' . __( 'answer', 'wprsync' ) . ':</b><code>';
			echo nl2br( $parsed_exec['resp'] );
			echo '</code></span>';
			echo '</div>';

			echo '<div class="loading" style="display: none;"></div>';
		} // End if().

		echo '</div> ';
		exit();
	}

	public function do_sync() {

		$data = shortcode_atts( [
			'path'     => '',
			'version'  => false,
			'name'     => false,
			'category' => false,
		], $_POST );

		$errors       = [];
		$data['path'] = stripslashes( $data['path'] );

		// check path
		if ( ! is_dir( $data['path'] ) || ( strpos( ABSPATH, $data['path'] ) !== false && ABSPATH != $data['path'] ) ) {
			// translators: check if path is correct
			$errors[] = sprintf( __( 'The given path "%s" is not a directory or not inside the WordPress installation', 'wprsync' ), $data['path'] );
		}

		// check system
		if ( ! wprsync_check_rsync() || ! wprsync_check_phpexec() ) {
			$errors[] = __( 'Please check your system requirements. php shell_exec has to be enabled and rsync has to be installed.', 'wprsync' );
		}

		// check data
		$options = get_option( wprsync_get_instance()->Settings->settings_option );
		if ( '' == $options['dest'] ) {
			$errors[] = __( 'Please add a Destination', 'wprsync' );
		}

		if ( empty( $errors ) ) {

			$cmd = $this->get_cmd( $data['path'], false );

			if ( 'local' == $options['user'] || 'local' == $options['host'] ) {
				shell_exec( "mkdir -p $remote_path" );
			} else {
				shell_exec( "ssh {$options['user']}@{$options['host']} mkdir -p $remote_path" );
			}
			$exec = shell_exec( $cmd );
			if ( is_null( $exec ) ) {
				// translators: shell exec error
				$errors[] = sprintf( __( 'The following command caused an undefined error: "%s"', 'wprsync' ), $cmd );
			}
		}

		if ( ! empty( $errors ) ) {

			$answer = [
				'type'    => 'error',
				'message' => implode( '<br>', $errors ),
			];
			echo json_encode( $answer );
			exit();
		}

		$parsed_exec = $this->parse_rsync_response( $exec );
		$files_conut = count( $parsed_exec['files'] );

		// translators: x file(s) are/is sucessully synced
		$message = sprintf( _n( '%s File sucessully synced', '%s Files sucessully synced', $files_conut, 'wprsync' ), $files_conut );

		$this->add_latest_sync( $data['path'], $data['version'] );
		$new_lsync = $this->get_latest_sync( $data['path'] );

		$answer = [
			'type'        => 'success',
			'message'     => $message,
			'plain_exec'  => nl2br( $exec ),
			'parsed_exec' => $parsed_exec,
			'cmd'         => $cmd,
			'latest_sync' => [
				'date'    => date( 'd.m.Y H:i', $new_lsync['date'] ),
				'version' => $new_lsync['version'],
			],
		];
		echo json_encode( $answer );
		exit();

	}

	public function exclude_build_files( $excludes ) {
		$new_excludes = [
			'node_modules/***',
			'.*/***',
			'.gitignore',
		];

		return array_merge( $excludes, $new_excludes );
	}

	/**
	 * Helpers
	 */

	public function get_rsync_element( $path = '', $args = [] ) {

		if ( '' == $path || strpos( $path, ABSPATH ) === false ) {
			return '<tr><td colspan="420">' . __( 'Error: Path not correct', 'wprsync' ) . '</td></tr>';
		}

		$args = shortcode_atts( [
			'category' => '',
			'name'     => '',
			'add_info' => '',
			'version'  => '',
		], $args );

		$latest_sync = $this->get_latest_sync( $path );

		$enabled = true;
		if ( ! wprsync_check_rsync() || in_array( $path, $this->disabled_path ) ) {
			$enabled = false;
		}

		$html = '';
		$html .= '<tr class="' . ( $enabled ? '' : 'no-sync' ) . '">';

		$html .= '<td class="_about">';
		$html .= '<h4 class="_title">' . $args['name'] . '</h4>';
		$html .= '<span class="_additional-info">' . $args['add_info'] . '</span>';
		$html .= '</td>';

		$html .= '<td class="_version">';
		if ( '' != $args['version'] ) {
			if ( __( 'Uploads', 'wprsync' ) == $args['category'] ) {
				$version = $args['version'];
			} else {
				// translators: Version 1.0.0
				$version = sprintf( __( 'Version: %s', 'wprsync' ), $args['version'] );
			}
			if ( $latest_sync['version'] && $latest_sync['version'] != $args['version'] ) {
				$html .= "<b>$version</b>";
			} else {
				$html .= $version;
			}
		}
		$html .= '</td>';

		$html .= '<td class="_latest-sync">';
		if ( ! $latest_sync ) {
			$html .= __( 'Not yet synced', 'wprsync' );
		} else {
			$html .= '<span class="date">' . date( 'Y.m.d H:i', $latest_sync['date'] ) . '</span>';
			if ( $latest_sync['version'] ) {
				$html .= '<br>';
				if ( __( 'Uploads', 'wprsync' ) == $args['category'] ) {
					// translators: file count 100
					$html .= sprintf( __( 'File count: %s', 'wprsync' ), '<span class="version">' . $latest_sync['version'] . '</span>' );
				} else {
					// translators: Version 1.0.0
					$html .= sprintf( __( 'Version: %s', 'wprsync' ), '<span class="version">' . $latest_sync['version'] . '</span>' );
				}
			}
		}
		$html .= '</td>';

		$html .= '<td class="_sync">';
		if ( $enabled ) {
			$html .= '<button class="button" onclick="wprsync_do_sync(this,\'' . addslashes( $path ) . '\', \'' . $args['version'] . '\', \'' . $args['name'] . '\', \'' . $args['category'] . '\');">';
			$html .= '<span class="dashicons dashicons-update"></span> ' . __( 'sync', 'wprsync' );
			$html .= '</button>';
		}
		$html .= '</td>';
		$html .= '</tr>';

		return $html;

	}

	public function get_latest_sync( $path ) {
		$syncs = get_option( 'wprsync_latest_syncs' );
		if ( isset( $syncs[ $path ] ) ) {
			return $syncs[ $path ];
		}

		return false;
	}

	public function add_latest_sync( $path, $version = false ) {
		$syncs          = get_option( 'wprsync_latest_syncs' );
		$syncs[ $path ] = [
			'date'    => time(),
			'version' => $version,
		];
		update_option( 'wprsync_latest_syncs', $syncs );
	}

	public function parse_rsync_response( $resp ) {

		$resp_array = explode( "\n", $resp );
		//return $resp_array;
		$files        = [];
		$add_to_files = true;
		$i            = 0;
		foreach ( $resp_array as $key => $val ) {

			$poss_ext = [ 'php', 'html' ];
			$poss_ext = array_merge( $poss_ext, [ 'scss', 'css', 'js', 'json' ] );
			$poss_ext = array_merge( $poss_ext, [ 'svg', 'png', 'gif', 'jpg', 'jpeg', 'webp' ] );
			$poss_ext = array_merge( $poss_ext, [ 'woff', 'woff2', 'eot', 'otf', 'ttf' ] );
			$poss_ext = array_merge( $poss_ext, [ 'mp4', 'mov', 'avi', 'wmv', 'flv', 'webm' ] );
			$poss_ext = array_merge( $poss_ext, [ 'mp3', 'ogg', 'wma' ] );
			$poss_ext = apply_filters( 'wprsync_possible_file_extensions', $poss_ext );

			$ext = strtolower( end( explode( '.', $val ) ) );
			if ( ! in_array( $ext, $poss_ext ) ) {
				continue;
			}

			/*if ( './' == $val ) {
				$add_to_files = true;
				continue;
			}
			if ( '' == $val ) {
				$add_to_files = false;
				continue;
			}*/
			if ( $add_to_files && substr( $val, 0, 1 ) != ' ' ) {
				$i ++;
				$files[ md5( $val ) ] = [
					'file' => $val,
					'add'  => '',
				];
			}
		}

		return [
			'files' => $files,
			'resp'  => $resp,
		];
	}

	public function get_dir_files( $dir, $results = [] ) {

		$files = scandir( $dir );

		foreach ( $files as $key => $value ) {
			$path = realpath( $dir . DIRECTORY_SEPARATOR . $value );
			if ( ! is_dir( $path ) ) {
				$results[] = $path;
			} elseif ( '.' != $value && '..' != $value ) {
				$this->get_dir_files( $path, $results );
				$results[] = $path;
			}
		}

		return $results;
	}

	public function get_cmd( $path, $dry = true ) {

		if ( in_array( $path, $this->disabled_path ) ) {
			return false;
		}

		$options     = get_option( wprsync_get_instance()->Settings->settings_option );
		$remote_path = str_replace( ABSPATH, $options['dest'], $path );
		if ( 'local' == $options['user'] || 'local' == $options['host'] ) {
			$connection = $remote_path;
		} else {
			$connection = $options['user'] . '@' . $options['host'] . ':' . $remote_path;
		}
		$ex = '-av';
		if ( $dry ) {
			$ex = '-avn';
		}

		$args = [];

		if ( ABSPATH == $path ) {
			$args = array_merge( [
				"--exclude 'wp-config.php'",
				"--include 'index.php'",
				"--include 'license.txt'",
				"--include 'readme.html'",
				"--include 'wp-*.php'",
				"--include 'index.php'",
				"--include 'xmlrpc.php'",
				"--include 'wp-admin/***'",
				"--include 'wp-includes/***'",
				"--exclude '*'",
			], $args );
		} else {
			foreach ( $this->disabled_path as $disabled_path ) {
				$path_length = strlen( $path );
				if ( substr( $disabled_path, 0, $path_length ) == $path ) {
					$exclude = str_replace( $path, '', $disabled_path );
					$args[]  = "--exclude '{$exclude}***'";
				}
			}
		}

		$excludes = apply_filters( 'wprsync_excludes', $this->excluded );
		foreach ( $excludes as $exclude ) {
			$args[] = "--exclude '{$exclude}'";
		}

		if ( ! empty( $args ) ) {
			$args = implode( ' ', $args );

			return "rsync $ex $args $path $connection";
		}

		return "rsync $ex $path $connection";
	}
}
