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
 * Aggregatwurzel für das Konto eines Kunden: eine nur anfügbare Folge von Buchungen und die
 * daraus abgeleiteten OffenerPosten. Alle Zustandsänderungen an OffenerPosten laufen über
 * dieses Aggregat, damit seine Invarianten (z. B. ein OffenerPosten kann nur durch eine
 * Buchung ausgeglichen werden, die auf ihn verweist) stets gelten.
 */
final class Hauptbuchkonto
{
    /** @var Buchung[] */
    private array $buchungen = [];

    /** @var array<string, OffenerPosten> */
    private array $offenePosten = [];

    private bool $fuerMahnwesenGesperrt = false;

    public function __construct(
        public readonly string $kontonummer,
        public readonly string $kundenname,
        public readonly Kundenrisikoklasse $risikoklasse,
        public readonly string $waehrung
    ) {
    }

    /**
     * @return Buchung[]
     */
    public function getBuchungen(): array
    {
        return $this->buchungen;
    }

    /**
     * @return OffenerPosten[]
     */
    public function getOffenePosten(): array
    {
        return array_values($this->offenePosten);
    }

    public function istFuerMahnwesenGesperrt(): bool
    {
        return $this->fuerMahnwesenGesperrt;
    }

    public function sperren(): void
    {
        $this->fuerMahnwesenGesperrt = true;
    }

    public function entsperren(): void
    {
        $this->fuerMahnwesenGesperrt = false;
    }

    public function buchen(Buchung $buchung): void
    {
        if ($buchung->betrag->waehrung !== $this->waehrung) {
            throw new \RuntimeException(
                "Buchungswährung {$buchung->betrag->waehrung} entspricht nicht der Kontowährung {$this->waehrung}."
            );
        }

        $this->buchungen[] = $buchung;

        match ($buchung->art) {
            Buchungsart::Rechnung => $this->offenePosten[$buchung->id] = new OffenerPosten(
                $buchung->id,
                $buchung->id,
                $buchung->faelligkeitsdatum ?? throw new \RuntimeException('Rechnungsbuchung hat kein Fälligkeitsdatum.'),
                $buchung->betrag
            ),
            Buchungsart::Zahlung, Buchungsart::Gutschrift => $this->offenerPostenAnfordern(
                $buchung->referenzOffenerPostenId ?? throw new \RuntimeException('Buchung hat keinen Referenz-OffenerPosten.')
            )->zahlungVerbuchen($buchung->betrag),
            Buchungsart::Abschreibung => $this->offenerPostenAnfordern(
                $buchung->referenzOffenerPostenId ?? throw new \RuntimeException('Buchung hat keinen Referenz-OffenerPosten.')
            )->abschreiben(),
        };
    }

    public function offenerPostenStrittigSetzen(string $offenerPostenId, bool $strittig): void
    {
        $this->offenerPostenAnfordern($offenerPostenId)->alsStrittigMarkieren($strittig);
    }

    public function mahnungEskalieren(string $offenerPostenId, Mahnstufe $stufe, \DateTimeImmutable $laufDatum): void
    {
        $this->offenerPostenAnfordern($offenerPostenId)->mahnungEskalieren($stufe, $laufDatum);
    }

    private function offenerPostenAnfordern(string $offenerPostenId): OffenerPosten
    {
        return $this->offenePosten[$offenerPostenId]
            ?? throw new \RuntimeException("Kein OffenerPosten '{$offenerPostenId}' auf Konto {$this->kontonummer}.");
    }
}
