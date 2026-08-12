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

require_once __DIR__ . '/../../vendor/autoload.php';

use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Buchung;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Buchungsart;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Geldbetrag;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Hauptbuchkonto;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Kundenrisikoklasse;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnkandidat;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\Mahnstufe;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Modelle\OffenerPosten;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Mahnung\BerechnetMahnzinsen;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Mahnung\ErfordertRechtsverfahren;
use Phauthentic\Specification\Examples\OpenItemAccountingGerman\Spezifikationen\Mahnung\MahnstufenBerechtigung;

/**
 * OffenerPostenbuchhaltung / Mahnwesen-Demo.
 *
 * Diese Demo zeigt das Specification Pattern angewendet auf ein echtes DDD-Aggregat
 * (Hauptbuchkonto), das einen echten Buchhaltungsprozess steuert: welche OffenerPosten in
 * einem mehrstufigen Mahnprozess eskaliert werden, welche Posten Zinsen tragen und welche
 * an ein Rechtsverfahren übergeben werden müssen.
 */

/**
 * Wertet Mahnkandidaten anhand der Stufen-, Zins- und Rechtsverfahrensspezifikationen aus.
 */
class Mahnprozessor
{
    /**
     * @var array<array{stufe: Mahnstufe, karenztage: int, mindestbetrag: float}>
     */
    private array $stufenregeln = [
        ['stufe' => Mahnstufe::FreundlicheErinnerung, 'karenztage' => 7, 'mindestbetrag' => 1.0],
        ['stufe' => Mahnstufe::ErsteMahnung, 'karenztage' => 21, 'mindestbetrag' => 25.0],
        ['stufe' => Mahnstufe::ZweiteMahnung, 'karenztage' => 35, 'mindestbetrag' => 50.0],
        ['stufe' => Mahnstufe::LetzteMahnung, 'karenztage' => 49, 'mindestbetrag' => 100.0],
    ];

    private BerechnetMahnzinsen $zinsSpezifikation;

    private ErfordertRechtsverfahren $rechtsverfahrenSpezifikation;

    public function __construct()
    {
        $this->zinsSpezifikation = new BerechnetMahnzinsen();
        $this->rechtsverfahrenSpezifikation = new ErfordertRechtsverfahren();
    }

    /**
     * @return array<array{stufe: Mahnstufe, berechtigt: bool}>
     */
    public function stufenAuswerten(Mahnkandidat $kandidat): array
    {
        return array_map(
            fn (array $regel): array => [
                'stufe' => $regel['stufe'],
                'berechtigt' => (new MahnstufenBerechtigung(
                    $regel['stufe'],
                    $regel['karenztage'],
                    $regel['mindestbetrag']
                ))->isSatisfiedBy($kandidat),
            ],
            $this->stufenregeln
        );
    }

    public function naechsteStufeErmitteln(Mahnkandidat $kandidat): ?Mahnstufe
    {
        $berechtigteStufen = array_values(array_filter(
            $this->stufenAuswerten($kandidat),
            fn (array $ergebnis): bool => $ergebnis['berechtigt']
        ));

        if ($berechtigteStufen === []) {
            return null;
        }

        return end($berechtigteStufen)['stufe'];
    }

    public function zinsenFaellig(Mahnkandidat $kandidat): bool
    {
        return $this->zinsSpezifikation->isSatisfiedBy($kandidat);
    }

    public function rechtsverfahrenErforderlich(Mahnkandidat $kandidat): bool
    {
        return $this->rechtsverfahrenSpezifikation->isSatisfiedBy($kandidat);
    }
}

/**
 * Erstellt die Beispiel-Hauptbuchkonten der Demo, jeweils eine Mahnregel isolierend.
 *
 * @return array<Hauptbuchkonto>
 */
