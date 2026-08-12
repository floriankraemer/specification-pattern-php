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
 * An immutable, append-only ledger entry on a LedgerAccount.
 */
readonly class Posting
{
    public function __construct(
        public string $id,
        public PostingType $type,
        public Money $amount,
        public \DateTimeImmutable $postingDate,
        public string $description,
        /** Only set for PostingType::Invoice postings. */
        public ?\DateTimeImmutable $dueDate = null,
        /** The open item this posting clears or writes off. Not set for invoices. */
        public ?string $referenceOpenItemId = null
    ) {
        if ($type === PostingType::Invoice && $dueDate === null) {
            throw new \InvalidArgumentException('Invoice postings require a due date.');
        }

        if ($type !== PostingType::Invoice && $referenceOpenItemId === null) {
            throw new \InvalidArgumentException("{$type->name} postings require a reference open item.");
        }
    }
}
