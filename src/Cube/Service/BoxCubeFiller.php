<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Cube\Service;

use Causeway\BoxCubeFiller\Cube\ValueObject\BoxSpecification;

final class BoxCubeFiller implements BoxCubeFillerInterface
{
    /** @var array<string, array<int, int>> */
    private array $cubeCountsByLevelCache = [];

    public function minCubes(BoxSpecification $specification): int
    {
        $this->cubeCountsByLevelCache = [];

        $cubeCountsByLevel = $this->computeIdealCubeCountsByLevel(
            $specification->length,
            $specification->width,
            $specification->height,
        );

        if ($cubeCountsByLevel === []) {
            return 0;
        }

        $totalCubeCount = 0;
        $shortfallCubeCount = 0;

        for ($cubeLevel = max(array_keys($cubeCountsByLevel)); $cubeLevel >= 0; $cubeLevel--) {
            $neededCubeCount = ($cubeCountsByLevel[$cubeLevel] ?? 0) + $shortfallCubeCount;
            $availableCubeCount = min($neededCubeCount, $specification->supplyAtLevel($cubeLevel));
            $totalCubeCount += $availableCubeCount;
            $shortfallCubeCount = ($neededCubeCount - $availableCubeCount) * 8;
        }

        return $shortfallCubeCount > 0 ? -1 : $totalCubeCount;
    }

    /**
     * @return array<int, int>
     */
    private function computeIdealCubeCountsByLevel(int $length, int $width, int $height): array
    {
        if ($length === 0 || $width === 0 || $height === 0) {
            return [];
        }

        $cacheKey = "{$length}:{$width}:{$height}";
        if (isset($this->cubeCountsByLevelCache[$cacheKey])) {
            return $this->cubeCountsByLevelCache[$cacheKey];
        }

        [$cubeSize, $cubeLevel] = $this->computeLargestFittingCubeSize(min($length, $width, $height));

        if ($length === $cubeSize && $width === $cubeSize && $height === $cubeSize) {
            return $this->cubeCountsByLevelCache[$cacheKey] = [$cubeLevel => 1];
        }

        $cubeCountsByLevel = [];
        foreach ($this->splitDimensionByCubeSize($length, $cubeSize) as [$subLength, $lengthRepeatCount]) {
            foreach ($this->splitDimensionByCubeSize($width, $cubeSize) as [$subWidth, $widthRepeatCount]) {
                foreach ($this->splitDimensionByCubeSize($height, $cubeSize) as [$subHeight, $heightRepeatCount]) {
                    $repeatCount = $lengthRepeatCount * $widthRepeatCount * $heightRepeatCount;
                    foreach ($this->computeIdealCubeCountsByLevel($subLength, $subWidth, $subHeight) as $subCubeLevel => $count) {
                        $cubeCountsByLevel[$subCubeLevel] = ($cubeCountsByLevel[$subCubeLevel] ?? 0) + $count * $repeatCount;
                    }
                }
            }
        }

        return $this->cubeCountsByLevelCache[$cacheKey] = $cubeCountsByLevel;
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    private function splitDimensionByCubeSize(int $dimension, int $cubeSize): array
    {
        if ($dimension <= $cubeSize) {
            return [[$dimension, 1]];
        }

        $fullSliceCount = intdiv($dimension, $cubeSize);
        $remainder = $dimension % $cubeSize;

        $pieces = [[$cubeSize, $fullSliceCount]];
        if ($remainder > 0) {
            $pieces[] = [$remainder, 1];
        }

        return $pieces;
    }

    private function computeLargestFittingCubeSize(int $upperBound): array
    {
        $value = 1;
        $cubeLevel = 0;
        while ($value <= intdiv($upperBound, 2)) {
            $value *= 2;
            $cubeLevel++;
        }

        return [$value, $cubeLevel];
    }
}
