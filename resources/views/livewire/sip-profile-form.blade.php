<div>
    <div class="container-fluid">
        <div class="card card-primary mt-3 card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        {{ isset($sipProfile) ? 'Edit SIP Profile' : 'Create SIP Profile' }}
                    </h3>
                    <div>
                        @if (isset($sipProfile))
                            @can('sip_profile_delete')
                                <form action="{{ route('sipprofiles.destroy', $sipProfile->sip_profile_uuid) }}"
                                    method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fa fa-trash" aria-hidden="true"></i> {{ __('Delete') }}
                                    </button>
                                </form>
                            @endcan
                            
                            @can('sip_profile_add')
                            <a href="{{ route('sipprofiles.copy', $sipProfile->sip_profile_uuid) }}"
                                class="btn btn-primary btn-sm">
                                <i class="fa fa-clone" aria-hidden="true"></i> {{ __('Copy') }}
                            </a>
                                
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sip_profile_name" class="form-label">Profile Name <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('sip_profile_name') is-invalid @enderror"
                                    id="sip_profile_name" wire:model="sip_profile_name" placeholder="Enter profile name"
                                    required>
                                @error('sip_profile_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sip_profile_hostname" class="form-label">Hostname</label>
                                <input type="text"
                                    class="form-control @error('sip_profile_hostname') is-invalid @enderror"
                                    id="sip_profile_hostname" wire:model="sip_profile_hostname"
                                    placeholder="Enter hostname">
                                @error('sip_profile_hostname')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label d-block">Enabled</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="sip_profile_enabled" value="false">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="sip_profile_enabled" wire:model="sip_profile_enabled" value="true"
                                        {{ $sip_profile_enabled == 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label"
                                        for="sip_profile_enabled">{{ __('Enabled') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">{{ __('Domains Configuration') }}</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Domain Name') }}</th>
                                            <th class="text-center">{{ __('Alias') }}</th>
                                            <th class="text-center">{{ __('Parse') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($domains as $index => $domain)
                                            <tr>
                                                <td>
                                                    <input type="text"
                                                        class="form-control @error('domains.' . $index . '.sip_profile_domain_name') is-invalid @enderror"
                                                        wire:model="domains.{{ $index }}.sip_profile_domain_name"
                                                        placeholder="Enter domain name">
                                                    
                                                    @error('domains.' . $index . '.sip_profile_domain_name')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-center">
                                                    <select
                                                        class="form-select @error('domains.' . $index . '.sip_profile_domain_alias') is-invalid @enderror"
                                                        wire:model="domains.{{ $index }}.sip_profile_domain_alias">
                                                        <option value="true">True</option>
                                                        <option value="false">False</option>
                                                    </select>
                                                    @error('domains.' . $index . '.sip_profile_domain_alias')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-center">
                                                    <select
                                                        class="form-select @error('domains.' . $index . '.sip_profile_domain_parse') is-invalid @enderror"
                                                        wire:model="domains.{{ $index }}.sip_profile_domain_parse">
                                                        <option value="true">True</option>
                                                        <option value="false">False</option>
                                                    </select>
                                                    @error('domains.' . $index . '.sip_profile_domain_parse')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-center">
                                                    @if (count($domains) > 1)
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            wire:click="removeDomain({{ $index }})">
                                                            <i class="fas fa-times"></i> Remove
                                                        </button>
                                                    @endif
                                                    @if ($index === count($domains) - 1)
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            wire:click="addDomain">
                                                            <i class="fas fa-plus"></i> Add
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">Profile Settings</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Setting Name</th>
                                            <th>Value</th>
                                            <th class="text-center">Enabled</th>
                                            <th>Description</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($settings as $index => $setting)
                                            <tr>
                                                <td>
                                                    <input type="text"
                                                        class="form-control @error('settings.' . $index . '.sip_profile_setting_name') is-invalid @enderror"
                                                        wire:model="settings.{{ $index }}.sip_profile_setting_name"
                                                        placeholder="Enter setting name">
                                                    @error('settings.' . $index . '.sip_profile_setting_name')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="text"
                                                        class="form-control @error('settings.' . $index . '.sip_profile_setting_value') is-invalid @enderror"
                                                        wire:model="settings.{{ $index }}.sip_profile_setting_value"
                                                        placeholder="Enter setting value">
                                                    @error('settings.' . $index . '.sip_profile_setting_value')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-center">
                                                    <select
                                                        class="form-select @error('settings.' . $index . '.sip_profile_setting_enabled') is-invalid @enderror"
                                                        wire:model="settings.{{ $index }}.sip_profile_setting_enabled">
                                                        <option value="true">True</option>
                                                        <option value="false">False</option>
                                                    </select>
                                                    @error('settings.' . $index . '.sip_profile_setting_enabled')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="text"
                                                        class="form-control @error('settings.' . $index . '.sip_profile_setting_description') is-invalid @enderror"
                                                        wire:model="settings.{{ $index }}.sip_profile_setting_description"
                                                        placeholder="Enter description">
                                                    @error('settings.' . $index . '.sip_profile_setting_description')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-center">
                                                    @if (count($settings) > 1)
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            wire:click="removeSetting({{ $index }})">
                                                            <i class="fas fa-times"></i> Remove
                                                        </button>
                                                    @endif
                                                    @if ($index === count($settings) - 1)
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            wire:click="addSetting">
                                                            <i class="fas fa-plus"></i> Add
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="sip_profile_description" class="form-label">Description <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control @error('sip_profile_description') is-invalid @enderror" id="sip_profile_description"
                                    wire:model="sip_profile_description" rows="3" placeholder="Enter profile description" required></textarea>
                                @error('sip_profile_description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                            <i class="fas fa-save"></i>
                            {{ isset($sipProfile) ? 'Update SIP Profile' : 'Create SIP Profile' }}
                        </button>
                        <a href="{{ route('sipprofiles.index') }}" class="btn btn-secondary ml-2 px-4 py-2"
                            style="border-radius: 4px;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- @if (isset($sipProfile))
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true"
         wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{ __('Are you sure you want to delete this SIP Profile? This action cannot be undone.')}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form wire:submit.prevent="delete">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif --}}

    @push('scripts')
        <script>
            document.addEventListener('livewire:load', function() {
                Livewire.on('showDeleteModal', function() {
                    $('#deleteModal').modal('show');
                });
            });
        </script>
    @endpush
</div>
