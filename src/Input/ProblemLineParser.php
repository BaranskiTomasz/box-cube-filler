<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Input;

use Causeway\BoxCubeFiller\Cube\ValueObject\BoxSpecification;

final class ProblemLineParser implements ProblemLineParserInterface
{
    public function parse(string $line): BoxSpecification
    {
        $trimmedLine = trim($line);
        if ($trimmedLine === '') {
            throw new \InvalidArgumentException('Invalid input parameters. Missing data.');
        }

        $numberStrings = preg_split('/\s+/', $trimmedLine);

        $numbers = [];
        foreach ($numberStrings as $numberString) {
            $number = filter_var($numberString, FILTER_VALIDATE_INT);
            if ($number === false) {
                throw new \InvalidArgumentException('Invalid input parameters. Only integers are allowed.');
            }

            if ($number < 0) {
                throw new \InvalidArgumentException('Invalid input parameters. Negative values are not allowed.');
            }

            $numbers[] = $number;
        }

        if (count($numbers) < 3) {
            throw new \InvalidArgumentException('Invalid input parameters. Missing data.');
        }

        return new BoxSpecification(
            length: $numbers[0],
            width: $numbers[1],
            height: $numbers[2],
            supply: array_slice($numbers, 3),
        );
    }
}
