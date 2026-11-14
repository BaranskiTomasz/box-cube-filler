<?php

declare(strict_types=1);

namespace Causeway\BoxCubeFiller\Cube\ValueObject;

final readonly class BoxSpecification
{
    /**
     * @param list<int> $supply
     */
    public function __construct(
        public int $length,
        public int $width,
        public int $height,
        public array $supply,
    ) {
        if ($length < 0 || $width < 0 || $height < 0) {
            throw new \InvalidArgumentException('Box dimensions must be non-negative integers.');
        }

        foreach ($supply as $count) {
            if ($count < 0) {
                throw new \InvalidArgumentException('Cube supply counts must be non-negative integers.');
            }
        }
    }

    public function supplyAtLevel(int $level): int
    {
        return $this->supply[$level] ?? 0;
    }
}
