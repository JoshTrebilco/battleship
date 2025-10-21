<x-layout>
    <div class="min-h-screen w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Hero Section -->
            <div class="text-center mb-16">
                <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-linear-to-b from-sky-300 via-sky-500 to-blue-800 mb-4">
                    BATTLESHIP
                </h1>
                <p class="text-xl text-slate-300">
                    Commander, prepare for naval warfare! ⚓
                </p>
            </div>

            <!-- Game Options Cards -->
            <div class="grid sm:grid-cols-2 gap-6 sm:gap-8 max-w-4xl mx-auto">
                <!-- Join Game Card -->
                <div class="bg-slate-800/90 backdrop-blur-sm rounded-2xl shadow-xl transform transition duration-500 hover:scale-105 border border-slate-600">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center justify-center w-16 h-16 bg-linear-to-b from-sky-500 via-sky-600 to-blue-900 rounded-full mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-200 mb-4">Join a Battle</h2>
                        <p class="text-slate-300 mb-6">Received orders? Enter the battle code below to join your fleet!</p>

                        <form id="joinGameForm" class="space-y-4">
                            <input
                                type="text"
                                id="gameId"
                                name="game_id"
                                class="w-full px-4 py-3 rounded-lg border-2 border-slate-500 bg-slate-700/70 text-slate-200 placeholder-slate-400 focus:border-sky-600 focus:ring-0 focus:outline-none"
                                placeholder="Enter battle code or fleet URL..."
                                required
                            />
                            <button
                                type="submit"
                                id="joinButton"
                                class="w-full bg-linear-to-r from-slate-700 via-slate-600 to-slate-700 text-white rounded-lg px-4 py-3 font-semibold transform transition hover:translate-y-[-2px] hover:from-blue-900 hover:via-sky-600 hover:to-blue-900 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Report for Duty
                            </button>
                        </form>

                    <script>
                        document.getElementById('joinGameForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            const gameIdInput = document.getElementById('gameId').value.trim();
                            const button = document.getElementById('joinButton');
                            
                            if (gameIdInput === '') {
                                alert('Please enter a battle code or fleet URL');
                                return;
                            }
                            
                            // Extract game ID from URL or use as-is if it's just numbers
                            let gameId = gameIdInput;
                            
                            // Check if it's a full URL
                            if (gameIdInput.includes('/games/')) {
                                const match = gameIdInput.match(/\/games\/(\d+)/);
                                if (match) {
                                    gameId = match[1];
                                } else {
                                    alert('Invalid fleet URL format. Please check the URL and try again.');
                                    return;
                                }
                            } else if (!/^\d+$/.test(gameIdInput)) {
                                // Check if it's just numbers, if not show error
                                alert('Please enter a valid battle code (numbers only) or a complete fleet URL');
                                return;
                            }
                            
                            // Disable button to prevent double submission
                            button.disabled = true;
                            button.textContent = 'Reporting for duty...';
                            
                            // Redirect to the game
                            window.location.href = `./games/${gameId}`;
                        });

                        // Enable/disable button based on input
                        document.getElementById('gameId').addEventListener('input', function() {
                            const button = document.getElementById('joinButton');
                            const gameId = this.value.trim();
                            
                            button.disabled = gameId === '';
                        });
                    </script>
                </div>
            </div>

                <!-- Create Game Card -->
                <div class="bg-slate-800/90 backdrop-blur-sm rounded-2xl shadow-xl transform transition duration-500 hover:scale-105 border border-slate-600">
                    <div class="p-6 sm:p-8 h-full flex flex-col">
                        <div class="flex items-center justify-center w-16 h-16 bg-linear-to-b from-sky-500 via-sky-600 to-blue-900 rounded-full mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-200 mb-4">Start a new Battle</h2>
                        <p class="text-slate-300 mb-6">Take command! Create a new battle and invite fellow commanders to join your fleet.</p>

                        <form action="{{ route('games.store') }}" method="post" class="mt-auto">
                            @csrf
                            <button type="submit"
                                class="w-full bg-linear-to-r from-slate-700 via-slate-600 to-slate-700 text-white rounded-lg px-4 py-3 font-semibold transform transition hover:translate-y-[-2px] hover:from-blue-900 hover:via-sky-600 hover:to-blue-900">
                                Launch New Battle
                            </button>
                        </form>
                    </div>
                </div>
        </div>

            <!-- Auth Section -->
            <div class="mt-12 text-center">
                @auth
                    <div class="inline-flex items-center space-x-2 bg-slate-800/90 backdrop-blur-sm rounded-full px-6 py-3 shadow-lg border border-slate-600">
                        <span class="text-slate-200">Commanding as {{ auth()->user()->name }}</span>
                        <form action="{{ route('logout.destroy') }}" method="post" class="inline">
                            @csrf
                            <button type="submit"
                                class="text-sky-400 hover:text-sky-500 font-semibold">
                                Logout
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login.index') }}"
                        class="inline-flex items-center space-x-2 bg-slate-800/90 backdrop-blur-sm rounded-full px-6 py-3 shadow-lg text-slate-200 hover:shadow-xl transition duration-300 border border-slate-600">
                        <span>Login to Save Battle Records</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endauth
            </div>

            <!-- Fun Footer -->
            <div class="mt-16 text-center text-sky-400">
                <p class="text-sm">🎯 "A good commander never reveals their fleet." - Outwit and outmaneuver in every salvo! 💥</p>
            </div>
        </div>
    </div>
</x-layout>
