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