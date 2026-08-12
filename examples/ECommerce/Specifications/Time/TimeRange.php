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
 * Specification for checking if a time is within a specific range (flash sale hours)
 */
class TimeRange extends AbstractSpecification
{
    public function __construct(
        private string $startTime, // H:i format (e.g., "09:00")
        private string $endTime    // H:i format (e.g., "17:00")
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof \DateTimeImmutable) {
            return false;
        }

        $currentTime = $candidate->format('H:i');
        $startTime = $this->startTime;
        $endTime = $this->endTime;

        // Handle time ranges that span midnight (e.g., 22:00 to 02:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }
}
