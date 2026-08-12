# Open-Item Accounting / Dunning Example

A second, more domain-heavy example demonstrating the Specification Pattern against a real DDD aggregate.
A `LedgerAccount` runs a multi-level dunning (collections/reminder) process over its open items.

## Overview

Where the [ECommerce example](../ECommerce/README.md) shows the pattern composing rules over a read-oriented `Order`, this example shows it composing rules that read from an actual aggregate root with invariants and internal state transitions.
Postings create open items, payments clear them, disputes and account blocks change what may legally happen to them, and dunning runs mutate their escalation level over time.

## Business Scenario

`LedgerAccount` owns an append-only stream of `Posting`s (invoices, payments, credit memos, write-offs) and the `OpenItem`s derived from them.
Each dunning run needs to answer three independent questions per open item, all built from the same handful of atomic specifications:

### 1. Which dunning level should this item escalate to?

One parameterized `DunningLevelEligibility($targetLevel, $graceDays, $minimumAmount)` is reused across all four levels instead of four near-duplicate classes:

| Level | Grace period | Minimum amount |
|---|---|---|
| Friendly Reminder | 7 days | 1 |
| First Dunning | 21 days | 25 |
| Second Dunning | 35 days | 50 |
| Final Dunning | 49 days | 100 |

The same specification also folds in risk-based leniency (Preferred customers get +14 grace days, High-Risk customers get -7) without needing a separate rule per risk class.

### 2. Does this item accrue dunning interest?

Independent of the level ladder: item is open, not disputed, ≥100 remaining, overdue >45 days, already at First Dunning or beyond — and Preferred customers are exempted via `andNot`.

### 3. Does this item need legal referral?

Already at Final Dunning, more than 14 days since the last dunning run, ≥250 remaining, not disputed.
This rule reads the aggregate's own dunning history (`OpenItem::getLastDunningDate()`).

Two account-level facts — dispute and block — each live in a single atomic specification and are composed into every one of the rules above.
Flipping one flag (`LedgerAccount::block()`, `LedgerAccount::disputeOpenItem(...)`) changes the outcome everywhere without touching level, interest, or legal logic.

## Running the Example

```bash
php examples/OpenItemAccounting/run.php
```

## Architecture

```
Models/
├── Money                  (value object)
├── PostingType, OpenItemStatus, DunningLevel, CustomerRiskClass  (enums)
├── Posting                (immutable ledger entry)
├── OpenItem                (entity; mutated only via LedgerAccount)
├── LedgerAccount           (aggregate root)
└── DunningCandidate        (evaluation context: Account + OpenItem + AsOf)

Specifications/
├── OpenItem/
│   ├── IsOverdue
│   ├── MinimumOpenAmount
│   ├── IsNotDisputed
│   ├── IsOpen
│   └── HasNotReachedDunningLevel
├── Account/
│   ├── IsNotBlockedForDunning
│   └── RiskClass
└── Dunning/
    ├── DunningLevelEligibility   (Composite, parameterized per level)
    ├── AccruesDunningInterest    (Composite)
    └── RequiresLegalActionReferral (Composite)
```

## Sample Scenarios

The demo builds six accounts, each isolating one rule:

1. **Standard customer, 40 days overdue** — escalates to Second Dunning.
2. **Preferred customer, same 40 days overdue** — risk-based leniency limits this to First Dunning.
3. **High-risk customer, only 10 days overdue** — risk-based penalty escalates it early, to Friendly Reminder.
4. **Standard customer, 60 days overdue, disputed** — no escalation at all; the dispute halts every downstream rule.
5. **Standard customer already at Final Dunning** — triggers both interest accrual and legal referral.
6. **Standard customer, account blocked (e.g. insolvency)** — no dunning action despite matching every other threshold.
