<?php

namespace App\Http\Controllers;

use App\Enums\ShipType;
use App\Events\Gameplay\PlayerPlacedShip;
use App\Events\Gameplay\PlayerTakesShot;
use App\Events\Setup\PlayerJoinedGame;
use App\States\GameState;
use App\States\PlayerState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Thunk\Verbs\Exceptions\EventNotValidForCurrentState;

class PlayerController extends Controller
{
    public function join(Request $request, int $game_id): RedirectResponse
    {
        $this->ensureAuthenticated();

        $game = GameState::load($game_id);
        $currentPlayerId = Auth::user()->current_player_id;

        if ($currentPlayerId && $game->hasPlayer((int) $currentPlayerId)) {
            return redirect()->route('games.show', $game_id);
        }

        $player_id = snowflake_id();
        Auth::user()->update(['current_player_id' => $player_id]);

        try {
            verb(new PlayerJoinedGame(
                game_id: $game_id,
                player_id: $player_id,
            ));
        } catch (EventNotValidForCurrentState $exception) {
            return back()->withErrors($exception->getMessage());
        }

        return redirect()->route('games.show', $game_id);
    }

    public function placeShip(Request $request, int $game_id, int $player_id): RedirectResponse
    {
        $this->authorizePlayer($player_id);

        $validated = $request->validate([
            'ship_type' => ['required', Rule::in(array_map(fn (ShipType $type) => $type->value, ShipType::all()))],
            'row' => ['required', 'integer', 'min:0', 'max:9'],
            'col' => ['required', 'integer', 'min:0', 'max:9'],
            'direction' => ['required', Rule::in(['horizontal', 'vertical'])],
        ]);

        $player = PlayerState::load($player_id);
        $board = $player->board();

        if (! $board) {
            return back()->withErrors('Board is not ready yet.');
        }

        try {
            verb(new PlayerPlacedShip(
                game_id: $game_id,
                player_id: $player_id,
                board_id: $board->id,
                ship_type: ShipType::from($validated['ship_type']),
                row: (int) $validated['row'],
                col: (int) $validated['col'],
                direction: $validated['direction'],
            ));
        } catch (EventNotValidForCurrentState $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        return back()->with('status', 'Ship placed successfully.');
    }

    public function takeShot(Request $request, int $game_id, int $player_id): RedirectResponse
    {
        $this->authorizePlayer($player_id);

        $game = GameState::load($game_id);

        $validated = $request->validate([
            'target_player_id' => ['required', 'integer', Rule::in($game->player_ids), Rule::notIn([$player_id])],
            'row' => ['required', 'integer', 'min:0', 'max:9'],
            'col' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        $targetPlayer = PlayerState::load($validated['target_player_id']);
        $targetBoard = $targetPlayer->board();

        if (! $targetBoard) {
            return back()->withErrors('Target board is not available.');
        }

        try {
            $shot = verb(new PlayerTakesShot(
                game_id: $game_id,
                player_id: $player_id,
                target_player_id: $validated['target_player_id'],
                target_board_id: $targetBoard->id,
                row: (int) $validated['row'],
                col: (int) $validated['col'],
            ));
        } catch (EventNotValidForCurrentState $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        return back()->with('status', $shot->getShotResult($targetBoard));
    }

    protected function authorizePlayer(int $player_id): void
    {
        $this->ensureAuthenticated();

        abort_if((int) Auth::user()->current_player_id !== $player_id, 403);
    }

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::check(), 403);
    }
}

