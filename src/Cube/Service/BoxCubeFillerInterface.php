<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Cube\Service;

use Causeway\BoxCubeFiller\Cube\ValueObject\BoxSpecification;

interface BoxCubeFillerInterface
{
    public function minCubes(BoxSpecification $specification): int;
}
