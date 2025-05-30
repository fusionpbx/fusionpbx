@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($carrier) ? 'Edit Carrier' : 'Create Carrier' }}
            </h3>
        </div>

        <form action="{{ isset($carrier) ? route('carriers.update', $carrier->carrier_uuid) : route('carriers.store') }}"
              method="POST">
            @csrf
            @if(isset($carrier))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="carrier_name" class="form-label">Carrier Name</label>
                            <input
                                type="text"
                                class="form-control @error('carrier_name') is-invalid @enderror"
                                id="carrier_name"
                                name="carrier_name"
                                placeholder="Enter carrier name"
                                value="{{ old('carrier_name', $carrier->carrier_name ?? '') }}"
                                required
                            >
                            @error('carrier_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

				<div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="carrier_channels" class="form-label">Channels</label>
                            <input
                                type="number"
                                class="form-control @error('carrier_channels') is-invalid @enderror"
                                id="carrier_channels"
                                name="carrier_channels"
                                placeholder="Enter carrier channel"
                                value="{{ old('carrier_channels', $carrier->carrier_channels ?? '') }}"
                                required
                            >
                            @error('carrier_channels')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="priority" class="form-label">Priority</label>
                            <input
                                type="number"
                                class="form-control @error('priority') is-invalid @enderror"
                                id="priority"
                                name="priority"
                                placeholder="Enter carrier priority"
                                value="{{ old('priority', $carrier->priority ?? '') }}"
                                required
                            >
                            @error('priority')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

				<div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cancellation_ratio" class="form-label">Cancellation ratio</label>
                            <input
                                type="text"
                                class="form-control @error('cancellation_ratio') is-invalid @enderror"
                                id="cancellation_ratio"
                                name="cancellation_ratio"
                                placeholder="Enter carrier cancellation ratio"
                                value="{{ old('cancellation_ratio', $carrier->cancellation_ratio ?? '') }}"
                                required
                            >
                            @error('cancellation_ratio')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Short call friendly</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="short_call_friendly" name="short_call_friendly" value="true" {{ old('short_call_friendly', $carrier->short_call_friendly ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="short_call_friendly">{{ __('Enabled') }}</label>
                            </div>
                            @error('short_call_friendly')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Fax enabled</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="fax_enabled" name="fax_enabled" value="true" {{ old('fax_enabled', $carrier->fax_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="fax_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('fax_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

				<div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lcr_tags" class="form-label">Tags</label>
                            <input
                                type="text"
                                class="form-control @error('lcr_tags') is-invalid @enderror"
                                id="lcr_tags"
                                name="lcr_tags"
                                placeholder="Enter lcr tags"
                                value="{{ old('lcr_tags', $carrier->lcr_tags ?? '') }}"
                                required
                            >
                            @error('lcr_tags')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="true" {{ old('enabled', $carrier->enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($carrier) ? 'Update Carrier' : 'Create Carrier' }}
                </button>
                <a href="{{ route('carriers.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
