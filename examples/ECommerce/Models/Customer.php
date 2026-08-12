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
 * Customer entity representing a customer in the e-commerce system
 */
readonly class Customer
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $tier, // bronze, silver, gold, platinum
        public int $loyaltyPoints,
        public \DateTimeImmutable $accountCreatedAt,
        public ?\DateTimeImmutable $lastFlashSaleUsed,
        public bool $isNewsletterSubscriber
    ) {
    }

    public function getAccountAgeInDays(): int
    {
        return $this->accountCreatedAt->diff(new \DateTimeImmutable())->days;
    }

    public function daysSinceLastFlashSale(): ?int
    {
        if ($this->lastFlashSaleUsed === null) {
            return null;
        }

        return $this->lastFlashSaleUsed->diff(new \DateTimeImmutable())->days;
    }
}
