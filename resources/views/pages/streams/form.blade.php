@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($stream) ? 'Edit Stream' : 'Create Stream' }}
            </h3>
        </div>

        <form action="{{ isset($stream) ? route('streams.update', $stream->stream_uuid) : route('streams.store') }}"
              method="POST">
            @csrf
            @if(isset($stream))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stream_name" class="form-label">Stream Name</label>
                            <input
                                type="text"
                                class="form-control @error('stream_name') is-invalid @enderror"
                                id="stream_name"
                                name="stream_name"
                                placeholder="Enter stream name"
                                value="{{ old('stream_name', $stream->stream_name ?? '') }}"
                                required
                            >
                            @error('stream_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stream_location" class="form-label">Location</label>
                            <input
                                type="text"
                                class="form-control @error('stream_location') is-invalid @enderror"
                                id="stream_location"
                                name="stream_location"
                                placeholder="Enter stream location"
                                value="{{ old('stream_location', $stream->stream_location ?? '') }}"
                                required
                            >
                            @error('stream_location')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="stream_enabled" name="stream_enabled" value="true" {{ old('stream_enabled', $stream->stream_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="stream_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('stream_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="stream_description" class="form-label">Stream Description</label>
                            <textarea
                                class="form-control @error('stream_description') is-invalid @enderror"
                                id="stream_description"
                                name="stream_description"
                                rows="3"
                                placeholder="Enter stream description"
                            >{{ old('stream_description', $stream->stream_description ?? '') }}</textarea>
                            @error('stream_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($stream) ? 'Update Stream' : 'Create Stream' }}
                </button>
                <a href="{{ route('streams.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
