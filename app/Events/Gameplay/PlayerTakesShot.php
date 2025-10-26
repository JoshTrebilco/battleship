<?php

namespace App\Events\Gameplay;

use App\Events\BroadcastEvent;
use App\States\BoardState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(BoardState::class)]
#[AppliesToState(PlayerState::class)]
#[AppliesToState(GameState::class)]
class PlayerTakesShot extends Event
{
    public function __construct(
        public int $game_id,
        public int $player_id,
        public int $target_player_id,
        public int $target_board_id,
        public int $row,
        public int $col,
    ) {}

    public function validateBoard(BoardState $board)
    {
        // Check if shot is within bounds
        $this->assert(
            $this->row >= 0 && $this->row < BoardState::BOARD_SIZE &&
            $this->col >= 0 && $this->col < BoardState::BOARD_SIZE,
            'Shot is out of bounds.'
        );

        // Check if position has already been shot
        $shotKey = "{$this->row},{$this->col}";
        $this->assert(
            ! isset($board->shots[$shotKey]),
            'Position has already been shot.'
        );
    }

    public function validatePlayer(PlayerState $player)
    {
        $this->assert(
            $player->setup,
            'Player must be set up before taking shots.'
        );
    }

    public function validateGame(GameState $game)
    {
        $this->assert(
            $game->hasPlayer($this->player_id),
            'Player must be in the game to take shots.'
        );

        $this->assert(
            $game->hasPlayer($this->target_player_id),
            'Target player must be in the game.'
        );

        $this->assert(
            $this->player_id !== $this->target_player_id,
            'Player cannot shoot at themselves.'
        );

        $this->assert(
            $game->isInProgress(),
            'Game must be in progress to take shots.'
        );
    }

    public function applyToBoard(BoardState $board)
    {
        $shotKey = "{$this->row},{$this->col}";
        $shipType = $board->getShipAt($this->row, $this->col);
        $hit = $shipType !== null;

        // Record the shot
        $board->shots[$shotKey] = [
            'row' => $this->row,
            'col' => $this->col,
            'hit' => $hit,
            'ship' => $shipType,
            'shooter_id' => $this->player_id,
        ];

        // Update ship hits if it was a hit
        if ($hit) {
            $board->ships[$shipType]['hits']++;
        }
    }

    public function applyToPlayer(PlayerState $player)
    {
        // Player state doesn't need to change for taking shots
        // Shot history is tracked on the target's board
    }

    public function applyToGame(GameState $game)
    {
        // Game state doesn't need to change for individual shots
        // Game over conditions are checked in the fired() method
    }

    public function fired(BoardState $board, PlayerState $player, GameState $game)
    {
        $shipType = $board->getShipAt($this->row, $this->col);

        // If it was a hit, check if the ship is now sunk
        if ($shipType && $board->isShipSunk($shipType)) {
            ShipSunk::fire(
                game_id: $this->game_id,
                player_id: $this->player_id,
                target_player_id: $this->target_player_id,
                ship_type: $shipType
            );
        } else {
            // If it was a miss, end the turn
            TurnEnded::fire(
                game_id: $this->game_id
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

    public function getShotResult(BoardState $board): array
    {
        $shipType = $board->getShipAt($this->row, $this->col);
        $hit = $shipType !== null;

        return [
            'hit' => $hit,
            'ship' => $shipType,
            'sunk' => $hit ? $board->isShipSunk($shipType) : false,
        ];
    }
}
