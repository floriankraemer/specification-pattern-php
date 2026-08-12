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

namespace Phauthentic\Specification\Examples\ECommerce\Models;

use Phauthentic\Specification\SpecificationInterface;

/**
 * Promotion entity representing a promotional campaign
 */
readonly class Promotion
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $discountPercentage,
        public SpecificationInterface $eligibilitySpecification
    ) {
    }

    public function isEligible(mixed $candidate): bool
    {
        return $this->eligibilitySpecification->isSatisfiedBy($candidate);
    }

    public function calculateDiscount(float $originalAmount): float
    {
        return $originalAmount * ($this->discountPercentage / 100);
    }

    public function calculateFinalPrice(float $originalAmount): float
    {
        return $originalAmount - $this->calculateDiscount($originalAmount);
    }
}
