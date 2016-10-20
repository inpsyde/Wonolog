<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

/**
 * We want to load this file just once. Being loaded by Composer autoload, and being in WordPress context,
 * we have to put special care on this.
 */
if ( defined( __NAMESPACE__ . '\\BOOTSTRAPPED' ) ) {
	return;
}

define( __NAMESPACE__ . '\\BOOTSTRAPPED', 1 );

/**
 * When WP is loaded, we can bootstrap on 'muplugins_loaded' or immediately if that hook was already fired
 *
 * @return bool
 */
function load_now_or_on_muplugins_loaded() {

	if ( ! function_exists( '\did_action' ) ) {
		return FALSE;
	}

	if ( did_action( 'muplugins_loaded' ) ) {
		FrontController::boot();

		return TRUE;
	}

	return add_action( 'muplugins_loaded', [ FrontController::class, 'boot' ], 20 );
}

/**
 * When WP is not fully loaded, but ABSPATH is available. If this is a WP 4.7+ install we can load `plugin.php`
 * where `add_action` is defined, so we can bootstrap at the first action available: "muplugins_loaded".
 * Version check is needed because in older versions to load plugin.php earlier will cause errors.
 *
 * @return bool
 */
function load_plugin_file_and_load() {

	if ( ! defined( 'ABSPATH' ) ) {
		return FALSE;
	}

	global $wp_version;
	$wp_version or @include ABSPATH . '/wp-includes/version.php';
	$wp_ver = $wp_version ? : '0.0';
	if ( version_compare( $wp_ver, '4.7' ) >= 0 ) {
		unset( $wp_ver );
		require_once ABSPATH . '/wp-includes/plugin.php';
		add_action( 'muplugins_loaded', [ FrontController::class, 'boot' ], 20 );

		return TRUE;
	}

	unset( $wp_ver );

	return FALSE;
}

/*
 * If any other thing failed, we can treat `$wp_filter` as an array of arrays. In 4.7+ this will work because WP will
 * normalize arrays into `WP_Hook` instances in `WP_Hook::build_preinitialized_hooks`.
 */
function define_global_filters_and_load() {

	global $wp_filter;
	is_array( $wp_filter ) or $wp_filter = [];
	array_key_exists( 'muplugins_loaded', $wp_filter ) or $wp_filter[ 'muplugins_loaded' ] = [];
	array_key_exists( 20, $wp_filter[ 'muplugins_loaded' ] ) or $wp_filter[ 'muplugins_loaded' ][ 20 ] = [];

	$wp_filter[ 'muplugins_loaded' ][ 20 ][ FrontController::class . '::boot' ] = [
		'function'      => [ FrontController::class, 'boot' ],
		'accepted_args' => 0,
	];

	return TRUE;
}

// let's bootstrap package, in a way || another...

load_now_or_on_muplugins_loaded() || load_plugin_file_and_load() || define_global_filters_and_load();
