<?php

namespace App\Events\Setup;

use App\Events\BroadcastEvent;
use App\States\BoardState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(PlayerState::class)]
#[AppliesToState(BoardState::class)]
#[AppliesToState(GameState::class)]
class PlayerFinishedSetup extends Event
{
    public function __construct(
        public int $game_id,
        public int $player_id,
        public int $board_id,
    ) {}

    public function validatePlayer(PlayerState $player)
    {
        $this->assert(
            $player->setup,
            'Player must be set up before finishing ship placement.'
        );
    }

    public function validateBoard(BoardState $board)
    {
        $this->assert(
            $board->player_id === $this->player_id,
            'Board does not belong to this player.'
        );

        $this->assert(
            $board->isSetupComplete(),
            'Player must have placed all ships before finishing setup.'
        );
    }

    public function validateGame(GameState $game)
    {
        $this->assert(
            $game->hasPlayer($this->player_id),
            'Player must be in the game.'
        );

        $this->assert(
            ! $game->isInProgress(),
            'Game is already in progress.'
        );
    }

    public function applyToPlayer(PlayerState $player)
    {
        // Player state doesn't need to change
        // Setup completion is tracked by board state
    }

    public function applyToBoard(BoardState $board)
    {
        // Board state doesn't need to change
        // isSetupComplete() already tracks this
    }

    public function applyToGame(GameState $game)
    {
        // Game state doesn't need to change
        // GameStarted will be fired if both players are ready
    }

    public function fired(PlayerState $player, BoardState $board, GameState $game)
    {
        // Check if both players have finished setup
        $allBoardsReady = $game->boards()->every(fn (BoardState $board) => $board->isSetupComplete());

        if ($allBoardsReady && $game->hasAllPlayersJoined()) {
            GameStarted::fire(
                game_id: $this->game_id,
                player_id: $game->player_ids[0] // Start with first player
            );
        }
    }

    public function handle(PlayerState $player, BoardState $board, GameState $game)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setPlayerState($player);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
