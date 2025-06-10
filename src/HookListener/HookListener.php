<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

interface HookListener
{
    /**
     * @return list<string>
     */
    public function listenTo(): array;
}
