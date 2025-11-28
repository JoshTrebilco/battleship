@php
    $host = \Illuminate\Support\Str::of(config('app.url'))->after('://')->trim('/');
    $channel = "{$host}.game.{$game->id}";
@endphp

<x-layout>
    <div class="w-full min-h-screen px-4 sm:px-6 lg:px-10 py-4 sm:py-6 md:py-10">
        <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 md:space-y-10">
            <div class="flex items-center gap-3 text-slate-300">
                <a href="{{ route('games.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-200 transition">
                    <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    Back to briefing room
                </a>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-600">/</span>
                <span class="text-sky-200 font-semibold">Operation {{ $game->id }}</span>
            </div>

            <div class="grid gap-4 sm:gap-6 md:gap-8 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <div>
                    <x-board :game="$game" :auth_player_id="$auth_player_id" :channel="$channel" />
                </div>
                <div class="space-y-8">
                    <x-panel :game="$game" :auth_player_id="$auth_player_id" :channel="$channel" />
                </div>
            </div>
        </div>
    </div>

    <div id="winner-overlay" class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm z-50 hidden">
        <div class="w-full h-full flex flex-col items-center justify-center px-4">
            <div class="bg-slate-900/90 border border-slate-700 rounded-3xl px-10 py-8 text-center shadow-2xl shadow-black/60 space-y-6 max-w-lg mx-auto">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.5em] text-amber-400">Victory Report</p>
                    <h2 id="winner-text" class="text-3xl font-bold text-white">Fleet victorious!</h2>
                    <p class="text-slate-300 text-sm">
                        Debrief your crew or start a new engagement from command.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button
                        id="dismiss-overlay"
                        class="px-5 py-3 rounded-2xl bg-slate-800 text-slate-200 border border-slate-600 hover:bg-slate-700 transition"
                    >
                        Continue viewing board
                    </button>
                    <a
                        href="{{ route('games.index') }}"
                        class="px-5 py-3 rounded-2xl bg-linear-to-r from-sky-500 to-blue-600 text-white font-semibold shadow hover:translate-y-[-2px] transition inline-flex items-center justify-center"
                    >
                        Back to command
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>

<script>
    class BattleshipUI {
        constructor() {
            this.channel = window.Echo.channel(@json($channel));
            this.players = {!! $game->players()->map(fn ($p) => ['id' => (string) $p->id, 'name' => $p->name])->values()->toJson() !!};
            this.initialWinner = '{{ $game->winner_id ?? '' }}';
            this.overlay = document.getElementById('winner-overlay');
            this.winnerText = document.getElementById('winner-text');
            this.dismissButton = document.getElementById('dismiss-overlay');
        }

        init() {
            if (this.initialWinner) {
                this.showWinner(this.initialWinner);
            }

            this.channel.listen('BroadcastEvent', (payload) => {
                this.handleEvent(payload.event, payload.gameState);
            });

            this.dismissButton?.addEventListener('click', () => {
                this.overlay?.classList.add('hidden');
            });
        }

        handleEvent(event, gameState) {
            if (event === 'App\\\\Events\\\\Gameplay\\\\GameEnded') {
                this.showWinner(gameState?.winner_id);
                return;
            }

            window.location.reload();
        }

        showWinner(winnerId) {
            const commander = this.players.find(player => player.id === String(winnerId));
            this.winnerText.textContent = commander
                ? `${commander.name} won the battle`
                : 'Battle concluded';
            this.overlay?.classList.remove('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.Echo) {
            window.battleshipUI = new BattleshipUI();
            window.battleshipUI.init();
        }
    });
</script>

