<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
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
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 */
class TestCase extends PHPUnit_Framework_TestCase {

	protected function setUp() {

		parent::setUp();
		Monkey::setUpWP();
	}

	protected function tearDown() {

		Monkey::tearDownWP();
		parent::tearDown();
	}
}
