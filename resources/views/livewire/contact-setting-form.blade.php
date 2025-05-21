<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between ">
            <h6>Settings</h6>
            <button type="button" class="btn btn-sm btn-primary" wire:click="addSetting"> <i class="fa fa-plus" aria-hidden="true"></i></button>
        </div>
        <div class="card-body">
            @foreach ($settings as $index => $setting)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="settings.{{ $index }}.contact_setting_category">Category</label>
                                <input type="text" wire:model="settings.{{ $index }}.contact_setting_category" 
                                    class="form-control" placeholder="Category">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="settings.{{ $index }}.contact_setting_subcategory">Subcategory</label>
                                <input type="text" wire:model="settings.{{ $index }}.contact_setting_subcategory" 
                                    class="form-control" placeholder="Subcategory">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="settings.{{ $index }}.contact_setting_name">Name *</label>
                                <input type="text" wire:model="settings.{{ $index }}.contact_setting_name" 
                                    class="form-control" placeholder="Setting Name" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="settings.{{ $index }}.contact_setting_value">Value</label>
                                <input type="text" wire:model="settings.{{ $index }}.contact_setting_value" 
                                    class="form-control" placeholder="Setting Value">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="settings.{{ $index }}.contact_setting_order">Order</label>
                                <select wire:model="settings.{{ $index }}.contact_setting_order" class="form-select">
                                    <option value="">Select Order</option>
                                    @for ($i = 0; $i <= 999; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="settings.{{ $index }}.contact_setting_enabled">Enabled</label>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model="settings.{{ $index }}.contact_setting_enabled"
                                        id="contact_setting_enabled_{{ $index }}">
                                    <label class="form-check-label" for="contact_setting_enabled_{{ $index }}">
                                        Enabled</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="settings.{{ $index }}.contact_setting_description" class="form-label">Description</label>
                                <textarea id="settings.{{ $index }}.contact_setting_description" 
                                    wire:model="settings.{{ $index }}.contact_setting_description"
                                    class="form-control @error('settings.'.$index.'.contact_setting_description') is-invalid @enderror" 
                                    rows="3"></textarea>
                                @error('settings.'.$index.'.contact_setting_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12">
                                <button type="button" class="btn btn-sm btn-danger"
                                    wire:click="removeSetting({{ $index }})"><i class="bi bi-trash"></i> </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            
        </div>
    </div>
</div>