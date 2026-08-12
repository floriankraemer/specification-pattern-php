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

namespace Phauthentic\Specification\Examples\ECommerce\Specifications\Customer;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\ECommerce\Models\Customer;
use Phauthentic\Specification\Examples\ECommerce\Models\Order;

/**
 * Specification for checking if a customer has VIP tier status
 */
class IsVipCustomer extends AbstractSpecification
{
    /**
     * @param array<string> $vipTiers
     */
    public function __construct(
        private array $vipTiers = ['gold', 'platinum']
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        $customer = $this->getCustomerFromCandidate($candidate);
        if (!$customer) {
            return false;
        }

        return in_array($customer->tier, $this->vipTiers, true);
    }

    private function getCustomerFromCandidate(mixed $candidate): ?Customer
    {
        if ($candidate instanceof Customer) {
            return $candidate;
        }

        if ($candidate instanceof Order) {
            return $candidate->customer;
        }

        return null;
    }
}
