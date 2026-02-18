<?php

namespace FusionPBX\Livewire;

use Livewire\Component;
use FusionPBX\Services\CDR\LiveCDRService;
use Livewire\Attributes\On;

class LiveCDRTable extends Component
{
    public $calls = [];
    public $statistics = [];
    public $autoRefresh = true;
    public $refreshInterval = 2; // seconds

    public function mount()
    {
        $this->loadCalls();
    }

    #[On('call.created')]
    #[On('call.answered')]
    #[On('call.hangup')]
    public function loadCalls()
    {
        $service = app(LiveCDRService::class);
        $this->calls = $service->getActiveCalls();
        $this->statistics = $service->getStatistics();
    }

    public function hangup($uuid)
    {
        $eslManager = app(\FusionPBX\Services\ESL\ESLManager::class);
        $eslManager->hangup($uuid);
        
        $this->dispatch('call-hangup', uuid: $uuid);
    }

    public function transfer($uuid, $destination)
    {
        $eslManager = app(\FusionPBX\Services\ESL\ESLManager::class);
        $eslManager->transfer($uuid, $destination);
        
        $this->dispatch('call-transferred', uuid: $uuid);
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function render()
    {
        return view('livewire.live-cdr-table');
    }
}
