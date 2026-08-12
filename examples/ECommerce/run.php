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

use Phauthentic\Specification\Examples\ECommerce\Models\Customer;
use Phauthentic\Specification\Examples\ECommerce\Models\Product;
use Phauthentic\Specification\Examples\ECommerce\Models\Order;
use Phauthentic\Specification\Examples\ECommerce\Models\Promotion;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Campaigns\BlackFridayVipCampaign;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Campaigns\LoyaltyRewardCampaign;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Campaigns\FlashSaleCampaign;
use Phauthentic\Specification\Examples\ECommerce\Specifications\Campaigns\FirstPurchaseCampaign;

/**
 * E-commerce Promotional Eligibility System Demo
 *
 * This demonstrates complex business rules using the Specification Pattern
 * with nested and composite specifications.
 */

class PromotionEngine
{
    /** @var array<Promotion> */
    private array $promotions = [];

    public function addPromotion(Promotion $promotion): void
    {
        $this->promotions[] = $promotion;
    }

    /**
     * @return array<Promotion>
     */
    public function getEligiblePromotions(Order $order): array
    {
        return array_filter($this->promotions, fn($promotion) => $promotion->isEligible($order));
    }

    public function getBestPromotion(Order $order): ?Promotion
    {
        $eligible = $this->getEligiblePromotions($order);

        if (empty($eligible)) {
            return null;
        }

        // Return the promotion with highest discount percentage
        usort($eligible, fn($a, $b) => $b->discountPercentage <=> $a->discountPercentage);

        return $eligible[0];
    }
}

// Sample data creation
function createSampleProducts(): array
{
    return [
        new Product('1', 'iPhone 15', 'electronics', 999.99, false, false, 10),
        new Product('2', 'Samsung TV', 'electronics', 799.99, false, false, 5),
        new Product('3', 'Designer Dress', 'fashion', 299.99, false, false, 20),
        new Product('4', 'Running Shoes', 'fashion', 149.99, false, false, 15),
        new Product('5', 'Coffee Maker', 'home', 89.99, false, false, 8),
        new Product('6', 'Novel Book', 'books', 19.99, false, false, 50),
        new Product('7', 'Clearance Shirt', 'fashion', 29.99, true, false, 12),
        new Product('8', 'Digital Game', 'books', 49.99, false, true, 100),
        new Product('9', 'Gift Card', 'gift', 50.00, false, false, 25),
    ];
}

function createSampleCustomers(): array
{
    $now = new \DateTimeImmutable('2025-11-25 13:00:00'); // During Black Friday

    return [
        // VIP Gold customer - should qualify for Black Friday
        new Customer(
            '1',
            'John Doe',
            'john@example.com',
            'gold',
            1500,
            $now->sub(new \DateInterval('P245D')), // Account 8 months old
            null, // Never used flash sale
            true
        ),

        // Loyal customer with high points - should qualify for loyalty rewards
        new Customer(
            '2',
            'Jane Smith',
            'jane@example.com',
            'silver',
            1200,
            $now->sub(new \DateInterval('P90D')), // Account 3 months old
            null,
            true
        ),

        // New customer - should qualify for first purchase
        new Customer(
            '3',
            'Bob Wilson',
            'bob@example.com',
            'bronze',
            0,
            $now->sub(new \DateInterval('P15D')), // Account 15 days old
            null,
            false
        ),

        // Customer who recently used flash sale - won't qualify for flash sale
        new Customer(
            '4',
            'Alice Brown',
            'alice@example.com',
            'silver',
            800,
            $now->sub(new \DateInterval('P60D')), // Account 2 months old
            $now->sub(new \DateInterval('P3D')), // Used flash sale 3 days ago
            true
        ),

        // New VIP - account too young for Black Friday
        new Customer(
            '5',
            'Charlie Davis',
            'charlie@example.com',
            'platinum',
            500,
            $now->sub(new \DateInterval('P20D')), // Account 20 days old
            null,
            true
        ),
    ];
}

