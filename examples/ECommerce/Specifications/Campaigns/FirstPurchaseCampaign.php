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

namespace Phauthentic\Specification\Examples\ECommerce\Specifications\Campaigns;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\ECommerce\Models\Order;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Customer\IsNewCustomer;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Order\MinimumOrderValue;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Order\IsFirstOrder;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Product\IsNotDigitalProduct;

/**
 * First Purchase Discount Campaign Specification
 *
 * Business Rules:
 * - New customer (account < 30 days)
 * - First order ever
 * - Minimum order value $50
 * - Excludes gift cards and digital products
 */
class FirstPurchaseCampaign extends AbstractSpecification
{
    private AbstractSpecification $specification;

    public function __construct()
    {
        // New customer (account created within 30 days)
        $newCustomerSpec = new IsNewCustomer(30);

        // First order
        $firstOrderSpec = new IsFirstOrder();

        // Minimum order value
        $minValueSpec = new MinimumOrderValue(50.0);

        // Excludes digital products and gift cards (handled at Order level)
        $notDigitalSpec = new IsNotDigitalProduct();

        // Combine specifications
        $this->specification = $newCustomerSpec
            ->and($firstOrderSpec)
            ->and($minValueSpec);
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        // Check if order contains digital products or gift cards
        if ($candidate->hasDigitalItems() || $candidate->hasGiftCards()) {
            return false;
        }

        return $this->specification->isSatisfiedBy($candidate);
    }
}
