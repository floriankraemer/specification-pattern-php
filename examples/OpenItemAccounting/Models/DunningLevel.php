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

namespace Phauthentic\Specification\Examples\OpenItemAccounting\Models;

/**
 * Escalation level of a dunning (collections/reminder) process, ordered by severity.
 */
enum DunningLevel: int
{
    case None = 0;
    case FriendlyReminder = 1;
    case FirstDunning = 2;
    case SecondDunning = 3;
    case FinalDunning = 4;
}
