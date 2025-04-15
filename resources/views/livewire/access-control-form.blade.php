<div>
    <div class="container-fluid">
        <div class="card card-primary mt-3 card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    {{ isset($accessControl) ? 'Edit Access Control' : 'Create Access Control' }}
                </h3>

                @if (isset($accessControl))
                <div class="card-tools">
                    <a href="" class="btn btn-primary btn-sm">
                        <i class="fa fa-file-import" aria-hidden="true"></i>
                        {{ __('Import') }}
                    </a>

                    @can('access_control_add')
                    <a href="{{ route('accesscontrol.copy', $accessControl->access_control_uuid ) }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-clone" aria-hidden="true"></i> {{ __('Copy') }}
                    </a>                       
                    @endcan
                    
                    @can('access_control_delete')
                    <form action="{{ route('accesscontrol.destroy', $accessControlUuid) }}"
                        method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-trash" aria-hidden="true"></i> {{ __('Delete') }}
                        </button>
                    </form>             
                    @endcan
                </div>
                @endif
            </div>

            <form wire:submit.prevent="save">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="accessControlName" class="form-label">{{ __('Access Control Name') }}</label>
                                <input
                                    type="text"
                                    class="form-control @error('accessControlName') is-invalid @enderror"
                                    id="accessControlName"
                                    wire:model.defer="accessControlName"
                                    placeholder="Enter access control name"
                                    required
                                >
                                @error('accessControlName')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="accessControlDefault" class="form-label">{{ __('Default Action') }}</label>
                                <select
                                    class="form-select @error('accessControlDefault') is-invalid @enderror"
                                    id="accessControlDefault"
                                    wire:model.defer="accessControlDefault"
                                    required
                                >
                                    <option value="">{{ __('Select an action') }}</option>
                                    <option value="allow">{{ __('Allow') }}</option>
                                    <option value="deny">{{ __('Deny') }}</option>
                                </select>
                                @error('accessControlDefault')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('Default action for this access control.') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">{{ __('Access Control Nodes') }}</label>
                                
                                <div class="table-responsive">
                                    @can('access_control_node_view')
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Node Type') }}</th>
                                                <th>{{ __('CIDR') }}</th>
                                                <th>{{ __('Description') }}</th>
                                                <th width="100">{{ __('Actions') }}</th>
                                                @if (count($nodes) > 1)
                                                <th width="50" class="text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" 
                                                            wire:click="toggleSelectAll"
                                                            {{ count($selectedNodes) === count($nodes) ? 'checked' : '' }}>
                                                    </div>
                                                </th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($nodes as $index => $node)
                                                <tr>
                                                    <td>
                                                        <select class="form-select @error('nodes.'.$index.'.node_type') is-invalid @enderror" 
                                                            wire:model.defer="nodes.{{ $index }}.node_type">
                                                            <option value="">{{ __('Select') }}</option>
                                                            <option value="allow">{{ __('Allow') }}</option>
                                                            <option value="deny">{{ __('Deny') }}</option>
                                                        </select>
                                                        @error('nodes.'.$index.'.node_type')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control @error('nodes.'.$index.'.node_cidr') is-invalid @enderror" 
                                                            wire:model.defer="nodes.{{ $index }}.node_cidr"
                                                            placeholder="192.168.1.0/24">
                                                        @error('nodes.'.$index.'.node_cidr')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control @error('nodes.'.$index.'.node_description') is-invalid @enderror" 
                                                            wire:model.defer="nodes.{{ $index }}.node_description"
                                                            placeholder="Node description">
                                                        @error('nodes.'.$index.'.node_description')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                            wire:click="removeNode({{ $index }})"
                                                            {{ count($nodes) <= 1 ? 'disabled' : '' }}>
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                    @if (count($nodes) > 1)
                                                    <td class="text-center">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" 
                                                                wire:model="selectedNodes" 
                                                                value="{{ $node['access_control_node_uuid'] }}">
                                                        </div>
                                                    </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>                                       
                                </div>
                                @can('access_control_node_add')
                                <div class="mt-2">
                                    <button type="button" class="btn btn-secondary" wire:click="addNode">
                                        <i class="fa fa-plus"></i> {{ __('Add Node') }}
                                    </button>
                                    @can('access_control_node_delete')

                                    @if (count($selectedNodes) > 0)
                                    <button type="button" class="btn btn-danger" wire:click="deleteSelected">
                                        <i class="fa fa-trash"></i> {{ __('Delete Selected') }}
                                    </button>
                                    @endif
                                        
                                    @endcan
                                </div>                               
                                <small class="form-text text-muted mt-2">
                                    {{ __('Add nodes to define specific access rules.') }}
                                </small>
                                @endcan
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="accessControlDescription" class="form-label">{{ __('Description') }}</label>
                                <textarea
                                    class="form-control @error('accessControlDescription') is-invalid @enderror"
                                    id="accessControlDescription"
                                    wire:model.defer="accessControlDescription"
                                    rows="3"
                                    placeholder="Enter access control description"
                                ></textarea>
                                @error('accessControlDescription')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                        {{ isset($accessControl) ? __('Update') : __('Create') }}
                    </button>
                    <a href="{{ route('accesscontrol.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>