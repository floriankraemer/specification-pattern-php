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
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Account\RiskClass;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsNotDisputed;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsOpen;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\IsOverdue;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\OpenItem\MinimumOpenAmount;

/**
 * Dunning interest accrual rule.
 *
 * Business rules:
 * - The item must already be at First Dunning level or beyond
 * - The item must be open, not disputed, and overdue by more than 45 days
 * - The remaining balance must be at least 100
 * - Preferred customers are exempt from interest, regardless of the above
 * - The account must not be blocked for dunning
 */
class AccruesDunningInterest extends AbstractSpecification
{
    private const INTEREST_GRACE_DAYS = 45;

    private const MINIMUM_INTEREST_AMOUNT = 100.0;

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof DunningCandidate) {
            return false;
        }

        $itemSpec = (new IsOpen())
            ->and(new IsNotDisputed())
            ->and(new MinimumOpenAmount(self::MINIMUM_INTEREST_AMOUNT))
            ->and(new IsOverdue($candidate->asOf, self::INTEREST_GRACE_DAYS));

        $accountSpec = (new IsNotBlockedForDunning())
            ->andNot(new RiskClass(CustomerRiskClass::Preferred));

        return $candidate->openItem->getCurrentDunningLevel()->value >= DunningLevel::FirstDunning->value
            && $itemSpec->isSatisfiedBy($candidate->openItem)
            && $accountSpec->isSatisfiedBy($candidate->account);
    }
}
