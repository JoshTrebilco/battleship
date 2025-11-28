@props(['game', 'auth_player_id', 'channel'])

@php
    use App\Enums\ShipType;
    use App\States\PlayerState;
    use Illuminate\Support\Str;

    $hasJoined = $auth_player_id && $game->hasPlayer($auth_player_id);
    $player = null;
    $playerBoard = null;

    if ($hasJoined) {
        try {
            $player = PlayerState::load($auth_player_id);
            $playerBoard = $player?->board();
        } catch (\Throwable $e) {
            $player = null;
            $playerBoard = null;
        }
    }

    $placedShips = array_keys($playerBoard->ships ?? []);
    $remainingShips = collect(ShipType::all())->reject(fn (ShipType $ship) => in_array($ship->value, $placedShips));
    $players = $game->players();
    $opponents = $players->reject(fn ($p) => $p->id == $auth_player_id);
    $targetPlayerId = $opponents->first()?->id;
    $isTurn = $game->active_player_id && (string) $game->active_player_id === (string) $auth_player_id;
    $gameLink = url('/games/' . $game->id);
@endphp

<div class="space-y-6 w-full">
    {{-- @if (session('status'))
        <div class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-2xl">
            @if (isset(session('status')['hit']))
                {{ session('status')['hit'] ? 'Hit!' : 'Miss!' }}
            @else
                {{ session('status') }}
            @endif
        </div>
    @endif --}}

    @if ($errors->any())
        <div class="bg-rose-500/10 border border-rose-500/40 text-rose-200 px-4 py-3 rounded-2xl space-y-2">
            <p class="font-semibold">Command could not be executed:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $hasJoined && ! $game->ended)
        <div class="bg-slate-900/80 border border-slate-700 rounded-3xl p-6 space-y-4 shadow-xl shadow-black/40">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-sky-500 to-blue-700 flex items-center justify-center text-white">
                    ⚓
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-sky-100">Join the Battle</h3>
                    <p class="text-sm text-slate-400">Reserve your post on the command bridge.</p>
                </div>
            </div>
            @auth
                <form action="{{ route('players.join', ['game_id' => $game->id]) }}" method="post" class="space-y-4">
                    @csrf
                    <button
                        type="submit"
                        class="w-full bg-linear-to-r from-sky-600 to-blue-700 text-white font-semibold rounded-2xl px-4 py-3 shadow-lg shadow-sky-900/40 hover:translate-y-[-2px] transition disabled:opacity-50 disabled:cursor-not-allowed"
                        @if($game->hasAllPlayersJoined()) disabled @endif
                    >
                        {{ $game->hasAllPlayersJoined() ? 'Fleet already full' : 'Join Fleet' }}
                    </button>
                </form>
            @else
                <p class="text-sm text-slate-400">
                    Please <a href="{{ route('login.index') }}" class="text-sky-300 underline">sign in</a> to enlist.
                </p>
            @endauth
        </div>
    @endif

    @if ($hasJoined && ! $game->hasAllPlayersJoined())
    <div class="bg-slate-900/80 border border-slate-700 rounded-3xl p-6 space-y-4 shadow-xl shadow-black/40">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-slate-600 to-slate-800 flex items-center justify-center text-white">
                🔗
            </div>
            <div>
                <h3 class="text-lg font-semibold text-sky-100">Invite a Captain</h3>
                <p class="text-sm text-slate-400">Share this secure channel to complete your fleet.</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <input
                type="text"
                readonly
                value="{{ $gameLink }}"
                class="flex-1 px-4 py-3 rounded-2xl border border-slate-700 bg-slate-800/70 text-slate-200 text-sm focus:outline-none"
            />
            <button
                type="button"
                id="copy-link-btn"
                data-link="{{ $gameLink }}"
                class="px-4 py-3 rounded-2xl bg-linear-to-r from-sky-500 to-sky-700 text-white font-semibold shadow hover:translate-y-[-2px] transition"
            >
                Copy Link
                </button>
            </div>
        </div>
    @endif

    @if ($hasJoined && $game->hasAllPlayersJoined() && ! $game->started && ! $game->ended)
        <div class="bg-slate-900/80 border border-slate-700 rounded-3xl p-6 space-y-4 shadow-xl shadow-black/40">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-amber-500 to-orange-700 flex items-center justify-center text-white">
                    🚢
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-sky-100">Deploy Fleet</h3>
                    <p class="text-sm text-slate-400">Place each ship on a 10x10 grid.</p>
                </div>
            </div>

            @if ($remainingShips->isEmpty())
                <div class="bg-slate-800/60 border border-slate-700 rounded-2xl px-4 py-3 text-sm text-slate-300">
                    All ships deployed. Awaiting other commander to finish setup.
                </div>
            @else
                <form action="{{ route('players.placeShip', ['game_id' => $game->id, 'player_id' => $auth_player_id]) }}" method="post" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex flex-col text-sm text-slate-300 space-y-1">
                            Ship Type
                            <select name="ship_type" class="rounded-2xl bg-slate-800 border border-slate-700 px-4 py-2 text-slate-100 focus:outline-none">
                                @foreach ($remainingShips as $ship)
                                    <option value="{{ $ship->value }}">
                                        {{ $ship->displayName() }} ({{ $ship->length() }} tiles)
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="flex flex-col text-sm text-slate-300 space-y-1">
                            Direction
                            <select name="direction" class="rounded-2xl bg-slate-800 border border-slate-700 px-4 py-2 text-slate-100 focus:outline-none">
                                <option value="horizontal">Horizontal</option>
                                <option value="vertical">Vertical</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex flex-col text-sm text-slate-300 space-y-1">
                            Column (A-J)
                            <input type="text" name="col" class="rounded-2xl bg-slate-800 border border-slate-700 px-4 py-2 text-slate-100 focus:outline-none" required>
                        </label>
                        <label class="flex flex-col text-sm text-slate-300 space-y-1">
                            Row (1-10)
                            <input type="number" name="row" min="1" max="10" class="rounded-2xl bg-slate-800 border border-slate-700 px-4 py-2 text-slate-100 focus:outline-none" required>
                        </label>
                    </div>
                    <button
                        type="submit"
                        class="w-full bg-linear-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-2xl px-4 py-3 shadow hover:translate-y-[-2px] transition"
                    >
                        Deploy Ship
                    </button>
                </form>
            @endif
        </div>
    @endif


    @if (! $hasJoined && $game->isInProgress())
        <div class="bg-slate-900/80 border border-slate-700 rounded-3xl p-6 space-y-2 shadow-xl shadow-black/40">
            <h3 class="text-lg font-semibold text-sky-100">Spectator Mode</h3>
            <p class="text-sm text-slate-400">
                This battle is already underway. Observe the action or start a new game to command your own fleet.
            </p>
        </div>
    @endif

    <div class="bg-slate-900/80 border border-slate-700 rounded-3xl p-6 space-y-4 shadow-xl shadow-black/40">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-slate-500 to-slate-700 flex items-center justify-center text-white">
                🧭
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-lg font-semibold text-sky-100">Fleet Status</h3>
                    <span class="inline-flex items-center rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200 border border-slate-700">
                        {{ $game->ended ? 'Battle Over' : ($game->started ? 'In Progress' : 'Deploying') }}
                    </span>
                </div>
                <p class="text-sm text-slate-400">Monitor commanders, readiness, and victory conditions.</p>
            </div>
        </div>

        <ul class="space-y-3">
            @foreach ($players as $fleetPlayer)
                <li class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700">
                    <x-token
                        :variant="$fleetPlayer->board()?->isSetupComplete() ? 'ship' : 'pending'"
                        :size="40"
                        :label="Str::upper(Str::substr($fleetPlayer->name, 0, 2))"
                    />
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sky-100 font-semibold">{{ $fleetPlayer->name }}</span>
                            @if ($fleetPlayer->id === $auth_player_id)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-sky-500/20 border border-sky-500/40 text-sky-100">You</span>
                            @endif
                            @if ($fleetPlayer->id === $game->active_player_id && ! $game->ended)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-200">Taking Turn</span>
                            @endif
                            @if ($fleetPlayer->id === $game->winner_id)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-200">Winner</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-400">
                            Ships deployed: {{ count($fleetPlayer->board()?->ships ?? []) }}/{{ \App\Enums\ShipType::totalShips() }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const copyBtn = document.getElementById('copy-link-btn');
        if (!copyBtn) return;

        copyBtn.addEventListener('click', async () => {
            const link = copyBtn.dataset.link;
            try {
                await navigator.clipboard.writeText(link);
                copyBtn.textContent = 'Copied!';
                setTimeout(() => copyBtn.textContent = 'Copy Link', 1500);
            } catch (error) {
                console.error('Unable to copy link', error);
            }
        });
    });
</script>

