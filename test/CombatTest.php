<?php

namespace Chatemon;

use Chatemon\Exception\CombatAlreadyWonException;
use Chatemon\Exception\CombatNotWonException;
use Chatemon\Exception\MoveDoesNotExistException;
use Chatemon\Factory\CombatantFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * @todo Write better tests to ensure the damage algorithm matches website
 * @todo Clean everything up as it stinks
 * @todo Clean up damage algorithm so its more readable
 */
final class CombatTest extends TestCase
{

    protected Combat $combat;

    public function setUp(): void
    {
        $handler = new StreamHandler(__DIR__ . '/../log/combat.log', Logger::DEBUG);
        $logger = new Logger('test-logger', [$handler]);

        $this->combat = new Combat(
            CombatantFactory::create(
                ['name' => 'One', 'level' => 1, 'attack' => 100, 'defence' => 5,
                    'health' => 20, 'moves' => [], 'id' => Uuid::uuid4()->toString()]
            ),
            CombatantFactory::create(
                ['name' => 'Two', 'level' => 1, 'attack' => 100, 'defence' => 5,
                    'health' => 12, 'moves' => [], 'id' => Uuid::uuid4()->toString()]
            ),
            $logger
        );
    }

    public function testConstructSetsCombatantsAndId()
    {
        self::assertTrue(Uuid::isValid($this->combat->getId()));
    }

    public function testTakingTurnDecreasesDefenderHealthAndIncrementsTurn()
    {
        $this->combat->takeTurn(0);

        self::assertGreaterThanOrEqual(2, $this->combat->getCombatantTwo()->health);
        self::assertLessThanOrEqual(4, $this->combat->getCombatantTwo()->health);

        self::assertEquals(1, $this->combat->getTurns());
        self::assertEquals('Two', $this->combat->getTurn());
    }

    public function testRunningCombatTurnsGeneratesWinner()
    {
        while (!$this->combat->isWinner()) {
            $this->combat->takeTurn(0);
        }

        self::assertEquals(3, $this->combat->getTurns());
        self::assertEquals('One', $this->combat->getWinner()->name);
    }

    public function testRunningCombatTurnWhenWinnerExistsThrowsException()
    {
        while (!$this->combat->isWinner()) {
            $this->combat->takeTurn(0);
        }
        self::expectException(CombatAlreadyWonException::class);
        $this->combat->takeTurn(0);
    }

    public function testGettingWinnerWhenNoWinnerExistsThrowsException()
    {
        self::expectException(CombatNotWonException::class);
        $this->combat->getWinner();
    }

    public function testPlayingTurnWithInvalidMoveThrowsException()
    {
        self::expectException(MoveDoesNotExistException::class);
        $this->combat->takeTurn(100);
    }

    public function damageAlgorithmDataProvider()
    {
        return [
            [100, 100, 100, 100, 73, 86],
            [1, 50, 10, 1, 18, 22],
        ];
    }

    /**
     * @dataProvider damageAlgorithmDataProvider
     */
    public function testDamageCalculator(
        int $attackerLevel, int $attackerAttack, int $moveDamage, int $defenderDefense, $minimumDamage, $maximumDamage
    )
    {
        $damage = $this->combat->calculateDamage($attackerLevel, $attackerAttack, $moveDamage, $defenderDefense);
        self::assertGreaterThanOrEqual($minimumDamage, $damage);
        self::assertLessThanOrEqual($maximumDamage, $damage);
    }

    public function testToArrayConvertsToReadableArray()
    {
        $combatArray = $this->combat->toArray();
        $fields = ['combatantOne', 'combatantTwo', 'turn', 'turns', 'id', 'winner'];
        foreach($fields as $field) {
            self::assertArrayHasKey($field, $combatArray);
        }
        self::assertArrayHasKey('moves', $combatArray['combatantOne']);
        self::assertIsArray($combatArray['combatantOne']['moves'][0]);

        self::assertArrayHasKey('moves', $combatArray['combatantTwo']);
        self::assertIsArray($combatArray['combatantTwo']['moves'][0]);

    }

}
