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
 * Wertobjekt, das einen Geldbetrag in einer bestimmten Währung darstellt.
 */
readonly class Geldbetrag
{
    public function __construct(
        public float $betrag,
        public string $waehrung
    ) {
    }

    public static function null(string $waehrung): self
    {
        return new self(0.0, $waehrung);
    }

    public function addieren(self $andere): self
    {
        $this->waehrungMussUebereinstimmen($andere);

        return new self($this->betrag + $andere->betrag, $this->waehrung);
    }

    public function subtrahieren(self $andere): self
    {
        $this->waehrungMussUebereinstimmen($andere);

        return new self($this->betrag - $andere->betrag, $this->waehrung);
    }

    public function istKleinerAls(self $andere): bool
    {
        $this->waehrungMussUebereinstimmen($andere);

        return $this->betrag < $andere->betrag;
    }

    public function istGroesserAls(self $andere): bool
    {
        $this->waehrungMussUebereinstimmen($andere);

        return $this->betrag > $andere->betrag;
    }

    public function istKleinerOderGleich(self $andere): bool
    {
        $this->waehrungMussUebereinstimmen($andere);

        return $this->betrag <= $andere->betrag;
    }

    public function istGroesserOderGleich(self $andere): bool
    {
        $this->waehrungMussUebereinstimmen($andere);

        return $this->betrag >= $andere->betrag;
    }

    private function waehrungMussUebereinstimmen(self $andere): void
    {
        if ($this->waehrung !== $andere->waehrung) {
            throw new \RuntimeException("Währungskonflikt: {$this->waehrung} vs {$andere->waehrung}");
        }
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->betrag, $this->waehrung);
    }
}
