<?php

namespace App\Events\Setup;

use App\Events\BroadcastEvent;
use App\States\GameState;
use App\States\PlayerState;
use Illuminate\Support\Facades\Auth;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(GameState::class)]
#[AppliesToState(PlayerState::class)]
class PlayerJoinedGame extends Event
{
    public function __construct(
        public int $game_id,
        public int $player_id,
    ) {}

    public function validateGame(GameState $game)
    {
        $this->assert($game->created, 'Game must be created before a player can join.');
        $this->assert(! $game->isInProgress(), 'The game is already in progress.');
        $this->assert(count($game->player_ids) < 2, 'Only 2 players may join the game.');
    }

    public function validatePlayer(PlayerState $player)
    {
        $this->assert(! $player->setup, 'Player has already joined game.');
    }

    public function applyToGame(GameState $game)
    {
        $game->player_ids[] = $this->player_id;
    }

    public function applyToPlayers(PlayerState $player)
    {
        $player->setup = true;
        $player->name = Auth::user()?->name ?? 'Unknown Player';
    }

    public function fired(GameState $game)
    {
        // Create a board for the player
        BoardCreated::fire(
            player_id: $this->player_id
        );
    }

    public function handle(GameState $game, PlayerState $player)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setPlayerState($player);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
