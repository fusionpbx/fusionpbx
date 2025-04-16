@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mt-3 card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-layer-group mr-2"></i>
                    {{ __('Group Permissions') }} ({{ $group->group_name ?? '' }})
                </h3>

                <div class="card-tools">
                    <div class="d-flex gap-2 " role="group" aria-label="Group actions">
                        <a href="{{ route('groups.index') }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-fast-backward" aria-hidden="true"></i>
                            {{ __('Back') }}
                        </a>

                        <a href="" class="btn btn-primary btn-sm">
                            <i class="fa fa-refresh" aria-hidden="true"></i>
                            {{ __('Reload') }}
                        </a>

                        <a href="" class="btn btn-primary btn-sm">
                            <i class="fas fa-users mr-1"></i> {{ __('Users') }}
                        </a>
                        
                        <form action="{{ route('permissions.index', ['groupUuid' => $group->group_uuid]) }}" method="GET" class="d-flex gap-2">
                            <input type="hidden" name="group_uuid" value="{{ $groupUuid }}">

                            <select name="filter" class="form-select mr-2 " onchange="this.form.submit()">
                                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
                                <option value="assigned" {{ $filter === 'assigned' ? 'selected' : '' }}>{{ __('Assigned') }}</option>
                                <option value="not_assigned" {{ $filter === 'not_assigned' ? 'selected' : '' }}>{{ __('Not Assigned') }}</option>
                                <option value="protected" {{ $filter === 'protected' ? 'selected' : '' }}>{{ __('Protected') }}</option>
                            </select>

                            <input type="text" name="search" class="form-control mr-2"
                                placeholder="{{ __('Search...') }}" value="{{ $search ?? '' }}">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Search') }}</button>
                            @if ($search)
                                <a href="{{ route('permissions.index', ['groupUuid' => $group->group_uuid]) }}"
                                    class="btn btn-secondary ml-2">{{ __('Clear') }}</a>
                            @endif
                        </form>

                            @can('group_permission_edit')
                            <button type="submit" form="permissions-form" class="btn btn-primary btn-sm">
                                <i class="fa fa-bolt" aria-hidden="true"></i> {{ __('Save') }}
                            </button>
                            @endcan
                        
                    </div>
                </div>
            </div>

            <form action="{{ route('permissions.update', ['groupUuid' => $group->group_uuid]) }}" method="POST" id="permissions-form">
                @csrf
                @method('PUT')            
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    @foreach ($permissionsByApp as $appName)
                                        <th>{{ $appName }}</th>
                                        <th>{{__('Protected')}}</th>
                                        @foreach ($permissions->where('application_name', $appName) as $permission)                      
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="permissions[]"
                                                value="{{ $permission->permission_name }}"
                                                id="permission-{{ $permission->permission_name }}" 
                                                {{ $permission->groupPermissionByGroup  ? 'checked' : '' }}
                                                {{ !auth()->user()->hasPermission('group_permission_edit') ? 'disabled' : '' }}
                                                >
                                                
                                            <label class="form-check-label"
                                                for="permission-{{ $permission->permission_name }}">
                                                {{ $permission->permission_name }}
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="permissions_protected[]"
                                            value="{{ $permission->permission_name }}" 
                                            id="permission-protected-{{ $permission->permission_name }}"
                                            {{ $permission->groupPermissionByGroup?->permission_protected === 'true' 
                                                ? 'checked' 
                                                : ''
                                            }} 
                                            {{ !auth()->user()->hasPermission('group_permission_edit') ? 'disabled' : '' }}
                                            >
                                        </div> 
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>    
            </form>
        </div>
    </div>
@endsection
