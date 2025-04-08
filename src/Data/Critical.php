<?php

namespace Inpsyde\Wonolog\Data;

use Monolog\Logger;

/**
 * A log event with predefined level set to CRITICAL.
 *
 * @package wonolog
 */
final class Critical implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @inheritdoc
	 */
	public function level() {

		return Logger::CRITICAL;
	}
}
