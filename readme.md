# Specification Pattern

![PHP >= 8.1](https://img.shields.io/static/v1?label=PHP&message=^8.1&color=787CB5&style=for-the-badge&logo=php)
![phpstan Level 8](https://img.shields.io/static/v1?label=phpstan&message=Level%208&color=%3CCOLOR%3E&style=for-the-badge)
![License: MIT](https://img.shields.io/static/v1?label=License&message=MIT&color=%3CCOLOR%3E&style=for-the-badge)

A PHP implementation of the [Specification Pattern](https://en.wikipedia.org/wiki/Specification_pattern), a pattern that is frequently used in the context of **domain-driven design**. However, it is not exclusivly useful in DDD.

---

In computer programming, the specification pattern is a particular software design pattern, whereby business rules can be recombined by chaining the business rules together using boolean logic.

A specification pattern outlines a business rule that is combinable with other business rules. In this pattern, a unit of business logic inherits its functionality from the abstract aggregate Composite Specification class. The Composite Specification class has one function called IsSatisfiedBy that returns a boolean value. After instantiation, the specification is "chained" with other specifications, making new specifications easily maintainable, yet highly customizable business logic. Furthermore, upon instantiation the business logic may, through method invocation or inversion of control, have its state altered in order to become a delegate of other classes such as a persistence repository.

As a consequence of performing runtime composition of high-level business/domain logic, the Specification pattern is a convenient tool for converting ad-hoc user search criteria into low level logic to be processed by repositories.

Since a specification is an encapsulation of logic in a reusable form it is very simple to thoroughly unit test, and when used in this context is also an implementation of the humble object pattern.

* https://en.wikipedia.org/wiki/Specification_pattern
* http://www.martinfowler.com/apsupp/spec.pdf

## How to use them

When using specifications with Domain-Driven Design, a common question is how to check conditions on aggregates without breaking encapsulation. There are several approaches, from delegating checks to aggregate query methods, to using read models in CQRS architectures, to pragmatic property access for simpler use cases.

See [How to use Specifications](docs/How-to-use-Specifications.md) for detailed guidance and complete examples.

## Example

The following example demonstrates a debt collection business rule for invoices.

```php
// Define specifications for invoice collection rules
$overDue = new OverDueSpecification();
$noticeSent = new NoticeSentSpecification();
$inCollection = new InCollectionSpecification();

// Business Rule: Send to collection agency when invoice is:
// - Past due date AND
// - Customer has been notified AND
// - Not already in collection
$sendToCollection = $overDue
    ->and($noticeSent)
    ->andNot($inCollection);

// Apply the business rule to all invoices
foreach ($service->getInvoices() as $invoice) {
    if ($sendToCollection->isSatisfiedBy($invoice)) {
        $invoice->sendToCollection();
    }
}
```

Each specification encapsulates a single business rule check (e.g., `OverDueSpecification` checks if `$invoice->dueDate < now()`). The pattern allows combining these atomic rules using boolean logic (`and`, `or`, `not`, `andNot`, `orNot`) to form complex, readable business rules that can be reused and unit tested independently.

## Examples

* [ECommerce](examples/ECommerce/README.md) — promotional eligibility rules composed over an `Order` read model.
* [OpenItemAccounting](examples/OpenItemAccounting/README.md) — a dunning process composed over a real `LedgerAccount` DDD aggregate, its `Posting`s, and `OpenItem`s.
* [OpenItemAccountingGerman](examples/OpenItemAccountingGerman/README.md) — the same dunning process, fully in German (class names, identifiers, and comments).

## Specifications and DDD aggregates

Specifications read an aggregate's state through its public getters; they don't need write access.
That's fine, and matches how this library's examples model aggregates.

Public getters aren't the invariant risk — public setters are.
An aggregate like `OpenItem` in the [OpenItemAccounting example](examples/OpenItemAccounting/README.md) exposes its identity and immutable fields as `public readonly` properties, and keeps every mutable field (`openAmount`, `status`, `currentDunningLevel`, ...) private behind read-only getters and `@internal`-tagged mutator methods (`applyPayment()`, etc.) meant to be called only by the owning aggregate.
PHP has no `internal` keyword, so this boundary is enforced by convention and docblocks rather than by the compiler — but the shape is the same: reads stay open, writes stay locked down to the aggregate itself.

Keep the responsibilities split:

* **Specifications** — read-only predicates over already-consistent state: eligibility checks, query filters, dunning-level rules. Composable, unit-testable in isolation, no side effects.
* **Aggregate methods** — state transitions and invariant enforcement: whether a payment *can* be applied, and applying it. These stay internal to the aggregate, not modeled as specifications.

If a "check" starts wanting to mutate the aggregate to answer its question, that's a sign the logic belongs inside the aggregate, not in a specification.

## License

This library is under the MIT license.

Copyright Florian Krämer
