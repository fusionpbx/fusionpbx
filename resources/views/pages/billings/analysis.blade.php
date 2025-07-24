@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ 'Price analysis' }}
            </h3>
        </div>

        <div class="card-body">
            <form method="post" action="{{ route('billings.analysis') }}">
                @csrf
                <div class="row mt-3">

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="direction" class="form-label">Direction</label>
                            <select class="form-select" name="direction">
                                <option value="inbound">Inbound call</option>
                                <option value="outbound">Outbound call</option>
                                <option value="local">local (Extension-to-extension) call</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="caller_destination" class="form-label">Caller destination</label>
                            <input type="text" class="form-control" name="caller_destination" value="">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="number" class="form-label">Rates table</label>
                            <input type="text" class="form-control" id="profile" name="profile" value="default">
                        </div>
                    </div>
                </div>

				<div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="short_call_friendly" class="form-label">Short call friendly</label>
                            <select class="form-select" name="short_call_friendly">
                                <option value=""></option>
                                <option value="true">True</option>
                                <option value="false">False</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lcr_tag" class="form-label">Tag</label>
                            <input type="text" class="form-control" name="lcr_tag" value="">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="number" class="form-label">Include disabled carriers</label>
                            <input type="checkbox" class="form-check-input" name="include_disabled_carriers" value="true">
                        </div>
                        <div class="form-group">
                            <label for="number" class="form-label">Include disabled rates</label>
                            <input type="checkbox" class="form-check-input" name="include_disabled_rates" value="true">
                        </div>
                        <div class="form-group">
                            <label for="number" class="form-label">Ignore dates</label>
                            <input type="checkbox" class="form-check-input" name="ignore_dates" value="true">
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary px-4 py-2 float-end" style="border-radius: 4px;">{{ 'Check' }}</button>
                    </div>
                </div>
            </form>

        </div>


        <div class="card-header">
            <h3 class="card-title">{{ 'Sales' }}</h3>
        </div>
        <div class="card-body">
			<table class="table">
                <thead>
                    <tr>
                        <th>{{ __('LCR Profile') }}</th>
                        <th>{{ __('Digits') }}</th>
                        <th>{{ __('Connect rate') }}</th>
                        <th>{{ __('Talk rate') }}</th>
                        <th>{{ __('Connect increment') }}</th>
                        <th>{{ __('Talk increment') }}</th>
                    </tr>
                </thead>
				<tbody>
                    @foreach($sales as $profile => $data)
                        @php
                            $item = $data->first();
                        @endphp

                        @if($item)
                            {{ $item->algo }}
                        @endif
					<tr>
						<td>{{ $profile ?? '' }}</td>
						<td>{{ $item->digits ?? '' }}</td>
						<td>{{ $item->connect_rate ?? '' }}</td>
						<td>{{ $item->rate ?? '' }}</td>
						<td>{{ $item->connect_increment ?? '' }}</td>
						<td>{{ $item->talk_increment ?? '' }}</td>
					</tr>
                    @endforeach
				</tbody>
			</table>
        </div>

        <div class="card-header">
            <h3 class="card-title">{{ 'Purchases' }}</h3>
        </div>
        <div class="card-body">
			<table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Carrier') }}</th>
                        <th>{{ __('Digits') }}</th>
                        <th>{{ __('Connect rate') }}</th>
                        <th>{{ __('Talk rate') }}</th>
                        <th>{{ __('Connect increment') }}</th>
                        <th>{{ __('Talk increment') }}</th>
                    </tr>
                </thead>
				<tbody>
                    @foreach($purchases as $data)
					<tr>
						<td>{{ $data->description ?? '' }}</td>
						<td>{{ $data->carrier_name ?? '' }}</td>
						<td>{{ $data->digits ?? '' }}</td>
						<td>{{ $data->connect_rate ?? '' }}</td>
						<td>{{ $data->rate ?? '' }}</td>
						<td>{{ $data->connect_increment ?? '' }}</td>
						<td>{{ $data->talk_increment ?? '' }}</td>
					</tr>
                    @endforeach
				</tbody>
			</table>
        </div>

    </div>
</div>
@endsection
