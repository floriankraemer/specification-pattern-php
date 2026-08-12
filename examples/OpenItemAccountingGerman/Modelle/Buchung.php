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
 * Ein unveränderlicher Buchungssatz auf einem Hauptbuchkonto.
 */
readonly class Buchung
{
    public function __construct(
        public string $id,
        public Buchungsart $art,
        public Geldbetrag $betrag,
        public \DateTimeImmutable $buchungsdatum,
        public string $beschreibung,
        /** Nur bei Buchungsart::Rechnung-Buchungen gesetzt. */
        public ?\DateTimeImmutable $faelligkeitsdatum = null,
        /** Der OffenerPosten, den diese Buchung ausgleicht oder abschreibt. Bei Rechnungen nicht gesetzt. */
        public ?string $referenzOffenerPostenId = null
    ) {
        if ($art === Buchungsart::Rechnung && $faelligkeitsdatum === null) {
            throw new \InvalidArgumentException('Rechnungsbuchungen benötigen ein Fälligkeitsdatum.');
        }

        if ($art !== Buchungsart::Rechnung && $referenzOffenerPostenId === null) {
            throw new \InvalidArgumentException("{$art->name}-Buchungen benötigen einen Referenz-OffenerPosten.");
        }
    }
}
