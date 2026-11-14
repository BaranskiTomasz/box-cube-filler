<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class EntryPointTest extends TestCase
{
    public function testProducesTheProblemStatementsExpectedOutputWhenRunAsARealProcess(): void
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

        $expectedOutput = "1\n8\n1000\n-1\n9\n62\n59\n50070\n9\n";

        [$exitCode, $stdout, $stderr] = $this->runEntryPoint($input);

        self::assertSame(0, $exitCode, "stderr was: {$stderr}");
        self::assertSame($expectedOutput, $stdout);
    }

    /**
     * @return array{0: int, 1: string, 2: string} exit code, stdout, stderr
     */
    private function runEntryPoint(string $stdinContent): array
    {
        $binary = dirname(__DIR__, 2) . '/bin/myprogram';
        $process = proc_open(
            ['php', $binary],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        self::assertIsResource($process);

        fwrite($pipes[0], $stdinContent);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [$exitCode, $stdout, $stderr];
    }
}
