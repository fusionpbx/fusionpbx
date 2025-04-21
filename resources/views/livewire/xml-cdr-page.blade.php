<div>
    {{-- Filter Form --}}
    <div class="row g-2">
        <div class="col-md-5">
            <div class="form-group mb-3">
                <label>Destination</label>
                <input type="number" wire:model.defer="filters.caller_destination" class="form-control">
            </div>
        </div>
    </div>

    <div class="row g-2 mb-5 justify-content-end">
        <div class="col-auto">
            <button wire:click="applyFilters" class="btn btn-primary">Apply</button>
        </div>
        <div class="col-auto">
            <button wire:click="resetFilters" class="btn btn-secondary">Reset</button>
        </div>
    </div>

    {{-- Table --}}
    <livewire:xml-cdr-table :filters="$filters" :key="json_encode($filters)"/>
</div>
