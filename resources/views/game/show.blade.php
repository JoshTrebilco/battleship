@php
    $channel = Str::after(config('app.url'), 'https://').'.'.'game.'.$game->id;
@endphp

<x-layout>
    <!-- Header with Back Link -->
    <div class="mb-6">
        <a href="{{ route('games.index') }}"
            class="inline-flex items-center space-x-2 text-slate-700 hover:translate-x-[-2px] transition-transform">
            <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-2xl font-bold">Connect 4</span>
        </a>
    </div>

    <!-- Game Board -->
    <div class="mt-2 lg:mt-5 flex flex-col gap-6 lg:flex-row lg:items-start">
        <x-board :game="$game" :auth_player_id="$auth_player_id" :channel="$channel" />
        <x-panel :game="$game" :auth_player_id="$auth_player_id" :channel="$channel" />
        
        <!-- Winner Modal (controlled by JavaScript) -->
        <div id="winner-modal" class="fixed inset-0 bg-white/90 backdrop-blur-sm flex flex-col items-center justify-center hidden z-50">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-8 border border-cyan-200 shadow-xl text-center">
                <div class="flex items-center justify-center space-x-3 mb-6">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center">
                        <!-- Pre-rendered winner tokens -->
                        <div id="winner-token-blue" class="hidden">
                            <x-token color="blue" :size="40" />
                        </div>
                        <div id="winner-token-green" class="hidden">
                            <x-token color="green" :size="40" />
                        </div>
                        <div id="winner-token-red" class="hidden">
                            <x-token color="red" :size="40" />
                        </div>
                        <div id="winner-token-yellow" class="hidden">
                            <x-token color="yellow" :size="40" />
                        </div>
                    </div>
                    <h2 id="winner-text" class="text-2xl font-bold text-slate-700">
                        <!-- Winner text will be populated by JavaScript -->
                    </h2>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button id="view-board-btn"
                        class="hover:cursor-pointer inline-flex bg-gradient-to-r from-slate-500 to-slate-600 text-white rounded-lg px-6 py-3 font-semibold transform transition hover:translate-y-[-2px]">
                        View Board
                    </button>
                    <a href="{{ route('games.index') }}"
                        class="inline-flex bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg px-6 py-3 font-semibold transform transition hover:translate-y-[-2px]">
                        Back to Games
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sound Control -->
    <div class="fixed bottom-4 right-4 z-40">
        <button id="sound-toggle" class="bg-white/90 backdrop-blur-sm rounded-full p-3 shadow-lg border border-cyan-200 hover:shadow-xl transition-all duration-200">
            <svg id="sound-on-icon" class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M6.343 6.343a1 1 0 00-1.414 1.414L7.586 9H4a1 1 0 00-1 1v4a1 1 0 001 1h3.586l-2.657 2.657a1 1 0 001.414 1.414L9.414 15H11a1 1 0 001-1v-4a1 1 0 00-1-1H9.414l2.657-2.657z" />
            </svg>
            <svg id="sound-off-icon" class="w-6 h-6 text-slate-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
        </button>
    </div>
