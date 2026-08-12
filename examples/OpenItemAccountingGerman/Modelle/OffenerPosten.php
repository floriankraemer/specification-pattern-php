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
 * Eine noch nicht ausgeglichene Rechnung, die auf einem Hauptbuchkonto geführt wird.
 *
 * Zustandsänderungen sind nur über das besitzende Hauptbuchkonto vorgesehen — jeder Mutator
 * unten ist deshalb mit @internal markiert. PHP kennt kein `internal`-Schlüsselwort, diese
 * Grenze wird also per Konvention und nicht vom Compiler durchgesetzt.
 */
final class OffenerPosten
{
    private Geldbetrag $offenerBetrag;

    private OffenerPostenstatus $status;

    private Mahnstufe $aktuelleMahnstufe;

    private ?\DateTimeImmutable $letztesMahndatum = null;

    private bool $strittig = false;

    /**
     * @internal Nur das Hauptbuchkonto darf einen OffenerPosten erzeugen.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $rechnungsbuchungId,
        public readonly \DateTimeImmutable $faelligkeitsdatum,
        public readonly Geldbetrag $ursprungsbetrag
    ) {
        $this->offenerBetrag = $ursprungsbetrag;
        $this->status = OffenerPostenstatus::Offen;
        $this->aktuelleMahnstufe = Mahnstufe::Keine;
    }

    public function getOffenerBetrag(): Geldbetrag
    {
        return $this->offenerBetrag;
    }

    public function getStatus(): OffenerPostenstatus
    {
        return $this->status;
    }

    public function getAktuelleMahnstufe(): Mahnstufe
    {
        return $this->aktuelleMahnstufe;
    }

    public function getLetztesMahndatum(): ?\DateTimeImmutable
    {
        return $this->letztesMahndatum;
    }

    public function istStrittig(): bool
    {
        return $this->strittig;
    }

    /**
     * @internal Nur über Hauptbuchkonto::buchen() aufrufen.
     */
    public function zahlungVerbuchen(Geldbetrag $betrag): void
    {
        $this->offenerBetrag = $this->offenerBetrag->subtrahieren($betrag);

        if ($this->offenerBetrag->betrag <= 0) {
            $this->status = OffenerPostenstatus::Ausgeglichen;
        }
    }

    /**
     * @internal Nur über Hauptbuchkonto::buchen() aufrufen.
     */
    public function abschreiben(): void
    {
        $this->status = OffenerPostenstatus::Abgeschrieben;
    }

    /**
     * @internal Nur über Hauptbuchkonto::offenerPostenStrittigSetzen() aufrufen.
     */
    public function alsStrittigMarkieren(bool $strittig): void
    {
        $this->strittig = $strittig;
    }

    /**
     * @internal Nur über Hauptbuchkonto::mahnungEskalieren() aufrufen.
     */
    public function mahnungEskalieren(Mahnstufe $stufe, \DateTimeImmutable $laufDatum): void
    {
        $this->aktuelleMahnstufe = $stufe;
        $this->letztesMahndatum = $laufDatum;
    }

    public function tageUeberfaellig(\DateTimeImmutable $stichtag): int
    {
        $tage = (int)$this->faelligkeitsdatum->diff($stichtag)->format('%r%a');

        return max(0, $tage);
    }
}