function beispielkontenErstellen(\DateTimeImmutable $stichtag): array
{
    $waehrung = 'EUR';

    // Konto 1: Standardkunde, 40 Tage überfällig - sollte auf die zweite Mahnung eskalieren.
    $acme = new Hauptbuchkonto('10045', 'Acme Fertigungs GmbH', Kundenrisikoklasse::Standard, $waehrung);
    $acme->buchen(new Buchung(
        'RE-1001',
        Buchungsart::Rechnung,
        new Geldbetrag(500.0, $waehrung),
        $stichtag->modify('-45 days'),
        'Lieferung von Industrieteilen',
        $stichtag->modify('-40 days')
    ));

    // Konto 2: Bevorzugter Kunde, ebenfalls 40 Tage überfällig - Kulanz begrenzt dies auf die erste Mahnung.
    $nordisch = new Hauptbuchkonto('10046', 'Nordische Handelsgruppe', Kundenrisikoklasse::Bevorzugt, $waehrung);
    $nordisch->buchen(new Buchung(
        'RE-1002',
        Buchungsart::Rechnung,
        new Geldbetrag(500.0, $waehrung),
        $stichtag->modify('-45 days'),
        'Quartalsweise Lagerauffüllung',
        $stichtag->modify('-40 days')
    ));

    // Konto 3: Hochrisikokunde, nur 10 Tage überfällig - risikobedingter Zuschlag eskaliert früh.
    $schnellmode = new Hauptbuchkonto('10047', 'Schnellmode Express', Kundenrisikoklasse::Hochrisiko, $waehrung);
    $schnellmode->buchen(new Buchung(
        'RE-1003',
        Buchungsart::Rechnung,
        new Geldbetrag(50.0, $waehrung),
        $stichtag->modify('-15 days'),
        'Musterbestellung',
        $stichtag->modify('-10 days')
    ));

    // Konto 4: Standardkunde, 60 Tage überfällig und strittig - Streitfall stoppt jede Regel.
    $bergmann = new Hauptbuchkonto('10048', 'Bergmann Logistik', Kundenrisikoklasse::Standard, $waehrung);
    $bergmann->buchen(new Buchung(
        'RE-1004',
        Buchungsart::Rechnung,
        new Geldbetrag(1000.0, $waehrung),
        $stichtag->modify('-65 days'),
        'Speditionsleistungen',
        $stichtag->modify('-60 days')
    ));
    $bergmann->offenerPostenStrittigSetzen('RE-1004', true);

    // Konto 5: Standardkunde bereits auf letzter Mahnstufe - löst Zinsanfall und Rechtsverfahren aus.
    $continental = new Hauptbuchkonto('10049', 'Continental Lebensmittel Ltd', Kundenrisikoklasse::Standard, $waehrung);
    $continental->buchen(new Buchung(
        'RE-1005',
        Buchungsart::Rechnung,
        new Geldbetrag(5000.0, $waehrung),
        $stichtag->modify('-60 days'),
        'Rohstofflieferung',
        $stichtag->modify('-55 days')
    ));
    $continental->mahnungEskalieren('RE-1005', Mahnstufe::LetzteMahnung, $stichtag->modify('-20 days'));

    // Konto 6: Standardkunde, für Mahnwesen gesperrt (z. B. Insolvenzverfahren) - sperrt jede Regel.
    $vantage = new Hauptbuchkonto('10050', 'Vantage Industriezulieferer', Kundenrisikoklasse::Standard, $waehrung);
    $vantage->buchen(new Buchung(
        'RE-1006',
        Buchungsart::Rechnung,
        new Geldbetrag(2000.0, $waehrung),
        $stichtag->modify('-95 days'),
        'Maschinenteile-Bestellung',
        $stichtag->modify('-90 days')
    ));
    $vantage->sperren();

    return [$acme, $nordisch, $schnellmode, $bergmann, $continental, $vantage];
}

/**
 * @param array<array{stufe: Mahnstufe, berechtigt: bool}> $stufen
 */
function stufenberechtigungAusgeben(array $stufen): void
{
    foreach ($stufen as $ergebnis) {
        $symbol = $ergebnis['berechtigt'] ? '✓' : '✗';
        echo "    {$symbol} {$ergebnis['stufe']->name}\n";
    }
}

function beiBerechtigungEskalieren(
    Hauptbuchkonto $konto,
    OffenerPosten $posten,
    \DateTimeImmutable $stichtag,
    ?Mahnstufe $naechsteStufe
): void {
    if ($naechsteStufe === null) {
        echo "  → Keine Eskalation in diesem Lauf\n";
        return;
    }

    $konto->mahnungEskalieren($posten->id, $naechsteStufe, $stichtag);
    echo "  → Eskaliert auf {$naechsteStufe->name}\n";
}

