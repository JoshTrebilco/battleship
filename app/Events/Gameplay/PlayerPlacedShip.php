<?php

namespace App\Events\Gameplay;

use App\Enums\ShipType;
use App\Events\BroadcastEvent;
use App\Events\Setup\PlayerFinishedSetup;
use App\States\BoardState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(BoardState::class)]
#[AppliesToState(PlayerState::class)]
#[AppliesToState(GameState::class)]
class PlayerPlacedShip extends Event
{
    public function __construct(
        public int $game_id,
        public int $player_id,
        public int $board_id,
        public ShipType $ship_type,
        public int $row,
        public int $col,
        public string $direction,
    ) {}

    public function validateBoard(BoardState $board)
    {
        // Check if ship is already placed
        $this->assert(
            ! array_key_exists($this->ship_type->value, $board->ships),
            'Ship has already been placed.'
        );

        // Check if placement is valid
        $shipLength = $this->ship_type->length();
        $positions = $board->calculateShipPositions($this->row, $this->col, $this->direction, $shipLength);

        $this->assert(
            $board->isValidShipPlacement($positions),
            'Invalid ship placement - out of bounds or overlapping with existing ship.'
        );
    }

    public function validatePlayer(PlayerState $player)
    {
        $this->assert(
            $player->setup,
            'Player must be set up before placing ships.'
        );
    }

    public function validateGame(GameState $game)
    {
        $this->assert(
            $game->hasPlayer($this->player_id),
            'Player must be in the game to place ships.'
        );

        $this->assert(
            ! $game->started,
            'Cannot place ships once the game has started.'
        );
    }

    public function applyToBoard(BoardState $board)
    {
        $shipLength = $this->ship_type->length();
        $positions = $board->calculateShipPositions($this->row, $this->col, $this->direction, $shipLength);

        // Add ship to ships array
        $board->ships[$this->ship_type->value] = [
            'type' => $this->ship_type->value,
            'length' => $shipLength,
            'positions' => $positions,
            'hits' => 0,
            'sunk' => false,
        ];

        // Mark positions on grid
        foreach ($positions as $position) {
            $board->grid[$position['row']][$position['col']] = $this->ship_type->value;
        }
    }

    public function applyToPlayer(PlayerState $player)
    {
        // Player state doesn't need to change for ship placement
        // The board relationship is handled through the board_id
    }

    public function applyToGame(GameState $game)
    {
        // Game state doesn't need to change for ship placement
        // Ship placement is tracked on the board
    }

    public function fired(BoardState $board, PlayerState $player, GameState $game)
    {
        // Check if player has finished placing all ships
        if ($board->isSetupComplete()) {
            PlayerFinishedSetup::fire(
                game_id: $this->game_id,
                player_id: $this->player_id,
                board_id: $this->board_id,
            );
        }
    }

    public function handle(BoardState $board, PlayerState $player, GameState $game)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setPlayerState($player);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
