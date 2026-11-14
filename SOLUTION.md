# Solution notes

## Approach

The task essentially comes down to two separate questions for each box:

1. If we had an unlimited supply of cubes of every size, how many cubes
   would an optimal arrangement use?
2. Given the actual, limited supply, how much of that ideal plan can we
   achieve, and is it even possible to fill the box?

**Phase 1, finding the ideal plan** (`BoxCubeFiller::computeIdealCubeCountsByLevel`).
At each step, I look for the largest cube (with a side length that is a
power of two) that can fit along all three dimensions; that's
`computeLargestFittingCubeSize()`, called with the shortest of the three
dimensions, `min(length, width, height)`, since that's what determines
the maximum cube size that can fit.

If all three dimensions turn out to be exactly that size, the entire box
is simply one cube of that size, and the recursion ends. Otherwise, I
split off a section of that size from each dimension that is longer
(`splitDimensionByCubeSize()`), then apply the same logic to the
remaining sections through three nested loops and a recursive call back
into `computeIdealCubeCountsByLevel()`. In the worst case, this splits
the box into up to eight smaller boxes, each processed the same way.

This is optimal because a cube of side `2^k` always beats eight cubes of
side `2^(k-1)` for the same volume, and any tiling of a `2^k`-cube region
can be refined into eight half-size cubes without changing the piece
count's lower bound. Along a single axis of width `n`, at most
`floor(n / 2^k)` disjoint intervals of length `2^k` fit regardless of their
offset, so no placement of same-size cubes can beat the corner-aligned
count. I checked this by hand against a few small cases (a 3x3x3 region
only ever fits one 2x2x2 cube, for example, confirmed by both the
recursive rule and by direct enumeration).

**Phase 2, the limited supply** (the loop in `BoxCubeFiller::minCubes`).
Once the ideal plan is computed, I walk it from the largest cube size
down to the smallest. At each level, I work out how many cubes of that
size are actually needed (the ideal plan's demand plus any shortage
carried down from the level above), then take as many as the real supply
allows (`BoxSpecification::supplyAtLevel()`). Any shortage doesn't
disappear: one missing cube can always be replaced by eight cubes of the
next smaller size, since that's exactly how many fit inside it, so the
shortage is multiplied by eight and carried down to the next level. If,
after processing the smallest cube size, a shortage still remains,
there's nowhere left to carry it, and the box cannot be filled (`-1`).

The result of phase 1 is computed once per distinct
`(length, width, height)` triple and cached, because the same sub-box
size often recurs at different positions within the same box (more on
this below).

## The first performance bug

My first version of phase 1 had no caching. `1000 1000 1000` ran almost
instantly, but `1023 1023 1023` took about 1.5 seconds and 5.2 million
calls for only 73 distinct `(length, width, height)` triples: the
recursion kept re-solving identical sub-problems instead of reusing
them.

The fix: cache phase 1's result per triple. Each axis only takes
`O(log(max_dimension))` distinct values, so the cache stays small no
matter how often a triple recurs. `1023 1023 1023` now resolves in under
a millisecond.

The slice-counting fix below turned out to independently collapse most
of this duplication too, so `1023 1023 1023` is fast even without any
cache. The cache still earns its keep at a larger scale (a
`(2^60 - 1)`-sided cube: 3 ms with it, half a second without), so I kept
it and moved the regression test there instead.

## The second performance bug

While stress-testing the fix above, I tried a different shape: a long,
thin box like `1000000 1 1`. Memory usage grew without bound; the
process consumed roughly 12 GB of RAM before the OS killed it.

The cause: with the smallest dimension at `1`, every recursive step is
forced down to cube size `1`, and my splitting logic peeled off exactly
one unit per call (`1000000` → `999999` → ...), about a million distinct
triples the cache never gets to reuse.

The fix: count full slices with `intdiv(dimension, cubeSize)` and
multiply, instead of recursing once per slice. That turns a million
calls into one, confirmed under a hard memory cap.

## The cache must not outlive a single box

The two fixes above make one large box fast on its own, but
`FillBoxesCommand` reuses a single `BoxCubeFiller` across the whole run,
and its cache was never cleared between lines. A load test feeding 5,000
distinct boxes through the command found over 38,000 accumulated
entries; a large enough `problems.txt` would eventually exhaust memory
the same way.

The fix: reset the cache at the start of every `minCubes()` call instead
of once per process. Memoization still does its job within one box's own
recursion, which is all it was ever needed for.

## The fourth bug

Testing with extreme values turned up one more crash: a box dimension
at `PHP_INT_MAX` brings down the entire program, not just that line.
`computeLargestFittingCubeSize()` doubles a value until it overflows a
native integer; PHP silently promotes the overflow to a `float`, and a
`float` where `strict_types` expects `int` is an uncaught `TypeError`.
That's a different overflow point from the already-disclosed phase 2
limitation below.

The fix: check for overflow before it happens, not after.
`while ($value <= intdiv($upperBound, 2))` asks the same question as
the old loop without ever computing a value that could exceed
`PHP_INT_MAX`.

## What I intentionally did not validate

The problem statement asks for minimal parsing effort, so most malformed
input isn't guarded beyond basic type checks: nothing caps dimension or
supply size beyond what fits in a PHP integer (the fourth bug above is
why the algorithm itself has to stay safe at that scale). A few checks
exist anyway, since the alternative is a silently wrong answer:

- negative dimensions or supply counts: `Invalid input parameters.
  Negative values are not allowed.`
- a non-integer token: `Invalid input parameters. Only integers are
  allowed.`
- fewer than three numbers: `Invalid input parameters. Missing data.`
  (a genuinely blank line is skipped silently instead)

Only that one line is rejected each time. Diagnostics go to stderr
(`getErrorOutput()`), so stdout stays pure answers.
