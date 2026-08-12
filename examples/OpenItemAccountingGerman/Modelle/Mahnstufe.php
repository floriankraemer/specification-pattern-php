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
 * Eskalationsstufe eines Mahnprozesses, nach Schweregrad geordnet.
 */
enum Mahnstufe: int
{
    case Keine = 0;
    case FreundlicheErinnerung = 1;
    case ErsteMahnung = 2;
    case ZweiteMahnung = 3;
    case LetzteMahnung = 4;
}
