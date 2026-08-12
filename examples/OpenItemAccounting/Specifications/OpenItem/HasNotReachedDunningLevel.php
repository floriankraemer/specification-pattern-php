<?php

/**
 * Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Florian Krämer (https://florian-kraemer.net)
 * @author    Florian Krämer
 * @link      https://github.com/Phauthentic
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningLevel;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\OpenItem;

/**
 * Satisfied when an open item's current dunning level is below $level,
 * i.e. it is still eligible to be escalated to that level.
 */
class HasNotReachedDunningLevel extends AbstractSpecification
{
    public function __construct(
        private DunningLevel $level
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof OpenItem) {
            return false;
        }

        return $candidate->getCurrentDunningLevel()->value < $this->level->value;
    }
}
