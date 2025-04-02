@extends('layouts.app')

@section('content')
<div class="container-fluid ">
    <div class="card card-primary mt-3 card-outline">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($group) ? 'Edit Group' : 'Create Group' }}
            </h3>

            @if (isset($group))
            <div class="card-tools">
                <a  href="{{ route('permissions.index', ['group_uuid' => old('group_uuid', $group->group_uuid ?? '')]) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-key" aria-hidden="true"></i>
                    {{ __('Permissions') }}
                </a>

                <a href="{{ route('usergroup.index', [$group->group_uuid]) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-users mr-1"></i> {{__('Members')}}
                </a>

                <a href="{{route('groups.copy', $group->group_uuid)}}" class="btn btn-primary btn-sm">
                    <i class="fa fa-clone" aria-hidden="true"></i> {{ __('Copy') }}
                </a>

                <form action="{{ route('groups.destroy', $group->group_uuid) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to delete this group?')">
                        <i class="fa fa-trash" aria-hidden="true"></i> {{ __('Delete') }}
                    </button>
                </form>
            </div>
            @endif
        </div>

        <form action="{{ isset($group) ? route('groups.update', $group->group_uuid) : route('groups.store') }}"
              method="POST">
            @csrf
            @if(isset($group))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="group_name" class="form-label">Group Name</label>
                            <input
                                type="text"
                                class="form-control @error('group_name') is-invalid @enderror"
                                id="group_name"
                                name="group_name"
                                placeholder="Enter group name"
                                value="{{ old('group_name', $group->group_name ?? '') }}"
                                required
                            >
                            @error('group_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_uuid" class="form-label">Domain</label>
                            <select
                                class="form-select @error('domain_uuid') is-invalid @enderror"
                                id="domain_uuid"
                                name="domain_uuid"
                            >
                                <option value="">Global</option>
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->domain_uuid }}"
                                        {{ old('domain_uuid', (isset($group) ? $group->domain_uuid : Auth::user()->domain_uuid) ??  '') == $domain->domain_uuid ? 'selected' : '' }}>
                                        {{ $domain->domain_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('domain_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="group_level" class="form-label" class="form-control @error('group_level') is-invalid @enderror">Group Level</label>
                            <input
                                type="number"
                                min="0"
                                placeholder="50"
                                step="1"
                                list="group_levels"
                                id="group_level"
                                name="group_level"
                                required
                            >
                            <datalist id="group_levels">
                                <option value="10"></option>
                                <option value="20"></option>
                                <option value="30"></option>
                                <option value="40"></option>
                                <option value="50"></option>
                                <option value="60"></option>
                                <option value="70"></option>
                                <option value="80"></option>
                                <option value="90"></option>
                            </datalist>
                            <!--
                            <select
                                class="form-select @error('group_level') is-invalid @enderror"
                                id="group_level"
                                name="group_level"
                                required
                            >
                                <option value="">Select Level</option>
                                @foreach(range(10, 90, 10) as $level)
                                    <option value="{{ $level }}"
                                        {{ old('group_level', $group->group_level ?? '') == $level ? 'selected' : '' }}>
                                        {{ $level }}
                                    </option>
                                @endforeach
                            </select>
                            -->
                            @error('group_level')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Protected</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="group_protected" name="group_protected" {{ old('group_protected', $group->group_protected ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="group_protected">{{ __('Protected') }}</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="group_description" class="form-label">Group Description</label>
                            <textarea
                                class="form-control @error('group_description') is-invalid @enderror"
                                id="group_description"
                                name="group_description"
                                rows="3"
                                placeholder="Enter group description"
                            >{{ old('group_description', $group->group_description ?? '') }}</textarea>
                            @error('group_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($group) ? 'Update Group' : 'Create Group' }}
                </button>
                <a href="{{ route('groups.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
