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
 * Evaluation context pairing an open item with the account it belongs to, as of a given date.
 * Lets dunning specifications reason about account-level and item-level rules together,
 * the same way the ECommerce example's Order carries its Customer.
 */
readonly class DunningCandidate
{
    public function __construct(
        public LedgerAccount $account,
        public OpenItem $openItem,
        public \DateTimeImmutable $asOf
    ) {
    }
}
