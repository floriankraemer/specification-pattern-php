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

namespace Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\OffenerPosten;

/**
 * Spezifikation zur Prüfung, ob der offene Restbetrag eines OffenerPostens einen Mindestwert erreicht.
 */
class MindestOffenerBetrag extends AbstractSpecification
{
    public function __construct(
        private float $mindestbetrag
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof OffenerPosten) {
            return false;
        }

        return $candidate->getOffenerBetrag()->betrag >= $this->mindestbetrag;
    }
}
