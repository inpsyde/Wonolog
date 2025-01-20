<?php

namespace Inpsyde\Wonolog\Data;

use Monolog\Logger;

/**
 * A log event with predefined level set to EMERGENCY.
 *
 * @package wonolog
 */
final class Emergency implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @inheritdoc
	 */
	public function level() {

		return Logger::EMERGENCY;
	}
}
