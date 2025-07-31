@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
			<h3>Select payment method</h3>
   		</div>

		<div class="card-body">
			<div class="d-flex flex-wrap gap-3">
			@foreach($payments as $payment)
				<div style="flex: 0 0 275px;">
					<div class="info-box">
						<div class="info-box-content">
							<span class="info-box-text text-center">
								<a href="{{ route('billings.payment.create', [$billing->billing_uuid, $payment]) }}">
									<img src="{{ asset("assets/logos/{$payment}.png") }}" alt="{{ $payment }}">
								</a>
							</span>
						</div>
					</div>
				</div>
			@endforeach
			</div>
   		</div>
    </div>
</div>
@endsection
