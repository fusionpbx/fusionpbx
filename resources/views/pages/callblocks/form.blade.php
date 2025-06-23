@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($callblock) ? 'Edit CallBlock' : 'Create CallBlock' }}
            </h3>
        </div>

        <form action="{{ isset($callblock) ? route('callblocks.update', $callblock->call_block_uuid) : route('callblocks.store') }}"
              method="POST">
            @csrf
            @if(isset($callblock))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_direction" class="form-label">Direction</label>
                            <select class="form-select" name="call_block_direction">
                                <option value="inbound" @selected(old('call_block_direction', $callblock->call_block_direction ?? null) == "inbound")>Inbound</option>
                                <option value="outbound" @selected(old('call_block_direction', $callblock->call_block_direction ?? null) == "outbound")>Outbound</option>
                            </select>
                            @error('call_block_direction')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                @can('call_block_all')
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="extension_uuid" class="form-label">Extension</label>
                            <select class="form-select" name="extension_uuid">
                                <option value="">{{ __("All") }}</option>
                                @foreach($extensions as $extension)
                                <option value="{{ $extension->extension_uuid }}" @selected(old('extension_uuid', $callblock->extension_uuid ?? null) == $extension->extension_uuid)>{{ $extension->extension }} {{ $extension->description }}</option>
                                @endforeach
                            </select>
                            @error('extension_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                @endcan

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_name" class="form-label">Name</label>
                            <input
                                type="text"
                                class="form-control @error('call_block_name') is-invalid @enderror"
                                id="call_block_name"
                                name="call_block_name"
                                placeholder="Enter callblock name"
                                value="{{ old('call_block_name', $callblock->call_block_name ?? '') }}"
                                required
                            >
                            @error('call_block_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="call_block_country_code" class="form-label">Country Code</label>
                            <input
                                type="text"
                                class="form-control @error('call_block_country_code') is-invalid @enderror"
                                id="call_block_country_code"
                                name="call_block_country_code"
                                placeholder="Enter country code"
                                value="{{ old('call_block_country_code', $callblock->call_block_country_code ?? '') }}"
                                required
                            >
                            @error('call_block_country_code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="call_block_number" class="form-label">Caller ID Number</label>
                            <input
                                type="text"
                                class="form-control @error('call_block_number') is-invalid @enderror"
                                id="call_block_number"
                                name="call_block_number"
                                placeholder="Enter Caller ID Number"
                                value="{{ old('call_block_number', $callblock->call_block_number ?? '') }}"
                                required
                            >
                            @error('call_block_number')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_action" class="form-label">Action</label>

                            <x-switch-call-block-action name="call_block_action" selected="$callblock->call_block_app" />

                            @error('call_block_action')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="call_block_enabled" name="call_block_enabled" value="true" {{ old('call_block_enabled', $callblock->call_block_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="call_block_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('call_block_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_description" class="form-label">CallBlock Description</label>
                            <textarea
                                class="form-control @error('call_block_description') is-invalid @enderror"
                                id="call_block_description"
                                name="call_block_description"
                                rows="3"
                                placeholder="Enter callblock description"
                            >{{ old('call_block_description', $callblock->call_block_description ?? '') }}</textarea>
                            @error('call_block_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($callblock) ? 'Update CallBlock' : 'Create CallBlock' }}
                </button>
                <a href="{{ route('callblocks.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
