<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Data;

/**
 * @package wonolog
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

		$this->message = (string) $message;
		$this->channel = (string) $channel;
		$this->context = $context;
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
