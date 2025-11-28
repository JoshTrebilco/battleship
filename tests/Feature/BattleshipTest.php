<?php

use App\Enums\ShipType;
use App\Events\Gameplay\GameEnded;
use App\Events\Gameplay\PlayerPlacedShip;
use App\Events\Gameplay\PlayerTakesShot;
use App\Events\Gameplay\ShipSunk;
use App\Events\Gameplay\TurnEnded;
use App\Events\Setup\GameCreated;
use App\Events\Setup\PlayerJoinedGame;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Exceptions\EventNotValidForCurrentState;
use Thunk\Verbs\Facades\Verbs;

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();
});

function placeShip($game_state, int $player_id, ShipType $ship_type, int $row, int $col, string $direction)
{
    $player = PlayerState::load($player_id);
    $board = $player->board();

    dump($board);
    dump($player);

    return verb(new PlayerPlacedShip(
        game_id: $game_state->id,
        player_id: $player_id,
        board_id: $board->id,
        ship_type: $ship_type,
        row: $row,
        col: $col,
        direction: $direction,
    ));
}

function takeShot($game_state, int $shooter_id, int $target_player_id, int $row, int $col)
{
    $target_player = PlayerState::load($target_player_id);
    $target_board = $target_player->board();

    return verb(new PlayerTakesShot(
        game_id: $game_state->id,
        player_id: $shooter_id,
        target_player_id: $target_player_id,
        target_board_id: $target_board->id,
        row: $row,
        col: $col,
    ));
}

it('can play a complete game of Battleship', function () {
    // Game Setup
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);

    expect($game_state->created)->toBeTrue()
        ->and($game_state->player_ids)->toHaveCount(0)
        ->and($game_state->ended)->toBeFalse()
        ->and($game_state->winner_id)->toBeNull()
        ->and($game_state->active_player_id)->toBeNull()
        ->and($game_state->last_player_id)->toBeNull()
        ->and(fn () => GameCreated::fire(game_id: $game_state->id))->toThrow(EventNotValidForCurrentState::class);

    // Player 1 joins
    verb(new PlayerJoinedGame(
        game_id: $game_state->id,
        player_id: $player1_id,
    ));

    expect($game_state->hasPlayer($player1_id))->toBeTrue();

    $player1 = PlayerState::load($player1_id);
    $board1 = $player1->board();

    expect($player1->setup)->toBeTrue()
        ->and($board1)->not->toBeNull()
        ->and($board1->player_id)->toBe($player1_id);

    // Player 2 joins
    verb(new PlayerJoinedGame(
        game_id: $game_state->id,
        player_id: $player2_id,
    ));

    expect($game_state->hasPlayer($player2_id))->toBeTrue();

    $player2 = PlayerState::load($player2_id);
    $board2 = $player2->board();

    expect($player2->setup)->toBeTrue()
        ->and($board2)->not->toBeNull()
        ->and($board2->player_id)->toBe($player2_id);

    // Player 1 places all ships
    placeShip($game_state, $player1_id, ShipType::CARRIER, 0, 0, 'horizontal');
    placeShip($game_state, $player1_id, ShipType::BATTLESHIP, 1, 0, 'horizontal');
    placeShip($game_state, $player1_id, ShipType::CRUISER, 2, 0, 'horizontal');
    placeShip($game_state, $player1_id, ShipType::SUBMARINE, 3, 0, 'horizontal');
    placeShip($game_state, $player1_id, ShipType::DESTROYER, 4, 0, 'horizontal');

    expect($board1->isSetupComplete())->toBeTrue();

    // Player 2 places all ships
    placeShip($game_state, $player2_id, ShipType::CARRIER, 0, 0, 'vertical');
    placeShip($game_state, $player2_id, ShipType::BATTLESHIP, 0, 1, 'vertical');
    placeShip($game_state, $player2_id, ShipType::CRUISER, 0, 2, 'vertical');
    placeShip($game_state, $player2_id, ShipType::SUBMARINE, 0, 3, 'vertical');
    placeShip($game_state, $player2_id, ShipType::DESTROYER, 0, 4, 'vertical');

    expect($board2->isSetupComplete())->toBeTrue();

    // Game should start automatically when both players finish setup
    expect($game_state->started)->toBeTrue()
        ->and($game_state->active_player_id)->toBe($player1_id);

    // Player 1 shoots at Player 2's carrier (hits)
    takeShot($game_state, $player1_id, $player2_id, 0, 0);
    takeShot($game_state, $player1_id, $player2_id, 1, 0);
    takeShot($game_state, $player1_id, $player2_id, 2, 0);
    takeShot($game_state, $player1_id, $player2_id, 3, 0);
    takeShot($game_state, $player1_id, $player2_id, 4, 0);

    // Carrier should be sunk
    expect($board2->isShipSunk('carrier'))->toBeTrue();

    // Player 1 continues shooting (hits don't end turn in Battleship)
    takeShot($game_state, $player1_id, $player2_id, 0, 1);
    takeShot($game_state, $player1_id, $player2_id, 1, 1);
    takeShot($game_state, $player1_id, $player2_id, 2, 1);
    takeShot($game_state, $player1_id, $player2_id, 3, 1);

    // Battleship should be sunk
    expect($board2->isShipSunk('battleship'))->toBeTrue();

    // Continue sinking all ships...
    takeShot($game_state, $player1_id, $player2_id, 0, 2);
    takeShot($game_state, $player1_id, $player2_id, 1, 2);
    takeShot($game_state, $player1_id, $player2_id, 2, 2);

    takeShot($game_state, $player1_id, $player2_id, 0, 3);
    takeShot($game_state, $player1_id, $player2_id, 1, 3);
    takeShot($game_state, $player1_id, $player2_id, 2, 3);

    takeShot($game_state, $player1_id, $player2_id, 0, 4);
    takeShot($game_state, $player1_id, $player2_id, 1, 4);

    // All ships should be sunk
    expect($board2->isAllShipsSunk())->toBeTrue();

    // Game should be ended
    expect($game_state->ended)->toBeTrue()
        ->and($game_state->winner_id)->toBe($player1_id)
        ->and($game_state->winner())->toBe($player1);

    // Assert that GameEnded event was fired
    Verbs::assertCommitted(GameEnded::class, function ($event) use ($player1_id) {
        return $event->winner_id === $player1_id;
    });
});

