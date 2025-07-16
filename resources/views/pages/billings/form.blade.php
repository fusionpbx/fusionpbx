@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($billing) ? 'Edit Billing' : 'Create Billing' }}
            </h3>
        </div>

        <form action="{{ isset($billing) ? route('billings.update', $billing->billing_uuid) : route('billings.store') }}"
              method="POST">
            @csrf
            @if(isset($billing))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="parent_billing_uuid" class="form-label">Parent profile</label>
                            <select
                                class="form-select @error('parent_billing_uuid') is-invalid @enderror"
                                id="parent_billing_uuid"
                                name="parent_billing_uuid"
                            >
                                <option value="">(none)</option>
                                @foreach($billings as $b)
                                    <option value="{{ $b->billing_uuid }}" @selected(old('parent_billing_uuid', $billing->parent_billing_uuid ?? null) == $b->billing_uuid )>
                                        {{ $b->contact_organization }} {{ $b->contact_name_family }} {{ $b->contact_name_given }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_billing_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_uuid_from" class="form-label">From</label>
                            <x-switch-contacts name="contact_uuid_from" selected="{{ $billing->contact_uuid_from ?? null }}" />
                            @error('contact_uuid_from')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_uuid_to" class="form-label">To</label>
                            <x-switch-contacts name="contact_uuid_to" selected="{{ $billing->contact_uuid_to ?? null }}" />
                            @error('contact_uuid_to')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type" class="form-label">Bill shall be done by</label>
                            <select class="form-select" name="type" id="type">
                                <option value="domain" @selected(($billing->type ?? null) == "domain")>{{ __('tenant domain') }}</option>
                                <option value="authcode" @selected(($billing->type ?? null) == "authcode")>{{ __('authcode assigned to each extension') }}</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type_value" class="form-label">Account code</label>
                            <select class="form-select @if(($billing->type ?? 'domain') == 'authcode') d-none @endif" id="type_value_select">
                                @foreach($domains as $domain)
                                <option value="{{ $domain->domain_uuid }}+{{ $domain->domain_name }}" @selected(($billing->type_value ?? null) == $domain->domain_uuid . '+' . $domain->domain_name)>{{ $domain->domain_name }}</option>
                                @endforeach
                            </select>
                            <input class="form-control @if(($billing->type ?? 'domain') == 'domain') d-none @endif" type="text" id="type_value" name="type_value" value="{{ old('type_value', $billing->type_value ?? '') }}">
                            @error('billing_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="billing_cycle" class="form-label">Billing cycle (Enter fixed day. Maximum day is 28)</label>
                            <input
                                type="number"
                                class="form-control @error('billing_cycle') is-invalid @enderror"
                                id="billing_cycle"
                                name="billing_cycle"
                                min="1"
                                max="28"
                                step="1"
                                placeholder="15"
                                value="{{ old('billing_cycle', $billing->billing_cycle ?? '') }}"
                                required
                            >
                            @error('billing_cycle')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="credit_type" class="form-label">Billing type</label>
                            <select class="form-select" name="credit_type">
                                <option value="prepaid" @selected($billing->credit_type ?? null == "prepaid")>{{ __('prepaid') }}</option>
                                <option value="postpaid" @selected($billing->credit_type ?? null == "postpaid")>{{ __('postpaid') }}</option>
                            </select>
                            @error('credit_type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="credit" class="form-label">Post paid credit</label>
                            <input
                                type="number"
                                class="form-control @error('credit') is-invalid @enderror"
                                id="credit"
                                name="credit"
                                min="-999999"
                                step="0.01"
                                value="{{ old('credit', $billing->credit ?? '') }}"
                                required
                            >
                            @error('credit')
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
                            <label for="force_postpaid_full_payment" class="form-label">Force full balance payment</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="force_postpaid_full_payment" name="force_postpaid_full_payment" value="true" {{ old('force_postpaid_full_payment', $billing->force_postpaid_full_payment ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="force_postpaid_full_payment">{{ __('Enabled') }}</label>
                            </div>
                            @error('force_postpaid_full_payment')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pay_days" class="form-label">Delay days</label>
                            <input
                                type="number"
                                class="form-control @error('credit') is-invalid @enderror"
                                id="pay_days"
                                name="pay_days"
                                min="0"
                                step="1"
                                value="{{ old('pay_days', $billing->pay_days ?? '') }}"
                                required
                            >
                            @error('pay_days')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="balance" class="form-label">Balance</label>
                            <input
                                type="number"
                                class="form-control @error('balance') is-invalid @enderror"
                                id="balance"
                                name="balance"
                                value="{{ old('balance', $billing->balance ?? '') }}"
                                required
                            >
                            @error('balance')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="auto_topup_charge" class="form-label">Auto-topup balance</label>
                        <div class="d-flex align-items-center gap-2">
                            <input
                                type="number"
                                class="form-control @error('auto_topup_charge') is-invalid @enderror"
                                id="auto_topup_charge"
                                name="auto_topup_charge"
                                min="0"
                                step="1"
                                value="{{ old('auto_topup_charge', $billing->auto_topup_charge ?? '') }}"
                            >
                            @error('auto_topup_charge')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <span>@</span>
                            <input
                                type="number"
                                class="form-control @error('auto_topup_minimum_balance') is-invalid @enderror"
                                id="auto_topup_minimum_balance"
                                name="auto_topup_minimum_balance"
                                min="0"
                                step="1"
                                value="{{ old('auto_topup_minimum_balance', $billing->auto_topup_minimum_balance ?? '') }}"
                            >
                            @error('auto_topup_minimum_balance')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lcr_profile" class="form-label">Pricing list</label>
                            <input
                                type="text"
                                class="form-control @error('lcr_profile') is-invalid @enderror"
                                id="lcr_profile"
                                name="lcr_profile"
                                value="{{ old('lcr_profile', $billing->lcr_profile ?? '') }}"
                            >
                            @error('lcr_profile')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="max_rate" class="form-label">Maximum rate allowed</label>
                            <input
                                type="number"
                                class="form-control @error('max_rate') is-invalid @enderror"
                                id="max_rate"
                                name="max_rate"
                                min="0"
                                step="0.00001"
                                value="{{ old('max_rate', $billing->max_rate ?? '') }}"
                            >
                            @error('max_rate')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="referred_by_uuid" class="form-label">Referred by</label>
                            <x-switch-contacts name="referred_by_uuid" selected="{{ $billing->referred_by_uuid ?? null }}" />
                            @error('referred_by_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="referred_depth" class="form-label">Referred depth</label>
                            <input
                                type="number"
                                class="form-control @error('referred_depth') is-invalid @enderror"
                                id="referred_depth"
                                name="referred_depth"
                                min="0"
                                step="1"
                                value="{{ old('referred_depth', $billing->referred_depth ?? '') }}"
                            >
                            @error('referred_depth')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="referred_percentage" class="form-label">Referred percentage</label>
                            <input
                                type="number"
                                class="form-control @error('referred_percentage') is-invalid @enderror"
                                id="referred_percentage"
                                name="referred_percentage"
                                min="0"
                                max="100"
                                value="{{ old('referred_percentage', $billing->referred_percentage ?? '') }}"
                            >
                            @error('referred_percentage')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="billing_notes" class="form-label">Notes</label>
                            <textarea
                                class="form-control @error('billing_notes') is-invalid @enderror"
                                id="billing_notes"
                                name="billing_notes"
                                rows="3"
                            >{{ old('billing_notes', $billing->billing_notes ?? '') }}</textarea>
                            @error('billing_notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="whmcs_user_id" class="form-label">WHMCS User ID</label>
                            <input
                                type="number"
                                class="form-control @error('whmcs_user_id') is-invalid @enderror"
                                id="whmcs_user_id"
                                name="whmcs_user_id"
                                min="1"
                                step="1"
                                value="{{ old('whmcs_user_id', $billing->whmcs_user_id ?? '') }}"
                            >
                            @error('whmcs_user_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>


            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($billing) ? 'Update Billing' : 'Create Billing' }}
                </button>
                <a href="{{ route('billings.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push("scripts")

<script>

document.addEventListener('DOMContentLoaded', function()
{
    const type = document.getElementById('type');
    const type_value_select = document.getElementById('type_value_select');
    const type_value = document.getElementById('type_value');

    type.addEventListener('change', function()
    {
        switch(this.value)
        {
            case "domain":
                type_value_select.classList.remove("d-none");
                type_value.classList.add("d-none");
                break;
            case "authcode":
                type_value_select.classList.add("d-none");
                type_value.classList.remove("d-none");
                break;
        }
    });

    type_value_select.addEventListener('change', function()
    {
        type_value.value = this.value;
    });
});

</script>

@endpush
