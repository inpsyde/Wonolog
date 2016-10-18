<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
trait LogDataTrait {

	/**
	 * @var string
	 */
	private $message;

	/**
	 * @var string
	 */
	private $channel;

	/**
	 * @var array
	 */
	private $context;

	/**
	 * @param string $message
	 * @param string $channel
	 * @param array  $context
	 */
	public function __construct( $message, $channel, array $context = [] ) {

		$this->message = $message;
		$this->context = $context;
		$this->channel = $channel;
	}

	/**
	 * @return array
	 */
	public function context() {

		return $this->context;
	}

	/**
	 * @return string
	 */
	public function message() {

		return $this->message;
	}

	/**
	 * @return string
	 */
	public function channel() {

		return $this->channel;
	}
}
