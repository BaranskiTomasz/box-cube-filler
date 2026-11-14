# Box Cube Filler

Given a box and a limited supply of cubes whose sides are powers of two,
finds the minimum number of cubes needed to exactly fill the box, or `-1`
if it can't be done.

Running the program against the problem statement's sample input produces
exactly the expected output (`1, 8, 1000, -1, 9, 62, 59, 50070, 9`); all
nine examples pass and no failing case was found. See `SOLUTION.md` for
the algorithm, the optimality argument, four performance/correctness
bugs I found and fixed, and a known limitation I deliberately left
unfixed.

## Requirements

- PHP 8.3+ with the `dom`, `xml` and `xmlwriter` extensions (only needed to
  run the test suite; the program itself has no extension dependencies
  beyond a standard PHP CLI build).
- Composer 2.

## Install

```bash
composer install
```

## Run

```bash
./bin/myprogram < problems.txt
```

or, equivalently:

```bash
php bin/myprogram < problems.txt
```

Each line of input is `length width height supply_1 supply_2 ...`
(`supply_k` = how many cubes of side `2^(k-1)` are available). One answer
is printed per line, in the same order as the input.

## Test

```bash
composer test
# or directly:
vendor/bin/phpunit
```

- `tests/Unit/` tests each class in isolation (the algorithm, the
  `BoxSpecification` value object, and input parsing/validation —
  including the regression guards for the bugs described in
  `SOLUTION.md`).
- `tests/Functional/FillBoxesCommandTest.php` runs `FillBoxesCommand` end
  to end against its real collaborators, not mocked.
- `tests/Integration/EntryPointTest.php` runs the real `bin/myprogram`
  entry point as a subprocess, so the DI container wiring itself is
  exercised too, not just the command in isolation.