// Board Setup Tests
test('board has exactly 10x10 grid', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    $player = PlayerState::load($player_id);
    $board = $player->board();

    expect($board->grid)->toHaveCount(10)
        ->and($board->grid[0])->toHaveCount(10)
        ->and($board->ships)->toHaveCount(0)
        ->and($board->shots)->toHaveCount(0);
});

test('board setup is complete when all ships placed', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);

    dump($game_state);
    dump($player_id);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    dump($game_state->player_ids);
    dump($player_id);
    $player = PlayerState::load($player_id);
    $board = $player->board();

    dump($board);

    expect($board->isSetupComplete())->toBeFalse();
    dump($board->isSetupComplete());

    // Place all 5 ships
    placeShip($game_state, $player_id, ShipType::CARRIER, 0, 0, 'horizontal');
    placeShip($game_state, $player_id, ShipType::BATTLESHIP, 1, 0, 'horizontal');
    placeShip($game_state, $player_id, ShipType::CRUISER, 2, 0, 'horizontal');
    placeShip($game_state, $player_id, ShipType::SUBMARINE, 3, 0, 'horizontal');
    placeShip($game_state, $player_id, ShipType::DESTROYER, 4, 0, 'horizontal');

    expect($board->isSetupComplete())->toBeTrue();
});

