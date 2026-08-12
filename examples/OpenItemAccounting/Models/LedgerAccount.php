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
 * Aggregate root for a customer's ledger: an append-only stream of Postings and the
 * OpenItems derived from them. All state changes to open items are routed through this
 * aggregate so its invariants (e.g. an item can only be cleared by a posting that
 * references it) always hold.
 */
final class LedgerAccount
{
    /** @var Posting[] */
    private array $postings = [];

    /** @var array<string, OpenItem> */
    private array $openItems = [];

    private bool $blockedForDunning = false;

    public function __construct(
        public readonly string $accountNumber,
        public readonly string $customerName,
        public readonly CustomerRiskClass $riskClass,
        public readonly string $currency
    ) {
    }

    /**
     * @return Posting[]
     */
    public function getPostings(): array
    {
        return $this->postings;
    }

    /**
     * @return OpenItem[]
     */
    public function getOpenItems(): array
    {
        return array_values($this->openItems);
    }

    public function isBlockedForDunning(): bool
    {
        return $this->blockedForDunning;
    }

    public function block(): void
    {
        $this->blockedForDunning = true;
    }

    public function unblock(): void
    {
        $this->blockedForDunning = false;
    }

    public function post(Posting $posting): void
    {
        if ($posting->amount->currency !== $this->currency) {
            throw new \RuntimeException(
                "Posting currency {$posting->amount->currency} does not match account currency {$this->currency}."
            );
        }

        $this->postings[] = $posting;

        match ($posting->type) {
            PostingType::Invoice => $this->openItems[$posting->id] = new OpenItem(
                $posting->id,
                $posting->id,
                $posting->dueDate ?? throw new \RuntimeException('Invoice posting is missing a due date.'),
                $posting->amount
            ),
            PostingType::Payment, PostingType::CreditMemo => $this->requireOpenItem(
                $posting->referenceOpenItemId ?? throw new \RuntimeException('Posting is missing a reference open item.')
            )->applyPayment($posting->amount),
            PostingType::WriteOff => $this->requireOpenItem(
                $posting->referenceOpenItemId ?? throw new \RuntimeException('Posting is missing a reference open item.')
            )->writeOff(),
        };
    }

    public function disputeOpenItem(string $openItemId, bool $disputed): void
    {
        $this->requireOpenItem($openItemId)->markDisputed($disputed);
    }

    public function escalateDunning(string $openItemId, DunningLevel $level, \DateTimeImmutable $runDate): void
    {
        $this->requireOpenItem($openItemId)->escalateDunning($level, $runDate);
    }

    private function requireOpenItem(string $openItemId): OpenItem
    {
        return $this->openItems[$openItemId]
            ?? throw new \RuntimeException("No open item '{$openItemId}' on account {$this->accountNumber}.");
    }
}
