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
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnstufe;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\OffenerPosten;

/**
 * Erfüllt, wenn die aktuelle Mahnstufe eines OffenerPostens unter $stufe liegt, d. h. er
 * noch auf diese Stufe eskaliert werden kann.
 */
class HatMahnstufeNichtErreicht extends AbstractSpecification
{
    public function __construct(
        private Mahnstufe $stufe
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof OffenerPosten) {
            return false;
        }

        return $candidate->getAktuelleMahnstufe()->value < $this->stufe->value;
    }
}
