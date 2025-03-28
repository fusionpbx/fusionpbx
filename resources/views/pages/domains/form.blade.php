@extends('layouts.app')

@section('content')
<div class="container-fluid ">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($domain) ? 'Edit Domain' : 'Create Domain' }}
            </h3>
        </div>

        <form action="{{ isset($domain) ? route('domains.update', $domain->domain_uuid) : route('domains.store') }}"
              method="POST">
            @csrf
            @if(isset($domain))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_name" class="form-label">Domain Name</label>
                            <input
                                type="text"
                                class="form-control @error('domain_name') is-invalid @enderror"
                                id="domain_name"
                                name="domain_name"
                                placeholder="Enter domain name"
                                value="{{ old('domain_name', $domain->domain_name ?? '') }}"
                                required
                            >
                            @error('domain_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_parent_uuid" class="form-label">Domain Parent</label>
                            <select
                                class="form-select @error('domain_parent_uuid') is-invalid @enderror"
                                id="domain_parent_uuid"
                                name="domain_parent_uuid"
                            >
                                <option value="">Select Parent</option>
                                @foreach($domains as $d)
                                    <option value="{{ $d->domain_uuid }}"
                                        {{ old('domain_parent_uuid', $domain->domain_parent_uuid ?? '') == $d->domain_uuid ? 'selected' : '' }}>
                                        {{ $d->domain_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('domain_parent_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Enabled</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="domain_enabled" name="domain_enabled" value="true" {{ old('domain_enabled', $domain->domain_enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="domain_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('domain_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="domain_description" class="form-label">Domain Description</label>
                            <textarea
                                class="form-control @error('domain_description') is-invalid @enderror"
                                id="domain_description"
                                name="domain_description"
                                rows="3"
                                placeholder="Enter domain description"
                            >{{ old('domain_description', $domain->domain_description ?? '') }}</textarea>
                            @error('domain_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($domain) ? 'Update Domain' : 'Create Domain' }}
                </button>
                <a href="{{ route('domains.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
