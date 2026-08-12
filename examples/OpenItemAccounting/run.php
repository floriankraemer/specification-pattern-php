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

use Phauthentic\Specification\Examples\OpenItemAccounting\Models\CustomerRiskClass;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningCandidate;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\DunningLevel;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\LedgerAccount;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\Money;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\OpenItem;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\Posting;
use Phauthentic\Specification\Examples\OpenItemAccounting\Models\PostingType;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Dunning\AccruesDunningInterest;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Dunning\DunningLevelEligibility;
use Phauthentic\Specification\Examples\OpenItemAccounting\Specifications\Dunning\RequiresLegalActionReferral;

/**
 * Open-Item Accounting / Dunning (Mahnwesen) Demo.
 *
 * This demonstrates the Specification Pattern applied to a real DDD aggregate
 * (LedgerAccount) driving a real accounting process: deciding which open items
 * to escalate through a multi-level dunning process, which items accrue
 * interest, and which must be referred to legal action.
 */

/**
 * Evaluates dunning candidates against the level, interest, and legal-referral specifications.
 */
class DunningEngine
{
    /**
     * @var array<array{level: DunningLevel, graceDays: int, minimumAmount: float}>
     */
    private array $levelRules = [
        ['level' => DunningLevel::FriendlyReminder, 'graceDays' => 7, 'minimumAmount' => 1.0],
        ['level' => DunningLevel::FirstDunning, 'graceDays' => 21, 'minimumAmount' => 25.0],
        ['level' => DunningLevel::SecondDunning, 'graceDays' => 35, 'minimumAmount' => 50.0],
        ['level' => DunningLevel::FinalDunning, 'graceDays' => 49, 'minimumAmount' => 100.0],
    ];

    private AccruesDunningInterest $interestSpec;

    private RequiresLegalActionReferral $legalReferralSpec;

    public function __construct()
    {
        $this->interestSpec = new AccruesDunningInterest();
        $this->legalReferralSpec = new RequiresLegalActionReferral();
    }

    /**
     * @return array<array{level: DunningLevel, eligible: bool}>
     */
    public function evaluateLevels(DunningCandidate $candidate): array
    {
        return array_map(
            fn (array $rule): array => [
                'level' => $rule['level'],
                'eligible' => (new DunningLevelEligibility(
                    $rule['level'],
                    $rule['graceDays'],
                    $rule['minimumAmount']
                ))->isSatisfiedBy($candidate),
            ],
            $this->levelRules
        );
    }

    public function determineNextLevel(DunningCandidate $candidate): ?DunningLevel
    {
        $eligibleLevels = array_values(array_filter(
            $this->evaluateLevels($candidate),
            fn (array $result): bool => $result['eligible']
        ));

        if ($eligibleLevels === []) {
            return null;
        }

        return end($eligibleLevels)['level'];
    }

    public function accruesInterest(DunningCandidate $candidate): bool
    {
        return $this->interestSpec->isSatisfiedBy($candidate);
    }

    public function requiresLegalReferral(DunningCandidate $candidate): bool
    {
        return $this->legalReferralSpec->isSatisfiedBy($candidate);
    }
}

/**
 * Builds the sample LedgerAccounts used by the demo, each isolating one dunning rule.
 *
 * @return array<LedgerAccount>
 */
function createSampleAccounts(\DateTimeImmutable $asOf): array
{
    $currency = 'EUR';

    // Account 1: Standard customer, 40 days overdue - should escalate to Second Dunning.
    $acme = new LedgerAccount('10045', 'Acme Manufacturing GmbH', CustomerRiskClass::Standard, $currency);
    $acme->post(new Posting(
        'INV-1001',
        PostingType::Invoice,
        new Money(500.0, $currency),
        $asOf->modify('-45 days'),
        'Delivery of industrial parts',
        $asOf->modify('-40 days')
    ));

    // Account 2: Preferred customer, same 40 days overdue - risk-based leniency limits this to First Dunning.
    $nordic = new LedgerAccount('10046', 'Nordic Retail Group', CustomerRiskClass::Preferred, $currency);
    $nordic->post(new Posting(
        'INV-1002',
        PostingType::Invoice,
        new Money(500.0, $currency),
        $asOf->modify('-45 days'),
        'Quarterly stock replenishment',
        $asOf->modify('-40 days')
    ));

    // Account 3: High-risk customer, only 10 days overdue - risk-based penalty escalates it early.
    $fastFashion = new LedgerAccount('10047', 'Fast Fashion Express', CustomerRiskClass::HighRisk, $currency);
    $fastFashion->post(new Posting(
        'INV-1003',
        PostingType::Invoice,
        new Money(50.0, $currency),
        $asOf->modify('-15 days'),
        'Sample order',
        $asOf->modify('-10 days')
    ));

    // Account 4: Standard customer, 60 days overdue and disputed - dispute halts every rule.
    $bergmann = new LedgerAccount('10048', 'Bergmann Logistics', CustomerRiskClass::Standard, $currency);
    $bergmann->post(new Posting(
        'INV-1004',
        PostingType::Invoice,
        new Money(1000.0, $currency),
        $asOf->modify('-65 days'),
        'Freight forwarding services',
        $asOf->modify('-60 days')
    ));
    $bergmann->disputeOpenItem('INV-1004', true);

    // Account 5: Standard customer already at Final Dunning - triggers interest accrual and legal referral.
    $continental = new LedgerAccount('10049', 'Continental Foods Ltd', CustomerRiskClass::Standard, $currency);
    $continental->post(new Posting(
        'INV-1005',
        PostingType::Invoice,
        new Money(5000.0, $currency),
        $asOf->modify('-60 days'),
        'Bulk raw material supply',
        $asOf->modify('-55 days')
    ));
    $continental->escalateDunning('INV-1005', DunningLevel::FinalDunning, $asOf->modify('-20 days'));

    // Account 6: Standard customer, blocked for dunning (e.g. insolvency proceedings) - blocks every rule.
    $vantage = new LedgerAccount('10050', 'Vantage Industrial Supply', CustomerRiskClass::Standard, $currency);
    $vantage->post(new Posting(
        'INV-1006',
        PostingType::Invoice,
        new Money(2000.0, $currency),
        $asOf->modify('-95 days'),
        'Machinery parts order',
        $asOf->modify('-90 days')
    ));
    $vantage->block();

    return [$acme, $nordic, $fastFashion, $bergmann, $continental, $vantage];
}

