<?php

namespace App\States;

use App\Enums\ShipType;
use Thunk\Verbs\State;

class BoardState extends State
{
    public const BOARD_SIZE = 10;

    public array $grid = [];

    public array $ships = [];

    public array $shots = [];

    public ?int $player_id = null;

    public function setup()
    {
        // Initialize empty 10x10 grid
        $this->grid = array_fill(0, self::BOARD_SIZE, array_fill(0, self::BOARD_SIZE, null));

        // Initialize empty ships array
        $this->ships = [];

        // Initialize empty shots array
        $this->shots = [];
    }

    public function isSetupComplete(): bool
    {
        return count($this->ships) === ShipType::totalShips();
    }

    public function getPlacedShips(): array
    {
        return $this->ships;
    }

    public function getShipAt(int $row, int $col): ?string
    {
        if ($row < 0 || $row >= self::BOARD_SIZE || $col < 0 || $col >= self::BOARD_SIZE) {
            return null;
        }

        return $this->grid[$row][$col];
    }

    public function isShipSunk(string $shipType): bool
    {
        if (! isset($this->ships[$shipType])) {
            return false;
        }

        return $this->ships[$shipType]['hits'] >= $this->ships[$shipType]['length'];
    }

    public function isAllShipsSunk(): bool
    {
        foreach ($this->ships as $ship) {
            if (! $this->isShipSunk($ship['type'])) {
                return false;
            }
        }

        return count($this->ships) > 0;
    }

    public function calculateShipPositions(int $row, int $col, string $direction, int $length): array
    {
        $positions = [];

        for ($i = 0; $i < $length; $i++) {
            switch ($direction) {
                case 'horizontal':
                    $positions[] = ['row' => $row, 'col' => $col + $i];
                    break;
                case 'vertical':
                    $positions[] = ['row' => $row + $i, 'col' => $col];
                    break;
            }
        }

        return $positions;
    }

    public function isValidShipPlacement(array $positions): bool
    {
        foreach ($positions as $position) {
            // Check bounds
            if ($position['row'] < 0 || $position['row'] >= BoardState::BOARD_SIZE ||
                $position['col'] < 0 || $position['col'] >= BoardState::BOARD_SIZE) {
                return false;
            }

            // Check if position is already occupied
            if ($this->grid[$position['row']][$position['col']] !== null) {
                return false;
            }
        }

        return true;
    }

    public function getShots(): array
    {
        return $this->shots;
    }

    public function player(): ?PlayerState
    {
        return $this->player_id ? PlayerState::load($this->player_id) : null;
    }
}
