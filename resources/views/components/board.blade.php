@props(['game', 'auth_player_id', 'channel'])

@php
    use App\States\PlayerState;

    $rows = range(0, 9);
    $cols = range(0, 9);
    $colLabels = range('A', 'J');

    $player = null;
    $playerBoard = null;

    if ($auth_player_id) {
        try {
            $player = PlayerState::load($auth_player_id);
            $playerBoard = $player?->board();
        } catch (\Throwable $e) {
            $player = null;
            $playerBoard = null;
        }
    }

    $opponent = $game->players()->first(fn ($p) => $p->id !== $auth_player_id);
    $opponentBoard = $opponent?->board();

    $playerShots = $playerBoard?->getShots() ?? [];
    $opponentShots = $opponentBoard?->getShots() ?? [];
    $yourShots = [];

    foreach ($opponentShots as $key => $shot) {
        if (($shot['shooter_id'] ?? null) == $auth_player_id) {
            $yourShots[$key] = $shot;
        }
    }

    $cellClasses = [
        'ship' => 'bg-sky-500/60 border border-sky-300 text-white',
        'water' => 'bg-slate-700/70 border border-slate-600',
        'miss' => 'bg-slate-500/50 border border-slate-400',
        'hit' => 'bg-rose-500/80 border border-rose-300 animate-pulse',
        'unknown' => 'bg-slate-800/60 border border-slate-700',
    ];

    $statusForPlayerBoard = function (?array $grid, array $shots, int $row, int $col): string {
        $key = "{$row},{$col}";
        $shot = $shots[$key] ?? null;
        $hasShip = $grid[$row][$col] ?? null;

        if ($shot) {
            return $shot['hit'] ? 'hit' : 'miss';
        }

        return $hasShip ? 'ship' : 'water';
    };

    $statusForOpponentBoard = function (array $shots, int $row, int $col): string {
        $key = "{$row},{$col}";
        $shot = $shots[$key] ?? null;

        if ($shot) {
            return $shot['hit'] ? 'hit' : 'miss';
        }

        return 'unknown';
    };
@endphp

<div class="space-x-6 space-y-6 md:space-y-0 w-full flex flex-col md:flex-row">
    <div class="w-full md:w-1/2 bg-slate-900/80 border border-slate-700 rounded-3xl p-6 shadow-2xl shadow-black/40">
        <div class="h-24 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-sky-200 tracking-wide">Your Fleet</h2>
                <p class="text-sm text-slate-400">Position your ships and monitor enemy fire.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200 border border-slate-700">
                    Ships placed: {{ count($playerBoard->ships ?? []) }}/{{ \App\Enums\ShipType::totalShips() }}
                </span>
                @if($playerBoard?->isSetupComplete())
                    <span class="inline-flex items-center rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-300 border border-emerald-500/40">
                        Setup Complete
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-500/20 px-3 py-1 text-xs font-semibold text-amber-300 border border-amber-500/40">
                        Deploying
                    </span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="inline-grid grid-cols-[repeat(11,minmax(24px,1fr))] gap-1 text-center text-xs font-semibold text-slate-400">
                <div></div>
                @foreach($colLabels as $label)
                    <div class="py-1">{{ $label }}</div>
                @endforeach

                @foreach($rows as $rowIndex => $row)
                    <div class="flex items-center justify-center text-slate-400">{{ $row }}</div>
                    @foreach($cols as $colIndex => $col)
                        @php
                            $status = $playerBoard
                                ? $statusForPlayerBoard($playerBoard->grid ?? [], $playerShots, $row, $col)
                                : 'unknown';
                        @endphp
                        <div
                            class="aspect-square rounded-lg flex items-center justify-center text-[10px] {{ $cellClasses[$status] ?? $cellClasses['unknown'] }}"
                        >
                            @if($status === 'ship')
                                <span class="uppercase text-[9px] tracking-wide">
                                    {{ substr($playerBoard->grid[$row][$col] ?? 'S', 0, 1) }}
                                </span>
                            @elseif($status === 'hit')
                                ✱
                            @elseif($status === 'miss')
                                ·
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-4 text-xs text-slate-300">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-sky-500/60 border border-sky-300"></span>
                Ship segment
            </div>
        </div>
    </div>

    <div class="w-full md:w-1/2 bg-slate-900/80 border border-slate-700 rounded-3xl p-6 shadow-2xl shadow-black/40">
        <div class="h-24 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-sky-200 tracking-wide">Enemy Waters</h2>
                <p class="text-sm text-slate-400">Track your fired shots and known intel.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-200 border border-slate-700">
                    Shots fired: {{ count($yourShots) }}
                </span>
                @if($game->ended && $game->winner_id === $auth_player_id)
                    <span class="inline-flex items-center rounded-full bg-sky-500/20 px-3 py-1 text-xs font-semibold text-sky-200 border border-sky-500/40">
                        Victory
                    </span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="inline-grid grid-cols-[repeat(11,minmax(24px,1fr))] gap-1 text-center text-xs font-semibold text-slate-400">
                <div></div>
                @foreach($colLabels as $label)
                    <div class="py-1">{{ $label }}</div>
                @endforeach

                @foreach($rows as $rowIndex => $row)
                    <div class="flex items-center justify-center text-slate-400">{{ $row }}</div>
                    @foreach($cols as $colIndex => $col)
                        @php
                            $status = $statusForOpponentBoard($yourShots, $row, $col);
                        @endphp
                        <div
                            class="aspect-square rounded-lg flex items-center justify-center text-[10px] {{ $cellClasses[$status] ?? $cellClasses['unknown'] }}"
                        >
                            @if($status === 'hit')
                                ✱
                            @elseif($status === 'miss')
                                ·
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-4 text-xs text-slate-300">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-rose-500/80 border border-rose-300"></span>
                Confirmed hit
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-slate-500/50 border border-slate-400"></span>
                Miss
            </div>
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded bg-slate-800/60 border border-slate-700"></span>
                Unknown / Fog
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const channel = window.Echo.channel(@json($channel));

        channel.listen('BroadcastEvent', () => {
            window.location.reload();
        });
    });
</script>

