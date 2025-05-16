<div>
    <div class="card mb-4">
        <div class="card-header">
            <h6>Groups</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach ($availableGroups as $uuid => $name)
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="{{ $uuid }}"
                                wire:model="selectedGroups" id="group_{{ $uuid }}">
                            <label class="form-check-label" for="group_{{ $uuid }}">
                                {{ $name }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
