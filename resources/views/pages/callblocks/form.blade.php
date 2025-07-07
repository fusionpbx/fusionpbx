@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ isset($callblock) ? 'Edit CallBlock' : 'Create CallBlock' }}
            </h3>
        </div>

        <form action="{{ isset($callblock) ? route('callblocks.update', $callblock->call_block_uuid) : route('callblocks.store') }}"
              method="POST">
            @csrf
            @if(isset($callblock))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_direction" class="form-label">Direction</label>
                            <select class="form-select" name="call_block_direction">
                                <option value="inbound" @selected(old('call_block_direction', $callblock->call_block_direction ?? null) == "inbound")>Inbound</option>
                                <option value="outbound" @selected(old('call_block_direction', $callblock->call_block_direction ?? null) == "outbound")>Outbound</option>
                            </select>
                            @error('call_block_direction')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="extension_uuid" class="form-label">Extension</label>
                            <select class="form-select" name="extension_uuid">
                                @can('call_block_all')
                                <option value="">{{ __("All") }}</option>
                                @else
                                <option value="">{{ __("Mine") }}</option>
                                @endcan
                                @foreach($extensions as $extension)
                                <option value="{{ $extension->extension_uuid }}" @selected(old('extension_uuid', $callblock->extension_uuid ?? null) == $extension->extension_uuid)>{{ $extension->extension }} {{ $extension->description }}</option>
                                @endforeach
                            </select>
                            @error('extension_uuid')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_name" class="form-label">Name</label>
                            <input
                                type="text"
                                class="form-control @error('call_block_name') is-invalid @enderror"
                                id="call_block_name"
                                name="call_block_name"
                                placeholder="Enter callblock name"
                                value="{{ old('call_block_name', $callblock->call_block_name ?? '') }}"
                                required
                            >
                            @error('call_block_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="call_block_country_code" class="form-label">Country Code</label>
                            <input
                                type="text"
                                class="form-control @error('call_block_country_code') is-invalid @enderror"
                                id="call_block_country_code"
                                name="call_block_country_code"
                                placeholder="Enter country code"
                                value="{{ old('call_block_country_code', $callblock->call_block_country_code ?? '') }}"
                                required
                            >
                            @error('call_block_country_code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="call_block_number" class="form-label">Caller ID Number</label>
                            <input
                                type="text"
                                class="form-control @error('call_block_number') is-invalid @enderror"
                                id="call_block_number"
                                name="call_block_number"
                                placeholder="Enter Caller ID Number"
                                value="{{ old('call_block_number', $callblock->call_block_number ?? '') }}"
                                required
                            >
                            @error('call_block_number')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_action" class="form-label">Action</label>

                            <x-switch-call-block-action name="call_block_action" selected="$callblock->call_block_app" />

                            @error('call_block_action')
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
                                <input class="form-check-input" type="checkbox" role="switch" id="call_block_enabled" name="call_block_enabled" value="true" {{ old('call_block_enabled', $callblock->call_block_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="call_block_enabled">{{ __('Enabled') }}</label>
                            </div>
                            @error('call_block_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="call_block_description" class="form-label">Description</label>
                            <textarea
                                class="form-control @error('call_block_description') is-invalid @enderror"
                                id="call_block_description"
                                name="call_block_description"
                                rows="3"
                                placeholder="Enter callblock description"
                            >{{ old('call_block_description', $callblock->call_block_description ?? '') }}</textarea>
                            @error('call_block_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ isset($callblock) ? 'Update CallBlock' : 'Create CallBlock' }}
                </button>
                <a href="{{ route('callblocks.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>

        @if(!isset($callblock))
        <form action="{{ route('callblocks.block') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">{{ __("Recent calls") }}</label>

                            <select class='form-select' id='recent_calls_direction' name='call_block_direction' required>
                                <option value='' disabled='disabled'>{{ __("Direction") }}</option>
                                <option value='inbound'>{{ __("Inbound") }}</option>
                                <option value='outbound'>{{ __("Outbound") }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4"></div>

                    @if(isset($xmlCDR))
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label"></label>
                                @can('call_block_all')
                                    <select class='form-select' name='extension_uuid' required>
                                        <option value='' disabled='disabled'>{{ __("Extension") }}</option>
                                        <option value='' selected='selected'>{{ __("All") }}</option>
                                        @if(!$extensions->isEmpty())
                                            @foreach($extensions as $extension)
                                                <option value="{{ $extension->extension_uuid }}">{{ $extension->extension }} {{ $extension->description }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                @endcan
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label"></label>
                            <x-switch-call-block-action name="call_block_action" required />
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label"></label><br>
                            <button type="button" class="btn btn-danger px-4 py-2" data-bs-toggle="modal" data-bs-target="#block" style="border-radius: 4px;"><i class="fa-solid fa-ban"></i> {{ __("Block") }}</button>
                        </div>
                    </div>
                    @endif

                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        @foreach(['inbound','outbound'] as $direction)
                            <table class="laravel-livewire-table table table-striped table-hover table-bordered" id="list_{{ $direction }}" @if($direction == 'outbound') style="display: none;" @endif>
                                <thead>
                                    <tr>
                                        <th style='width: 1%;'>
                                            <input type='checkbox' class="form-check-input select-group">
                                        </th>
                                        <th style='width: 1%;'></th>
                                        <th>{{ __("Name") }}</th>
                                        <th>{{ __("Number") }}</th>
                                        <th>{{ __("Destination") }}</th>
                                        <th>{{ __("Called") }}</th>
                                        <th>{{ __("Duration") }}</th>
                                    </tr>
                                </thead>
                                <tbody>

                                @if(!$xmlCDR->isEmpty())
                                    @foreach($xmlCDR as $row)
                                        @if($row->direction == $direction)
                                            @if(strlen($row->caller_id_number) >= 7)
                                                @php
                                                    $time_format = Setting::getSetting('domain', 'time_format', 'text');
                                                    $tmp_start_epoch = date('j M Y', $row->start_epoch);
                                                    $tmp_start_time = date('H:i:s', $row->start_epoch);
                                                    $seconds = ($row->hangup_cause == "ORIGINATOR_CANCEL") ? $row->duration : $row->billsec;
                                                    $title_mod = '';
                                                    $file_mod = '';
                                                    $icon_prefix = '';
                                                    $title_prefix = '';
                                                @endphp

                                                @switch ($row->direction)
                                                    @case('inbound')
                                                        @php
                                                            $icon_prefix = 'icon_cdr_inbound';
                                                            $title_prefix = __('Inbound');
                                                            $file_mod = $row->billsec == 0 ? '_voicemail' : '_answered';
                                                            $title_mod = $row->billsec == 0 ? ' ' . __('Missed') : '';
                                                        @endphp
                                                        @break

                                                    @case('outbound')
                                                        @php
                                                            $icon_prefix = 'icon_cdr_outbound';
                                                            $title_prefix = __('Outbound');
                                                            $file_mod = $row->billsec == 0 ? '_failed' : '_answered';
                                                            $title_mod = $row->billsec == 0 ? ' ' . __('Failed') : '';
                                                        @endphp
                                                        @break
                                                @endswitch

                                                @php
                                                    $icon_path = asset("assets/icons/xml_cdr/{$icon_prefix}{$file_mod}.png");
                                                    $icon_title = $title_prefix . $title_mod;
                                                @endphp

                                                <tr class='list-row row_{{ $row->direction }}'>
                                                    <td>
                                                        <input type='checkbox' class='form-check-input checkbox_{{ $row->direction }}' name='selected_xml_cdrs[]' value='{{ $row->xml_cdr_uuid }}'>
                                                    </td>
                                                    <td>
                                                        <img src="{{ $icon_path }}" style="border: none;" title="{{ $icon_title }}">
                                                    </td>
                                                    <td>{{ $row->caller_id_name }}</td>
                                                    <td>{{ $row->caller_id_number }}</td>
                                                    <td>{{ $row->caller_destination }}</td>
                                                    <td>{{ $tmp_start_epoch }} {{ $tmp_start_time }}</td>
                                                    <td>{{ gmdate('G:i:s', $seconds) }}</td>
                                                </tr>
                                            @endif
                                        @endif
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal fade" id="block" tabindex="-1" aria-labelledby="block" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirmation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Do you really want to block this?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="action" value="block" class="btn btn-primary">{{ __('Continue') }}</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('Cancel')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

@push("scripts")
<script>

document.addEventListener('DOMContentLoaded', function () {
    const selector = document.getElementById('recent_calls_direction');
    const inboundTable = document.getElementById('list_inbound');
    const outboundTable = document.getElementById('list_outbound');

    selector.addEventListener('change', function()
    {
        inboundTable.style.display = this.value === 'inbound' ? '' : 'none';
        outboundTable.style.display = this.value === 'outbound' ? '' : 'none';
    });

    document.querySelectorAll('.select-group').forEach(groupCheckbox => {
        groupCheckbox.addEventListener('change', function()
        {
            const table = this.closest("table");
            const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');

            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    });
});

</script>
@endpush