function createSampleOrders(array $customers, array $products): array
{
    $now = new \DateTimeImmutable('2025-11-25 13:00:00'); // During Black Friday & flash sale

    return [
        // Order 1: VIP Gold during Black Friday - should qualify for multiple promotions
        new Order(
            '1',
            $customers[0], // John Doe - VIP Gold
            [$products[0], $products[2], $products[3], $products[5]], // iPhone, Dress, Shoes, Book
            999.99 + 299.99 + 149.99 + 19.99, // $1469.96
            4,
            $now,
            false // Not first order
        ),

        // Order 2: Loyal customer - should qualify for loyalty rewards
        new Order(
            '2',
            $customers[1], // Jane Smith - loyal with points
            [$products[1], $products[4], $products[5]], // TV, Coffee Maker, Book
            799.99 + 89.99 + 19.99, // $909.97
            3,
            $now,
            false
        ),

        // Order 3: New customer first order - should qualify for first purchase discount
        new Order(
            '3',
            $customers[2], // Bob Wilson - new customer
            [$products[3], $products[5]], // Shoes, Book
            149.99 + 19.99, // $169.98
            2,
            $now,
            true // First order
        ),

        // Order 4: Small order during flash sale - may not qualify
        new Order(
            '4',
            $customers[3], // Alice Brown - recently used flash sale
            [$products[0]], // iPhone
            999.99,
            1,
            $now,
            false
        ),

        // Order 5: New VIP with clearance items - won't qualify for Black Friday
        new Order(
            '5',
            $customers[4], // Charlie Davis - new VIP
            [$products[6], $products[5]], // Clearance Shirt, Book
            29.99 + 19.99, // $49.98
            2,
            $now,
            true
        ),

        // Order 6: Digital products - won't qualify for first purchase
        new Order(
            '6',
            $customers[2], // Bob Wilson again
            [$products[7], $products[8]], // Digital Game, Gift Card
            49.99 + 50.00, // $99.99
            2,
            $now->add(new \DateInterval('P1D')), // Next day
            false // Not first order
        ),
    ];
}

function formatOrderDetails(Order $order): string
{
    $categories = $order->getCategoryCounts();
    $categoryStr = implode(', ', array_map(
        fn($cat, $count) => "$cat: $count",
        array_keys($categories),
        array_values($categories)
    ));

    return sprintf(
        "%s (%s, %d points)\n- Order Total: $%.2f\n- Items: %d (%s)\n- Account Age: %d days\n- Order Date: %s",
        $order->customer->name,
        ucfirst($order->customer->tier),
        $order->customer->loyaltyPoints,
        $order->totalAmount,
        $order->itemCount,
        $categoryStr,
        $order->customer->getAccountAgeInDays(),
        $order->createdAt->format('Y-m-d H:i:s')
    );
}

