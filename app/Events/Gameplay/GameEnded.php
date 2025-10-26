<?php

namespace App\Events\Gameplay;

use App\Events\BroadcastEvent;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(GameState::class)]
class GameEnded extends Event
{
    public function __construct(
        public int $game_id,
        public int $winner_id,
        public string $reason = 'all_ships_sunk',
    ) {}

    public function validate(GameState $game)
    {
        $this->assert(
            $game->hasPlayer($this->winner_id),
            'Winner must be a player in the game.'
        );

        $this->assert(
            ! $game->ended,
            'Game has already ended.'
        );

        $this->assert(
            $game->isInProgress(),
            'Game must be in progress to end.'
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->ended = true;
        $game->winner_id = $this->winner_id;
        $game->active_player_id = null;
    }

    public function handle(GameState $game)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
