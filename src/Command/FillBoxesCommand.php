<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Command;

use Causeway\BoxCubeFiller\Cube\Service\BoxCubeFillerInterface;
use Causeway\BoxCubeFiller\Input\ProblemLineParserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FillBoxesCommand extends Command
{
    /** @var resource */
    private $inputStream;

    /**
     * @param resource|null $inputStream
     */
    public function __construct(
        private readonly ProblemLineParserInterface $parser,
        private readonly BoxCubeFillerInterface $filler,
        $inputStream = null,
    ) {
        parent::__construct();
        $this->inputStream = $inputStream ?? STDIN;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:fill-boxes')
            ->setDescription(
                'Reads box-filling problems from stdin and prints the minimum cube count for each, one per line.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        while (($line = fgets($this->inputStream)) !== false) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            try {
                $specification = $this->parser->parse($line);
            } catch (\InvalidArgumentException $exception) {
                $errorOutput->writeln($exception->getMessage());
                continue;
            }

            $output->writeln((string) $this->filler->minCubes($specification));
        }

        return Command::SUCCESS;
    }
}
