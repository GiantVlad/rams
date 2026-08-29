# Plan A — Easy Wins & Code Hygiene

Goal: close all G-13/G-15/G-16 items and fix the small correctness defects in a single focused pass. Low risk; no new behavior.

## A-1. Remove dead `BiddingRules` + its test

Why: leftover from the pre-5.1 bidding variant; `GameService` never imports or calls it.

- Delete `api/app/Domain/Rams/BiddingRules.php`
- Delete `api/tests/Unit/Rams/BiddingRulesTest.php`
- Remove the `use App\Domain\Rams\BiddingRules` line in any file that still imports it (verify with grep).
- Run `cd api && composer test` — must stay green.

## A-2. Dedupe `TrickRulesTest.php`

- `api/tests/Unit/TrickRulesTest.php` is a legacy standalone copy (namespace `Tests\Unit`, plain PHPUnit `TestCase`). The canonical copy is `api/tests/Unit/Rams/TrickRulesTest.php` (same 4 tests, Laravel `TestCase`).
- Delete `api/tests/Unit/TrickRulesTest.php` and re-run the suite.

## A-3. Tighten `player_index` validation

- `GameController::exchange()` and `GameController::move()` validate `0..4` but the game has 4 players (0..3).
- Change both to `min:0, max:3`.
- Add a feature assertion in `RamsGameApiTest` that a `player_index` of 4 returns 422.

## A-4. Enable Scoring bounds (G-15)

- In `Scoring::applyRoundScoring`, uncomment the bounds check and make it dynamic: `if ($tricks < 0 || $tricks > $players)` — accepts 0..4 for 4-player (or 0..5 if you want to allow a theoretical 5th trick; the existing `applyRoundScoring` already enforces `count($tricksWonByPlayer) === $players`, so a 5th trick is structurally impossible). Keep `0..$players` for clarity.
- Add a unit test that rejects `$tricks > $players`.

## A-5. Clean up `TrickRules` debug logs

- Remove the two `error_log('Debug: ...')` lines in `TrickRules::assertLegalPlay` — they leak to production and are stale.

## A-6. Expand RulesPopup (G-17)

- Add a section 5 covering Jacks (Boys), partiya, and the pass/choose-to-play flow. Keep it in sync with `ai_dev/specs/01_game_rules_canonical.md` §6–7.
- Add a small Vitest snapshot or Playwright spec (`tests/e2e/rules-popup.spec.js` already exists) asserting the new sections render.

## A-7. Terminology alias annotation (G-12)

- Add a one-line comment in `api/app/Services/Rams/GameService.php` and `api/app/Models/Player.php`: `// maltzy_count: tracks Jacks pairs declared this round`.
- Add a `// Boys: code name for same-color Jack pair` comment near `detectBoys` / `boys_state`.
- No renames — just comments to prevent future confusion.

## Verification

- `cd api && composer test` — green, and the suite now has 27 unit tests (2 removed) + same features.
- `./vendor/bin/pint --test` — still PASS.
- `web/package.json` + `web/package-lock.json` untouched.