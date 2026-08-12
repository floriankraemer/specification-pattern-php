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

namespace Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle;

/**
 * Bewertungskontext, der einen OffenerPosten mit dem zugehörigen Konto zu einem bestimmten
 * Stichtag verbindet. Ermöglicht es Mahnspezifikationen, kontoweite und postenspezifische
 * Regeln gemeinsam zu betrachten, so wie der Order im ECommerce-Beispiel seinen Customer
 * mitführt.
 */
readonly class Mahnkandidat
{
    public function __construct(
        public Hauptbuchkonto $konto,
        public OffenerPosten $offenerPosten,
        public \DateTimeImmutable $stichtag
    ) {
    }
}
