@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ 'Select payment method' }}
            </h3>
   		</div>

		<div class="card-body">
			<div class="d-flex flex-wrap gap-3">
			@foreach($paymentgateways as $paymentgateway)
				<div style="flex: 0 0 275px;">
					<div class="info-box">
						<div class="info-box-content">
							<span class="info-box-text text-center">
								<a href="{{ route('billings.payment.create', [$billing->billing_uuid, $paymentgateway]) }}">
									<img src="{{ asset("assets/logos/{$paymentgateway}.png") }}" alt="{{ $paymentgateway }}">
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
