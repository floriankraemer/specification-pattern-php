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
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnkandidat;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnstufe;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Konto\IstNichtFuerMahnwesenGesperrt;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstNichtStrittig;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\IstOffen;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\OffenerPosten\MindestOffenerBetrag;

/**
 * Regel für die Übergabe an ein Rechtsverfahren (Inkasso/Klage).
 *
 * Geschäftsregeln:
 * - Der Posten muss bereits die letzte Mahnstufe erreicht haben
 * - Seit dem letzten Mahnlauf müssen mehr als 14 Tage vergangen sein
 * - Der Posten muss offen, nicht strittig und mindestens 250 offen sein
 * - Das Konto darf nicht bereits gesperrt sein (dann liefe es schon im Rechtsverfahren)
 */
class ErfordertRechtsverfahren extends AbstractSpecification
{
    private const RECHTSVERFAHREN_KARENZTAGE = 14;

    private const MINDEST_RECHTSVERFAHREN_BETRAG = 250.0;

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Mahnkandidat) {
            return false;
        }

        $postenSpezifikation = (new IstOffen())
            ->and(new IstNichtStrittig())
            ->and(new MindestOffenerBetrag(self::MINDEST_RECHTSVERFAHREN_BETRAG));

        $kontoSpezifikation = new IstNichtFuerMahnwesenGesperrt();

        $istAufLetzterStufe = $candidate->offenerPosten->getAktuelleMahnstufe() === Mahnstufe::LetzteMahnung;

        $letzterLauf = $candidate->offenerPosten->getLetztesMahndatum();
        $ueberfaelligSeitLetztemMahnlauf = $letzterLauf !== null
            && (int)$letzterLauf->diff($candidate->stichtag)->format('%r%a') > self::RECHTSVERFAHREN_KARENZTAGE;

        return $istAufLetzterStufe
            && $ueberfaelligSeitLetztemMahnlauf
            && $postenSpezifikation->isSatisfiedBy($candidate->offenerPosten)
            && $kontoSpezifikation->isSatisfiedBy($candidate->konto);
    }
}