// Ship Placement Tests
test('can place ship horizontally', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    $player = PlayerState::load($player_id);
    $board = $player->board();

    placeShip($game_state, $player_id, ShipType::CARRIER, 0, 0, 'horizontal');

    expect($board->getShipAt(0, 0))->toBe('carrier')
        ->and($board->getShipAt(0, 1))->toBe('carrier')
        ->and($board->getShipAt(0, 2))->toBe('carrier')
        ->and($board->getShipAt(0, 3))->toBe('carrier')
        ->and($board->getShipAt(0, 4))->toBe('carrier')
        ->and($board->getShipAt(0, 5))->toBeNull();
});

test('can place ship vertically', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    $player = PlayerState::load($player_id);
    $board = $player->board();

    placeShip($game_state, $player_id, ShipType::BATTLESHIP, 0, 0, 'vertical');

    expect($board->getShipAt(0, 0))->toBe('battleship')
        ->and($board->getShipAt(1, 0))->toBe('battleship')
        ->and($board->getShipAt(2, 0))->toBe('battleship')
        ->and($board->getShipAt(3, 0))->toBe('battleship')
        ->and($board->getShipAt(4, 0))->toBeNull();
});

test('cannot place ship out of bounds', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    // Try to place ship that goes off the board
    expect(fn () => placeShip($game_state, $player_id, ShipType::CARRIER, 0, 8, 'horizontal'))
        ->toThrow(EventNotValidForCurrentState::class);

    expect(fn () => placeShip($game_state, $player_id, ShipType::CARRIER, 8, 0, 'vertical'))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot place ship overlapping another ship', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    // Place first ship
    placeShip($game_state, $player_id, ShipType::CARRIER, 0, 0, 'horizontal');

    // Try to place overlapping ship
    expect(fn () => placeShip($game_state, $player_id, ShipType::BATTLESHIP, 0, 2, 'vertical'))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot place same ship type twice', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    // Place carrier
    placeShip($game_state, $player_id, ShipType::CARRIER, 0, 0, 'horizontal');

    // Try to place another carrier
    expect(fn () => placeShip($game_state, $player_id, ShipType::CARRIER, 1, 0, 'horizontal'))
        ->toThrow(EventNotValidForCurrentState::class);
});

// Shot Taking Tests
test('can take shot and hit ship', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    $target_player = PlayerState::load($target_id);
    $target_board = $target_player->board();

    // Place a ship
    placeShip($game_state, $target_id, ShipType::DESTROYER, 0, 0, 'horizontal');

    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    // Take shot
    takeShot($game_state, $shooter_id, $target_id, 0, 0);

    $shots = $target_board->getShots();
    expect($shots)->toHaveCount(1)
        ->and($shots['0,0']['hit'])->toBeTrue()
        ->and($shots['0,0']['ship'])->toBe('destroyer');
});

test('can take shot and miss', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    $target_player = PlayerState::load($target_id);
    $target_board = $target_player->board();

    // Take shot at empty space
    takeShot($game_state, $shooter_id, $target_id, 5, 5);

    $shots = $target_board->getShots();
    expect($shots)->toHaveCount(1)
        ->and($shots['5,5']['hit'])->toBeFalse()
        ->and($shots['5,5']['ship'])->toBeNull();
});

test('cannot shoot same position twice', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    // Take first shot
    takeShot($game_state, $shooter_id, $target_id, 0, 0);

    // Try to shoot same position again
    expect(fn () => takeShot($game_state, $shooter_id, $target_id, 0, 0))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot shoot out of bounds', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    // Try to shoot out of bounds
    expect(fn () => takeShot($game_state, $shooter_id, $target_id, 10, 0))
        ->toThrow(EventNotValidForCurrentState::class);

    expect(fn () => takeShot($game_state, $shooter_id, $target_id, 0, 10))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot shoot at yourself', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    // Try to shoot at yourself
    expect(fn () => takeShot($game_state, $player_id, $player_id, 0, 0))
        ->toThrow(EventNotValidForCurrentState::class);
});

