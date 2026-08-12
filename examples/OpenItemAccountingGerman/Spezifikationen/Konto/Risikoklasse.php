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

namespace Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Konto;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Hauptbuchkonto;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Kundenrisikoklasse;

/**
 * Erfüllt, wenn die Risikoklasse eines Kontos einer der angegebenen Klassen entspricht.
 */
class Risikoklasse extends AbstractSpecification
{
    /**
     * @var array<Kundenrisikoklasse>
     */
    private array $risikoklassen;

    public function __construct(Kundenrisikoklasse ...$risikoklassen)
    {
        $this->risikoklassen = $risikoklassen;
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Hauptbuchkonto) {
            return false;
        }

        return in_array($candidate->risikoklasse, $this->risikoklassen, true);
    }
}
