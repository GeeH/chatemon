<?php

namespace ChatemonTest;

use Chatemon\Combat;
use Chatemon\Exception\CombatAlreadyWonException;
use Chatemon\Exception\CombatNotWonException;
use Chatemon\Exception\MoveDoesNotExistException;
use Chatemon\Factory\CombatantFactory;
use Chatemon\Randomizer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * @todo Clean everything up as it stinks
 * @todo Clean up damage algorithm so its more readable
 */
final class CombatTest extends TestCase
{

    protected Combat $combat;

    public function setUp(): void
    {
        $this->combat = self::getCombat();
    }

    public static function getCombat(Randomizer $randomizer = null): Combat
    {
        $handler = new StreamHandler(__DIR__ . '/../log/combat.log', Logger::DEBUG);
        $logger = new Logger('test-logger', [$handler]);

        if (is_null($randomizer)) {
            $randomizer = new Randomizer();
        }

        return new Combat(
            CombatantFactory::create(
                ['name' => 'One', 'level' => 1, 'attack' => 100, 'defence' => 5,
                    'health' => 20, 'maxHealth' => 20, 'moves' => [], 'id' => Uuid::uuid4()->toString()]
            ),
            CombatantFactory::create(
                ['name' => 'Two', 'level' => 1, 'attack' => 100, 'defence' => 5,
                    'health' => 12, 'maxHealth' => 12, 'moves' => [], 'id' => Uuid::uuid4()->toString()]
            ),
            $randomizer,
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
        int $attackerLevel, int $attackerAttack, int $moveDamage,
        int $defenderDefense, int $minimumDamage, int $maximumDamage
    )
    {
        $damage = $this->combat
            ->calculateDamage($attackerLevel, $attackerAttack, $moveDamage, $defenderDefense);
        self::assertGreaterThanOrEqual($minimumDamage, $damage);
        self::assertLessThanOrEqual($maximumDamage, $damage);
    }

    public function testMoveMissesWithMaximumDiceRoll()
    {
        $randomizerMock = self::getMockBuilder(Randomizer::class)
            ->getMock();

        $randomizerMock->expects($this->once())
            ->method('__invoke')
            ->with(1, 100)
            ->willReturn(100);

        $combat = $this->getCombat($randomizerMock);
        $combat->takeTurn(2);
    }

    public function testToArrayConvertsToReadableArray()
    {
        $combatArray = $this->combat->toArray();
        $fields = ['combatantOne', 'combatantTwo', 'turn', 'turns', 'id', 'winner'];
        foreach ($fields as $field) {
            self::assertArrayHasKey($field, $combatArray);
        }
        self::assertArrayHasKey('moves', $combatArray['combatantOne']);
        self::assertIsArray($combatArray['combatantOne']['moves'][0]);

        self::assertArrayHasKey('moves', $combatArray['combatantTwo']);
        self::assertIsArray($combatArray['combatantTwo']['moves'][0]);
    }

//    public function testFromArrayCreatesAFunctionalCombatObject()
//    {
//        $json = '{"combatantOne":{"level":13,"attack":142,"defence":20,"maxHealth":50,"health":36,"name":"Developer","id":"d4611e48-481d-4bf0-9c5b-81fb3dfc66c9","moves":[[{"name":"Elevator Pitch","accuracy":100,"damage":10}],[{"name":"HR Message","accuracy":90,"damage":20}],[{"name":"Use Your Words","accuracy":50,"damage":100}]]},"combatantTwo":{"level":21,"attack":130,"defence":23,"maxHealth":47,"health":38,"name":"HR Executive","id":"4d22033d-4936-478e-940b-1587eed2319b","moves":[[{"name":"Elevator Pitch","accuracy":100,"damage":10}],[{"name":"HR Message","accuracy":90,"damage":20}],[{"name":"Use Your Words","accuracy":50,"damage":100}]]},"turn":"One","turns":2,"id":"7e7e0ac3-a3ac-48ef-8839-f0893c5f2902","winner":false}';
//        $data = json_decode($json, true);
//
//        $combatantOne = CombatantFactory::create($data['combatantOne']);
//        $combatantTwo = CombatantFactory::create($data['combatantTwo']);
//
//        $logger = new Logger(new StreamHandler('foo'));
//        $combat = new Combat($combatantOne, $combatantTwo, new Randomizer(), $logger);
//        $combat->setStateFromArray($data);
//    }

}
