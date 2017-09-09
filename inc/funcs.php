<?php

function wprsync_check_phpexec() {

	if ( ! function_exists( 'shell_exec' ) ) {
		return false;
	}

	if ( shell_exec( 'echo EXEC' ) != "EXEC\n" ) {
		return false;
	}

	return true;
}

function wprsync_check_rsync() {

	if ( ! function_exists( 'shell_exec' ) ) {
		return false;
	}

	$exec = shell_exec( 'rsync --version' );
	if ( is_null( $exec ) ) {
		return false;
	}

	return true;
}

function wprsync_test_rsync() {

	if ( ! wprsync_check_rsync() || ! wprsync_check_phpexec() ) {
		return [
			'message' => __( 'Please check your system requirements', 'wprsync' ),
			'status'  => 'error',
		];
	}

	$msg = '';

	$options = get_option( wprsync_get_instance()->Settings->settings_option );

	if ( '' == $options['user'] || '' == $options['host'] || '' == $options['dest'] ) {

		$msg = __( 'Please insert your remote server setings below', 'wprsync' );

		return [
			'message' => $msg,
			'status'  => 'warning',
		];

	} else {

		if ( 'local' == $options['user'] || 'local' == $options['host'] ) {
			$connection = $options['dest'];
		} else {
			$connection = $options['user'] . '@' . $options['host'] . ':' . $options['dest'];
		}
		$exec = shell_exec( 'rsync --list-only ' . $connection );
		$stat = 'error';

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

function wprsync_get_uploads_subfolders() {

	$folders  = [];
	$base_dir = wp_upload_dir()['basedir'];
	$all_dirs = glob( $base_dir . '/*', GLOB_ONLYDIR );
	foreach ( $all_dirs as $subdir ) {
		$the_dir = substr( str_replace( $base_dir, '', $subdir ), 1 );
		if ( $the_dir > 1000 && $the_dir < 2200 ) {
			$all_sub_dirs = glob( $subdir . '/*', GLOB_ONLYDIR );
			foreach ( $all_sub_dirs as $subsubdir ) {
				$the_sub_dir = substr( str_replace( $base_dir, '', $subsubdir ), 1 );
				$folders[]   = [
					'name' => $the_sub_dir . '/',
					'path' => $base_dir . '/' . $the_sub_dir . '/',
				];
			}
		} else {
			$folders[] = [
				'name' => $the_dir . '/',
				'path' => $base_dir . '/' . $the_dir . '/',
			];
		}
	}

	return $folders;
}