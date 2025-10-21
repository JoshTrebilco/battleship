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

            <!-- Login Card -->
            <div class="max-w-md mx-auto">
                <div class="bg-slate-800/90 backdrop-blur-sm rounded-2xl shadow-xl transform transition duration-500 hover:scale-105 border border-slate-600">
                    <div class="p-8">
                        <div class="flex items-center justify-center w-16 h-16 bg-linear-to-b from-sky-500 via-sky-600 to-blue-900 rounded-full mb-6 mx-auto">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>

                        <form class="space-y-6" action="{{ route('login.store') }}" method="POST">
                            @csrf
                            @if($game_id)
                                <input type="hidden" name="game_id" value="{{ $game_id }}">
                            @endif

                            <div>
                                <label for="name" class="block text-lg font-semibold text-slate-200 mb-2">
                                    Commander Name:
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    autocomplete="name"
                                    value="{{ old('name') }}"
                                    required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-slate-500 bg-slate-700/70 text-slate-200 placeholder-slate-400 focus:border-sky-600 focus:ring-0 focus:outline-none"
                                    placeholder="Enter your call sign..."
                                >
                            </div>

                            <button
                                type="submit"
                                class="w-full bg-linear-to-r from-slate-700 via-slate-600 to-slate-700 text-white rounded-lg px-4 py-3 font-semibold transform transition hover:translate-y-[-2px] hover:from-blue-900 hover:via-sky-600 hover:to-blue-900"
                            >
                                Report for Duty
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Back Link -->
                {{-- <div class="mt-8 text-center">
                    <a href="{{ route('games.index') }}"
                        class="inline-flex items-center space-x-2 text-slate-700 hover:translate-x-[-2px] transition-transform">
                        <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span>Back to Games</span>
                    </a>
                </div> --}}
            </div>
        </div>
    </div>
</x-layout>
