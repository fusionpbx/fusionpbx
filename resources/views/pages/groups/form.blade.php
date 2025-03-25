@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($group) ? 'Edit Group' : 'Create Group' }}
            </h3>
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
                                class="form-control rounded-0 @error('group_name') is-invalid @enderror" 
                                id="group_name" 
                                name="group_name" 
                                placeholder="Enter group name"
                                value="{{ old('group_name', $group->group_name ?? '') }}"
                                required
                                style="border-radius: 4px !important; border: 1px solid #ced4da; padding: 10px;"
                            >
                            @error('group_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="group_level" class="form-label">Group Level</label>
                            <select 
                                class="form-control rounded-0 @error('group_level') is-invalid @enderror" 
                                id="group_level" 
                                name="group_level"
                                required
                                style="border-radius: 4px !important; border: 1px solid #ced4da; padding: 10px; height: calc(2.25rem + 12px);"
                            >
                                <option value="">Select Level</option>
                                @foreach(range(10, 90, 10) as $level)
                                    <option value="{{ $level }}" 
                                        {{ old('group_level', $group->group_level ?? '') == $level ? 'selected' : '' }}>
                                        {{ $level }}
                                    </option>
                                @endforeach
                            </select>
                            @error('group_level')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="group_description" class="form-label">Group Description</label>
                            <textarea 
                                class="form-control rounded-0 @error('group_description') is-invalid @enderror" 
                                id="group_description" 
                                name="group_description" 
                                rows="3" 
                                placeholder="Enter group description"
                                style="border-radius: 4px !important; border: 1px solid #ced4da; padding: 10px;"
                            >{{ old('group_description', $group->group_description ?? '') }}</textarea>
                            @error('group_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_uuid" class="form-label">Domain</label>
                            <select 
                                class="form-control rounded-0 @error('domain_uuid') is-invalid @enderror" 
                                id="domain_uuid" 
                                name="domain_uuid"
                                style="border-radius: 4px !important; border: 1px solid #ced4da; padding: 10px; height: calc(2.25rem + 12px);"
                            >
                                <option value="">Select Domain</option>
                                @foreach($domain as $domain)
                                    <option value="{{ $domain->domain_uuid }}" 
                                        {{ old('domain_uuid', $group->domain_uuid ?? '') == $domain->domain_uuid ? 'selected' : '' }}>
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
                            <div class="custom-control custom-switch">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="group_protected" name="group_protected" {{ old('group_protected', $group->group_protected ?? false) ? 'checked' : '' }}">
                                    <label class="form-check-label" for="group_protected" name="group_protected" {{ old('group_protected', $group->group_protected ?? false) ? 'checked' : '' }}">{{ old('group_protected', $group->group_protected ?? false) ? 'Protected' : 'Not Protected' }}</label>
                                  </div>
                            </div>
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