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
                            <label for="dialplan_name" class="form-label">Name</label>
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
                            <label for="dialplan_number" class="form-label">Number</label>
                            <input
                                type="text"
                                class="form-control @error('dialplan_number') is-invalid @enderror"
                                id="dialplan_number"
                                name="dialplan_number"
                                placeholder="Enter dialplan number"
                                value="{{ old('dialplan_number', $dialplan->dialplan_number ?? '') }}"
                            >
                            @error('dialplan_number')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="hostname" class="form-label">Hostname</label>
                            <input
                                type="text"
                                class="form-control @error('hostname') is-invalid @enderror"
                                id="hostname"
                                name="hostname"
                                placeholder="Enter dialplan hostname"
                                value="{{ old('hostname', $dialplan->hostname ?? '') }}"
                            >
                            @error('hostname')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dialplan_context" class="form-label">Context</label>
                            <input
                                type="text"
                                class="form-control @error('dialplan_context') is-invalid @enderror"
                                id="dialplan_context"
                                name="dialplan_context"
                                placeholder="Enter dialplan context"
                                value="{{ old('dialplan_context', $dialplan->dialplan_context ?? '') }}"
                            >
                            @error('dialplan_context')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dialplan_order" class="form-label">Order</label>
                            <input
                                type="number"
                                step="5"
                                min="0"
                                max="1000"
                                class="form-control @error('dialplan_order') is-invalid @enderror"
                                id="dialplan_order"
                                name="dialplan_order"
                                placeholder="Enter dialplan order"
                                value="{{ old('dialplan_order', $dialplan->dialplan_order ?? '') }}"
                            >
                            @error('dialplan_order')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_uuid" class="form-label">Domain</label>
                            <select
                                class="form-select @error('domain_uuid') is-invalid @enderror"
                                id="domain_uuid"
                                name="domain_uuid"
                            >
                                <option value="">Global</option>
                                @foreach($domains as $domain)
                                    <option value="{{ $domain->domain_uuid }}"
                                        {{ old('domain_uuid', (isset($group) ? $group->domain_uuid : Auth::user()->domain_uuid) ??  '') == $domain->domain_uuid ? 'selected' : '' }}>
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
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="dialplan_description" class="form-label">Description</label>
                            <textarea
                                class="form-control @error('dialplan_description') is-invalid @enderror"
                                id="dialplan_description"
                                name="dialplan_description"
                                rows="3"
                                placeholder="Enter domain description"
                            >{{ old('dialplan_description', $dialplan->dialplan_description ?? '') }}</textarea>
                            @error('dialplan_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Continue</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="dialplan_continue" name="dialplan_continue" value="true" {{ old('dialplan_continue', $dialplan->dialplan_continue ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="dialplan_continue">{{ __('Continue') }}</label>
                            </div>
                            @error('dialplan_continue')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Destination</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="dialplan_destination" name="dialplan_destination" value="true" {{ old('dialplan_destination', $dialplan->dialplan_destination ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="dialplan_destination">{{ __('Destination') }}</label>
                            </div>
                            @error('dialplan_destination')
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

            <div class="card card-primary m-3 repeater">
                <div class="card-header">
                    <h3 class="card-title">Detail</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tag</th>
                                <th>Type</th>
                                <th>Data</th>
                                <th>Break</th>
                                <th>Inline</th>
                                <th>Group</th>
                                <th>Order</th>
                                <th>Enabled</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="repeater-container">
                            @if($dialplan->dialplanDetails->isEmpty())
                                @include('pages.dialplans.detail_template')
                            @else
                                @include('pages.dialplans.detail_template')
                                @foreach($dialplan->dialplanDetails as $i => $detail)
                                    @include('pages.dialplans.detail', ['detail' => $detail, 'index' => $i])
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="card-body p-3 text-end">
                    <button class="btn btn-success repeater-add">
                        <i class="fas fa-add"></i>
                    </button>
                </div>
            </div>

            <div class="modal fade" id="dialplan_xml" tabindex="-1" aria-labelledby="dialplan_xml" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Dialplan XML</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>
<pre><code class="language-xml">{{ $dialplan->dialplan_xml ?? '' }}</code></pre>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($dialplan) ? 'Update Dialplan' : 'Create Dialplan' }}
                </button>
                @if(isset($dialplan))
                <button type="button" class="btn btn-secondary px-4 py-2" data-bs-toggle="modal" data-bs-target="#dialplan_xml" style="border-radius: 4px;">View XML</button>
                @endif
                <a href="{{ route('dialplans.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
