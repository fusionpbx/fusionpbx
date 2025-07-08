@extends('layouts.app')

@section('content')
<div class="container-fluid ">
    <div class="card card-primary mt-3 card-outline">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($lcr) ? 'Edit LCR' : 'Create LCR' }}
            </h3>
        </div>

        <form action="{{ isset($lcr) ? route('lcr.update', $lcr->lcr_uuid) : route('lcr.store') }}"
                method="POST">
            @csrf
            @if(isset($lcr))
                @method('PUT')
            @endif

            <input type="hidden" name="carrier_uuid" value="{{ old('carrier_uuid', $lcr->carrier_uuid ?? $carrier_uuid) }}">

            <div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Origination Digits</label>
                            <input type="text" class="form-control" name="origination_digits" value="{{ old('origination_digits', $lcr->origination_digits ?? '') }}">
                        </div>
                        @error('origination_digits')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Digits</label>
                            <input type="text" class="form-control" name="digits" value="{{ old('digits', $lcr->digits ?? '') }}">
                        </div>
                        @error('digits')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Call Direction</label>
                            <select name="lcr_direction" class="form-select">
                                <option value="inbound" {{ old('lcr_direction', $lcr->lcr_direction ?? '') == 'inbound' ? 'selected' : '' }}>Inbound</option>
                                <option value="outbound" {{ old('lcr_direction', $lcr->lcr_direction ?? '') == 'outbound' ? 'selected' : '' }}>Outbound</option>
                                <option value="local" {{ old('lcr_direction', $lcr->lcr_direction ?? '') == 'local' ? 'selected' : '' }}>Local</option>
                            </select>
                        </div>
                        @error('lcr_direction')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Rate</label>
                            <input type="number" step="0.000001" class="form-control" name="rate" value="{{ old('rate', $lcr->rate ?? '') }}">
                        </div>
                        @error('rate')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">Currency</label>
                            {{ currency_select($lcr->currency ?? '') }}
                        </div>
                        @error('currency')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Connection Rate</label>
                            <input type="number" step="0.000001" class="form-control" name="connect_rate" value="{{ old('connect_rate', $lcr->connect_rate ?? '') }}">
                        </div>
                        @error('connect_rate')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Connect Increment</label>
                            <input type="number" step="1" class="form-control" name="connect_increment" value="{{ old('connect_increment', $lcr->connect_increment ?? '') }}">
                        </div>
                        @error('connect_increment')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Talking Increment</label>
                            <input type="number" step="1" class="form-control" name="talk_increment" value="{{ old('talk_increment', $lcr->talk_increment ?? '') }}">
                        </div>
                        @error('talk_increment')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Intrastate Rate</label>
                            <input type="number" step="0.000001" class="form-control" name="intrastate_rate" value="{{ old('intrastate_rate', $lcr->intrastate_rate ?? '') }}">
                        </div>
                        @error('intrastate_rate')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Intralata Rate</label>
                            <input type="number" step="0.000001" class="form-control" name="intralata_rate" value="{{ old('intralata_rate', $lcr->intralata_rate ?? '') }}">
                        </div>
                        @error('intralata_rate')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            @if($carrier_uuid)
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Lead Strip</label>
                            <input type="number" class="form-control" name="lead_strip" value="{{ old('lead_strip', $lcr->lead_strip ?? '') }}">
                        </div>
                        @error('lead_strip')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Trail Strip</label>
                            <input type="number" class="form-control" name="trail_strip" value="{{ old('trail_strip', $lcr->trail_strip ?? '') }}">
                        </div>
                        @error('trail_strip')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Prefix</label>
                            <input type="text" class="form-control" name="prefix" value="{{ old('prefix', $lcr->prefix ?? '') }}">
                        </div>
                        @error('prefix')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control" name="suffix" value="{{ old('suffix', $lcr->suffix ?? '') }}">
                        </div>
                        @error('suffix')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">LCR Profile</label>
                            <input type="text" class="form-control" name="lcr_profile" value="{{ old('lcr_profile', $lcr->lcr_profile ?? '') }}">
                        </div>
                        @error('lcr_profile')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Starting Date</label>
                            <input type="datetime-local" class="form-control" name="date_start" value="{{ old('date_start', $lcr->date_start ?? '') }}">
                        </div>
                        @error('date_start')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Ending Date</label>
                            <input type="datetime-local" class="form-control" name="date_end" value="{{ old('date_end', $lcr->date_end ?? '') }}">
                        </div>
                        @error('date_end')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Quality</label>
                            <input type="number" class="form-control" name="quality" value="{{ old('quality', $lcr->quality ?? '') }}">
                        </div>
                        @error('quality')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Reliability</label>
                            <input type="number" class="form-control" name="reliability" value="{{ old('reliability', $lcr->reliability ?? '') }}">
                        </div>
                        @error('reliability')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">CID</label>
                            <input type="text" class="form-control" name="cid" value="{{ old('cid', $lcr->cid ?? '') }}">
                        </div>
                        @error('cid')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Enabled</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="true" {{ old('enabled', $lcr->enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea
                                class="form-control @error('call_block_description') is-invalid @enderror"
                                name="description"
                                rows="3"
                                placeholder="Enter description"
                            >{{ old('description', $description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($lcr) ? 'Update LCR' : 'Create LCR' }}
                </button>
                <a href="{{ route('lcr.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
