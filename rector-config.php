<?php

declare(strict_types=1);

use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

$buildConfig = require __DIR__ . '/rector-config-common.php';

return $buildConfig([
    SetList::TYPE_DECLARATION,
    SetList::TYPE_DECLARATION_DOCBLOCKS,
    PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    PHPUnitSetList::PHPUNIT_NARROW_ASSERTS,
    PHPUnitSetList::PHPUNIT_MOCK_TO_STUB,
]);
