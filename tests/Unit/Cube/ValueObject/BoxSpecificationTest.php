<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Tests\Unit\Cube\ValueObject;

use Causeway\BoxCubeFiller\Cube\ValueObject\BoxSpecification;
use PHPUnit\Framework\TestCase;

final class BoxSpecificationTest extends TestCase
{
    public function testAcceptsZeroAsADimensionOrSupplyCount(): void
    {
        $specification = new BoxSpecification(0, 0, 0, [0, 0]);

        self::assertSame(0, $specification->length);
        self::assertSame(0, $specification->supplyAtLevel(0));
    }

    public function testRejectsANegativeLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BoxSpecification(-1, 5, 5, []);
    }

    public function testRejectsANegativeWidth(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BoxSpecification(5, -1, 5, []);
    }

    public function testRejectsANegativeHeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BoxSpecification(5, 5, -1, []);
    }

    public function testRejectsANegativeSupplyCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BoxSpecification(2, 2, 2, [1, -1]);
    }

    public function testSupplyAtLevelReturnsZeroForALevelBeyondTheSuppliedList(): void
    {
        $specification = new BoxSpecification(2, 2, 2, [1]);

        self::assertSame(0, $specification->supplyAtLevel(5));
    }
}
