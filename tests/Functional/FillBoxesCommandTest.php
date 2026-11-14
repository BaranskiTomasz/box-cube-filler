<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Tests\Functional;

use Causeway\BoxCubeFiller\Command\FillBoxesCommand;
use Causeway\BoxCubeFiller\Cube\Service\BoxCubeFiller;
use Causeway\BoxCubeFiller\Input\ProblemLineParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class FillBoxesCommandTest extends TestCase
{
    public function testProducesExactlyTheProblemStatementsExpectedOutputForItsSampleInput(): void
    {
        $input = <<<'TXT'
            2 2 2 8 1
            1 2 4 8 1
            10 10 10 2000
            10 10 10 900
            4 4 8 10 10 1
            5 5 5 61 7 1
            5 5 6 61 4 1
            1000 1000 1000 0 0 0 46501 0 2791 631 127 19 1
            1 1 9 9 1
            TXT;

        $expectedOutput = <<<'TXT'
            1
            8
            1000
            -1
            9
            62
            59
            50070
            9

            TXT;

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $input);
        rewind($stream);

        $command = new FillBoxesCommand(new ProblemLineParser(), new BoxCubeFiller(), $stream);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame($expectedOutput, $tester->getDisplay());
    }

    public function testSkipsBlankLines(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "2 2 2 8 1\n\n\n1 2 4 8 1\n");
        rewind($stream);

        $command = new FillBoxesCommand(new ProblemLineParser(), new BoxCubeFiller(), $stream);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame("1\n8\n", $tester->getDisplay());
    }

    public function testReportsAnInvalidLineButKeepsProcessingTheRest(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "2 2 2 8 1\n2 3 four 5 6\n1 2 4 8 1\n");
        rewind($stream);

        $command = new FillBoxesCommand(new ProblemLineParser(), new BoxCubeFiller(), $stream);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(
            "1\nInvalid input parameters. Only integers are allowed.\n8\n",
            $tester->getDisplay(),
        );
    }

    public function testWritesDiagnosticsToStderrSeparatelyFromAnswersOnStdout(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "2 2 2 8 1\n2 3 four 5 6\n1 2 4 8 1\n");
        rewind($stream);

        $command = new FillBoxesCommand(new ProblemLineParser(), new BoxCubeFiller(), $stream);
        $tester = new CommandTester($command);
        $tester->execute([], ['capture_stderr_separately' => true]);

        self::assertSame("1\n8\n", $tester->getDisplay());
        self::assertSame("Invalid input parameters. Only integers are allowed.\n", $tester->getErrorOutput());
    }

    public function testReturnsSuccessAndWritesNothingForEmptyInput(): void
    {
        $stream = fopen('php://memory', 'r+');

        $command = new FillBoxesCommand(new ProblemLineParser(), new BoxCubeFiller(), $stream);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
    }

    public function testProcessesALargeBatchQuicklyWithoutUnboundedCacheGrowth(): void
    {
        $lineCount = 5000;
        $lines = [];
        for ($i = 0; $i < $lineCount; $i++) {
            $dimension = 500 + $i;
            $lines[] = "{$dimension} {$dimension} {$dimension} " . str_repeat('1000000 ', 20);
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, implode("\n", $lines) . "\n");
        rewind($stream);

        $filler = new BoxCubeFiller();
        $command = new FillBoxesCommand(new ProblemLineParser(), $filler, $stream);
        $tester = new CommandTester($command);

        $start = microtime(true);
        $tester->execute([]);
        $elapsedSeconds = microtime(true) - $start;

        $outputLines = explode("\n", rtrim($tester->getDisplay(), "\n"));
        self::assertCount($lineCount, $outputLines);
        self::assertLessThan(5.0, $elapsedSeconds, 'a large batch of distinct boxes must stay fast');

        $cacheProperty = new \ReflectionProperty(BoxCubeFiller::class, 'cubeCountsByLevelCache');
        $cacheEntryCount = count($cacheProperty->getValue($filler));

        self::assertLessThan(
            200,
            $cacheEntryCount,
            'the cache must be scoped to one minCubes() call, not accumulate across unrelated problems in the same batch',
        );
    }
}
