<?php

namespace App\Livewire;

use Livewire\Component;

class XmlCdrPage extends Component
{
    public $filters = [];

    public function mount()
    {
        $this->filters = $this->initFilters();
    }

    private function initFilters()
    {
        return [
            "caller_destination" => null,
        ];
    }

    public function resetFilters()
    {
        $this->filters = $this->defaultFilters();
    }

    public function applyFilters() {}

    public function render()
    {
        return view("livewire.xml-cdr-page");
    }
}
