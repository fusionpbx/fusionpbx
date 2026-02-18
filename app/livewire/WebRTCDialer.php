<?php

namespace FusionPBX\Livewire;

use Livewire\Component;
use FusionPBX\Models\Extension;
use FusionPBX\Services\ESL\ESLManager;

class WebRTCDialer extends Component
{
    public $dialNumber = '';
    public $extension;
    public $isRegistered = false;
    public $isInCall = false;
    public $currentCall = null;
    public $callDuration = 0;
    public $isMuted = false;
    public $isOnHold = false;
    public $recentCalls = [];

    public function mount($extensionUuid = null)
    {
        if ($extensionUuid) {
            $this->extension = Extension::find($extensionUuid);
        }
    }

    public function dial()
    {
        if (empty($this->dialNumber)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Please enter a number to dial'
            ]);
            return;
        }

        // Originate call via ESL
        $eslManager = app(ESLManager::class);
        
        try {
            $eslManager->originate(
                "user/{$this->extension->extension}",
                $this->dialNumber,
                $this->extension->context ?? 'default',
                $this->extension->effective_caller_id_name ?? '',
                $this->extension->effective_caller_id_number ?? ''
            );

            $this->isInCall = true;
            $this->currentCall = [
                'number' => $this->dialNumber,
                'start_time' => now(),
            ];

            $this->dispatch('call-started', number: $this->dialNumber);
            
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to initiate call: ' . $e->getMessage()
            ]);
        }
    }

    public function hangup()
    {
        if ($this->currentCall) {
            $eslManager = app(ESLManager::class);
            // Hangup would require tracking the channel UUID
            // This is a simplified version
            
            $this->isInCall = false;
            $this->addToRecentCalls($this->dialNumber);
            $this->currentCall = null;
            $this->callDuration = 0;
            
            $this->dispatch('call-ended');
        }
    }

    public function toggleMute()
    {
        $this->isMuted = !$this->isMuted;
        $this->dispatch('mute-toggled', muted: $this->isMuted);
    }

    public function toggleHold()
    {
        $this->isOnHold = !$this->isOnHold;
        $this->dispatch('hold-toggled', onHold: $this->isOnHold);
    }

    public function addDigit($digit)
    {
        $this->dialNumber .= $digit;
    }

    public function backspace()
    {
        $this->dialNumber = substr($this->dialNumber, 0, -1);
    }

    public function clear()
    {
        $this->dialNumber = '';
    }

    protected function addToRecentCalls($number)
    {
        array_unshift($this->recentCalls, [
            'number' => $number,
            'time' => now(),
            'duration' => $this->callDuration,
        ]);

        // Keep only last 10 calls
        $this->recentCalls = array_slice($this->recentCalls, 0, 10);
    }

    public function redial($number)
    {
        $this->dialNumber = $number;
        $this->dial();
    }

    public function render()
    {
        return view('livewire.webrtc-dialer');
    }
}
