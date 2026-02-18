<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <!-- Statistics -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100">Total Calls</h3>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ $statistics['total'] ?? 0 }}</p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Ringing</h3>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-300">{{ $statistics['ringing'] ?? 0 }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-green-900 dark:text-green-100">Answered</h3>
            <p class="text-2xl font-bold text-green-600 dark:text-green-300">{{ $statistics['answered'] ?? 0 }}</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-purple-900 dark:text-purple-100">Inbound</h3>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-300">{{ $statistics['inbound'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Controls -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Active Calls</h2>
        <div class="flex gap-2">
            <button 
                wire:click="toggleAutoRefresh" 
                class="px-4 py-2 rounded-lg {{ $autoRefresh ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600' }} text-white transition"
            >
                {{ $autoRefresh ? 'Auto-Refresh ON' : 'Auto-Refresh OFF' }}
            </button>
            <button 
                wire:click="loadCalls" 
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition"
            >
                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Calls Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">UUID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Caller ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Destination</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Direction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">State</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($calls as $call)
                    <tr wire:key="call-{{ $call['uuid'] }}" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-gray-100">
                            {{ substr($call['uuid'], 0, 8) }}...
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <div>{{ $call['caller_id_name'] ?? 'Unknown' }}</div>
                            <div class="text-gray-500">{{ $call['caller_id_number'] ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $call['destination_number'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $call['direction'] === 'inbound' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ ucfirst($call['direction']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($call['state'] === 'ringing') bg-yellow-100 text-yellow-800
                                @elseif($call['state'] === 'answered') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($call['state']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ isset($call['duration']) ? gmdate('H:i:s', $call['duration']) : '--:--:--' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button 
                                wire:click="hangup('{{ $call['uuid'] }}')" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                title="Hangup"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <p class="mt-2 text-sm font-medium">No active calls</p>
                            <p class="mt-1 text-sm">Calls will appear here in real-time</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($autoRefresh)
        <script>
            setInterval(() => {
                @this.call('loadCalls');
            }, {{ $refreshInterval * 1000 }});
        </script>
    @endif
</div>
