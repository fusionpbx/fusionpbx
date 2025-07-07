<div>
	<div class="container-fluid">
		<div class="card card-primary mt-3 card-outline">
			<div class="card-header">
				<h3 class="card-title">
					{{ isset($carrier) ? 'Edit Carrier' : 'Create Carrier' }}
				</h3>
			</div>

			<div class="card-body">
				<form wire:submit.prevent="save" method="POST">
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
                                    min="1"
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
                                    min="0"
									class="form-control @error('priority') is-invalid @enderror"
									id="priority"
									name="priority"
									placeholder="Enter carrier priority"
									value="{{ old('priority', $carrier->priority ?? '5') }}"
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
								<div class="d-flex">
									<input
										type="range"
										min="0"
										max="100"
										step="1"
										class="form-control @error('cancellation_ratio') is-invalid @enderror"
										id="cancellation_ratio"
										name="cancellation_ratio"
										placeholder="Enter carrier cancellation ratio"
										value="{{ old('cancellation_ratio', $carrier->cancellation_ratio ?? '100') }}"
										required
										wire:model="cancellation_ratio"
										oninput="this.nextElementSibling.value = this.value"
									>&nbsp<output>{{ old('cancellation_ratio', $carrier->cancellation_ratio ?? '100') }}</output>%
								</div>
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

					@if (isset($carrier))
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
													@error('carrierGateways.' . $index . '.gateway_uuid')
														<div class="invalid-feedback d-block">{{ $message }}</div>
													@enderror
												</td>
												<td>
													<input type="text" class="form-control @error('carrierGateways.' . $index . '.prefix') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.prefix">
													@error('carrierGateways.' . $index . '.prefix')
														<div class="invalid-feedback d-block">{{ $message }}</div>
													@enderror
												</td>
												<td>
													<input type="number" class="form-control @error('carrierGateways.' . $index . '.suffix') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.suffix">
													@error('carrierGateways.' . $index . '.suffix')
														<div class="invalid-feedback d-block">{{ $message }}</div>
													@enderror
												</td>
												<td>
													<input type="number" class="form-control @error('carrierGateways.' . $index . '.priority') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.priority" required min="0">
													@error('carrierGateways.' . $index . '.priority')
														<div class="invalid-feedback d-block">{{ $message }}</div>
													@enderror
												</td>
												<td>
													<input type="text" class="form-control @error('carrierGateways.' . $index . '.codec') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.codec">
													@error('carrierGateways.' . $index . '.codec')
														<div class="invalid-feedback d-block">{{ $message }}</div>
													@enderror
												</td>
												<td>
													<select class="form-select @error('carrierGateways.' . $index . '.enabled') is-invalid @enderror" wire:model="carrierGateways.{{ $index }}.enabled" required>
														<option value=""></option>
														<option value="true">True</option>
														<option value="false">False</option>
													</select>
													@error('carrierGateways.' . $index . '.enabled')
														<div class="invalid-feedback d-block">{{ $message }}</div>
													@enderror
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

					<div class="card-footer">
						<button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
							{{ isset($carrier) ? 'Update Carrier' : 'Create Carrier' }}
						</button>
						<a href="{{ route('carriers.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
							Cancel
						</a>
					</div>
				</form>

					<h5 class="mt-4 mb-3">LCR</h5>
					<div class="card mb-4">
						 <div class="card-header">
							<div class="card-tools">
								<div class="d-flex gap-2" role="carrier" aria-label="Carriers actions">
									@can('lcr_add')
									<a href="{{ route('lcr.create', ['carrier_uuid' => $carrier->carrier_uuid]) }}" class="btn btn-primary btn-sm">
										<i class="fas fa-plus mr-1"></i> {{__('Add')}}
									</a>
									@endcan
									@can('lcr_edit')
									<a class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#csv_upload">
										<i class="fas fa-upload mr-1"></i> {{__('Upload')}}
									</a>
									<a href="{{ route('lcr.export', ['carrier_uuid' => $carrier->carrier_uuid]) }}" class="btn btn-primary btn-sm">
										<i class="fas fa-download mr-1"></i> {{__('Download')}}
									</a>

									<div class="modal fade" id="csv_upload" tabindex="-1" aria-labelledby="csv_upload" aria-hidden="true">
										<div class="modal-dialog modal-lg" role="document">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Upload CSV</h5>
													<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
												</div>
												<div class="modal-body">
													<form method="POST" action="{{ route('lcr.import') }}" enctype="multipart/form-data">
														@csrf
														<input type="hidden" name="carrier_uuid" value="{{ $carrier->carrier_uuid }}">
														<div class="row">
															<div class="col-md-10">
																<div class="form-group">
																	<input name="upload_file" type="file" class="form-control" id="upload_file" accept=".csv">
																</div>
															</div>
															<div class="col-md-2">
																<button type="submit" class="btn btn-primary px-4" style="border-radius: 4px;">
																	{{ __('Send') }}
																</button>
															</div>
														</div>
														<div class="row mt-3">
															<div class="col-md-10">
																<div class="form-group">
																	<label class="form-label d-block">Clear ALL rates before importing</label>
																	<div class="form-check form-switch">
																		<input class="form-check-input" type="checkbox" role="switch" id="enabled" name="clear_before" value="1">
																	</div>
																</div>
															</div>
														</div>
														<div class="row mt-3">
															<div class="col-md-4">
																<div class="form-group">
																	<label class="form-label d-block">LCR Profile / Pricing list</label>
																	<input class="form-control" name='lcr_profile' type='text' value='default' required='required'>
																</div>
															</div>
														</div>
														<div class="row mt-3">
															<p style="font-size: 11px;">
																<br>CSV details. File must containt this values in next order. * are required.
																<br>
																<br>Destination -> description,
																<br>Prefix -> digits *,
																<br>Connect Increment -> connect_increment, if not specified then it will be the same as talk_increment
																<br>Talking Increment -> talk_increment *,
																<br>Rate -> rate *,
																<br>Connect Rate -> connect_rate, if not specified then it will be the same as rate
																<br>IntraState Rate -> intrastate_rate, if not specified then it will be the same as rate. Only useful for USA.
																<br>IntraLata Rate -> intralata_rate, if not specified then it will be the same as rate. Only useful for USA.
																<br>Currency -> currency, [3 chars]
																<br>Direction -> lcr_direction, [inbound, outbound, internal]
																<br>Start Date-> date_start [optional, if not specified then current date and time]
																<br>End Date -> date_end [optional, if not specified then it will be 2099-12-31 06:50:00]
																<br>Profile -> lcr_profile [use defaul if you dont know what to do, check lcr.conf.xml]
																<br>Lead Strip -> lead_strip,
																<br>Trail Strip -> trial_strip,
																<br>Add Prefix -> prefix,
																<br>Add Suffix -> suffix
																<br>Random -> any value you want
																<br>
																<br>This is a simple import, check the box if you want to overwrite prefixes
															</p>
														</div>
													</form>
												</div>
												<div class="modal-footer">
													<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
												</div>
											</div>
										</div>
									</div>
									@endcan
								</div>
							</div>
						</div>
						<div class="card-body">
							<div class="table-responsive">
								<table class="laravel-livewire-table table table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th>{{ __('Digits') }}</th>
											<th>{{ __('Call direction') }}</th>
											<th>{{ __('Rate') }}</th>
											<th>{{ __('Currency') }}</th>
											<th>{{ __('Intrastate Rate') }}</th>
											<th>{{ __('Intralata Rate') }}</th>
											<th>{{ __('Lead strip') }}</th>
											<th>{{ __('Trail strip') }}</th>
											<th>{{ __('Prefix') }}</th>
											<th>{{ __('Suffix') }}</th>
											<th>{{ __('Enabled') }}</th>
										</tr>
									</thead>
									<tbody>
										@foreach($carrier->lcr as $lcr)
										<tr>
											<td><a href="{{ route('lcr.edit', $lcr->lcr_uuid) }}">{{ $lcr->digits}}</a></td>
											<td>{{ $lcr->lcr_direction}}</td>
											<td>{{ $lcr->rate}}</td>
											<td>{{ $lcr->currency}}</td>
											<td>{{ $lcr->intrastate_rate}}</td>
											<td>{{ $lcr->intralata_rate}}</td>
											<td>{{ $lcr->lead_strip}}</td>
											<td>{{ $lcr->trail_strip}}</td>
											<td>{{ $lcr->prefix}}</td>
											<td>{{ $lcr->suffix}}</td>
											<td>
												<svg class="d-inline-block @if($lcr->enabled) text-success @else text-danger @endif laravel-livewire-tables-btn-small" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
													@if($lcr->enabled)
													<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
													@else
													<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
													@endif
												</svg>
											</td>
										</tr>
										@endforeach
									</tbody>
								</table>
							</div>
						</div>
					</div>
					@endif
				</div>

			</div>
		</div>
	</div>
</div>
