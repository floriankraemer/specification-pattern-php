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
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\HatMahnstufeNichtErreicht;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstNichtStrittig;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstOffen;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstUeberfaellig;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\MindestOffenerBetrag;

/**
 * Regel für die Eskalation auf eine Mahnstufe.
 *
 * Geschäftsregeln:
 * - Der Posten muss noch offen und nicht strittig sein
 * - Der Posten darf die Zielstufe noch nicht erreicht haben
 * - Der offene Restbetrag muss den Mindestbetrag der Stufe erreichen
 * - Der Posten muss die Karenzzeit der Stufe überschritten haben
 * - Bevorzugte Kunden erhalten 14 zusätzliche Karenztage, Hochrisikokunden 7 weniger
 * - Das Konto darf nicht für das Mahnwesen gesperrt sein
 *
 * Eine parametrisierte Klasse ersetzt vier nahezu identische Klassen je Mahnstufe.
 */
class MahnstufenBerechtigung extends AbstractSpecification
{
    public function __construct(
        private Mahnstufe $zielstufe,
        private int $karenztage,
        private float $mindestbetrag
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Mahnkandidat) {
            return false;
        }

        $risikoAnpassung = match ($candidate->konto->risikoklasse) {
            Kundenrisikoklasse::Bevorzugt => 14,
            Kundenrisikoklasse::Hochrisiko => -7,
            default => 0,
        };

        $postenSpezifikation = (new IstOffen())
            ->and(new IstNichtStrittig())
            ->and(new HatMahnstufeNichtErreicht($this->zielstufe))
            ->and(new MindestOffenerBetrag($this->mindestbetrag))
            ->and(new IstUeberfaellig($candidate->stichtag, $this->karenztage + $risikoAnpassung));

        $kontoSpezifikation = new IstNichtFuerMahnwesenGesperrt();

        return $postenSpezifikation->isSatisfiedBy($candidate->offenerPosten)
            && $kontoSpezifikation->isSatisfiedBy($candidate->konto);
    }
}
