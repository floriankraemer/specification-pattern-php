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

namespace Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Account;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\CustomerRiskClass;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\LedgerAccount;

/**
 * Satisfied when an account's risk class is one of the given classes.
 */
class RiskClass extends AbstractSpecification
{
    /**
     * @var array<CustomerRiskClass>
     */
    private array $riskClasses;

    public function __construct(CustomerRiskClass ...$riskClasses)
    {
        $this->riskClasses = $riskClasses;
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof LedgerAccount) {
            return false;
        }

        return in_array($candidate->riskClass, $this->riskClasses, true);
    }
}
