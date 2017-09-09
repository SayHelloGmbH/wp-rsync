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

	if ( '' == $options['dest'] ) {

		$msg = __( 'Please insert your remote server setings below', 'wprsync' );

		return [
			'message' => $msg,
			'status'  => 'warning',
		];

	} else {

		$connection = $options['user'] . '@' . $options['host'] . ':' . $options['dest'];
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