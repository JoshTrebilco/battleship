<?php

namespace App\Events\Gameplay;

use App\Events\BroadcastEvent;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(GameState::class)]
class TurnEnded extends Event
{
    public function __construct(
        public int $game_id,
    ) {}

    public function validate(GameState $game)
    {
        $this->assert(
            $game->isInProgress(),
            'Game must be in progress to end a turn.'
        );

        $this->assert(
            $game->active_player_id !== null,
            'There must be an active player to end their turn.'
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->last_player_id = $game->active_player_id;
        $game->moveToNextPlayer();
    }

    public function handle(GameState $game)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
