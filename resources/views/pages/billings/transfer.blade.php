@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ 'Transfer credit' }}
            </h3>
        </div>

        <form method="post" action="{{ route('billings.transfer_post', $billing) }}">
            @csrf

			<div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">From</label>
                            <input type="text" class="form-control" value="{{ $billing->contactTo->contact_name_given }} {{ $billing->contactTo->contact_name_given }} {{ $billing->contactTo->contact_name_family }} @ {{ $billing->contactTo->contact_organization }} {{ $billing->currency }}" readonly>
                        </div>
                    </div>
                </div>

				<div class="row mt-3">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="billing_uuid_to" class="form-label">To</label>
							<select class="form-select" name="billing_uuid_to">
								@php
								$currencies = [];
								@endphp
								@foreach($billings as $b)
                                    @if($b->billing_uuid == $billing->billing_uuid)
                                        @continue
                                    @endif
								<option value="{{ $b->billing_uuid }}">{{ $b->contact_name_given }} {{ $b->contact_name_given }} {{ $b->contact_name_family }} @ {{ $b->contact_organization }} {{ $b->currency }}</option>

								@if(strcmp($billing->currency, $b->currency) !== 0)
									@php
									$currencies[] = $b->currency;
									@endphp
								@endif
								@endforeach

								@php
									$currencies = array_unique($currencies);
								@endphp
							</select>
                        </div>
                    </div>

                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Transfer</label>
                            <input type="number" class="form-control" name="transfer" value="transfer" step="0.01" min="1" max="{{ $max }}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <input type="text" class="form-control" value="{{ $billing->currency }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Rates</label>
							<input type="text" class="form-control" value="1 {{ $billing->currency }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>

							@php
							$l = [];
							@endphp
							@foreach($currencies as $target_currency)
								@php
								$l[] = currency_convert_rate($target_currency, $billing->currency) . ' ' . $target_currency;
								@endphp
							@endforeach

							<input type="text" class="form-control" value="{{ implode(' -- ', $l) }}" readonly>

                        </div>
                    </div>
                </div>
            </div>

			<div class="card-footer">
				<button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
					{{ 'Save' }}
				</button>
				<a href="{{ route('billings.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
					Cancel
				</a>
			</div>

		</form>

    </div>
</div>
@endsection
