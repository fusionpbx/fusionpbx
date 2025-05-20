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

			<input type="hidden" name="app_uuid" value="{{ old('app_uuid', $dialplan->app_uuid ?? $app_uuid) }}">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dialplan_name" class="form-label">Inbound Route Name</label>
                            <input
                                type="text"
                                class="form-control @error('dialplan_name') is-invalid @enderror"
                                id="dialplan_name"
                                name="dialplan_name"
                                placeholder="Enter inbound name"
                                value="{{ old('dialplan_name', $inbound->dialplan_name ?? '') }}"
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
                            <label for="destination_uuid" class="form-label">Destination number</label>
                            <select
                                class="form-select @error('destination_uuid') is-invalid @enderror"
                                id="destination_uuid"
                                name="destination_uuid"
                                required
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
                            <label for="action_1" class="form-label">Action</label>
                            <x-switch-destinations name="action_1" bridgeType="dialplan" callCenterType="dialplan" conferenceCenterType="dialplan" extensionType="dialplan" ivrMenuType="dialplan" timeConditionType="dialplan" toneType="dialplan" voiceMailType="dialplan" />
                            @error('action_1')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="limit" class="form-label">Limit</label>
                            <input
                                type="text"
                                class="form-control @error('limit') is-invalid @enderror"
                                id="limit"
                                name="limit"
                                value="{{ old('limit', $inbound->limit ?? '') }}"
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
                            <label for="caller_id_outbound_prefix" class="form-label">Caller ID number prefix</label>
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
                            @error('dialplan_order')
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

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dialplan_description" class="form-label">Inbound Route Description</label>
                            <textarea
                                class="form-control @error('dialplan_description') is-invalid @enderror"
                                id="dialplan_description"
                                name="dialplan_description"
                                rows="3"
                                placeholder="Enter inbound description"
                            >{{ old('dialplan_description', $inbound->dialplan_description ?? '') }}</textarea>
                            @error('dialplan_description')
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
