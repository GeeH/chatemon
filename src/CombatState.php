<?php declare(strict_types=1);


namespace Chatemon;


final class CombatState
{
    protected string $turn = 'One';
    protected int $turns = 0;
    protected bool $winner = false;

    protected function __construct(string $turn = 'One', int $turns = 0, bool $winner = false)
    {
        assert(in_array($turn, ['One', 'Two'], true), 'Turn must be "One" or "Two"');
        assert($turns >= 0, 'Turns must be greater or equal to zero');

        $this->turn   = $turn;
        $this->turns  = $turns;
        $this->winner = $winner;
    }

    public static function fromArray(array $data) : CombatState
    {
        assert(array_key_exists('turn', $data), 'data expects "turn" key to exist');
        assert(array_key_exists('turns', $data), 'data expects "turns" key to exist');
        assert(array_key_exists('winner', $data), 'data expects "winner" key to exist');

        return new CombatState($data['turn'], $data['turns'], $data['winner']);
    }

    public static function fresh() : CombatState
    {
        return new CombatState('One', 0, false);
    }

    public function getTurn() : string
    {
        return $this->turn;
    }

    public function getTurns() : int
    {
        return $this->turns;
    }

    public function hasWinner() : bool
    {
        return $this->winner;
    }

    public function changeTurn() : string
    {
        $this->turn = $this->turn === 'One' ? 'Two' : 'One';

        return $this->turn;
    }

    public function incrementTurnCount() : int
    {
        $this->turns++;

        return $this->turns;
    }

    public function toArray() : array
    {
        return [
            'turn'   => $this->getTurn(),
            'turns'  => $this->getTurns(),
            'winner' => $this->hasWinner(),
        ];
    }

    public function markWon(): void
    {
        $this->winner = true;
    }
}