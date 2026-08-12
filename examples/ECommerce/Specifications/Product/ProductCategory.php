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

namespace Phauthentic\Specification\Examples\ECommerce\Specifications\Product;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\ECommerce\Models\Product;

/**
 * Specification for checking if a product belongs to specific categories
 */
class ProductCategory extends AbstractSpecification
{
    /**
     * @param array<string> $allowedCategories
     */
    public function __construct(
        private array $allowedCategories
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Product) {
            return false;
        }

        return in_array($candidate->category, $this->allowedCategories, true);
    }
}
