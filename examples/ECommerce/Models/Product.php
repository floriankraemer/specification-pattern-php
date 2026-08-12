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

/**
 * Product entity representing a product in the e-commerce system
 */
readonly class Product
{
    public function __construct(
        public string $id,
        public string $name,
        public string $category, // electronics, fashion, home, books, etc.
        public float $price,
        public bool $isClearance,
        public bool $isDigital,
        public int $stockQuantity
    ) {
    }

    public function isInStock(): bool
    {
        return $this->stockQuantity > 0;
    }

    public function isGiftCard(): bool
    {
        return str_contains(strtolower($this->name), 'gift card') ||
               str_contains(strtolower($this->category), 'gift');
    }
}
