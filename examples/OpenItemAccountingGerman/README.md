# OffenerPostenbuchhaltung / Mahnwesen-Beispiel (Deutsch)

Vollständig deutschsprachige Fassung des [OpenItemAccounting-Beispiels](../OpenItemAccounting/README.md).
Klassennamen, Bezeichner und Kommentare sind komplett auf Deutsch gehalten.

## Überblick

Dieses Beispiel zeigt das Specification Pattern angewendet auf ein echtes DDD-Aggregat mit Invarianten und internen Zustandsübergängen.
Buchungen erzeugen OffenerPosten, Zahlungen gleichen sie aus, Streitfälle und Kontosperren ändern, was mit ihnen geschehen darf, und Mahnläufe verändern über die Zeit ihre Eskalationsstufe.

## Geschäftsszenario

`Hauptbuchkonto` besitzt eine nur anfügbare Folge von `Buchung`en (Rechnungen, Zahlungen, Gutschriften, Abschreibungen) und die daraus abgeleiteten `OffenerPosten`.
Jeder Mahnlauf muss pro OffenerPosten drei unabhängige Fragen beantworten, die alle aus denselben atomaren Spezifikationen aufgebaut sind:

### 1. Auf welche Mahnstufe soll dieser Posten eskaliert werden?

Eine einzige parametrisierte `MahnstufenBerechtigung($zielstufe, $karenztage, $mindestbetrag)` wird für alle vier Stufen wiederverwendet, statt vier nahezu identische Klassen zu pflegen:

| Stufe | Karenzzeit | Mindestbetrag |
|---|---|---|
| Freundliche Erinnerung | 7 Tage | 1 |
| Erste Mahnung | 21 Tage | 25 |
| Zweite Mahnung | 35 Tage | 50 |
| Letzte Mahnung | 49 Tage | 100 |

Dieselbe Spezifikation berücksichtigt zusätzlich die risikoabhängige Kulanz (bevorzugte Kunden erhalten +14 Karenztage, Hochrisikokunden -7), ohne eine eigene Regel je Risikoklasse zu benötigen.

### 2. Fallen für diesen Posten Mahnzinsen an?

Unabhängig von der Stufenleiter: der Posten ist offen, nicht strittig, mindestens 100 offen, mehr als 45 Tage überfällig und bereits mindestens auf der ersten Mahnstufe — bevorzugte Kunden werden über `andNot` ausgenommen.

### 3. Muss dieser Posten an ein Rechtsverfahren übergeben werden?

Bereits auf der letzten Mahnstufe, mehr als 14 Tage seit dem letzten Mahnlauf, mindestens 250 offen, nicht strittig.
Diese Regel liest die eigene Mahnhistorie des Aggregats (`OffenerPosten::getLetztesMahndatum()`).

Zwei kontoweite Tatsachen — Streitfall und Sperre — leben jeweils in einer einzigen atomaren Spezifikation und fließen in jede der obigen Regeln ein.
Das Umschalten eines einzigen Flags (`Hauptbuchkonto::sperren()`, `Hauptbuchkonto::offenerPostenStrittigSetzen(...)`) ändert damit überall das Ergebnis, ohne Stufen-, Zins- oder Rechtslogik anzufassen.

## Beispiel ausführen

```bash
php examples/OpenItemAccountingGerman/run.php
```

## Architektur

```
Modelle/
├── Geldbetrag                              (Wertobjekt)
├── Buchungsart, OffenerPostenstatus, Mahnstufe, Kundenrisikoklasse  (Enums)
├── Buchung                                 (unveränderlicher Buchungssatz)
├── OffenerPosten                             (Entität; nur über Hauptbuchkonto änderbar)
├── Hauptbuchkonto                          (Aggregatwurzel)
└── Mahnkandidat                            (Bewertungskontext: Konto + OffenerPosten + Stichtag)

Spezifikationen/
├── OffenerPosten/
│   ├── IstUeberfaellig
│   ├── MindestOffenerBetrag
│   ├── IstNichtStrittig
│   ├── IstOffen
│   └── HatMahnstufeNichtErreicht
├── Konto/
│   ├── IstNichtFuerMahnwesenGesperrt
│   └── Risikoklasse
└── Mahnung/
    ├── MahnstufenBerechtigung   (Komposit, je Stufe parametrisiert)
    ├── BerechnetMahnzinsen      (Komposit)
    └── ErfordertRechtsverfahren (Komposit)
```

## Beispielszenarien

Die Demo legt sechs Konten an, die jeweils eine Regel isoliert zeigen:

1. **Standardkunde, 40 Tage überfällig** — eskaliert auf die zweite Mahnung.
2. **Bevorzugter Kunde, ebenfalls 40 Tage überfällig** — Kulanz begrenzt dies auf die erste Mahnung.
3. **Hochrisikokunde, nur 10 Tage überfällig** — risikobedingter Zuschlag eskaliert früh, auf die freundliche Erinnerung.
4. **Standardkunde, 60 Tage überfällig, strittig** — keine Eskalation; der Streitfall stoppt jede nachgelagerte Regel.
5. **Standardkunde bereits auf letzter Mahnstufe** — löst sowohl Zinsanfall als auch Rechtsverfahren aus.
6. **Standardkunde, Konto gesperrt (z. B. Insolvenz)** — keine Mahnaktion, obwohl alle anderen Schwellen erfüllt sind.
