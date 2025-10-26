<?php

namespace App\Enums;

enum ShipType: string
{
    case CARRIER = 'carrier';
    case BATTLESHIP = 'battleship';
    case CRUISER = 'cruiser';
    case SUBMARINE = 'submarine';
    case DESTROYER = 'destroyer';

    public function length(): int
    {
        return match ($this) {
            self::CARRIER => 5,
            self::BATTLESHIP => 4,
            self::CRUISER => 3,
            self::SUBMARINE => 3,
            self::DESTROYER => 2,
        };
    }

    public function displayName(): string
    {
        return match ($this) {
            self::CARRIER => 'Carrier',
            self::BATTLESHIP => 'Battleship',
            self::CRUISER => 'Cruiser',
            self::SUBMARINE => 'Submarine',
            self::DESTROYER => 'Destroyer',
        };
    }

    public static function all(): array
    {
        return [
            self::CARRIER,
            self::BATTLESHIP,
            self::CRUISER,
            self::SUBMARINE,
            self::DESTROYER,
        ];
    }

    public static function totalShips(): int
    {
        return count(self::all());
    }
}
