<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Input;

use Causeway\BoxCubeFiller\Cube\ValueObject\BoxSpecification;

interface ProblemLineParserInterface
{
    public function parse(string $line): BoxSpecification;
}
