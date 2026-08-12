# How to Use Specifications

This document explains different approaches to using the Specification Pattern, particularly in the context of Domain-Driven Design (DDD) where aggregates should encapsulate their internal state.

## The Challenge: Specifications and Encapsulation

In strict DDD, aggregates should not expose their internal properties directly. This raises the question: How can a specification check conditions on an aggregate without breaking encapsulation?

```php
// This violates encapsulation - directly accessing internal state
public function isSatisfiedBy(mixed $candidate): bool
{
    return $candidate->totalAmount >= $this->minimumValue;
}
```

There are several approaches to solve this, each with its own trade-offs.

---

## Approach 1: Aggregate Exposes Query Methods

Instead of exposing properties, the aggregate provides behavior-focused query methods. The specification delegates the actual check to the aggregate.

**Pros:**

- Maintains encapsulation
- Business logic stays in the aggregate
- Specification remains composable

**Cons:**

- Aggregate interface grows with each new specification need
- May lead to many single-purpose query methods

### Example

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order;

/**
 * The Order aggregate - encapsulates all internal state
 */
class Order
{
    private float $totalAmount;
    private \DateTimeImmutable $dueDate;
    private bool $noticeSent;
    private bool $inCollection;

    public function __construct(
        float $totalAmount,
        \DateTimeImmutable $dueDate
    ) {
        $this->totalAmount = $totalAmount;
        $this->dueDate = $dueDate;
        $this->noticeSent = false;
        $this->inCollection = false;
    }

    // Query methods - expose questions, not data
    public function hasTotalAmountOfAtLeast(float $minimum): bool
    {
        return $this->totalAmount >= $minimum;
    }

    public function isOverdue(\DateTimeImmutable $now): bool
    {
        return $this->dueDate < $now;
    }

    public function hasNoticeSent(): bool
    {
        return $this->noticeSent;
    }

    public function isInCollection(): bool
    {
        return $this->inCollection;
    }

    // Command methods
    public function markNoticeSent(): void
    {
        $this->noticeSent = true;
    }

