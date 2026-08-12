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
 * Order entity representing an order in the e-commerce system
 */
readonly class Order
{
    /**
     * @param array<Product> $items
     */
    public function __construct(
        public string $id,
        public Customer $customer,
        public array $items,
        public float $totalAmount,
        public int $itemCount,
        public \DateTimeImmutable $createdAt,
        public bool $isFirstOrder
    ) {
    }

    /**
     * @return array<string, int> Category counts
     */
    public function getCategoryCounts(): array
    {
        $counts = [];
        foreach ($this->items as $product) {
            $counts[$product->category] = ($counts[$product->category] ?? 0) + 1;
        }
        return $counts;
    }

    public function containsCategory(string $category): bool
    {
        foreach ($this->items as $product) {
            if ($product->category === $category) {
                return true;
            }
        }
        return false;
    }

    public function hasClearanceItems(): bool
    {
        foreach ($this->items as $product) {
            if ($product->isClearance) {
                return true;
            }
        }
        return false;
    }

    public function hasDigitalItems(): bool
    {
        foreach ($this->items as $product) {
            if ($product->isDigital) {
                return true;
            }
        }
        return false;
    }

    public function hasGiftCards(): bool
    {
        foreach ($this->items as $product) {
            if ($product->isGiftCard()) {
                return true;
            }
        }
        return false;
    }
}
