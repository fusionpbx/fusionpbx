<div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">WebRTC Dialer</h2>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-semibold
                {{ $isRegistered ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                {{ $isRegistered ? 'Registered' : 'Not Registered' }}
            </span>
        </div>
    </div>

    <!-- Display -->
    <div class="mb-6">
        <input 
            type="text" 
            wire:model="dialNumber" 
            readonly
            class="w-full text-3xl font-mono text-center p-4 bg-gray-50 dark:bg-gray-900 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            placeholder="Enter number"
        />
    </div>

    <!-- Call Status -->
    @if($isInCall && $currentCall)
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 rounded-lg border-2 border-green-500">
            <div class="text-center">
                <p class="text-sm text-green-800 dark:text-green-200 mb-1">Connected to</p>
                <p class="text-xl font-bold text-green-900 dark:text-green-100">{{ $currentCall['number'] }}</p>
                <p class="text-sm text-green-700 dark:text-green-300 mt-2" id="call-timer">00:00:00</p>
            </div>
        </div>
    @endif

    <!-- Dialpad -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'] as $digit)
            <button 
                wire:click="addDigit('{{ $digit }}')"
                class="p-4 text-2xl font-bold bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 
                       text-gray-900 dark:text-white rounded-lg transition transform active:scale-95"
                {{ $isInCall ? 'disabled' : '' }}
            >
                {{ $digit }}
                @if($digit == '2') <span class="block text-xs text-gray-500">ABC</span> @endif
                @if($digit == '3') <span class="block text-xs text-gray-500">DEF</span> @endif
                @if($digit == '4') <span class="block text-xs text-gray-500">GHI</span> @endif
                @if($digit == '5') <span class="block text-xs text-gray-500">JKL</span> @endif
                @if($digit == '6') <span class="block text-xs text-gray-500">MNO</span> @endif
                @if($digit == '7') <span class="block text-xs text-gray-500">PQRS</span> @endif
                @if($digit == '8') <span class="block text-xs text-gray-500">TUV</span> @endif
                @if($digit == '9') <span class="block text-xs text-gray-500">WXYZ</span> @endif
            </button>
        @endforeach
    </div>

    <!-- Call Controls -->
    @if($isInCall)
        <div class="grid grid-cols-3 gap-3 mb-4">
            <button 
                wire:click="toggleMute"
                class="p-3 rounded-lg transition {{ $isMuted ? 'bg-red-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' }}"
            >
                <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($isMuted)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    @endif
                </svg>
                <span class="text-xs mt-1 block">{{ $isMuted ? 'Unmute' : 'Mute' }}</span>
            </button>

            <button 
                wire:click="toggleHold"
                class="p-3 rounded-lg transition {{ $isOnHold ? 'bg-yellow-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' }}"
            >
                <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs mt-1 block">{{ $isOnHold ? 'Resume' : 'Hold' }}</span>
            </button>

            <button 
                wire:click="hangup"
                class="p-3 bg-red-500 hover:bg-red-600 text-white rounded-lg transition"
            >
                <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="text-xs mt-1 block">Hangup</span>
            </button>
        </div>
    @else
        <!-- Action Buttons -->
        <div class="grid grid-cols-3 gap-3">
            <button 
                wire:click="dial"
                class="col-span-2 p-4 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition transform active:scale-95"
            >
                <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                Call
            </button>
            <button 
                wire:click="backspace"
                class="p-4 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-900 dark:text-white rounded-lg transition"
            >
                <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Recent Calls -->
    @if(count($recentCalls) > 0)
        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Recent Calls</h3>
            <div class="space-y-2">
                @foreach($recentCalls as $recent)
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-900 rounded">
                        <div class="flex-1">
                            <p class="font-mono text-sm text-gray-900 dark:text-white">{{ $recent['number'] }}</p>
                            <p class="text-xs text-gray-500">{{ $recent['time']->diffForHumans() }}</p>
                        </div>
                        <button 
                            wire:click="redial('{{ $recent['number'] }}')"
                            class="text-green-600 hover:text-green-800 dark:text-green-400"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Call Timer Script -->
    @if($isInCall && $currentCall)
        <script>
            let startTime = new Date('{{ $currentCall["start_time"] }}').getTime();
            setInterval(() => {
                let now = new Date().getTime();
                let diff = Math.floor((now - startTime) / 1000);
                let hours = Math.floor(diff / 3600);
                let minutes = Math.floor((diff % 3600) / 60);
                let seconds = diff % 60;
                document.getElementById('call-timer').textContent = 
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            }, 1000);
        </script>
    @endif
</div>
