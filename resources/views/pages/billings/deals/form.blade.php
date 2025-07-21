@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($billingDeal) ? 'Edit Billing Deal' : 'Create Billing Deal' }}
            </h3>
        </div>

        <form action="{{ isset($billingDeal) ? route('billings.deals.update', $billingDeal->billing_deal_uuid) : route('billings.deals.store') }}"
              method="POST">
            @csrf
            @if(isset($billingDeal))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="label" class="form-label">Label</label>
                            <input
                                type="text"
                                class="form-control @error('label') is-invalid @enderror"
                                id="label"
                                name="label"
                                value="{{ old('label', $billingDeal->label ?? '') }}"
                                required
                            >
                            @error('label')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="direction" class="form-label">Direction</label>
                            <select class="form-select" id="direction" name="direction">
                                <option value="outbound" @selected(($billingDeal->direction ?? '') == "outbound")>Outgoing call</option>
                                <option value="inbound" @selected(($billingDeal->direction ?? '') == "inbound")>Incoming call</option>
                                <option value="local" @selected(($billingDeal->direction ?? '') == "local")>Extension-to-Extension call</option>
                            </select>
                            @error('direction')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="digits" class="form-label">Prefix</label>
                            <input
                                type="text"
                                class="form-control @error('digits') is-invalid @enderror"
                                id="digits"
                                name="digits"
                                value="{{ old('digits', $billingDeal->digits ?? '') }}"
                                required
                            >
                            @error('digits')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="minutes" class="form-label">Minutes</label>
                            <input
                                type="number"
                                class="form-control @error('minutes') is-invalid @enderror"
                                id="minutes"
                                name="minutes"
                                min="1"
                                step="1"
                                value="{{ old('minutes', $billingDeal->minutes ?? '') }}"
                                required
                            >
                            @error('minutes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="rate" class="form-label">New rate</label>
                            <input
                                type="number"
                                class="form-control @error('rate') is-invalid @enderror"
                                id="rate"
                                name="rate"
                                min="0"
                                step="0.0001"
                                value="{{ old('rate', $billingDeal->rate ?? '') }}"
                                required
                            >
                            @error('rate')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Currency</label>
                        {{ currency_select($lcr->currency ?? '') }}
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="billing_deal_notes" class="form-label">Notes</label>
                            <textarea
                                class="form-control @error('billing_deal_notes') is-invalid @enderror"
                                id="billing_deal_notes"
                                name="billing_deal_notes"
                                rows="3"
                            >{{ old('billing_deal_notes', $billingDeal->billing_deal_notes ?? '') }}</textarea>
                            @error('billing_deal_notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($billingDeal) ? 'Update Billing Deal' : 'Create Billing Deal' }}
                </button>
                <a href="{{ route('billings.deals.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
