# E-Commerce Promotional System Example

A comprehensive example demonstrating complex business rules using the Specification Pattern in PHP. This example shows how to implement nested and composite specifications for e-commerce promotional campaigns.

## 🎯 Overview

This example implements a realistic e-commerce promotional eligibility system that validates whether customers, products, and orders qualify for various promotional campaigns. It demonstrates the power of the Specification Pattern for implementing complex, nested business rules that can be easily composed, tested, and maintained.

## 🏪 Business Scenario

The system implements four promotional campaigns with real-world business rules:

### 1. **Black Friday VIP Campaign** (25% off)
- Customer must be VIP (gold/platinum tier)
- Order total must be at least $100
- Must include products from electronics **OR** fashion categories
- Customer account must be older than 6 months
- Promotion valid only during November 20-30

### 2. **Loyalty Reward Campaign** (15% off)
- Customer has 1000+ loyalty points
- Not a new customer (account > 30 days)
- Order contains at least 3 items
- Excludes clearance products
- Can be combined with newsletter subscriber discount

### 3. **Flash Sale Campaign** (20% off)
- Time-sensitive (valid for specific hours, e.g., 12:00-14:00)
- Limited to specific product categories (electronics, fashion)
- Customer must not have used flash sale in last 7 days
- Order must be placed within stock availability window

### 4. **First Purchase Discount** (10% off)
- New customer (account < 30 days)
- First order ever
- Minimum order value $50
- Excludes gift cards and digital products

## 🚀 Running the Example

### Prerequisites
- PHP 8.1 or higher
- Composer

### Installation
1. Install dependencies:
```bash
composer install
```

2. Run the example:
```bash
php examples/ECommerce/run.php
```

### Sample Output
```
=== E-COMMERCE PROMOTIONAL ELIGIBILITY SYSTEM ===

Testing Order #1: John Doe (Gold, 1500 points)
- Order Total: $1469.96
- Items: 4 (electronics: 1, fashion: 2, books: 1)
- Account Age: 245 days

Checking Promotions:
✓ Black Friday VIP Campaign (25% off)
  ✓ Customer is VIP (Gold tier)
  ✓ Order total >= $100
  ✓ Contains electronics or fashion
  ✓ Account older than 6 months
  ✓ Date within promotion period

✓ Loyalty Reward Campaign (15% off)
  ✓ Loyalty points >= 1000
  ✓ Account older than 30 days
  ✓ Order has 3+ items
  ✓ No clearance products

✗ Flash Sale Campaign
  ✗ Not within flash sale time window

✗ First Purchase Discount
  ✗ Not a new customer
  ✗ Not first order

Total Applicable Discounts: 2
Best Discount: 25% (Black Friday VIP Campaign)
Final Price: $1102.47 (saved $367.49)
```

## 🏗️ Architecture

### Domain Models
- **`Customer`**: Represents customers with tier, loyalty points, account age, etc.
- **`Product`**: Represents products with category, price, clearance status, etc.
- **`Order`**: Represents orders with customer, items, totals, and metadata
- **`Promotion`**: Represents promotional campaigns with discount rules

### Specification Hierarchy

```
├── Customer/
│   ├── IsVipCustomer
│   ├── HasLoyaltyPoints
│   ├── AccountAge
│   ├── IsNewCustomer
│   └── HasNotUsedFlashSaleRecently
├── Product/
│   ├── ProductCategory
│   ├── IsNotClearance
│   ├── IsNotDigitalProduct
│   └── PriceRange
├── Order/
│   ├── MinimumOrderValue
│   ├── MinimumItemCount
│   ├── ContainsProductCategory
│   └── IsFirstOrder
├── Time/
│   ├── DateRange
│   └── TimeRange
└── Campaigns/
    ├── BlackFridayVipCampaign (Composite)
    ├── LoyaltyRewardCampaign (Composite)
    ├── FlashSaleCampaign (Composite)
    └── FirstPurchaseCampaign (Composite)
```

### Complex Specification Composition

The Black Friday VIP Campaign demonstrates nested AND/OR logic:

