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
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\CustomerRiskClass;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningCandidate;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningLevel;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Account\IsNotBlockedForDunning;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\HasNotReachedDunningLevel;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsNotDisputed;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsOpen;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsOverdue;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\MinimumOpenAmount;

/**
 * Dunning-level escalation rule.
 *
 * Business rules:
 * - The item must still be open and not disputed
 * - The item must not already be at or above the target level
 * - The remaining balance must meet the level's minimum amount
 * - The item must be overdue by more than the level's grace period
 * - Preferred customers get 14 extra grace days; high-risk customers get 7 fewer
 * - The account must not be blocked for dunning
 *
 * One parameterized class replaces four near-identical per-level classes.
 */
class DunningLevelEligibility extends AbstractSpecification
{
    public function __construct(
        private DunningLevel $targetLevel,
        private int $graceDays,
        private float $minimumAmount
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof DunningCandidate) {
            return false;
        }

        $riskAdjustment = match ($candidate->account->riskClass) {
            CustomerRiskClass::Preferred => 14,
            CustomerRiskClass::HighRisk => -7,
            default => 0,
        };

        $itemSpec = (new IsOpen())
            ->and(new IsNotDisputed())
            ->and(new HasNotReachedDunningLevel($this->targetLevel))
            ->and(new MinimumOpenAmount($this->minimumAmount))
            ->and(new IsOverdue($candidate->asOf, $this->graceDays + $riskAdjustment));

        $accountSpec = new IsNotBlockedForDunning();

        return $itemSpec->isSatisfiedBy($candidate->openItem) && $accountSpec->isSatisfiedBy($candidate->account);
    }
}
