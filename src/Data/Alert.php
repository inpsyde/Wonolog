<?php

namespace Inpsyde\Wonolog\Data;

use Monolog\Logger;

/**
 * A log event with predefined level set to ALERT.
 *
 * @package wonolog
 */
final class Alert implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @inheritdoc
	 */
	public function level() {

		return Logger::ALERT;
	}
}
