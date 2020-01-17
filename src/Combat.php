<?php declare(strict_types=1);

namespace Chatemon;

use Chatemon\Exception\CombatAlreadyWonException;
use Chatemon\Exception\CombatNotWonException;
use Chatemon\Exception\MoveDoesNotExistException;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

final class Combat
{
    protected Combatant $combatantOne;
    protected Combatant $combatantTwo;
    protected CombatState $combatState;
    protected string $id;
    private LoggerInterface $logger;
    private Randomizer $randomizer;

    /**
     * @throws Exception
     */
    public function __construct(
        Combatant $combatantOne,
        Combatant $combatantTwo,
        CombatState $combatState,
        Randomizer $randomizer,
        LoggerInterface $logger
    )
    {
        $this->combatantOne = $combatantOne;
        $this->combatantTwo = $combatantTwo;
        $this->combatState = $combatState;
        $this->id = Uuid::uuid4()->toString();
        $this->randomizer = $randomizer;
        $this->logger = $logger;
    }

    /**
     * @throws CombatAlreadyWonException
     * @throws MoveDoesNotExistException
     */
    public function takeTurn(int $moveIndex): void
    {
        if ($this->combatState->hasWinner()) {
            throw new CombatAlreadyWonException();
        }

        $this->logger->info('It\'s turn ' . $this->combatState->getTurn());

        /** @var Combatant $attacker */
        $attacker = $this->{'combatant' . $this->getTurn()};

        if (!array_key_exists($moveIndex, $attacker->moves)) {
            throw new MoveDoesNotExistException();
        }
        $move = $attacker->moves[$moveIndex];
        $this->logger->info('Attacker is ' . $attacker->name);

        /** @var Combatant $defender */
        $defender = $this->{'combatant' . ($this->combatState->getTurn() === 'One' ? 'Two' : 'One')};
        $this->logger->info('Defender is ' . $defender->name);

        $this->combatState->incrementTurnCount();
        $this->combatState->changeTurn();

        // roll a D100 dice
        $chanceToHit = $this->randomizer->__invoke(1, 100);
        if ($chanceToHit > $move->accuracy) {
            // we missed, do nothing
            $this->logger->info('Missed attack');
            return;
        }

        $damage = $this->calculateDamage($attacker->level, $attacker->attack, $move->damage, $defender->defence);

        $this->logger->info('Damage is ' . $damage);
        $defender->health -= $damage;

        $this->logger->info('Defender\'s health is now ' . $defender->health);

        if ($defender->health < 1) {
            $this->combatState->markWon();
        }
    }

    public function calculateDamage(
        int $attackerLevel,
        int $attackerAttack,
        int $moveDamage,
        int $defenderDefence
    ): int
    {
        /**
         *  ((2A/5+2)*B*C)/D)/50)+2)*X)*Y/10)*Z)/255
         * A = attacker's Level
         * B = attacker's Attack or Special
         * C = attack Power
         * D = defender's Defense or Special
         * X = same-Type attack bonus (1 or 1.5)
         * Y = Type modifiers (40, 20, 10, 5, 2.5, or 0)
         * Z = a random number between 217 and 255
         */

        return (int)floor(
            floor(
                floor(
                    floor(
                        floor(
                            floor(
                                floor(
                                    floor(
                                        floor(
                                            2 * $attackerLevel / 5 + 2) * $attackerAttack * $moveDamage)
                                    / $defenderDefence) / 50) + 2) * 1 * 10) / 10) * $this->randomizer->__invoke(217, 255)) / 255);
    }

    public function getCombatantOne(): Combatant
    {
        return $this->combatantOne;
    }

    public function getCombatantTwo(): Combatant
    {
        return $this->combatantTwo;
    }

    public function getTurn(): string
    {
        return $this->combatState->getTurn();
    }

    public function getTurns(): int
    {
        return $this->combatState->getTurns();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isWinner(): bool
    {
        return $this->combatState->hasWinner();
    }

    /**
     * @throws CombatNotWonException
     */
    public function getWinner(): Combatant
    {
        if (!$this->combatState->hasWinner()) {
            throw new CombatNotWonException();
        }
        return $this->combatantOne->health >= 1 ? $this->combatantOne : $this->combatantTwo;
    }

    public function toArray(): array
    {
        $return = array_merge(
            [
                'combatantOne' => (array)$this->getCombatantOne(),
                'combatantTwo' => (array)$this->getCombatantTwo(),
                'id' => $this->getId(),
            ],
            $this->combatState->toArray()
        );

        $return['combatantOne']['moves'] = [];
        foreach ($this->getCombatantOne()->moves as $move) {
            $return['combatantOne']['moves'][] = array($move);
        }

        $return['combatantTwo']['moves'] = [];
        foreach ($this->getCombatantTwo()->moves as $move) {
            $return['combatantTwo']['moves'][] = array($move);
        }

        return $return;
    }
}