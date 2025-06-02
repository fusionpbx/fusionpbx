@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ 'Create Outbound Route' }}
            </h3>
        </div>

        <form action="{{ route('dialplans.outbound.store') }}"
              method="POST">
            @csrf

			<input type="hidden" name="app_uuid" value="{{ old('app_uuid', $dialplan->app_uuid ?? $app_uuid) }}">

            <div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="gateway" class="form-label">Gateway</label>
                            <x-switch-gateways name="gateway" />
                            @error('gateway')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="gateway_2" class="form-label">Alternate 1</label>
                            <x-switch-gateways name="gateway_2" />
                            @error('gateway_2')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="gateway_3" class="form-label">Alternate 2</label>
                            <x-switch-gateways name="gateway_3" />
                            @error('gateway_3')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="dialplan_expression" class="form-label">Dialplan Expression</label>
                            <select class="form-select" name="dialplan_expression_select" id="dialplan_expression_select" required>
                                <option></option>
                                <option value="^(\d{2})$">2 Digits</option>
                                <option value="^(\d{3})$">3 Digits</option>
                                <option value="^(\d{4})$">4 Digits</option>
                                <option value="^(\d{5})$">5 Digits</option>
                                <option value="^(\d{6})$">6 Digits</option>
                                <option value="^(\d{7})$">7 Digits Local</option>
                                <option value="^(\d{8})$">8 Digits</option>
                                <option value="^(\d{9})$">9 Digits</option>
                                <option value="^(\d{10})$">10 Digits Long Distance</option>
                                <option value="^\+?(\d{11})$">11 Digits Long Distance</option>
                                <option value="^\+?1?([2-9]\d{2}[2-9]\d{2}\d{4})$">North America</option>
                                <option value="^(011\d{9,17})$">North America International</option>
                                <option value="^\+?1?((?:264|268|242|246|441|284|345|767|809|829|849|473|658|876|664|787|939|869|758|784|721|868|649|340|684|671|670|808)\d{7})$">North America Islands</option>
                                <option value="^(00\d{9,17})$">Europe International</option>
                                <option value="^(\d{12,20})$">International</option>
                                <option value="^(311)$">311 Information</option>
                                <option value="^(411)$">411 Information</option>
                                <option value="^(711)$">711 TTY</option>
                                <option value="(^911$|^933$)">911 Emergency</option>
                                <option value="(^988$)">988 National Suicide Prevention Lifeline</option>
                                <option value="^1?(8(00|33|44|55|66|77|88)[2-9]\d{6})$">Toll-Free</option>
                                <option value="^0118835100\d{8}$">iNum 0118335100xxxxxxxx</option>
                                <option value="^9(\d{2})$">Dial 9, then 2 Digits</option>
                                <option value="^9(\d{3})$">Dial 9, then 3 Digits</option>
                                <option value="^9(\d{4})$">Dial 9, then 4 Digits</option>
                                <option value="^9(\d{5})$">Dial 9, then 5 Digits</option>
                                <option value="^9(\d{6})$">Dial 9, then 6 Digits</option>
                                <option value="^9(\d{7})$">Dial 9, then 7 Digits</option>
                                <option value="^9(\d{8})$">Dial 9, then 8 Digits</option>
                                <option value="^9(\d{9})$">Dial 9, then 9 Digits</option>
                                <option value="^9(\d{10})$">Dial 9, then 10 Digits</option>
                                <option value="^9(\d{11})$">Dial 9, then 11 Digits</option>
                                <option value="^9(\d{12,20})$">Dial 9, then International</option>
                            </select>
                            <br>
                            <textarea
                                class="form-control @error('dialplan_expression') is-invalid @enderror"
                                name="dialplan_expression"
                                id="dialplan_expression"
                                value="{{ old('dialplan_expression') }}"
                                rows="5"
                            ></textarea>
                            @error('condition_expression_1')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prefix_number" class="form-label">Prefix</label>
                            <input
                                type="text"
                                class="form-control @error('prefix_number') is-invalid @enderror"
                                name="prefix_number"
                                value="{{ old('prefix_number') }}"
                            >
                            @error('prefix_number')
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
                                type="number"
                                class="form-control @error('limit') is-invalid @enderror"
                                id="limit"
                                name="limit"
                                value="{{ old('limit') }}"
                                min="1"
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
                            <label for="accountcode" class="form-label">Account code</label>
                            <input
                                type="text"
                                class="form-control @error('accountcode') is-invalid @enderror"
                                name="accountcode"
                                value="{{ old('accountcode') }}"
                            >
                            @error('accountcode')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="toll_allow" class="form-label">Toll allow</label>
                            <input
                                type="text"
                                class="form-control @error('toll_allow') is-invalid @enderror"
                                name="toll_allow"
                                value="{{ old('toll_allow') }}"
                            >
                            @error('toll_allow')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">PIN numbers</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="pin_numbers_enabled" name="pin_numbers_enabled" value="true">
                                <label class="form-check-label" for="pin_numbers_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('pin_numbers_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label d-block">Order</label>
                            <select class="form-select" name="dialplan_order" required>
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
                                <input class="form-check-input" type="checkbox" role="switch" id="dialplan_enabled" name="dialplan_enabled" value="true">
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
                            <label for="dialplan_description" class="form-label">Outbound Route Description</label>
                            <textarea
                                class="form-control @error('dialplan_description') is-invalid @enderror"
                                id="dialplan_description"
                                name="dialplan_description"
                                rows="3"
                                placeholder="Enter outbound description"
                            >{{ old('dialplan_description') }}</textarea>
                            @error('dialplan_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ 'Create Outbound Route' }}
                </button>
                <a href="{{ route('dialplans.index', ['app_uuid' => $app_uuid]) }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
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
    const dialplan_expression_select = document.getElementById('dialplan_expression_select');
    const dialplan_expression = document.getElementById('dialplan_expression');

    dialplan_expression_select.addEventListener("change", function(e)
    {
        dialplan_expression.value += this.value + '\n';
    });
});

</script>
@endpush
