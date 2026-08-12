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

namespace Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Dunning;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningCandidate;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningLevel;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Account\IsNotBlockedForDunning;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsNotDisputed;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsOpen;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\MinimumOpenAmount;

/**
 * Legal action referral rule.
 *
 * Business rules:
 * - The item must already be at Final Dunning level
 * - More than 14 days must have passed since the last dunning run
 * - The item must be open, not disputed, and have a remaining balance of at least 250
 * - The account must not already be blocked for dunning (it would already be in legal handling)
 */
class RequiresLegalActionReferral extends AbstractSpecification
{
    private const LEGAL_ACTION_GRACE_DAYS = 14;

    private const MINIMUM_LEGAL_ACTION_AMOUNT = 250.0;

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof DunningCandidate) {
            return false;
        }

        $itemSpec = (new IsOpen())
            ->and(new IsNotDisputed())
            ->and(new MinimumOpenAmount(self::MINIMUM_LEGAL_ACTION_AMOUNT));

        $accountSpec = new IsNotBlockedForDunning();

        $isAtFinalLevel = $candidate->openItem->getCurrentDunningLevel() === DunningLevel::FinalDunning;

        $lastRunDate = $candidate->openItem->getLastDunningDate();
        $overdueSinceLastDunningRun = $lastRunDate !== null
            && (int)$lastRunDate->diff($candidate->asOf)->format('%r%a') > self::LEGAL_ACTION_GRACE_DAYS;

        return $isAtFinalLevel
            && $overdueSinceLastDunningRun
            && $itemSpec->isSatisfiedBy($candidate->openItem)
            && $accountSpec->isSatisfiedBy($candidate->account);
    }
}