</x-layout>
<script>
    class SoundManager {
        constructor() {
            this.sounds = {};
            this.muted = localStorage.getItem('game-sound-muted') === 'true';
            this.loadSounds();
            this.setupToggle();
        }

        loadSounds() {
            const soundFiles = {
                'placed-token': '/sounds/placed-token.mp3',
                'winner': '/sounds/winner.mp3',
                'game-start': '/sounds/game-start.mp3',
            };

            Object.entries(soundFiles).forEach(([name, url]) => {
                this.sounds[name] = new Audio(url);
                this.sounds[name].preload = 'auto';
            });
        }

        play(soundName) {
            if (this.muted || !this.sounds[soundName]) return;
            
            try {
                this.sounds[soundName].currentTime = 0;
                this.sounds[soundName].play().catch(e => {
                    // Ignore autoplay errors
                    console.log('Sound play failed:', e);
                });
            } catch (e) {
                console.log('Sound error:', e);
            }
        }

        setupToggle() {
            const toggle = document.getElementById('sound-toggle');
            const onIcon = document.getElementById('sound-on-icon');
            const offIcon = document.getElementById('sound-off-icon');

            if (this.muted) {
                onIcon.classList.add('hidden');
                offIcon.classList.remove('hidden');
            }

            toggle.addEventListener('click', () => {
                this.muted = !this.muted;
                localStorage.setItem('game-sound-muted', this.muted.toString());
                
                if (this.muted) {
                    onIcon.classList.add('hidden');
                    offIcon.classList.remove('hidden');
                } else {
                    onIcon.classList.remove('hidden');
                    offIcon.classList.add('hidden');
                }
            });
        }
    }
    class Game {
        constructor() {
            this.players = {!! json_encode($game->players()->map(fn($p) => ['id' => (string)$p->id, 'name' => $p->name, 'color' => $p->color])) !!};
            this.authPlayerId = '{{ $auth_player_id ?? 'null' }}';
            this.activePlayerId = '{{ $game->activePlayer()?->id ?? 'null' }}';
            this.channel = window.Echo.channel(@json($channel));
            this.winnerOverlayDismissed = false;
            this.soundManager = new SoundManager();
        }

        handleEvent(event, gameState) {
            if (event === 'App\\Events\\Gameplay\\PlayerWonGame' && gameState?.winner_id !== undefined) {
                this.soundManager.play('winner');
                this.showWinner(gameState.winner_id);
            }

            if (event === 'App\\Events\\Gameplay\\PlacedToken') {
                this.soundManager.play('placed-token');
            }

            if (event === 'App\\Events\\Setup\\PlayerJoinedGame') {
                this.soundManager.play('player-joined');
            }

            if (event.startsWith('App\\Events\\Setup')) {
                window.location.reload();
            }
        }

        showWinner(winnerId) {
            // Don't show overlay if it was previously dismissed
            if (this.winnerOverlayDismissed) return;
            
            const winner = this.players.find(p => p.id === String(winnerId));
            if (!winner) return;

            this.updateWinnerToken(winner);
            this.updateWinnerText(winner);
            this.displayWinnerModal();
        }

        updateWinnerToken(winner) {
            // Hide all winner tokens first
            document.querySelectorAll('[id^="winner-token-"]').forEach(token => {
                token.classList.add('hidden');
            });
            
            // Show the correct winner token
            const winnerToken = document.getElementById(`winner-token-${winner.color}`);
            if (winnerToken) {
                winnerToken.classList.remove('hidden');
            }
        }

        updateWinnerText(winner) {
            const text = document.getElementById('winner-text');
            text.textContent = `${winner.name} won the game!`;
        }

        displayWinnerModal() {
            const modal = document.getElementById('winner-modal');
            modal.classList.remove('hidden');
            
            // Add celebration animation to the visible winner token
            const visibleToken = document.querySelector('[id^="winner-token-"]:not(.hidden)');
            if (visibleToken) {
                visibleToken.classList.add('animate-win-celebration');
                
                // Remove animation class after completion
                setTimeout(() => {
                    visibleToken.classList.remove('animate-win-celebration');
                }, 1500);
            }
        }

        hideWinnerModal() {
            const modal = document.getElementById('winner-modal');
            modal.classList.add('hidden');
            this.winnerOverlayDismissed = true;
        }

        checkInitialWinner() {
            @if($game->winner())
                this.showWinner('{{ $game->winner()->id }}');
            @endif
        }

        init() {
            this.checkInitialWinner();
            this.channel.listen('BroadcastEvent', (data) => {
                this.handleEvent(data.event, data.gameState);
            });
            
            // Add event listener for view board button
            document.getElementById('view-board-btn').addEventListener('click', () => {
                this.hideWinnerModal();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.game = new Game();
        window.game.init();
    });
</script>