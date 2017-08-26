<?php

namespace nicomartin\WPrsync;

class Sync {

	public function __construct() {
	}

	public function run() {
		add_action( 'wprsync_menupage', [ $this, 'list_themes' ] );
		add_action( 'wprsync_menupage', [ $this, 'list_plugins' ] );
		add_action( 'wp_ajax_wprsync_ajax_sync', [ $this, 'sync' ] );
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
					$path    = $themes_folder . $folder . '/';
					$theme   = wp_get_theme( $folder );
					$enabled = true;
					if ( ! wprsync_check_rsync() ) {
						$enabled = false;
					}
					?>
					<tr class="<?php echo( $enabled ? '' : 'no-sync' ); ?>">
						<td class="_about">
							<h4 class="_title"><?php echo $theme->get( 'Name' ); ?></h4>
							<?php

							$author     = $theme->get( 'Author' );
							$author_uri = $theme->get( 'AuthorURI' );
							if ( '' != $author_uri ) {
								$author = '<a target="_blank" href="' . $author_uri . '">' . $author . '</a>';
							}
							echo '<span class="_author">';
							// translators: theme created by __
							printf( __( 'By %s', 'wprsync' ), $author );
							echo '</span>';
							?>
						</td>
						<td class="_version">
							<?php
							// translators: Version 1.0.0
							printf( __( 'Version: %s', 'wprsync' ), $theme->get( 'Version' ) );
							?>
						</td>
						<td class="_latest-sync">
							<?php
							$latest_sync = $this->get_latest_sync( $path );
							if ( ! $latest_sync ) {
								_e( 'Not yet synced', 'wprsync' );
							} else {
								echo date( 'Y.m.d H:i', $latest_sync['date'] );
								if ( $latest_sync['version'] ) {
									echo '<br>';
									// translators: Version 1.0.0
									printf( __( 'Version: %s', 'wprsync' ), $latest_sync['version'] );
								}
							}
							?>
						</td>
						<td class="_sync">
							<?php if ( $enabled ) { ?>
								<button class="button" onclick="wprsync_do_sync(this,'<?php echo addslashes( $path ); ?>', '<?php echo $theme->get( 'Version' ); ?>');">
									<span class="dashicons dashicons-update"></span> <?php _e( 'sync', 'wprsync' ); ?>
								</button>
							<?php } ?>
						</td>
					</tr>
					<?php
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
					$folder  = explode( '/', $file )[0];
					$path    = $plugins_folder . $folder . '/';
					$enabled = true;
					if ( ! wprsync_check_rsync() ) {
						$enabled = false;
					}
					if ( 'wp-rsync' == $folder ) {
						$enabled = false;
					}
					?>
					<tr class="<?php echo( $enabled ? '' : 'no-sync' ); ?>">
						<td class="_about">
							<h4 class="_title"><?php echo $val['Name']; ?></h4>
							<?php

							$author     = $val['Author'];
							$author_uri = $val['AuthorURI'];
							if ( '' != $author_uri ) {
								$author = '<a target="_blank" href="' . $author_uri . '">' . $author . '</a>';
							}
							echo '<span class="_author">';
							// translators: theme created by __
							printf( __( 'By %s', 'wprsync' ), $author );
							echo '</span>';
							?>
						</td>
						<td class="_version">
							<?php
							// translators: Version 1.0.0
							printf( __( 'Version: %s', 'wprsync' ), $val['Version'] );
							?>
						</td>
						<td class="_latest-sync">
							<?php
							$latest_sync = $this->get_latest_sync( $path );
							if ( ! $latest_sync ) {
								_e( 'Not yet synced', 'wprsync' );
							} else {
								echo date( 'Y.m.d H:i', $latest_sync['date'] );
								if ( $latest_sync['version'] ) {
									echo '<br>';
									// translators: Version 1.0.0
									printf( __( 'Version: %s', 'wprsync' ), $latest_sync['version'] );
								}
							}
							?>
						</td>
						<td class="_sync">
							<?php if ( $enabled ) { ?>
								<button class="button" onclick="wprsync_do_sync(this,'<?php echo addslashes( $path ); ?>', '<?php echo $val['Version']; ?>');">
									<span class="dashicons dashicons-update"></span> <?php _e( 'sync', 'wprsync' ); ?>
								</button>
							<?php } ?>
						</td>
					</tr>
					<?php
				} // End foreach().
				?>
			</table>
		</div>
		<?php
	}

	public function sync() {

		$data = shortcode_atts( [
			'path'    => '',
			'version' => false,
			'force'   => false,
		], $_GET );

		$errors       = [];
		$data['path'] = stripslashes( $data['path'] );

		// check path
		if ( ! is_dir( $data['path'] ) || strpos( ABSPATH, $data['path'] ) !== false ) {
			// translators: check if path is correct
			$errors[] = sprintf( __( 'The given path "%s" is not a directory or not inside the WordPress installation', 'wprsync' ), $data['path'] );
		}

		// check system
		if ( ! wprsync_check_rsync() || ! wprsync_check_phpexec() ) {
			$errors[] = __( 'Please check your system requirements. php shell_exec has to be enabled and rsync has to be installed.', 'wprsync' );
		}

		// check data
		$options = get_option( wprsync_get_instance()->Settings->settings_option );
		if ( '' == $options['user'] || '' == $options['host'] || '' == $options['dest'] ) {
			$errors[] = __( 'Please check your Settings. Some seem to be missing.', 'wprsync' );
		}

		if ( empty( $errors ) ) {

			$path        = $data['path'];
			$remote_path = str_replace( ABSPATH, $options['dest'], $path );
			$connection  = $options['user'] . '@' . $options['host'] . ':' . $remote_path;
			if ( $data['force'] ) {
				$cmd = "rsync -avP $path $connection";
			} else {
				$cmd = "rsync -avPn $path $connection";
			}
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

		$exec_array   = explode( "\n", $exec );
		$files        = [];
		$add_to_files = false;
		foreach ( $exec_array as $key => $val ) {
			if ( './' == $val ) {
				$add_to_files = true;
				continue;
			}
			if ( '' == $val ) {
				$add_to_files = false;
				continue;
			}
			if ( $add_to_files && substr( $val, 0, 1 ) != ' ' ) {
				$files[] = $val;
			}
		}

		$files_conut = count( $files );
		if ( ! $data['force'] ) {
			// translators: X Files are ready to be synced
			$title = sprintf( _n( '%s File is ready to be synced', '%s Files are ready to be synced', $files_conut, 'wprsync' ), $files_conut );
		} else {
			// translators: X Files have been synced
			$title = sprintf( _n( '%s File has been synced', '%s Files have been synced', $files_conut, 'wprsync' ), $files_conut );
		}
		echo "<h3>$title</h3>";
		echo '<textarea style="width: 100%; height: 150px;">';
		foreach ( $files as $file ) {
			echo "$file\n";
		}
		echo '</textarea>';
		if ( ! $data['force'] && 0 != $files_conut ) {
			echo '<button id="sync-now" class="button button-primary">' . __( 'Sync now', 'wprsync' ) . '</button>';
		}
		if ( $data['force'] ) {
			$this->add_latest_sync( $data['path'], $data['version'] );
			$new_lsync = $this->get_latest_sync( $data['path'] );
			echo '<span style="display:none;" id="new-latest-sync-date">' . date( 'd.m.Y H:i', $new_lsync['date'] ) . ' </span>';
			echo '<span style="display:none;" id="new-latest-sync-version">';
			if ( $new_lsync['version'] ) {
				// translators: Version 1.0.0
				printf( __( 'Version: %s', 'wprsync' ), $new_lsync['version'] );
			}
			echo '</span>';
		}
		echo '</div> ';
		exit();
	}

	/**
	 * Helpers
	 */

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
}