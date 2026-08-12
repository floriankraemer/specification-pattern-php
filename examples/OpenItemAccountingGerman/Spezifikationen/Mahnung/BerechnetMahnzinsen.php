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

namespace Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Mahnung;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Kundenrisikoklasse;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnkandidat;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnstufe;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Konto\IstNichtFuerMahnwesenGesperrt;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Konto\Risikoklasse;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstNichtStrittig;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstOffen;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstUeberfaellig;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\MindestOffenerBetrag;

/**
 * Regel für den Anfall von Mahnzinsen.
 *
 * Geschäftsregeln:
 * - Der Posten muss bereits mindestens die erste Mahnstufe erreicht haben
 * - Der Posten muss offen, nicht strittig und mehr als 45 Tage überfällig sein
 * - Der offene Restbetrag muss mindestens 100 betragen
 * - Bevorzugte Kunden sind unabhängig davon von Zinsen befreit
 * - Das Konto darf nicht für das Mahnwesen gesperrt sein
 */
class BerechnetMahnzinsen extends AbstractSpecification
{
    private const ZINS_KARENZTAGE = 45;

    private const MINDEST_ZINSBETRAG = 100.0;

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Mahnkandidat) {
            return false;
        }

        $postenSpezifikation = (new IstOffen())
            ->and(new IstNichtStrittig())
            ->and(new MindestOffenerBetrag(self::MINDEST_ZINSBETRAG))
            ->and(new IstUeberfaellig($candidate->stichtag, self::ZINS_KARENZTAGE));

        $kontoSpezifikation = (new IstNichtFuerMahnwesenGesperrt())
            ->andNot(new Risikoklasse(Kundenrisikoklasse::Bevorzugt));

        return $candidate->offenerPosten->getAktuelleMahnstufe()->value >= Mahnstufe::ErsteMahnung->value
            && $postenSpezifikation->isSatisfiedBy($candidate->offenerPosten)
            && $kontoSpezifikation->isSatisfiedBy($candidate->konto);
    }
}
