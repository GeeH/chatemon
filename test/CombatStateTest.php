<?php

namespace ChatemonTest;

use Chatemon\CombatState;
use PHPUnit\Framework\TestCase;

class CombatStateTest extends TestCase
{
    public function testFreshCreatesBasicState() : void
    {
        $freshCombatState = CombatState::fresh();
        $turnProperty     = new \ReflectionProperty(CombatState::class, 'turn');
        $turnsProperty    = new \ReflectionProperty(CombatState::class, 'turns');
        $winnerProperty   = new \ReflectionProperty(CombatState::class, 'winner');
        $turnProperty->setAccessible(true);
        $turnsProperty->setAccessible(true);
        $winnerProperty->setAccessible(true);

        self::assertSame('One', $turnProperty->getValue($freshCombatState));
        self::assertSame(0, $turnsProperty->getValue($freshCombatState));
        self::assertFalse($winnerProperty->getValue($freshCombatState));

    }

    public function testThatCombatStateErrorsWithoutTurn() : void
    {
        $this->expectError();
        $this->expectErrorMessage('assert(): data expects "turn" key to exist failed');
        CombatState::fromArray([]);
    }

    public function testThatCombatStateErrorsWithoutTurnsKey() : void
    {
        $this->expectError();
        $this->expectErrorMessage('assert(): data expects "turns" key to exist');
        CombatState::fromArray(['turn' => '']);
    }

    public function testThatCombatStateErrorsWithoutWinnerKey() : void
    {
        $this->expectError();
        $this->expectErrorMessage('assert(): data expects "winner" key to exist failed');
        CombatState::fromArray(['turn' => '', 'turns' => 0]);
    }

    public function testThatCombatStateErrorsWithInvalidTurnValue() : void
    {
        $this->expectError();
        $this->expectErrorMessage('assert(): Turn must be "One" or "Two" failed');
        CombatState::fromArray(['turn' => '', 'turns' => 0, 'winner' => false]);
        CombatState::fromArray(['turn' => 'bob', 'turns' => 0, 'winner' => false]);
        CombatState::fromArray(['turn' => 0, 'turns' => 0, 'winner' => false]);
    }

    public function testThatCombatStateDoesNotErrorWithCorrectTurnValue() : void
    {
        try {
            $combatStateInstance = CombatState::fromArray(['turn' => 'One', 'turns' => 0, 'winner' => false]);
            self::assertInstanceOf(CombatState::class, $combatStateInstance);
        } catch (\AssertionError $assertionError) {
            $this->fail();
        }

        try {
            $combatStateInstance = CombatState::fromArray(['turn' => 'Two', 'turns' => 0, 'winner' => false]);
            self::assertInstanceOf(CombatState::class, $combatStateInstance);
        } catch (\AssertionError $assertionError) {
            $this->fail();
        }
    }

    public function testCombatStateErrorsWhenPassedNegativeTurns() : void
    {
        $this->expectError();
        $this->expectErrorMessage('assert(): Turns cannot be negative failed');
        CombatState::fromArray(['turn' => 'One', 'turns' => -1, 'winner' => false]);
    }

    public function testGetTurnReturnsPropertyValue() : void
    {
        $testValue = sha1(random_bytes(10));

        $turnProperty = new \ReflectionProperty(CombatState::class, 'turn');
        $turnProperty->setAccessible(true);

        $combatStateInstance = CombatState::fresh();
        $turnProperty->setValue($combatStateInstance, $testValue);

        self::assertSame($testValue, $combatStateInstance->getTurn());
    }

    public function testGetTurnsReturnsPropertyValue() : void
    {
        $testValue = random_int(11, 99);

        $turnsProperty = new \ReflectionProperty(CombatState::class, 'turns');
        $turnsProperty->setAccessible(true);

        $combatStateInstance = CombatState::fresh();
        $turnsProperty->setValue($combatStateInstance, $testValue);

        self::assertSame($testValue, $combatStateInstance->getTurns());
    }

    /**
     * @depends testFreshCreatesBasicState
     * @depends testGetTurnReturnsPropertyValue
     */
    public function testChangeTurnSwitchesTurn() : void
    {
        $combatStateInstance = CombatState::fresh();
        self::assertSame('One', $combatStateInstance->getTurn());

        $combatStateInstance->changeTurn();
        self::assertSame('Two', $combatStateInstance->getTurn());

        $combatStateInstance->changeTurn();
        self::assertSame('One', $combatStateInstance->getTurn());
    }

    /**
     * @depends testFreshCreatesBasicState
     * @depends testGetTurnsReturnsPropertyValue
     */
    public function testIncrementTurnCountUpdatesTurnCount()
    {
        $combatStateInstance = CombatState::fresh();
        self::assertSame(0, $combatStateInstance->getTurns());

        for ($i = 1; $i <= 5; $i++) {
            $combatStateInstance->incrementTurnCount();
            self::assertSame($i, $combatStateInstance->getTurns());
        }
    }

    public function testHasWinnerReturnsPropertyValue() : void
    {
        $testValue = (bool) random_int(0, 1);

        $winnerProperty = new \ReflectionProperty(CombatState::class, 'winner');
        $winnerProperty->setAccessible(true);

        $combatStateInstance = CombatState::fresh();
        $winnerProperty->setValue($combatStateInstance, $testValue);

        self::assertSame($testValue, $combatStateInstance->hasWinner());
    }

    /**
     * @depends testFreshCreatesBasicState
     * @depends testHasWinnerReturnsPropertyValue
     */
    public function testMarkWonSetsWinnerToTrue() : void
    {
        $combatStateInstance = CombatState::fresh();
        self::assertFalse($combatStateInstance->hasWinner());

        $combatStateInstance->markWon();
        self::assertTrue($combatStateInstance->hasWinner());
    }
}
