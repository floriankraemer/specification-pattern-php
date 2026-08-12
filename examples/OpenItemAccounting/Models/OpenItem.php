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
 * A not-yet-settled invoice tracked on a LedgerAccount.
 *
 * Mutations are only ever meant to be triggered by the owning LedgerAccount — every
 * mutator below is marked @internal for that reason. PHP has no `internal` keyword,
 * so this boundary is enforced by convention, not by the compiler.
 */
final class OpenItem
{
    private Money $openAmount;

    private OpenItemStatus $status;

    private DunningLevel $currentDunningLevel;

    private ?\DateTimeImmutable $lastDunningDate = null;

    private bool $disputed = false;

    /**
     * @internal Only LedgerAccount may construct an OpenItem.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $invoicePostingId,
        public readonly \DateTimeImmutable $dueDate,
        public readonly Money $originalAmount
    ) {
        $this->openAmount = $originalAmount;
        $this->status = OpenItemStatus::Open;
        $this->currentDunningLevel = DunningLevel::None;
    }

    public function getOpenAmount(): Money
    {
        return $this->openAmount;
    }

    public function getStatus(): OpenItemStatus
    {
        return $this->status;
    }

    public function getCurrentDunningLevel(): DunningLevel
    {
        return $this->currentDunningLevel;
    }

    public function getLastDunningDate(): ?\DateTimeImmutable
    {
        return $this->lastDunningDate;
    }

    public function isDisputed(): bool
    {
        return $this->disputed;
    }

    /**
     * @internal Only call via LedgerAccount::post().
     */
    public function applyPayment(Money $amount): void
    {
        $this->openAmount = $this->openAmount->subtract($amount);

        if ($this->openAmount->amount <= 0) {
            $this->status = OpenItemStatus::Cleared;
        }
    }

    /**
     * @internal Only call via LedgerAccount::post().
     */
    public function writeOff(): void
    {
        $this->status = OpenItemStatus::WrittenOff;
    }

    /**
     * @internal Only call via LedgerAccount::disputeOpenItem().
     */
    public function markDisputed(bool $disputed): void
    {
        $this->disputed = $disputed;
    }

    /**
     * @internal Only call via LedgerAccount::escalateDunning().
     */
    public function escalateDunning(DunningLevel $level, \DateTimeImmutable $runDate): void
    {
        $this->currentDunningLevel = $level;
        $this->lastDunningDate = $runDate;
    }

    public function daysOverdue(\DateTimeImmutable $asOf): int
    {
        $days = (int)$this->dueDate->diff($asOf)->format('%r%a');

        return max(0, $days);
    }
}
