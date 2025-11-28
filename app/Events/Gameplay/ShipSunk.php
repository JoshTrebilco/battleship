<?php

namespace App\Events\Gameplay;

use App\Events\BroadcastEvent;
use App\States\BoardState;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(BoardState::class)]
#[AppliesToState(GameState::class)]
class ShipSunk extends Event
{
    public function __construct(
        public int $game_id,
        public int $player_id,        // shooter
        public int $target_player_id,  // ship owner
        public int $board_id,
        public string $ship_type,
    ) {}

    public function validateBoard(BoardState $board)
    {
        $this->assert(
            $board->player_id === $this->target_player_id,
            'Board does not belong to target player.'
        );

        $this->assert(
            isset($board->ships[$this->ship_type]),
            'Ship does not exist on this board.'
        );

        $this->assert(
            ! $board->ships[$this->ship_type]['sunk'],
            'Ship is already sunk.'
        );

        $this->assert(
            $board->isShipSunk($this->ship_type),
            'Ship is not fully hit yet.'
        );
    }

    public function validateGame(GameState $game)
    {
        $this->assert(
            $game->hasPlayer($this->player_id),
            'Shooter must be in the game.'
        );

        $this->assert(
            $game->hasPlayer($this->target_player_id),
            'Target player must be in the game.'
        );

        $this->assert(
            $game->isInProgress(),
            'Game must be in progress.'
        );
    }

    public function applyToBoard(BoardState $board)
    {
        $board->ships[$this->ship_type]['sunk'] = true;
    }

    public function applyToGame(GameState $game)
    {
        // Game state doesn't change for individual ship sinking
        // Game over is checked in fired() method
    }

    public function fired(BoardState $board, GameState $game)
    {
        // Check if all ships are sunk (game over condition)
        if ($board->isAllShipsSunk()) {
            GameEnded::fire(
                game_id: $this->game_id,
                winner_id: $this->player_id,
                reason: 'all_ships_sunk'
            );
        }
    }

    public function handle(BoardState $board, GameState $game)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
