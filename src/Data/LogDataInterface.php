<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Data;

/**
 * @package wonolog
 */
interface LogDataInterface {

	const MESSAGE = 'message';
	const LEVEL = 'level';
	const CHANNEL = 'channel';
	const CONTEXT = 'context';

	/**
	 * @return int
	 */
	public function level();

	/**
	 * @return string
	 */
	public function message();

	/**
	 * @return string
	 */
	public function channel();

	/**
	 * @return array
	 */
	public function context();
}
