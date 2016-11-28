<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Processors;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @author  David Naber <kontakt@dnaber.de>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpContextProcessor {

	/**
	 * @var int
	 */
	private $site_id;

	/**
	 * @var bool
	 */
	private $is_rest_request;

	/**
	 * @param int $site_id ID of the initializing WP site
	 */
	public function __construct( $site_id ) {

		$this->site_id = (int) $site_id;
	}

	/**
	 * @param array $record The complete log record containing 'message', 'context'
	 *                      'level', 'level_name', 'channel', 'datetime' and 'extra'
	 *
	 * @return array
	 */
	public function __invoke( array $record ) {

		$record[ 'extra' ][ 'wp' ] = [
			'doing_cron' => defined( 'DOING_CRON' ) && DOING_CRON,
			'doing_ajax' => defined( 'DOING_AJAX' ) && DOING_AJAX,
			'doing_rest' => $this->doing_rest(),
			'is_admin'   => is_admin(),
		];

		if ( did_action( 'init' ) ) {
			$record[ 'extra' ][ 'wp' ][ 'user_id' ] = get_current_user_id();
		}

		if ( is_multisite() ) {
			$record[ 'extra' ][ 'wp' ][ 'ms_switched' ] = ms_is_switched();
			$record[ 'extra' ][ 'wp' ][ 'site_id' ]     = get_current_blog_id();
			$record[ 'extra' ][ 'wp' ][ 'network_id' ]  = get_current_network_id();
		}

		return $record;
	}

	/**
	 * @return bool
	 */
	private function doing_rest() {

		if ( isset( $this->is_rest_request ) ) {
			return $this->is_rest_request;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$this->is_rest_request = TRUE;

			return TRUE;
		}

		$home_url_path   = rtrim( parse_url( get_home_url( $this->site_id, '/' ), PHP_URL_PATH ), '/' );
		$rest_url_prefix = rest_get_url_prefix();

		$this->is_rest_request = 0 === strpos( add_query_arg( [] ), "{$home_url_path}/{$rest_url_prefix}/" );

		return $this->is_rest_request;
	}
}