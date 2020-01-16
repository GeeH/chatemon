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
    protected string $turn = 'One';
    protected int $turns = 0;
    protected string $id;
    protected bool $winner = false;
    private LoggerInterface $logger;

    /**
     * @throws Exception
     */
    public function __construct(Combatant $combatantOne, Combatant $combatantTwo, LoggerInterface $logger)
    {
        $this->combatantOne = $combatantOne;
        $this->combatantTwo = $combatantTwo;
        $this->id = Uuid::uuid4()->toString();
        $this->logger = $logger;
    }

    /**
     * @throws CombatAlreadyWonException
     * @throws MoveDoesNotExistException
     */
    public function takeTurn(int $moveIndex): void
    {
        if ($this->winner) {
            throw new CombatAlreadyWonException();
        }

        $this->logger->info('It\'s turn ' . $this->turns);

        /** @var Combatant $attacker */
        $attacker = $this->{'combatant' . $this->turn};

        if (!array_key_exists($moveIndex, $attacker->moves)) {
            throw new MoveDoesNotExistException();
        }
        $move = $attacker->moves[$moveIndex];
        $this->logger->info('Attacker is ' . $attacker->name);

        /** @var Combatant $defender */
        $defender = $this->{'combatant' . ($this->turn === 'One' ? 'Two' : 'One')};
        $this->logger->info('Defender is ' . $defender->name);

        $damage = $this->calculateDamage($attacker->level, $attacker->attack, $move->damage, $defender->defence);

        $this->logger->info('Damage is ' . $damage);
        $defender->health -= $damage;

        $this->logger->info('Defender\'s health is now ' . $defender->health);

        $this->turns++;
        $this->turn = $this->turn === 'One' ? 'Two' : 'One';

        if ($defender->health < 1) {
            $this->winner = true;
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
                                    / $defenderDefence) / 50) + 2) * 1 * 10) / 10) * random_int(217, 255)) / 255);
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
        return $this->turn;
    }

    public function getTurns(): int
    {
        return $this->turns;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isWinner(): bool
    {
        return $this->winner;
    }

    /**
     * @throws CombatNotWonException
     */
    public function getWinner(): Combatant
    {
        if (!$this->winner) {
            throw new CombatNotWonException();
        }
        return $this->combatantOne->health >= 1 ? $this->combatantOne : $this->combatantTwo;
    }

    public function toArray(): array
    {
        $return = [
            'combatantOne' => (array)$this->combatantOne,
            'combatantTwo' => (array)$this->combatantTwo,
            'turn' => $this->turn,
            'turns' => $this->turns,
            'id' => $this->id,
            'winner' => $this->winner,
        ];

        $return['combatantOne']['moves'] = [];
        foreach ($this->combatantOne->moves as $move) {
            $return['combatantOne']['moves'][] = array($move);
        }

        $return['combatantTwo']['moves'] = [];
        foreach ($this->combatantTwo->moves as $move) {
            $return['combatantTwo']['moves'][] = array($move);
        }

        return $return;
    }

}