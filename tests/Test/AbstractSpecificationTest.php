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

namespace Phauthentic\Specification\Test;

use Phauthentic\Specification\AbstractSpecification;
use Phauthentic\Specification\AndNotSpecification;
use Phauthentic\Specification\AndSpecification;
use Phauthentic\Specification\NotSpecification;
use Phauthentic\Specification\OrNotSpecification;
use Phauthentic\Specification\OrSpecification;
use Phauthentic\Specification\Test\Specifications\ClosureSpecification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractSpecification::class)]
#[CoversClass(AndSpecification::class)]
#[CoversClass(AndNotSpecification::class)]
#[CoversClass(OrSpecification::class)]
#[CoversClass(OrNotSpecification::class)]
#[CoversClass(NotSpecification::class)]
final class AbstractSpecificationTest extends TestCase
{
    /**
     * * Tests the and() method returns an AndSpecification instance
     */
    public function testAndMethod(): void
    {
        $spec1 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test';
        });

        $spec2 = new ClosureSpecification(function ($candidate) {
            return is_string($candidate);
        });

        $result = $spec1->and($spec2);

        $this->assertInstanceOf(AndSpecification::class, $result);
        $this->assertTrue($result->isSatisfiedBy('test'));
        $this->assertFalse($result->isSatisfiedBy('test2'));
    }

    /**
     * * Tests the andNot() method returns an AndNotSpecification instance
     */
    public function testAndNotMethod(): void
    {
        $spec1 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test';
        });

        $spec2 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test2';
        });

        $result = $spec1->andNot($spec2);

        $this->assertInstanceOf(AndNotSpecification::class, $result);
        $this->assertTrue($result->isSatisfiedBy('test'));
        $this->assertFalse($result->isSatisfiedBy('test2'));
    }

    /**
     * * Tests the or() method returns an OrSpecification instance
     */
    public function testOrMethod(): void
    {
        $spec1 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test';
        });

        $spec2 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test2';
        });

        $result = $spec1->or($spec2);

        $this->assertInstanceOf(OrSpecification::class, $result);
        $this->assertTrue($result->isSatisfiedBy('test'));
        $this->assertTrue($result->isSatisfiedBy('test2'));
        $this->assertFalse($result->isSatisfiedBy('test3'));
    }

    /**
     * * Tests the orNot() method returns an OrNotSpecification instance
     */
    public function testOrNotMethod(): void
    {
        $spec1 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test';
        });

        $spec2 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test2';
        });

        $result = $spec1->orNot($spec2);

        $this->assertInstanceOf(OrNotSpecification::class, $result);
        $this->assertTrue($result->isSatisfiedBy('test'));
        $this->assertFalse($result->isSatisfiedBy('test2'));
    }

    /**
     * * Tests the not() method returns a NotSpecification instance
     */
    public function testNotMethod(): void
    {
        $spec1 = new ClosureSpecification(function ($candidate) {
            return $candidate === 'test';
        });

        $result = $spec1->not();

        $this->assertInstanceOf(NotSpecification::class, $result);
        $this->assertFalse($result->isSatisfiedBy('test'));
        $this->assertTrue($result->isSatisfiedBy('test1'));
    }
}
