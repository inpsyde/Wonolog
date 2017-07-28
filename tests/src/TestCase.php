<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Inpsyde\Wonolog\Tests;

use PHPUnit_Framework_TestCase;
use Brain\Monkey;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class TestCase extends PHPUnit_Framework_TestCase {

	protected function setUp() {

		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown() {

		Monkey\tearDown();
		parent::tearDown();
	}
}
