<?php

namespace FusionPBX\Services\CDR;

use FusionPBX\Models\XmlCdr;
use FusionPBX\Services\ESL\ESLManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Live CDR Service
 * Tracks active calls in real-time using ESL events
 */
class LiveCDRService
{
    protected $eslManager;
    protected $activeCalls = [];
    
    public function __construct(ESLManager $eslManager = null)
    {
        $this->eslManager = $eslManager ?? new ESLManager(
            config('freeswitch.esl_host', '127.0.0.1'),
            config('freeswitch.esl_port', 8021),
            config('freeswitch.esl_password', 'ClueCon')
        );
    }

    /**
     * Start monitoring calls
     */
    public function start(): void
    {
        // Register ESL event handlers
        $this->eslManager->on('CHANNEL_CREATE', [$this, 'handleChannelCreate']);
        $this->eslManager->on('CHANNEL_ANSWER', [$this, 'handleChannelAnswer']);
        $this->eslManager->on('CHANNEL_HANGUP', [$this, 'handleChannelHangup']);
        $this->eslManager->on('CHANNEL_HANGUP_COMPLETE', [$this, 'handleChannelHangupComplete']);
        
        // Start ESL connection
        $this->eslManager->connect();
    }

    /**
     * Handle channel create event
     */
    public function handleChannelCreate(array $event): void
    {
        $uuid = $event['Unique-ID'] ?? $event['Channel-Call-UUID'] ?? null;
        
        if (!$uuid) {
            return;
        }

        $callData = [
            'uuid' => $uuid,
            'caller_id_name' => $event['Caller-Caller-ID-Name'] ?? '',
            'caller_id_number' => $event['Caller-Caller-ID-Number'] ?? '',
            'destination_number' => $event['Caller-Destination-Number'] ?? '',
            'context' => $event['Caller-Context'] ?? '',
            'direction' => $event['Call-Direction'] ?? 'inbound',
            'start_stamp' => date('Y-m-d H:i:s'),
            'state' => 'ringing',
            'answered' => false,
        ];

        $this->activeCalls[$uuid] = $callData;
        
        // Cache for quick access
        Cache::put("live_call:$uuid", $callData, 3600);
        
        // Broadcast event
        Event::dispatch('call.created', [$callData]);
    }

    /**
     * Handle channel answer event
     */
    public function handleChannelAnswer(array $event): void
    {
        $uuid = $event['Unique-ID'] ?? $event['Channel-Call-UUID'] ?? null;
        
        if (!$uuid || !isset($this->activeCalls[$uuid])) {
            return;
        }

        $this->activeCalls[$uuid]['state'] = 'answered';
        $this->activeCalls[$uuid]['answered'] = true;
        $this->activeCalls[$uuid]['answer_stamp'] = date('Y-m-d H:i:s');

        // Update cache
        Cache::put("live_call:$uuid", $this->activeCalls[$uuid], 3600);
        
        // Broadcast event
        Event::dispatch('call.answered', [$this->activeCalls[$uuid]]);
    }

    /**
     * Handle channel hangup event
     */
    public function handleChannelHangup(array $event): void
    {
        $uuid = $event['Unique-ID'] ?? $event['Channel-Call-UUID'] ?? null;
        
        if (!$uuid || !isset($this->activeCalls[$uuid])) {
            return;
        }

        $this->activeCalls[$uuid]['state'] = 'hangup';
        $this->activeCalls[$uuid]['end_stamp'] = date('Y-m-d H:i:s');
        $this->activeCalls[$uuid]['hangup_cause'] = $event['Hangup-Cause'] ?? 'NORMAL_CLEARING';

        // Calculate duration
        if (isset($this->activeCalls[$uuid]['answer_stamp'])) {
            $start = strtotime($this->activeCalls[$uuid]['answer_stamp']);
            $end = strtotime($this->activeCalls[$uuid]['end_stamp']);
            $this->activeCalls[$uuid]['duration'] = $end - $start;
        }

        // Update cache
        Cache::put("live_call:$uuid", $this->activeCalls[$uuid], 3600);
        
        // Broadcast event
        Event::dispatch('call.hangup', [$this->activeCalls[$uuid]]);
    }

    /**
     * Handle channel hangup complete event
     */
    public function handleChannelHangupComplete(array $event): void
    {
        $uuid = $event['Unique-ID'] ?? $event['Channel-Call-UUID'] ?? null;
        
        if (!$uuid) {
            return;
        }

        // Remove from active calls
        unset($this->activeCalls[$uuid]);
        
        // Remove from cache after 60 seconds
        Cache::put("live_call:$uuid", null, 60);
        
        // Broadcast event
        Event::dispatch('call.completed', [$uuid]);
    }

    /**
     * Get all active calls
     */
    public function getActiveCalls(): array
    {
        return array_values($this->activeCalls);
    }

    /**
     * Get call by UUID
     */
    public function getCall(string $uuid): ?array
    {
        return $this->activeCalls[$uuid] ?? Cache::get("live_call:$uuid");
    }

    /**
     * Get active call count
     */
    public function getActiveCallCount(): int
    {
        return count($this->activeCalls);
    }

    /**
     * Get calls by domain
     */
    public function getCallsByDomain(string $domainUuid): array
    {
        return array_filter($this->activeCalls, function ($call) use ($domainUuid) {
            return ($call['domain_uuid'] ?? '') === $domainUuid;
        });
    }

    /**
     * Get call statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => count($this->activeCalls),
            'ringing' => 0,
            'answered' => 0,
            'inbound' => 0,
            'outbound' => 0,
        ];

        foreach ($this->activeCalls as $call) {
            if ($call['state'] === 'ringing') {
                $stats['ringing']++;
            } elseif ($call['state'] === 'answered') {
                $stats['answered']++;
            }

            if ($call['direction'] === 'inbound') {
                $stats['inbound']++;
            } else {
                $stats['outbound']++;
            }
        }

        return $stats;
    }

    /**
     * Stop monitoring
     */
    public function stop(): void
    {
        $this->eslManager->disconnect();
    }
}