// Ship Sinking Tests
test('ship sinks when fully hit', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    $target_player = PlayerState::load($target_id);
    $target_board = $target_player->board();

    // Place a destroyer (2 spaces)
    placeShip($game_state, $target_id, ShipType::DESTROYER, 0, 0, 'horizontal');

    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    // Hit first position
    takeShot($game_state, $shooter_id, $target_id, 0, 0);
    expect($target_board->isShipSunk('destroyer'))->toBeFalse();

    // Hit second position - ship should sink
    takeShot($game_state, $shooter_id, $target_id, 0, 1);

    expect($target_board->isShipSunk('destroyer'))->toBeTrue();

    // Assert that ShipSunk event was fired
    Verbs::assertCommitted(ShipSunk::class, function ($event) use ($shooter_id, $target_id) {
        return $event->player_id === $shooter_id && $event->target_player_id === $target_id;
    });
});

test('all ships sunk triggers game end', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    $target_player = PlayerState::load($target_id);
    $target_board = $target_player->board();

    // Place all ships
    placeShip($game_state, $target_id, ShipType::CARRIER, 0, 0, 'horizontal');
    placeShip($game_state, $target_id, ShipType::BATTLESHIP, 1, 0, 'horizontal');
    placeShip($game_state, $target_id, ShipType::CRUISER, 2, 0, 'horizontal');
    placeShip($game_state, $target_id, ShipType::SUBMARINE, 3, 0, 'horizontal');
    placeShip($game_state, $target_id, ShipType::DESTROYER, 4, 0, 'horizontal');

    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    // Sink all ships
    $row = 0;
    foreach (ShipType::all() as $shipType) {
        $length = $shipType->length();
        for ($i = 0; $i < $length; $i++) {
            takeShot($game_state, $shooter_id, $target_id, $row, $i);
        }
        $row++;
    }

    expect($target_board->isAllShipsSunk())->toBeTrue()
        ->and($game_state->ended)->toBeTrue()
        ->and($game_state->winner_id)->toBe($shooter_id);

    // Assert that GameEnded event was fired
    Verbs::assertCommitted(GameEnded::class, function ($event) use ($shooter_id) {
        return $event->winner_id === $shooter_id;
    });
});

// Game State Tests
test('game state initial values', function () {
    $game_state = verb(new GameCreated)->state(GameState::class);

    expect($game_state->created)->toBeTrue()
        ->and($game_state->player_ids)->toHaveCount(0)
        ->and($game_state->active_player_id)->toBeNull()
        ->and($game_state->last_player_id)->toBeNull()
        ->and($game_state->winner_id)->toBeNull()
        ->and($game_state->ended)->toBeFalse()
        ->and($game_state->started)->toBeFalse()
        ->and($game_state->created_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});

test('game state has player', function () {
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    $game_state = GameState::factory()->create([
        'player_ids' => [$player1_id, $player2_id],
    ]);

    expect($game_state->hasPlayer($player1_id))->toBeTrue()
        ->and($game_state->hasPlayer($player2_id))->toBeTrue()
        ->and($game_state->hasPlayer(999))->toBeFalse()
        ->and($game_state->hasPlayer(null))->toBeFalse();
});

test('game state has all players joined', function () {
    // No players
    $game_state = GameState::factory()->create(['player_ids' => []]);
    expect($game_state->hasAllPlayersJoined())->toBeFalse();

    // One player
    $game_state = GameState::factory()->create(['player_ids' => [snowflake_id()]]);
    expect($game_state->hasAllPlayersJoined())->toBeFalse();

    // Two players
    $game_state = GameState::factory()->create(['player_ids' => [snowflake_id(), snowflake_id()]]);
    expect($game_state->hasAllPlayersJoined())->toBeTrue();
});

test('game state is in progress', function () {
    $player1_id = snowflake_id();

    // Game in progress
    $game_state = GameState::factory()->create([
        'player_ids' => [$player1_id],
        'active_player_id' => $player1_id,
        'ended' => false,
    ]);

    expect($game_state->isInProgress())->toBeTrue();

    // Game not in progress (no active player)
    $game_state = GameState::factory()->create([
        'player_ids' => [$player1_id],
        'active_player_id' => null,
    ]);

    expect($game_state->isInProgress())->toBeFalse();

    // Game ended
    $game_state = GameState::factory()->create([
        'player_ids' => [$player1_id],
        'active_player_id' => $player1_id,
        'ended' => true,
    ]);

    expect($game_state->isInProgress())->toBeFalse();
});

test('game state move to next player', function () {
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    $game_state = GameState::factory()->create([
        'player_ids' => [$player1_id, $player2_id],
        'active_player_id' => $player1_id,
    ]);

    // Move from player 1 to player 2
    $game_state->moveToNextPlayer();
    expect($game_state->active_player_id)->toBe($player2_id);

    // Move from player 2 back to player 1 (wraps around)
    $game_state->moveToNextPlayer();
    expect($game_state->active_player_id)->toBe($player1_id);
});

// Turn Management Tests
test('turn ends after miss', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    // Set up game as in progress
    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    // Miss a shot
    takeShot($game_state, $shooter_id, $target_id, 5, 5);

    // Assert that TurnEnded event was fired
    Verbs::assertCommitted(TurnEnded::class, function ($event) use ($game_state) {
        return $event->game_id === $game_state->id;
    });
});

