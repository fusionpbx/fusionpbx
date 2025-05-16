@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($inbound) ? 'Edit Inbound Route' : 'Create Inbound Route' }}
            </h3>
        </div>

        <form action="{{ isset($inbound) ? route('dialplans.inbound.update', $inbound->inbound_uuid) : route('dialplans.inbound.store') }}"
              method="POST">
            @csrf
            @if(isset($inbound))
                @method('PUT')
            @endif

			<input type="text" name="app_uuid" value="{{ old('app_uuid', $dialplan->app_uuid ?? $app_uuid) }}">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="inbound_name" class="form-label">Inbound Route Name</label>
                            <input
                                type="text"
                                class="form-control @error('inbound_name') is-invalid @enderror"
                                id="inbound_name"
                                name="inbound_name"
                                placeholder="Enter inbound name"
                                value="{{ old('inbound_name', $inbound->inbound_name ?? '') }}"
                                required
                            >
                            @error('inbound_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="destination_uuid" class="form-label">Destination number</label>
                            <select
                                class="form-select @error('destination_uuid') is-invalid @enderror"
                                id="destination_uuid"
                                name="destination_uuid"
                            >
                                <option value=""></option>
                                @foreach($destinations as $destination)
                                    <option value="{{ $destination->destination_uuid }}"
                                        {{ old('destination_uuid', (isset($inbound) ? $inbound->destination_uuid : Auth::user()->destination_uuid) ??  '') == $destination->destination_uuid ? 'selected' : '' }}>
                                        {{ $destination->destination_number }} {{ $destination->destination_description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="inbound_name" class="form-label">Limit</label>
                            <input
                                type="text"
                                class="form-control @error('inbound_name') is-invalid @enderror"
                                id="limit"
                                name="limit"
                                value="{{ old('limit', $inbound->inbound_name ?? '') }}"
                                required
                            >
                            @error('limit')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="inbound_name" class="form-label">Caller ID number prefix</label>
                            <input
                                type="text"
                                class="form-control @error('caller_id_outbound_prefix') is-invalid @enderror"
                                name="caller_id_outbound_prefix"
                                value="{{ old('caller_id_outbound_prefix', $inbound->caller_id_outbound_prefix ?? '') }}"
                                required
                            >
                            @error('caller_id_outbound_prefix')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Order</label>
                            <select class="form-select" name="dialplan_order">
                                @for ($i = 100; $i <= 990; $i += 10)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>

                            @error('inbound_enabled')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="inbound_enabled" name="inbound_enabled" value="true" {{ old('inbound_enabled', $inbound->inbound_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="inbound_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('inbound_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="inbound_description" class="form-label">Inbound Route Description</label>
                            <textarea
                                class="form-control @error('inbound_description') is-invalid @enderror"
                                id="inbound_description"
                                name="inbound_description"
                                rows="3"
                                placeholder="Enter inbound description"
                            >{{ old('inbound_description', $inbound->inbound_description ?? '') }}</textarea>
                            @error('inbound_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($inbound) ? 'Update Inbound Route' : 'Create Inbound Route' }}
                </button>
                <a href="{{ route('dialplans.index', ['app_uuid' => $app_uuid]) }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
