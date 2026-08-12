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

namespace Phauthentic\Specification\Examples\ECommerce\Specifications\Time;

use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification for checking if a date is within a specific range
 */
class DateRange extends AbstractSpecification
{
    public function __construct(
        private string $startDate, // Y-m-d format
        private string $endDate    // Y-m-d format
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof \DateTimeImmutable) {
            return false;
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $this->startDate);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $this->endDate);

        if (!$start || !$end) {
            return false;
        }

        // Set end date to end of day
        $end = $end->setTime(23, 59, 59);

        return $candidate >= $start && $candidate <= $end;
    }
}
