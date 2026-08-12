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

namespace Phauthentic\Specification\Examples\ECommerce\Specifications\Order;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\ECommerce\Models\Order;

/**
 * Specification for checking if an order contains products from specific categories
 */
class ContainsProductCategory extends AbstractSpecification
{
    /**
     * @param array<string> $requiredCategories
     */
    public function __construct(
        private array $requiredCategories
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        foreach ($this->requiredCategories as $category) {
            if ($candidate->containsCategory($category)) {
                return true;
            }
        }

        return false;
    }
}