function offenerPostenAusgeben(
    Hauptbuchkonto $konto,
    OffenerPosten $posten,
    \DateTimeImmutable $stichtag,
    Mahnprozessor $mahnprozessor
): void {
    $kandidat = new Mahnkandidat($konto, $posten, $stichtag);

    echo "  OffenerPosten {$posten->id}: {$posten->getOffenerBetrag()} offen von {$posten->ursprungsbetrag}\n";
    echo "  - Fällig: {$posten->faelligkeitsdatum->format('Y-m-d')} ({$posten->tageUeberfaellig($stichtag)} Tage überfällig)\n";
    echo '  - Strittig: ' . ($posten->istStrittig() ? 'JA' : 'nein') . "\n";

    $letzterLauf = $posten->getLetztesMahndatum();
    $letzterLaufSuffix = $letzterLauf !== null ? " (letzter Lauf {$letzterLauf->format('Y-m-d')})" : '';
    echo "  - Aktuelle Stufe: {$posten->getAktuelleMahnstufe()->name}{$letzterLaufSuffix}\n";

    echo "  Stufenberechtigung:\n";
    stufenberechtigungAusgeben($mahnprozessor->stufenAuswerten($kandidat));

    beiBerechtigungEskalieren($konto, $posten, $stichtag, $mahnprozessor->naechsteStufeErmitteln($kandidat));

    echo '  Zinsen fällig: ' . ($mahnprozessor->zinsenFaellig($kandidat) ? 'JA' : 'nein') . "\n";
    echo '  Rechtsverfahren erforderlich: ' . ($mahnprozessor->rechtsverfahrenErforderlich($kandidat) ? 'JA' : 'nein') . "\n";
    echo "\n";
}

function kontoAusgeben(Hauptbuchkonto $konto, \DateTimeImmutable $stichtag, Mahnprozessor $mahnprozessor): void
{
    echo "Konto {$konto->kontonummer}: {$konto->kundenname}\n";
    echo "- Risikoklasse: {$konto->risikoklasse->name}\n";
    echo '- Für Mahnwesen gesperrt: ' . ($konto->istFuerMahnwesenGesperrt() ? 'JA' : 'nein') . "\n\n";

    foreach ($konto->getOffenePosten() as $posten) {
        offenerPostenAusgeben($konto, $posten, $stichtag, $mahnprozessor);
    }

    echo "---\n\n";
}

function zusammenfassungAusgeben(): void
{
    echo "=== ZUSAMMENFASSUNG ===\n\n";
    echo "Diese Demo zeigt komplexe, praxisnahe Buchhaltungsregeln, umgesetzt mit dem Specification Pattern:\n\n";
    echo "- Mahnstufeneskalation: eine parametrisierte Spezifikation, wiederverwendet über vier Eskalationsstufen\n";
    echo "- Risikoabhängige Kulanz: dieselbe Regel passt ihre Karenzzeit je nach Kundenrisikoklasse an\n";
    echo "- Umgang mit Streitfällen: eine einzelne atomare Spezifikation stoppt für strittige Posten jede nachgelagerte Regel\n";
    echo "- Zinsanfall: eine eigenständige Regel aus denselben atomaren Bausteinen, die bevorzugte Kunden über AndNot ausnimmt\n";
    echo "- Rechtsverfahren: eine Regel, die von der eigenen Mahnhistorie des Aggregats abhängt (letzter Lauf)\n";
    echo "- Kontosperrung: eine Spezifikation, die in jede Regel einfließt, sodass eine Kontosperre den gesamten Prozess stoppt, ohne Stufen-, Zins- oder Rechtslogik anzufassen\n";
}

function demoAusfuehren(): void
{
    echo "=== OFFENPOSTENBUCHHALTUNG / MAHNLAUF ===\n\n";

    $stichtag = new \DateTimeImmutable('2026-08-12');
    $konten = beispielkontenErstellen($stichtag);
    $mahnprozessor = new Mahnprozessor();

    foreach ($konten as $konto) {
        kontoAusgeben($konto, $stichtag, $mahnprozessor);
    }

    zusammenfassungAusgeben();
}

demoAusfuehren();
