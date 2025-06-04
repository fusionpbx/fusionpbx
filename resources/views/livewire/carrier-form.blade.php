<div>
	<div class="container-fluid">
		<div class="card card-primary mt-3 card-outline">
			<div class="card-header">
				<h3 class="card-title">
					{{ isset($carrier) ? 'Edit Carrier' : 'Create Carrier' }}
				</h3>
			</div>

			<div class="card-body">
				<form wire:submit.prevent="save">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="carrier_name" class="form-label">Carrier Name</label>
								<input
									type="text"
									class="form-control @error('carrier_name') is-invalid @enderror"
									id="carrier_name"
									name="carrier_name"
									placeholder="Enter carrier name"
									value="{{ old('carrier_name', $carrier->carrier_name ?? '') }}"
									required
									wire:model="carrier_name"
								>
								@error('carrier_name')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-6">
							<div class="form-group">
								<label for="carrier_channels" class="form-label">Channels</label>
								<input
									type="number"
									class="form-control @error('carrier_channels') is-invalid @enderror"
									id="carrier_channels"
									name="carrier_channels"
									placeholder="Enter carrier channel"
									value="{{ old('carrier_channels', $carrier->carrier_channels ?? '') }}"
									required
									wire:model="carrier_channels"
								>
								@error('carrier_channels')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-6">
							<div class="form-group">
								<label for="priority" class="form-label">Priority</label>
								<input
									type="number"
									class="form-control @error('priority') is-invalid @enderror"
									id="priority"
									name="priority"
									placeholder="Enter carrier priority"
									value="{{ old('priority', $carrier->priority ?? '') }}"
									required
									wire:model="priority"
								>
								@error('priority')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-6">
							<div class="form-group">
								<label for="cancellation_ratio" class="form-label">Cancellation ratio</label>
								<input
									type="text"
									class="form-control @error('cancellation_ratio') is-invalid @enderror"
									id="cancellation_ratio"
									name="cancellation_ratio"
									placeholder="Enter carrier cancellation ratio"
									value="{{ old('cancellation_ratio', $carrier->cancellation_ratio ?? '') }}"
									required
									wire:model="cancellation_ratio"
								>
								@error('cancellation_ratio')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-6">
							<div class="form-group">
								<label class="form-label d-block">Short call friendly</label>
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" id="short_call_friendly" name="short_call_friendly" value="true" wire:model="short_call_friendly" {{ old('short_call_friendly', $carrier->short_call_friendly ?? true) ? 'checked' : '' }}>
									<label class="form-check-label" for="short_call_friendly">{{ __('Enabled') }}</label>
								</div>
								@error('short_call_friendly')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-6">
							<div class="form-group">
								<label class="form-label d-block">Fax enabled</label>
								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" id="fax_enabled" name="fax_enabled" value="true" wire:model="fax_enabled" {{ old('fax_enabled', $carrier->fax_enabled ?? true) ? 'checked' : '' }}>
									<label class="form-check-label" for="fax_enabled">{{ __('Enabled') }}</label>
								</div>
								@error('fax_enabled')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<div class="row mt-3">
						<div class="col-md-6">
							<div class="form-group" wire:ignore>
								<label for="lcr_tags" class="form-label">Tags</label>
								<input
									type="text"
									class="form-control form-tags @error('lcr_tags') is-invalid @enderror"
									id="lcr_tags"
									name="lcr_tags"
									placeholder="Enter lcr tags"
									value="{{ old('lcr_tags', $carrier->lcr_tags ?? '') }}"
									required
									data-ub-tag-variant="primary"
									wire:model="lcr_tags"
								>
								@error('lcr_tags')
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
									<input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="true" wire:model="enabled" {{ old('enabled', $carrier->enabled ?? true) ? 'checked' : '' }}>
									<label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
								</div>
								@error('enabled')
									<div class="invalid-feedback d-block">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>

					<h5 class="mt-4 mb-3">Gateways</h5>
					<div class="card mb-4">
						<div class="card-body">
							<div class="table-responsive">
								<table class="table table-bordered">
									<thead>
										<tr>
											<th>{{ __('Gateway') }}</th>
											<th>{{ __('Prefix') }}</th>
											<th>{{ __('Suffix') }}</th>
											<th>{{ __('Priority') }}</th>
											<th>{{ __('Codec') }}</th>
											<th>{{ __('Enabled') }}</th>
											<th class="text-center">{{ __('Action') }}</th>
										</tr>
									</thead>
									<tbody>
										@if(!empty($carrierGateways))
											@foreach($carrierGateways as $index => $carrierGateway)
											<tr>
												<td>
													<select name="gateway_uuid" class="form-select @error('carrierGateways.' . $index . '.gateway_uuid') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.gateway_uuid">
														<option value=""></option>
														@foreach($this->gateways as $gateway)
														<option value="{{ $gateway->gateway_uuid }}">{{ $gateway->gateway }}</option>
														@endforeach
													</select>
												</td>
												<td>
													<input type="text" class="form-control @error('carrierGateways.' . $index . '.prefix') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.prefix">
												</td>
												<td>
													<input type="number" class="form-control @error('carrierGateways.' . $index . '.suffix') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.suffix" required>
												</td>
												<td>
													<input type="number" class="form-control @error('carrierGateways.' . $index . '.priority') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.priority" required>
												</td>
												<td>
													<input type="number" class="form-control @error('carrierGateways.' . $index . '.codec') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.codec" required>
												</td>
												<td>
													<select class="form-select @error('carrierGateways.' . $index . '.enabled') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.enabled">
														<option value=""></option>
														<option value="true">True</option>
														<option value="false">False</option>
													</select>
												</td>
												<td class="text-center">
													@if (count($carrierGateways) > 1)
													<button type="button" class="btn btn-sm btn-danger" wire:click="removeCarrierGateway({{ $index }})"><i class="fas fa-times"></i> <i class="bi bi-trash"></i> </button>
													@endif

													@if ($index === count($carrierGateways) - 1)
														<button type="button" class="btn btn-sm btn-success" wire:click="addCarrierGateway"><i class="fas fa-plus"></i> Add</button>
													@endif
												</td>
											</tr>
											@endforeach
										@endif
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<div class="card-footer">
					<button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
						{{ isset($carrier) ? 'Update Carrier' : 'Create Carrier' }}
					</button>
					<a href="{{ route('carriers.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
						Cancel
					</a>
				</form>
			</div>
		</div>
	</div>
</div>
