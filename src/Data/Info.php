<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Data;

use Monolog\Logger;

/**
 * A log event with predefined level set to INFO.
 *
 * @package wonolog
 */
final class Info implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @inheritdoc
	 */
	public function level() {

		return Logger::INFO;
	}
}
