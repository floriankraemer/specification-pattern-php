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
use Phauthentic\Specification\Examples\ECommerce\Specifications\Customer\HasNotUsedFlashSaleRecently;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Order\ContainsProductCategory;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Time\TimeRange;

/**
 * Flash Sale Campaign Specification
 *
 * Business Rules:
 * - Time-sensitive (valid for specific hours, e.g., 12:00-14:00)
 * - Limited to specific product categories (electronics, fashion)
 * - Customer must not have used flash sale in last 7 days
 */
class FlashSaleCampaign extends AbstractSpecification
{
    private AbstractSpecification $specification;

    public function __construct()
    {
        // Customer hasn't used flash sale recently
        $flashSaleCooldownSpec = new HasNotUsedFlashSaleRecently(7);

        // Limited to electronics and fashion categories
        $categorySpec = new ContainsProductCategory(['electronics', 'fashion']);

        // Time range (e.g., noon to 2 PM)
        $timeRangeSpec = new TimeRange('12:00', '14:00');

        // Combine specifications
        $this->specification = $flashSaleCooldownSpec
            ->and($categorySpec)
            ->and($timeRangeSpec);
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        // Check customer and product specifications
        $customerProductSpec = (new HasNotUsedFlashSaleRecently(7))
            ->and(new ContainsProductCategory(['electronics', 'fashion']));

        // Check time range against order creation time
        $timeSpec = new TimeRange('12:00', '14:00');

        return $customerProductSpec->isSatisfiedBy($candidate) &&
               $timeSpec->isSatisfiedBy($candidate->createdAt);
    }
}
