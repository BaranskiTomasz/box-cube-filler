<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Tests\Unit\Input;

use Causeway\BoxCubeFiller\Input\ProblemLineParser;
use PHPUnit\Framework\TestCase;

final class ProblemLineParserTest extends TestCase
{
    private ProblemLineParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ProblemLineParser();
    }

    public function testParsesDimensionsAndSupplyFromAWellFormedLine(): void
    {
        $specification = $this->parser->parse('2 3 4 5 6');

        self::assertSame(2, $specification->length);
        self::assertSame(3, $specification->width);
        self::assertSame(4, $specification->height);
        self::assertSame([5, 6], $specification->supply);
    }

    public function testRejectsALineContainingANonIntegerToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input parameters. Only integers are allowed.');

        $this->parser->parse('2 3 four 5 6');
    }

    public function testRejectsADecimalToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parse('2 3 4.5 5 6');
    }

    public function testRejectsALeadingZeroToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parse('2 3 007 5 6');
    }

    public function testRejectsAnEmptyLine(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input parameters. Missing data.');

        $this->parser->parse('');
    }

    public function testRejectsALineWithFewerThanThreeNumbers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input parameters. Missing data.');

        $this->parser->parse('1 2');
    }

    public function testParsesABoxWithSupplyOnThePositiveFlow(): void
    {
        $specification = $this->parser->parse('10 10 10 1 2');

        self::assertSame(10, $specification->length);
        self::assertSame(10, $specification->width);
        self::assertSame(10, $specification->height);
        self::assertSame([1, 2], $specification->supply);
    }

    public function testReturnsAnEmptySupplyWhenOnlyDimensionsAreGiven(): void
    {
        $specification = $this->parser->parse('2 3 4');

        self::assertSame([], $specification->supply);
    }

    public function testRejectsNegativeTokens(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input parameters. Negative values are not allowed.');

        $this->parser->parse('-2 -3 -4 -5');
    }

    public function testAcceptsALeadingPlusSign(): void
    {
        $specification = $this->parser->parse('+2 3 4 5');

        self::assertSame(2, $specification->length);
    }

    public function testCollapsesRunsOfWhitespaceBetweenTokens(): void
    {
        $specification = $this->parser->parse("2  3\t4   5  6");

        self::assertSame(2, $specification->length);
        self::assertSame(3, $specification->width);
        self::assertSame(4, $specification->height);
        self::assertSame([5, 6], $specification->supply);
    }

    public function testIgnoresLeadingAndTrailingWhitespaceOnTheLine(): void
    {
        $specification = $this->parser->parse('  2 3 4 5 6  ');

        self::assertSame(2, $specification->length);
        self::assertSame([5, 6], $specification->supply);
    }
}
