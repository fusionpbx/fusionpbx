<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary  mt-3 card-outline">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-mobile-alt me-2"></i>
                                {{ $isEdit ? 'Edit Vendor' : 'New Vendor' }}
                            </h5>
                        </div>
    
                        <form wire:submit.prevent="save">
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-primary border-bottom pb-2 mb-3">
                                            <i class="fas fa-info-circle me-2"></i>Vendor Information
                                        </h6>
                                    </div>
    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="vendorName" class="form-label">
                                                Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="vendorName" wire:model="vendorName"
                                                class="form-control @error('vendorName') is-invalid @enderror"
                                                placeholder="Enter vendor name">
                                            @error('vendorName')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <div class="form-check form-switch">
                                                <input type="checkbox" id="vendorEnabled" wire:model="vendorEnabled"
                                                    class="form-check-input">
                                                <label for="vendorEnabled" class="form-check-label">
                                                    {{ $vendorEnabled ? 'Enabled' : 'Disabled' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
    
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="vendorDescription" class="form-label">Description</label>
                                            <textarea id="vendorDescription" wire:model="vendorDescription"
                                                class="form-control @error('vendorDescription') is-invalid @enderror" rows="3"
                                                placeholder="Optional vendor description"></textarea>
                                            @error('vendorDescription')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
    
                                <div class="row">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary border-bottom pb-2 mb-0">
                                                <i class="fas fa-cogs me-2"></i>Vendor Functions
                                            </h6>
                                            <button type="button" wire:click="addFunction" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus me-1"></i>Add Function
                                            </button>
                                        </div>
    
                                        @if (count($functions) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Type</th>
                                                            <th>Subtype</th>
                                                            <th>Value</th>
                                                            <th>Enabled</th>
                                                            <th>Groups</th>
                                                            <th width="120">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($functions as $index => $function)
                                                            <tr>
                                                                <td>
                                                                    <span
                                                                        class="badge bg-primary">{{ $function['type'] }}</span>
                                                                </td>
                                                                <td>{{ $function['subtype'] }}</td>
                                                                <td>
                                                                    <code>{{ Str::limit($function['value'], 30) }}</code>
                                                                </td>
                                                                <td>
                                                                    @if ($function['enabled'])
                                                                        <span class="badge bg-success">True</span>
                                                                    @else
                                                                        <span
                                                                            class="badge bg-secondary">False</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if (isset($function['selected_groups']) && count($function['selected_groups']) > 0)
                                                                        <span
                                                                            class="badge bg-info">{{ count($function['selected_groups']) }}
                                                                            groups</span>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm" role="group">
                                                                        <button type="button"
                                                                            wire:click="editFunction({{ $index }})"
                                                                            class="btn btn-outline-primary btn-sm"
                                                                            title="Edit">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button type="button"
                                                                            wire:click="removeFunction({{ $index }})"
                                                                            class="btn btn-outline-danger btn-sm"
                                                                            title="Delete"
                                                                            onclick="return confirm('Are you sure you want to delete this function?')">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-info d-flex align-items-center">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No functions added. Click "Add Function" to get started.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
    
                            <div class="card-footer d-flex justify-content-end gap-2">
                                <a href="{{ route('devices_vendors.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    {{ $isEdit ? 'Update' : 'Save' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>


        @if ($showFunctionForm)
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-cog me-2"></i>
                                {{ $editingFunctionIndex !== null ? 'Edit Function' : 'New Function' }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="cancelFunctionForm"></button>
                        </div>

                        <form wire:submit.prevent="saveFunction">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tempType" class="form-label">
                                                Type <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="tempType" wire:model="tempFunction.type"
                                                class="form-control @error('tempFunction.type') is-invalid @enderror"
                                                placeholder="Enter type">
                                            @error('tempFunction.type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tempSubtype" class="form-label">
                                                Subtype
                                            </label>
                                            <input type="text" id="tempSubtype" wire:model="tempFunction.subtype"
                                                class="form-control @error('tempFunction.subtype') is-invalid @enderror"
                                                placeholder="Enter subtype">
                                            @error('tempFunction.subtype')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="tempValue" class="form-label">
                                                Value <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="tempValue" wire:model="tempFunction.value"
                                                class="form-control @error('tempFunction.value') is-invalid @enderror"
                                                placeholder="Enter function value">
                                            @error('tempFunction.value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="tempDescription" class="form-label">Description</label>
                                            <textarea id="tempDescription" wire:model="tempFunction.description" class="form-control" rows="3"
                                                placeholder="Optional function description"></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <div class="form-check form-switch">
                                                <input type="checkbox" id="tempEnabled"
                                                    wire:model="tempFunction.enabled" class="form-check-input">
                                                <label for="tempEnabled" class="form-check-label">
                                                    {{ $tempFunction['enabled'] ? 'Enabled' : 'Disabled' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tempGroups" class="form-label">Groups</label>
                                            <select id="tempGroups" wire:model="tempFunction.groups"
                                                class="form-select" multiple>
                                                @foreach ($availableGroups as $group)
                                                    <option value="{{ $group['group_uuid'] }}">
                                                        {{ $group['group_name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">
                                                Hold Ctrl (or Cmd) to select multiple groups
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" wire:click="cancelFunctionForm">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    {{ $editingFunctionIndex !== null ? 'Update' : 'Add' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

 
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:load', function() {
                Livewire.on('functionFormClosed', () => {
                    document.body.classList.remove('modal-open');
                });
            });
        </script>
    @endpush
</div>