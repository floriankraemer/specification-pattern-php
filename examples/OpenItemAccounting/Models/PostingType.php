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
 * The kind of ledger entry being posted to an account.
 */
enum PostingType
{
    /** Creates a new open item (e.g. a customer invoice). */
    case Invoice;

    /** Fully or partially clears an existing open item. */
    case Payment;

    /** Reduces an existing open item without a cash payment. */
    case CreditMemo;

    /** Removes an open item from collections (bad debt). */
    case WriteOff;
}