    public function sendToCollection(): void
    {
        $this->inCollection = true;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Specifications;

use App\Domain\Order\Order;
use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification that delegates to the aggregate's query method
 */
class MinimumOrderValueSpecification extends AbstractSpecification
{
    public function __construct(
        private float $minimumValue
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        // Delegate to the aggregate - no direct property access
        return $candidate->hasTotalAmountOfAtLeast($this->minimumValue);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Specifications;

use App\Domain\Order\Order;
use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification for checking if an order is overdue
 */
class OverdueSpecification extends AbstractSpecification
{
    public function __construct(
        private \DateTimeImmutable $referenceDate
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        return $candidate->isOverdue($this->referenceDate);
    }
}
```

### Usage

```php
$now = new \DateTimeImmutable();

$overdue = new OverdueSpecification($now);
$minimumValue = new MinimumOrderValueSpecification(100.00);
$noticeSent = new NoticeSentSpecification();
$inCollection = new InCollectionSpecification();

// Compose specifications
$sendToCollection = $overdue
    ->and($noticeSent)
    ->and($minimumValue)
    ->andNot($inCollection);

foreach ($orders as $order) {
    if ($sendToCollection->isSatisfiedBy($order)) {
        $order->sendToCollection();
    }
}
```

---

## Approach 2: Specifications on Read Models (CQRS)

In CQRS (Command Query Responsibility Segregation) architectures, specifications operate on read models or projections, not the aggregate itself. The aggregate remains fully encapsulated for write operations.

**Pros:**

- Complete separation of read and write concerns
- Read models can be optimized for queries
- Aggregate stays fully encapsulated

**Cons:**

- Requires maintaining separate read models
- Eventual consistency between write and read sides
- More infrastructure complexity

### Example

```php
<?php

declare(strict_types=1);

namespace App\ReadModel;

/**
 * Read model for orders - optimized for queries
 * This is a projection, not the aggregate
 */
class OrderReadModel
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $totalAmount,
        public readonly \DateTimeImmutable $dueDate,
        public readonly bool $noticeSent,
        public readonly bool $inCollection,
        public readonly string $customerName,
        public readonly \DateTimeImmutable $createdAt
    ) {
    }

    public function isOverdue(\DateTimeImmutable $now): bool
    {
        return $this->dueDate < $now;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\ReadModel\Specifications;

use App\ReadModel\OrderReadModel;
use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification operating on the read model
 */
class MinimumOrderValueSpecification extends AbstractSpecification
{
    public function __construct(
        private float $minimumValue
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof OrderReadModel) {
            return false;
        }

        // Direct property access is fine on read models
        return $candidate->totalAmount >= $this->minimumValue;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\ReadModel\Specifications;

use App\ReadModel\OrderReadModel;
use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification for overdue orders on read model
 */
class OverdueSpecification extends AbstractSpecification
{
    public function __construct(
        private \DateTimeImmutable $referenceDate
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof OrderReadModel) {
            return false;
        }

        return $candidate->isOverdue($this->referenceDate);
    }
}
```

### Usage

```php
// Read side - uses read models from a projection/query service
$orderReadModels = $orderQueryService->findAllPendingOrders();

$now = new \DateTimeImmutable();
$sendToCollection = (new OverdueSpecification($now))
    ->and(new NoticeSentSpecification())
    ->andNot(new InCollectionSpecification());

// Filter read models
$ordersToCollect = array_filter(
    $orderReadModels,
    fn(OrderReadModel $order) => $sendToCollection->isSatisfiedBy($order)
);

// Write side - load aggregates only for the ones that need action
foreach ($ordersToCollect as $orderReadModel) {
    $order = $orderRepository->getById($orderReadModel->orderId);
    $order->sendToCollection();
    $orderRepository->save($order);
}
```

---

## Approach 3: Pragmatic Property Access

Many real-world implementations take a pragmatic stance: specifications are used for query/filtering logic where exposing read-only properties is acceptable. The aggregate's invariants and mutation logic remain protected, but query-related properties can be exposed via getters or public readonly properties.

**Pros:**

- Simple and straightforward
- Works well for filtering/querying use cases
- Full composability of specifications

**Cons:**

- Some encapsulation is sacrificed
- Requires discipline to not misuse exposed properties
- Purists may object

### Example

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order;

/**
 * Order aggregate with readonly property access for queries
 */
class Order
{
    private bool $inCollection = false;

    public function __construct(
        public readonly string $id,
        public readonly float $totalAmount,
        public readonly \DateTimeImmutable $dueDate,
        private bool $noticeSent = false
    ) {
    }

    // Getter for mutable state
    public function isNoticeSent(): bool
    {
        return $this->noticeSent;
    }

    public function isInCollection(): bool
    {
        return $this->inCollection;
    }

    // Commands remain protected
    public function markNoticeSent(): void
    {
        $this->noticeSent = true;
    }

    public function sendToCollection(): void
    {
        $this->inCollection = true;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Specifications;

use App\Domain\Order\Order;
use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification using readonly properties
 */
class MinimumOrderValueSpecification extends AbstractSpecification
{
    public function __construct(
        private float $minimumValue
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        // Access readonly property directly
        return $candidate->totalAmount >= $this->minimumValue;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Order\Specifications;

use App\Domain\Order\Order;
use Phauthentic\Specification\AbstractSpecification;

/**
 * Specification for overdue orders
 */
class OverdueSpecification extends AbstractSpecification
{
    public function __construct(
        private \DateTimeImmutable $referenceDate
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof Order) {
            return false;
        }

        return $candidate->dueDate < $this->referenceDate;
    }
}
```

### Usage

```php
$now = new \DateTimeImmutable();

$sendToCollection = (new OverdueSpecification($now))
    ->and(new NoticeSentSpecification())
    ->andNot(new InCollectionSpecification());

foreach ($orders as $order) {
    if ($sendToCollection->isSatisfiedBy($order)) {
        $order->sendToCollection();
    }
}
```

## Approach 4: Double Dispatch (The Purist Approach)

The aggregate remains completely "blind" to its properties from the outside. Instead, the aggregate accepts the specification and "feeds" it the necessary data through a specific internal interface.

**Pros:**

- Zero leakage: Properties remain private and no getters are created.
- The aggregate controls exactly what data the specification is allowed to see.

**Cons:**

- Requires a custom interface for the specification.
- Slightly higher cognitive complexity.

```php
namespace App\Domain\Order;

/**
 * Interface that defines what data an Order Spec is allowed to see
 */
interface OrderDataInterface {
    public function getTotalAmount(): float;
    public function getDueDate(): \DateTimeImmutable;
}

class Order implements OrderDataInterface
{
    private float $totalAmount;
    private \DateTimeImmutable $dueDate;

    // The aggregate "accepts" the spec and passes itself as the data provider
    public function satisfies(OrderSpecificationInterface $spec): bool
    {
        return $spec->isSatisfiedByOrder($this);
    }

    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getDueDate(): \DateTimeImmutable { return $this->dueDate; }
}

// The Specification now depends on the Interface, not the Aggregate
class MinimumOrderValueSpecification implements OrderSpecificationInterface
{
    public function isSatisfiedByOrder(OrderDataInterface $order): bool
    {
        return $order->getTotalAmount() >= $this->minimumValue;
    }
}
```

---

## Choosing the Right Approach

| Approach | Best For | Avoid When |
|----------|----------|------------|
| **Query Methods** | Strict DDD, complex aggregates | Many specifications needed (interface bloat) |
| **Read Models (CQRS)** | Large systems, complex queries, event sourcing | Simple applications, tight deadlines |
| **Pragmatic Access** | Filtering, simple domains, rapid development | Strict encapsulation requirements |
| **Double Dispatch** | High encapsulation, shared logic | Overkill for simple logic; adds boilerplate |

## Combining Approaches

These approaches are not mutually exclusive. A common pattern is to use Read Models for UI filtering (performance) and Query Methods or Double Dispatch for domain-critical business rules inside your services. A common pattern is:

1. Use **Read Models** for complex queries and filtering (e.g., search, reports)
2. Use **Query Methods** for domain-critical business rules
3. Use **Internal Aggregate Methods** for invariant checks before state changes

```php
// Read side: filter candidates using specifications on read models
$candidates = $orderQueryService->findOverdueOrders();
$eligibleForCollection = (new NoticeSentSpecification())
    ->andNot(new InCollectionSpecification());

$toProcess = array_filter(
    $candidates,
    fn($order) => $eligibleForCollection->isSatisfiedBy($order)
);

// Write side: aggregate enforces its own invariants
foreach ($toProcess as $orderReadModel) {
    $order = $orderRepository->getById($orderReadModel->orderId);
    
    // Aggregate validates internally before state change
    $order->sendToCollection(); // May throw if invariants not met
    
    $orderRepository->save($order);
}
```
