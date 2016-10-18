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

// We want to load this file just once
if ( defined( __NAMESPACE__ . '\\BOOTSTRAPPED' ) ) {
	return;
}

define( __NAMESPACE__ . '\\BOOTSTRAPPED', 1 );

// WP is loaded, we can bootstrap on 'muplugins_loaded' or immediately if that hook was already fired
if ( function_exists( '\did_action' ) ) {
	
	if ( did_action( 'muplugins_loaded' ) ) {
		FrontController::boot();

		return;
	}

	add_action( 'muplugins_loaded', [ FrontController::class, 'boot' ], 0, 11 );

	return;
}

// WP is not fully loaded, but ABSPATH is available and this is a WP 4.7+ install: we can load 'wp-includes/plugin.php'
// where `add_action` is defined, so we can bootstrap at the first action available: "muplugins_loaded".
// Version check is needed because in older versions to load plugin.php earlier will cause errors.
if (
	defined( 'ABSPATH' )
	&& ! empty( $GLOBALS[ 'wp_version' ] )
	&& version_compare( $GLOBALS[ 'wp_version' ], '4.7' ) >= 0
) {
	require_once ABSPATH . '/wp-includes/plugin.php';
	add_action( 'muplugins_loaded', [ FrontController::class, 'boot' ], 0, 11 );

	return;
}

// Ok, we can treat `$wp_filter` as an array of arrays. In 4.7+ this will work because WP will normalize arrays
// into WP_Hook instances.

global $wp_filter;
is_array( $wp_filter ) or $wp_filter = [];
array_key_exists( 'muplugins_loaded', $wp_filter ) or $wp_filter[ 'muplugins_loaded' ] = [];
array_key_exists( 0, $wp_filter[ 'muplugins_loaded' ] ) or $wp_filter[ 'muplugins_loaded' ][ 11 ] = [];

$wp_filter[ 'muplugins_loaded' ][ 0 ][ FrontController::class . '::boot' ] = [
	'function'      => [ FrontController::class, 'boot' ],
	'accepted_args' => 0,
];
