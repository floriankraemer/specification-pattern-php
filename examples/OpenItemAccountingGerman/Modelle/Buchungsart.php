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
 * Die Art der Buchung, die auf ein Konto gebucht wird.
 */
enum Buchungsart
{
    /** Erzeugt einen neuen OffenerPosten (z. B. eine Kundenrechnung). */
    case Rechnung;

    /** Gleicht einen bestehenden OffenerPosten ganz oder teilweise aus. */
    case Zahlung;

    /** Reduziert einen bestehenden OffenerPosten ohne Zahlungseingang. */
    case Gutschrift;

    /** Entfernt einen OffenerPosten aus dem Mahnwesen (Forderungsausfall). */
    case Abschreibung;
}
