@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($dialplan) ? 'Edit Dialplan' : 'Create Dialplan' }}
            </h3>
        </div>

        <form action="{{ isset($dialplan) ? route('dialplans.update', $dialplan->dialplan_uuid) : route('dialplans.store') }}"
              method="POST">
            @csrf
            @if(isset($dialplan))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dialplan_name" class="form-label">Dialplan Name</label>
                            <input
                                type="text"
                                class="form-control @error('dialplan_name') is-invalid @enderror"
                                id="dialplan_name"
                                name="dialplan_name"
                                placeholder="Enter dialplan name"
                                value="{{ old('dialplan_name', $dialplan->dialplan_name ?? '') }}"
                                required
                            >
                            @error('dialplan_name')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="dialplan_enabled" name="dialplan_enabled" value="true" {{ old('dialplan_enabled', $dialplan->dialplan_enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="dialplan_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('dialplan_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($dialplan) ? 'Update Dialplan' : 'Create Dialplan' }}
                </button>
                <a href="{{ route('dialplans.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
