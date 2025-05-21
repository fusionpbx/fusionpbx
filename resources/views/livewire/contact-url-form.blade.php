<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="">Urls <i class="fa fa-link" aria-hidden="true"></i></h6>
            @can('contact_url_add')
            <button type="button" class="btn btn-sm btn-primary" wire:click="addUrl"> <i class="fa fa-plus" aria-hidden="true"></i></button>
            @endcan
        </div>
        <div class="card-body">
            @foreach ($urls as $index => $url)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-6 mb-2">
                                <label for="url_label{{ $index }}" class="form-label">Label</label>
                                <select wire:model="urls.{{ $index }}.url_label" class="form-select">
                                    <option value="work">Work</option>
                                    <option value="personal">Personal</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="url_address{{ $index }}" class="form-label">Adress</label>
                                <input type="text" wire:model="urls.{{ $index }}.url_address" class="form-control"
                                    placeholder="http://...">
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="urls.{{ $index }}.url_primary"
                                    id="url_primary_{{ $index }}">
                                <label class="form-check-label" for="url_primary_{{ $index }}">
                                    Primary </label>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-12 mb-3">
                                <label for="url_description{{ $index }}"
                                    class="form-label">Description</label>
                                <textarea id="url_description{{ $index }}" wire:model="urls.{{ $index }}.url_description"
                                    class="form-control @error('url_description') is-invalid @enderror" rows="3"></textarea>
                                @error('url_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                        @can('contact_url_delete')
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-danger"
                                wire:click="removeUrl({{ $index }})"><i class="bi bi-trash"></i> </button>
                        </div>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