test('turn does not end after hit', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    $target_player = PlayerState::load($target_id);
    $target_board = $target_player->board();

    // Place a ship
    placeShip($game_state, $target_id, ShipType::DESTROYER, 0, 0, 'horizontal');

    // Set up game as in progress
    $game_state->started = true;
    $game_state->active_player_id = $shooter_id;

    // Hit the ship
    takeShot($game_state, $shooter_id, $target_id, 0, 0);

    // TurnEnded should not be fired for a hit
    Verbs::assertNotCommitted(TurnEnded::class);
});

// Edge Cases and Error Conditions
test('cannot place ship when game is in progress', function () {
    $player_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player_id));

    // Start the game
    $game_state->started = true;

    // Try to place ship when game is in progress
    expect(fn () => placeShip($game_state, $player_id, ShipType::CARRIER, 0, 0, 'horizontal'))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot take shot when game is not in progress', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    // Game is not started yet
    expect(fn () => takeShot($game_state, $shooter_id, $target_id, 0, 0))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot take shot when game is ended', function () {
    $shooter_id = snowflake_id();
    $target_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $shooter_id));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $target_id));

    // End the game
    $game_state->ended = true;

    expect(fn () => takeShot($game_state, $shooter_id, $target_id, 0, 0))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('only 2 players may join the game', function () {
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();
    $player3_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);

    // First player joins
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));
    expect($game_state->player_ids)->toHaveCount(1);

    // Second player joins
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    expect($game_state->player_ids)->toHaveCount(2);

    // Third player tries to join - should fail
    expect(fn () => verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player3_id)))
        ->toThrow(EventNotValidForCurrentState::class);

    expect($game_state->player_ids)->toHaveCount(2);
});

// Ship Type Tests
test('all ship types have correct lengths', function () {
    expect(ShipType::CARRIER->length())->toBe(5)
        ->and(ShipType::BATTLESHIP->length())->toBe(4)
        ->and(ShipType::CRUISER->length())->toBe(3)
        ->and(ShipType::SUBMARINE->length())->toBe(3)
        ->and(ShipType::DESTROYER->length())->toBe(2);
});

test('ship types have correct display names', function () {
    expect(ShipType::CARRIER->displayName())->toBe('Carrier')
        ->and(ShipType::BATTLESHIP->displayName())->toBe('Battleship')
        ->and(ShipType::CRUISER->displayName())->toBe('Cruiser')
        ->and(ShipType::SUBMARINE->displayName())->toBe('Submarine')
        ->and(ShipType::DESTROYER->displayName())->toBe('Destroyer');
});

test('total ships count is correct', function () {
    expect(ShipType::totalShips())->toBe(5)
        ->and(ShipType::all())->toHaveCount(5);
});
