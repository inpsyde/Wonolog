<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

putenv( 'TESTS_PATH=' . __DIR__ );
putenv( 'LIBRARY_PATH=' . dirname( __DIR__ ) );

$vendor = dirname( dirname( __FILE__ ) ) . '/vendor/';

if ( ! realpath( $vendor ) ) {
	die( 'Please install via Composer before running tests.' );
}

if ( ! defined( 'PHPUNIT_COMPOSER_INSTALL' ) ) {
	define( 'PHPUNIT_COMPOSER_INSTALL', $vendor . 'autoload.php' );
}

/**
 * Make sure strict standards are reported
 * @link https://github.com/inpsyde/wonolog/issues/4
 */
error_reporting( E_ALL );

require_once $vendor . '/antecedent/patchwork/Patchwork.php';
require_once $vendor . 'autoload.php';

unset( $vendor );