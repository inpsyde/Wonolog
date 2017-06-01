<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {

		if ( strpos( $path, 'vfs://' ) == 0 ) {
			return $path;
		}

		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}

		return $path;
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $path ) {

		return rtrim( $path, '\\/' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $path ) {

		return untrailingslashit( $path ) . DIRECTORY_SEPARATOR;
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $path ) {

		return is_dir( $path ) || mkdir( $path, 0777, TRUE );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {

		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {

		return FALSE;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {

		return 123;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {

		return 123;
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() {

		return FALSE;
	}
}

if ( ! function_exists( 'ms_is_switched' ) ) {
	function ms_is_switched() {

		return FALSE;
	}
}

if ( ! function_exists( 'get_current_blog_id' ) ) {
	function get_current_blog_id() {

		return 1;
	}
}

if ( ! function_exists( 'get_current_network_id' ) ) {
	function get_current_network_id() {

		return 1;
	}
}

if ( ! function_exists( 'get_rest_url' ) ) {
	function get_rest_url() {

		return 'https://example-com/wp-rest';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $query ) {

		$q = http_build_query( $query );

		return $q ? 'https://example-com?' . $q : 'https://example-com/';
	}
}

if ( ! function_exists( 'set_url_scheme' ) ) {
	function set_url_scheme( $str ) {

		return str_replace( 'http://',  'https://', $str );
	}
}



