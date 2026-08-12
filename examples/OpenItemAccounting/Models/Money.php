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

namespace Phauthentic\Specification\Examples\OpenItemAccounting\Models;

/**
 * Value object representing a monetary amount in a specific currency.
 */
readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency
    ) {
    }

    public static function zero(string $currency): self
    {
        return new self(0.0, $currency);
    }

    public function add(self $other): self
    {
        $this->requireSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->requireSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function isLessThan(self $other): bool
    {
        $this->requireSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->requireSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function isLessThanOrEqualTo(self $other): bool
    {
        $this->requireSameCurrency($other);

        return $this->amount <= $other->amount;
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        $this->requireSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    private function requireSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \RuntimeException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->amount, $this->currency);
    }
}