/**
 * @param array<array{level: DunningLevel, eligible: bool}> $levels
 */
function printLevelEligibility(array $levels): void
{
    foreach ($levels as $result) {
        $symbol = $result['eligible'] ? '✓' : '✗';
        echo "    {$symbol} {$result['level']->name}\n";
    }
}

function escalateIfEligible(LedgerAccount $account, OpenItem $item, \DateTimeImmutable $asOf, ?DunningLevel $nextLevel): void
{
    if ($nextLevel === null) {
        echo "  → No escalation this run\n";
        return;
    }

    $account->escalateDunning($item->id, $nextLevel, $asOf);
    echo "  → Escalated to {$nextLevel->name}\n";
}

function printOpenItem(LedgerAccount $account, OpenItem $item, \DateTimeImmutable $asOf, DunningEngine $engine): void
{
    $candidate = new DunningCandidate($account, $item, $asOf);

    echo "  Open Item {$item->id}: {$item->getOpenAmount()} open of {$item->originalAmount}\n";
    echo "  - Due: {$item->dueDate->format('Y-m-d')} ({$item->daysOverdue($asOf)} days overdue)\n";
    echo '  - Disputed: ' . ($item->isDisputed() ? 'YES' : 'no') . "\n";

    $lastRun = $item->getLastDunningDate();
    $lastRunSuffix = $lastRun !== null ? " (last run {$lastRun->format('Y-m-d')})" : '';
    echo "  - Current Level: {$item->getCurrentDunningLevel()->name}{$lastRunSuffix}\n";

    echo "  Level Eligibility:\n";
    printLevelEligibility($engine->evaluateLevels($candidate));

    escalateIfEligible($account, $item, $asOf, $engine->determineNextLevel($candidate));

    echo '  Accrues Interest: ' . ($engine->accruesInterest($candidate) ? 'YES' : 'no') . "\n";
    echo '  Requires Legal Referral: ' . ($engine->requiresLegalReferral($candidate) ? 'YES' : 'no') . "\n";
    echo "\n";
}

function printAccount(LedgerAccount $account, \DateTimeImmutable $asOf, DunningEngine $engine): void
{
    echo "Account {$account->accountNumber}: {$account->customerName}\n";
    echo "- Risk Class: {$account->riskClass->name}\n";
    echo '- Blocked For Dunning: ' . ($account->isBlockedForDunning() ? 'YES' : 'no') . "\n\n";

    foreach ($account->getOpenItems() as $item) {
        printOpenItem($account, $item, $asOf, $engine);
    }

    echo "---\n\n";
}

function printSummary(): void
{
    echo "=== SUMMARY ===\n\n";
    echo "This demo showcases complex, real-world accounting rules implemented using the Specification Pattern:\n\n";
    echo "- Dunning level escalation: one parameterized specification reused across four escalation levels\n";
    echo "- Risk-based leniency: the same rule adapts its grace period per customer risk class\n";
    echo "- Dispute handling: a single atomic specification halts every downstream rule for a disputed item\n";
    echo "- Interest accrual: an independent rule composed from the same atomic building blocks, using AndNot to exempt preferred customers\n";
    echo "- Legal referral: a rule that depends on the aggregate's own dunning history (last run date)\n";
    echo "- Account-level blocking: one specification composed into every rule, so blocking an account halts the entire process without touching level, interest, or legal logic\n";
}

function runDemo(): void
{
    echo "=== OPEN-ITEM ACCOUNTING / DUNNING RUN ===\n\n";

    $asOf = new \DateTimeImmutable('2026-08-12');
    $accounts = createSampleAccounts($asOf);
    $engine = new DunningEngine();

    foreach ($accounts as $account) {
        printAccount($account, $asOf, $engine);
    }

    printSummary();
}

runDemo();
