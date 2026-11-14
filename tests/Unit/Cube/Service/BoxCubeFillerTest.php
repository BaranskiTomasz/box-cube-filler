<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Tests\Unit\Cube\Service;

use Causeway\BoxCubeFiller\Cube\Service\BoxCubeFiller;
use Causeway\BoxCubeFiller\Cube\ValueObject\BoxSpecification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoxCubeFillerTest extends TestCase
{
    private BoxCubeFiller $filler;

    protected function setUp(): void
    {
        $this->filler = new BoxCubeFiller();
    }

    /**
     * @param list<int> $supply
     */
    #[DataProvider('problemStatementExamples')]
    public function testMatchesProblemStatementExamples(int $length, int $width, int $height, array $supply, int $expected): void
    {
        $specification = new BoxSpecification($length, $width, $height, $supply);

        self::assertSame($expected, $this->filler->minCubes($specification));
    }

    /**
     * @return iterable<string, array{int, int, int, list<int>, int}>
     */
    public static function problemStatementExamples(): iterable
    {
        yield '2x2x2 box, one 2-cube suffices' => [2, 2, 2, [8, 1], 1];
        yield '1x2x4 box, the 2-cube does not fit, falls back to unit cubes' => [1, 2, 4, [8, 1], 8];
        yield '10x10x10 with ample unit-cube supply' => [10, 10, 10, [2000], 1000];
        yield '10x10x10 with insufficient unit-cube supply is impossible' => [10, 10, 10, [900], -1];
        yield '4x4x8 with only one 4-cube available' => [4, 4, 8, [10, 10, 1], 9];
        yield '5x5x5 mixed supply' => [5, 5, 5, [61, 7, 1], 62];
        yield '5x5x6 mixed supply' => [5, 5, 6, [61, 4, 1], 59];
        yield '1000x1000x1000 large mixed supply' => [1000, 1000, 1000, [0, 0, 0, 46501, 0, 2791, 631, 127, 19, 1], 50070];
        yield '1x1x9 thin box, only unit cubes possible' => [1, 1, 9, [9, 1], 9];
    }

    public function testReturnsZeroForAZeroVolumeBox(): void
    {
        $specification = new BoxSpecification(0, 5, 5, []);

        self::assertSame(0, $this->filler->minCubes($specification));
    }

    public function testReturnsMinusOneWhenNoCubesAreSuppliedForANonEmptyBox(): void
    {
        $specification = new BoxSpecification(2, 2, 2, []);

        self::assertSame(-1, $this->filler->minCubes($specification));
    }

    public function testHandlesAnAdversarialNearPowerOfTwoBoxQuickly(): void
    {
        $specification = new BoxSpecification(1023, 1023, 1023, array_fill(0, 11, 2_000_000_000));

        $start = microtime(true);
        $result = $this->filler->minCubes($specification);
        $elapsedSeconds = microtime(true) - $start;

        self::assertSame(4_175_956, $result);
        self::assertLessThan(1.0, $elapsedSeconds, 'a near-power-of-two box must not recompute identical sub-boxes once per recursion path');
    }

    public function testHandlesAnAdversarialNearPowerOfTwoBoxAtExtremeScaleOnlyBecauseOfTheCache(): void
    {
        $dimension = (2 ** 60) - 1;
        $specification = new BoxSpecification($dimension, $dimension, $dimension, [0]);

        $start = microtime(true);
        $result = $this->filler->minCubes($specification);
        $elapsedSeconds = microtime(true) - $start;

        self::assertSame(-1, $result);
        self::assertLessThan(
            0.1,
            $elapsedSeconds,
            'at this scale, slice counting alone is not enough; the per-call cache is what keeps this fast',
        );
    }

    public function testHandlesAnElongatedBoxWithoutUnboundedRecursion(): void
    {
        $specification = new BoxSpecification(100_000_000, 1, 1, [100_000_000]);

        $start = microtime(true);
        $result = $this->filler->minCubes($specification);
        $elapsedSeconds = microtime(true) - $start;

        self::assertSame(100_000_000, $result);
        self::assertLessThan(1.0, $elapsedSeconds, 'splitting an oversized axis must count full-size slices directly, not recurse once per unit');
    }

    public function testHandlesAPhpIntMaxDimensionWithoutOverflowing(): void
    {
        $specification = new BoxSpecification(PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX, [0]);

        self::assertSame(-1, $this->filler->minCubes($specification));
    }
}