```php
// Black Friday VIP Campaign
$blackFridaySpec =
    (new IsVipCustomer(['gold', 'platinum']))
        ->and(new MinimumOrderValue(100))
        ->and(
            (new ContainsProductCategory('electronics'))
                ->or(new ContainsProductCategory('fashion'))
        )
        ->and(new AccountAge(180, 'min'))
        ->and(new DateRange('2025-11-20', '2025-11-30'));
```

## 📋 Test Scenarios

The example includes diverse test cases:

1. **VIP customer during Black Friday** - Multiple promotions apply
2. **Loyal customer with high points** - Loyalty rewards
3. **New customer with first order** - First purchase discount
4. **Customer during flash sale window** - Time-sensitive rules
5. **Edge cases** - Customers who almost qualify but miss criteria

## 🎯 Key Benefits Demonstrated

### 1. **Reusability**
Individual specifications are used across multiple campaigns:
- `AccountAge` used in Black Friday and Loyalty campaigns
- `ContainsProductCategory` used in multiple promotions

### 2. **Maintainability**
Business rules are centralized and easy to modify:
```php
// Change VIP tiers across all campaigns
new IsVipCustomer(['gold', 'platinum', 'diamond']);
```

### 3. **Testability**
Each specification can be unit tested independently:
```php
$spec = new IsVipCustomer(['gold']);
$this->assertTrue($spec->isSatisfiedBy($vipCustomer));
$this->assertFalse($spec->isSatisfiedBy($regularCustomer));
```

### 4. **Composability**
Complex rules built from simple, atomic specifications:
```php
$complexRule = $customerRule->and($orderRule)->and($timeRule);
```

### 5. **Readability**
Business rules expressed in domain language:
```php
$loyaltyCampaign = $hasPoints->and($notNewCustomer)->and($hasMinItems);
```

### 6. **Flexibility**
New campaigns created by combining existing specifications:
```php
$newCampaign = $existingSpec1->or($existingSpec2)->and($newSpec);
```

## 🔧 Extending the System

### Adding a New Campaign
1. Create atomic specifications if needed
2. Compose them into a campaign specification
3. Add to the promotion engine

```php
class HolidaySeasonCampaign extends AbstractSpecification
{
    public function __construct()
    {
        $this->specification = (new DateRange('2025-12-01', '2025-12-31'))
            ->and(new MinimumOrderValue(75))
            ->and(new IsNewsletterSubscriber());
    }
}
```

### Adding New Specifications
```php
class IsNewsletterSubscriber extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate instanceof Customer && $candidate->isNewsletterSubscriber;
    }
}
```

## 📊 Business Rule Examples

| Campaign | Customer Criteria | Order Criteria | Product Criteria | Time Criteria |
|----------|------------------|----------------|------------------|---------------|
| Black Friday VIP | VIP tier, 6+ months | ≥$100 | Electronics OR Fashion | Nov 20-30 |
| Loyalty Rewards | 1000+ points, 30+ days | ≥3 items | No clearance | Always |
| Flash Sale | Not used in 7 days | Any | Electronics/Fashion | 12:00-14:00 |
| First Purchase | <30 days, first order | ≥$50 | No digital/gift cards | Always |

## 🎪 Real-World Applications

This pattern is ideal for:
- **E-commerce**: Product recommendations, pricing rules, shipping eligibility
- **Financial Services**: Loan approvals, credit scoring, fraud detection
- **Insurance**: Policy eligibility, risk assessment, claims processing
- **Healthcare**: Treatment eligibility, appointment scheduling, billing rules
- **Travel**: Booking rules, loyalty programs, discount eligibility

## 🧪 Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
composer test -- --testsuite=unit
```

## 📈 Performance Considerations

- Specifications are evaluated lazily
- Composite specifications short-circuit when possible
- Results can be cached for expensive operations
- Database queries can be optimized based on specification requirements

## 🤝 Contributing

When adding new specifications:
1. Follow the existing naming conventions
2. Add comprehensive unit tests
3. Update this README with business rules
4. Ensure composability with existing specifications

---

This example demonstrates how the Specification Pattern enables clean, maintainable, and testable implementations of complex business rules in domain-driven design.