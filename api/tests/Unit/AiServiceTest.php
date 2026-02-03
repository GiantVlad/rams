<?php

namespace Tests\Unit;

use App\Domain\Rams\Rank;
use App\Domain\Rams\Suit;
use App\Services\Rams\AiService;
use PHPUnit\Framework\TestCase;

class AiServiceTest extends TestCase
{
    private AiService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = new AiService;
    }

    public function test_chooses_discards_lowest_non_trumps_first()
    {
        // Hand: 6♥, 7♥, J♣, Q♣, K♠
        // Trump: ♦
        $hand = [
            'H-6',
            'H-7',
            'C-11',
            'C-12',
            'S-13',
        ];
        $trump = 'D-14'; // Ace of Diamonds

        $discards = $this->aiService->chooseDiscardCards($hand, $trump);

        // Should discard lowest non-trumps: 6♥, 7♥, J♣ (Rank 11)
        // Current implementation sorts by rank ASC and takes up to 3.
        // 6 (6), 7 (7), 11 (J), 12 (Q), 13 (K) -> Discards 6, 7, 11

        $this->assertCount(3, $discards);
        $this->assertContains('H-6', $discards);
        $this->assertContains('H-7', $discards);
        $this->assertContains('C-11', $discards);
    }

    public function test_keeps_trumps_when_discarding()
    {
        // Hand: 6♥, 6♦ (Trump), 7♦ (Trump), 8♦ (Trump), 9♣
        // Trump: ♦
        $hand = [
            'H-6',
            'D-6',
            'D-7',
            'D-8',
            'C-9',
        ];
        $trump = 'D-14';

        $discards = $this->aiService->chooseDiscardCards($hand, $trump);

        // Should discard non-trumps: 6♥, 9♣
        // Should KEEP trumps: 6♦, 7♦, 8♦

        $this->assertContains('H-6', $discards);
        $this->assertContains('C-9', $discards);
        $this->assertNotContains('D-6', $discards);
        $this->assertNotContains('D-7', $discards);
        $this->assertNotContains('D-8', $discards);
    }

    public function test_plays_lowest_winning_card_if_possible()
    {
        // Trick: 10♥ (Lead)
        // Hand: 9♥, Q♥, A♥
        // Trump: ♣
        // Must follow suit (♥).
        // To win: Need > 10♥. Q♥ (12) and A♥ (14) both win.
        // Should play Q♥ (lowest winner), not A♥ (waste) or 9♥ (lose).

        $hand = ['H-9', 'H-12', 'H-14'];
        $trick = [['card' => 'H-10', 'player' => 0]];
        $trump = 'C-6';

        $played = $this->aiService->chooseCardIdToPlay($hand, $trick, $trump);

        // Improved implementation: Should play Q♥ (12).
        $this->assertEquals('H-12', $played);
    }

    public function test_keeps_aces_when_discarding()
    {
        // Hand: 6♠, A♠, 7♦, 8♦, 9♦
        // Trump: ♥
        // Should discard 6♠, 7♦, 8♦. Should KEEP A♠ (High value) and 9♦.

        $hand = ['S-6', 'S-14', 'D-7', 'D-8', 'D-9'];
        $trump = 'H-6';

        $discards = $this->aiService->chooseDiscardCards($hand, $trump);

        $this->assertContains('S-6', $discards);
        $this->assertNotContains('S-14', $discards);
    }

    public function test_plays_lowest_card_if_cannot_win()
    {
        // Trick: A♥ (Lead)
        // Hand: 7♥, 8♥, 9♥
        // Trump: ♣
        // Cannot win (A♥ is highest). Should play lowest (7♥).

        $hand = ['H-7', 'H-8', 'H-9'];
        $trick = [['card' => 'H-14', 'player' => 0]];
        $trump = 'C-6';

        $played = $this->aiService->chooseCardIdToPlay($hand, $trick, $trump);

        $this->assertEquals('H-7', $played);
    }

    public function test_trumps_if_cannot_follow_suit()
    {
        // Trick: A♥ (Lead)
        // Hand: 7♦, 8♦, 7♣ (Trump)
        // Trump: ♣
        // Cannot follow suit. Must trump if possible (or rule dependent, but AI should try to win).
        // Should play 7♣.

        $hand = ['D-7', 'D-8', 'C-7'];
        $trick = [['card' => 'H-14', 'player' => 0]];
        $trump = 'C-6';

        $played = $this->aiService->chooseCardIdToPlay($hand, $trick, $trump);

        $this->assertEquals('C-7', $played);
    }
}
