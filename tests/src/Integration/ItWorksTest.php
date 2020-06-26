<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Integration;

use Inpsyde\Wonolog\Tests\IntegrationTestCase;

class ItWorksTest extends IntegrationTestCase
{
    protected function bootstrap(): void
    {
        \Inpsyde\Wonolog\bootstrap();
    }

    public function testThisWorks()
    {
        static::assertFalse(false);
    }
}
