<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Data;

use Monolog\Logger;

/**
 * A log event with predefined level set to NOTICE.
 *
 * @package wonolog
 */
final class Notice implements LogDataInterface {

	use LogDataTrait;

	/**
	 * @inheritdoc
	 */
	public function level() {

		return Logger::NOTICE;
	}
}
