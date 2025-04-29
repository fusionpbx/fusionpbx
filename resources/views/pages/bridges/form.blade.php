@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($bridge) ? 'Edit Bridge' : 'Create Bridge' }}
            </h3>
        </div>

        <form action="{{ isset($bridge) ? route('bridges.update', $bridge->bridge_uuid) : route('bridges.store') }}"
              method="POST">
            @csrf
            @if(isset($bridge))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bridge_name" class="form-label">Bridge Name</label>
                            <input
                                type="text"
                                class="form-control @error('bridge_name') is-invalid @enderror"
                                id="bridge_name"
                                name="bridge_name"
                                placeholder="Enter bridge name"
                                value="{{ old('bridge_name', $bridge->bridge_name ?? '') }}"
                                required
                            >
                            @error('bridge_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bridge_destination" class="form-label">Destination</label>
                            <input
                                type="text"
                                class="form-control @error('bridge_destination') is-invalid @enderror"
                                id="bridge_destination"
                                name="bridge_destination"
                                placeholder="Enter bridge destination"
                                value="{{ old('bridge_destination', $bridge->bridge_destination ?? '') }}"
                                required
                            >
                            @error('bridge_destination')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="bridge_enabled" name="bridge_enabled" value="true" {{ old('bridge_enabled', $bridge->bridge_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="bridge_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('bridge_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="bridge_description" class="form-label">Bridge Description</label>
                            <textarea
                                class="form-control @error('bridge_description') is-invalid @enderror"
                                id="bridge_description"
                                name="bridge_description"
                                rows="3"
                                placeholder="Enter bridge description"
                            >{{ old('bridge_description', $bridge->bridge_description ?? '') }}</textarea>
                            @error('bridge_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($bridge) ? 'Update Bridge' : 'Create Bridge' }}
                </button>
                <a href="{{ route('bridges.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
