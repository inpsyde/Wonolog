<?php # -*- coding: utf-8 -*-
/*
 * This file is part of theInpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey\WP\Actions;
use Brain\Monkey\WP\Filters;
use Inpsyde\Wonolog\FrontController;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class FrontControllerTest extends TestCase {

	public function test_boot_do_nothing_if_disabled() {

		Filters::expectApplied( 'wonolog.enable' )
			->andReturn( FALSE );

		Actions::expectFired( 'wonolog.register-listeners' )
			->never();

		$controller = new FrontController();
		$controller->setup();
	}
}
