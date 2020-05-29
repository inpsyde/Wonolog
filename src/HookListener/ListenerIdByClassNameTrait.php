<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
trait ListenerIdByClassNameTrait
{
    /**
     * @return string
     */
    public function id(): string
    {
        return __CLASS__;
    }
}