function runDemo(): void
{
    echo "=== E-COMMERCE PROMOTIONAL ELIGIBILITY SYSTEM ===\n\n";

    // Create sample data
    $products = createSampleProducts();
    $customers = createSampleCustomers();
    $orders = createSampleOrders($customers, $products);

    // Create promotion engine with campaigns
    $engine = new PromotionEngine();
    $engine->addPromotion(new Promotion(
        'black-friday-vip',
        'Black Friday VIP Campaign',
        '25% off for VIP customers during Black Friday',
        25,
        new BlackFridayVipCampaign()
    ));
    $engine->addPromotion(new Promotion(
        'loyalty-reward',
        'Loyalty Reward Campaign',
        '15% off for loyal customers with rewards points',
        15,
        new LoyaltyRewardCampaign()
    ));
    $engine->addPromotion(new Promotion(
        'flash-sale',
        'Flash Sale Campaign',
        '20% off during limited time windows',
        20,
        new FlashSaleCampaign()
    ));
    $engine->addPromotion(new Promotion(
        'first-purchase',
        'First Purchase Discount',
        '10% off for new customers on first order',
        10,
        new FirstPurchaseCampaign()
    ));

    // Test each order
    foreach ($orders as $index => $order) {
        echo sprintf(
            "Testing Order #%d: %s\n\n",
            $index + 1,
            formatOrderDetails($order)
        );

        $eligiblePromotions = $engine->getEligiblePromotions($order);
        $bestPromotion = $engine->getBestPromotion($order);

        echo "Checking Promotions:\n";

        $promotionNames = [
            'black-friday-vip' => 'Black Friday VIP Campaign (25% off)',
            'loyalty-reward' => 'Loyalty Reward Campaign (15% off)',
            'flash-sale' => 'Flash Sale Campaign (20% off)',
            'first-purchase' => 'First Purchase Discount (10% off)',
        ];

        $eligibleIds = array_map(fn($p) => $p->id, $eligiblePromotions);

        foreach ($promotionNames as $id => $name) {
            $symbol = in_array($id, $eligibleIds) ? '✓' : '✗';
            echo "  $symbol $name\n";

            // Show why for ineligible promotions
            if (!in_array($id, $eligibleIds)) {
                $reason = getIneligibilityReason($order, $id);
                if ($reason) {
                    echo "    ✗ $reason\n";
                }
            }
        }

        echo sprintf("\nTotal Applicable Discounts: %d\n", count($eligiblePromotions));

        if ($bestPromotion) {
            $discount = $bestPromotion->calculateDiscount($order->totalAmount);
            $finalPrice = $bestPromotion->calculateFinalPrice($order->totalAmount);
            echo sprintf(
                "Best Discount: %d%% (%s)\n",
                $bestPromotion->discountPercentage,
                $bestPromotion->name
            );
            echo sprintf(
                "Final Price: $%.2f (saved $%.2f)\n",
                $finalPrice,
                $discount
            );
        } else {
            echo "No promotions apply\n";
        }

        echo "\n---\n\n";
    }

    // Summary
    echo "=== SUMMARY ===\n\n";
    echo "This demo showcases complex business rules implemented using the Specification Pattern:\n\n";
    echo "• Black Friday VIP Campaign: Nested AND/OR logic with customer tier, order value, product categories, account age, and date ranges\n";
    echo "• Loyalty Reward Campaign: Customer points, account age, item count, and product type exclusions\n";
    echo "• Flash Sale Campaign: Time-sensitive promotions with customer usage restrictions\n";
    echo "• First Purchase Campaign: New customer validation with product exclusions\n\n";
    echo "Each campaign demonstrates how atomic specifications can be composed into complex business rules.\n";
}

function getIneligibilityReason(Order $order, string $promotionId): ?string
{
    switch ($promotionId) {
        case 'black-friday-vip':
            if (!in_array($order->customer->tier, ['gold', 'platinum'])) {
                return 'Customer is not VIP (Gold/Platinum)';
            }
            if ($order->totalAmount < 100) {
                return 'Order total below $100 minimum';
            }
            if (!$order->containsCategory('electronics') && !$order->containsCategory('fashion')) {
                return 'Order does not contain electronics or fashion products';
            }
            if ($order->customer->getAccountAgeInDays() < 180) {
                return 'Account younger than 6 months';
            }
            return 'Not within Black Friday date range';

        case 'loyalty-reward':
            if ($order->customer->loyaltyPoints < 1000) {
                return 'Insufficient loyalty points (< 1000)';
            }
            if ($order->customer->getAccountAgeInDays() < 30) {
                return 'Account too new (< 30 days)';
            }
            if ($order->itemCount < 3) {
                return 'Order has fewer than 3 items';
            }
            if ($order->hasClearanceItems()) {
                return 'Order contains clearance products';
            }
            return null;

        case 'flash-sale':
            $daysSinceFlashSale = $order->customer->daysSinceLastFlashSale();
            if ($daysSinceFlashSale !== null && $daysSinceFlashSale < 7) {
                return 'Customer used flash sale recently';
            }
            if (!$order->containsCategory('electronics') && !$order->containsCategory('fashion')) {
                return 'Order does not contain eligible categories';
            }
            return 'Not within flash sale time window';

        case 'first-purchase':
            if ($order->customer->getAccountAgeInDays() >= 30) {
                return 'Customer account too old (≥ 30 days)';
            }
            if (!$order->isFirstOrder) {
                return 'Not customer\'s first order';
            }
            if ($order->totalAmount < 50) {
                return 'Order total below $50 minimum';
            }
            if ($order->hasDigitalItems() || $order->hasGiftCards()) {
                return 'Order contains excluded products (digital/gift cards)';
            }
            return null;

        default:
            return null;
    }
}

// Run the demo
runDemo();
