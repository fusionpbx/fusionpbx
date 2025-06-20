<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary  mt-3 card-outline">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ $isEditing ? 'Edit Device Profile' : 'Create Device Profile' }}
                            </h3>
                            <div class="card-tools">
                                @if ($isEditing)
                                    <button type="button" class="btn btn-primary btn-sm" wire:click="copyProfile">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="delete"
                                        onclick="return confirm('Are you sure you want to delete this device profile?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                @endif
                            </div>
                        </div>
    
                        <form wire:submit.prevent="save">
                            <div class="card-body">
                                <!-- Basic Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="device_profile_name">Profile Name *</label>
                                            <input type="text"
                                                class="form-control @error('device_profile_name') is-invalid @enderror"
                                                wire:model="device_profile_name" placeholder="Enter profile name">
                                            @error('device_profile_name')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="device_profile_enabled">Enabled</label>
                                            <select
                                                class="form-control @error('device_profile_enabled') is-invalid @enderror"
                                                wire:model="device_profile_enabled">
                                                <option value="true">True</option>
                                                <option value="false">False</option>
                                            </select>
                                            @error('device_profile_enabled')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
    
                                @if (auth()->user()->hasPermission('device_profile_domain'))
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="domain_uuid">Domain</label>
                                                <select class="form-control @error('domain_uuid') is-invalid @enderror"
                                                    wire:model="domain_uuid">
                                                    <option value="">Select Domain</option>
                                                    @foreach ($availableDomains as $domain)
                                                        <option value="{{ $domain['domain_uuid'] }}">
                                                            {{ $domain['domain_name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('domain_uuid')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif
    
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="device_profile_description">Description</label>
                                            <textarea class="form-control @error('device_profile_description') is-invalid @enderror"
                                                wire:model="device_profile_description" rows="3" placeholder="Enter profile description"></textarea>
                                            @error('device_profile_description')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
    
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title">Profile Keys</h5>
                                                <div class="card-tools">
                                                    <button type="button" class="btn btn-primary btn-sm"
                                                        wire:click="addProfileKey">
                                                        <i class="fas fa-plus"></i> Add Key
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                        wire:click="addEmptyKeysRows(5)">
                                                        <i class="fas fa-plus-circle"></i> Add 5 Rows
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Category</th>
                                                                <th>ID</th>
                                                                <th>Vendor</th>
                                                                <th>Type</th>
                                                                @if ($showKeySubtype)
                                                                    <th>Subtype</th>
                                                                @endif
                                                                <th>Line</th>
                                                                <th>Value</th>
                                                                @can('device_key_extension')
                                                                <th>Extension</th>
                                                                @endcan
                                                                <th>Protected</th>
                                                                <th>Label</th>
                                                                @can('device_key_icon')
                                                                <th>Icon</th>
                                                                @endcan
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($profileKeys as $index => $key)
                                                                <tr>
                                                                    <td>
                                                                        <select class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_category">
                                                                            @foreach ($this->categoryOptions[$index] ?? [] as $value => $label)
                                                                                <option value="{{ $value }}">
                                                                                    {{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" min="0" max="255"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_id">
                                                                    </td>
                                                                    <td>
                                                                        <select class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_vendor">
                                                                            <option value="">Select Vendor</option>
                                                                            @foreach ($vendorFunctions as $vendor)
                                                                                <option
                                                                                    value="{{ $vendor['vendor_name'] }}">
                                                                                    {{ $vendor['vendor_name'] }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <select class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_type">
                                                                            <option value="">Select Function</option>
                                                                            @foreach ($this->groupedVendorFunctions as $vendor => $functions)
                                                                                <optgroup label="{{ ucfirst($vendor) }}">
                                                                                    @foreach ($functions as $function)
                                                                                        <option
                                                                                            value="{{ $function['value'] }}">
                                                                                            {{ $function['label'] }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </optgroup>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    @if ($showKeySubtype)
                                                                        <td>
                                                                            <input type="text"
                                                                                class="form-control form-control-sm"
                                                                                wire:model="profileKeys.{{ $index }}.profile_key_subtype">
                                                                        </td>
                                                                    @endif
                                                                    <td>
                                                                        <input type="number" min="0" max="12"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_line">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_value">
                                                                    </td>
                                                                    @can('device_key_extension')
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_extension">
                                                                    </td>
                                                                    @endcan
                                                                    @can('device_key_protected')
                                                                    <td>
                                                                        <select class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_protected">
                                                                            <option value="false">False</option>
                                                                            <option value="true">True</option>
                                                                        </select>
                                                                    </td>
                                                                    @endcan
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_label">
                                                                    </td>
                                                                    @can('device_key_icon')
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileKeys.{{ $index }}.profile_key_icon">
                                                                    </td>
                                                                    @endcan
                                                                    @can('device_key_delete')
                                                                    <td>
                                                                        <button type="button"
                                                                            class="btn btn-danger btn-sm"
                                                                            wire:click="removeProfileKey({{ $index }})">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                    @endcan
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
    
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="card-title">Profile Settings</h5>
                                                <div class="card-tools">
                                                    <button type="button" class="btn btn-primary btn-sm"
                                                        wire:click="addEmptySettingsRow">
                                                        <i class="fas fa-plus"></i> Add Setting
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Value</th>
                                                                <th>Enabled</th>
                                                                <th>Description</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($profileSettings as $index => $setting)
                                                                <tr>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileSettings.{{ $index }}.profile_setting_name">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileSettings.{{ $index }}.profile_setting_value">
                                                                    </td>
                                                                    <td>
                                                                        <div class="form-check form-switch">
                                                                            <input type="checkbox" role="switch" id="profile_setting_enabled"
                                                                                wire:model="profileSettings.{{ $index }}.profile_setting_enabled"
                                                                                value="true" class="form-check-input"                                                    
                                                                                {{ $profileSettings[$index]['profile_setting_enabled'] == 'true' ? 'checked' : '' }}>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            wire:model="profileSettings.{{ $index }}.profile_setting_description">
                                                                    </td>
                                                                    <td>
                                                                        <button type="button"
                                                                            class="btn btn-danger btn-sm"
                                                                            wire:click="removeProfileSetting({{ $index }})">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                            {{ $isEditing ? 'Update Profile' : 'Create Profile' }}
                                        </button>
                                        <a href="{{ route('devices_profiles.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
