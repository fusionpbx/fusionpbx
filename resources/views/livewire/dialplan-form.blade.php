<div>
	<div class="container-fluid">
		<div class="card card-primary mt-3 card-outline">
			<div class="card-header">
				<div class="d-flex justify-content-between align-items-center">
					<h3 class="card-title">
						{{ isset($dialplan) ? 'Edit Dialplan' : 'Create Dialplan' }}
					</h3>
				</div>
			</div>

			<div class="card-body">
				<form wire:submit.prevent="save">
					<div class="card-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label for="dialplan_name" class="form-label">Name</label>
									<input
										type="text"
										class="form-control @error('dialplan_name') is-invalid @enderror"
										id="dialplan_name"
										name="dialplan_name"
										placeholder="Enter dialplan name"
										value="{{ old('dialplan_name', $dialplan->dialplan_name ?? '') }}"
										required
										wire:model="dialplan_name"
									>
									@error('dialplan_name')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label for="dialplan_number" class="form-label">Number</label>
									<input
										type="text"
										class="form-control @error('dialplan_number') is-invalid @enderror"
										id="dialplan_number"
										name="dialplan_number"
										placeholder="Enter dialplan number"
										value="{{ old('dialplan_number', $dialplan->dialplan_number ?? '') }}"
										wire:model="dialplan_number"
									>
									@error('dialplan_number')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label for="hostname" class="form-label">Hostname</label>
									<input
										type="text"
										class="form-control @error('hostname') is-invalid @enderror"
										id="hostname"
										name="hostname"
										placeholder="Enter dialplan hostname"
										value="{{ old('hostname', $dialplan->hostname ?? '') }}"
										wire:model="hostname"
									>
									@error('hostname')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label for="dialplan_context" class="form-label">Context</label>
									<input
										type="text"
										class="form-control @error('dialplan_context') is-invalid @enderror"
										id="dialplan_context"
										name="dialplan_context"
										placeholder="Enter dialplan context"
										value="{{ old('dialplan_context', $dialplan->dialplan_context ?? $dialplan_default_context ) }}"
										required
										wire:model="dialplan_context"
									>
									@error('dialplan_context')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label for="dialplan_order" class="form-label">Order</label>
									<input
										type="number"
										step="1"
										min="0"
										max="999"
										class="form-control @error('dialplan_order') is-invalid @enderror"
										id="dialplan_order"
										name="dialplan_order"
										placeholder="Enter dialplan order"
										value="{{ old('dialplan_order', $dialplan->dialplan_order ?? '200') }}"
										required
										wire:model="dialplan_order"
									>
									@error('dialplan_order')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

				@can('dialplan_domain')
						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label for="domain_uuid" class="form-label">Domain</label>
									<select
										class="form-select @error('domain_uuid') is-invalid @enderror"
										id="domain_uuid"
										name="domain_uuid"
										wire:model="domain_uuid"
									>
										<option value="">Global</option>
										@foreach($domains as $domain)
											<option value="{{ $domain->domain_uuid }}"
												{{ old('domain_uuid', (isset($group) ? $group->domain_uuid : Auth::user()->domain_uuid) ??  '') == $domain->domain_uuid ? 'selected' : '' }}>
												{{ $domain->domain_name }}
											</option>
										@endforeach
									</select>
									@error('domain_uuid')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>
				@endcan
						<div class="row mt-3">
							<div class="col-md-12">
								<div class="form-group">
									<label for="dialplan_description" class="form-label">Description</label>
									<textarea
										class="form-control @error('dialplan_description') is-invalid @enderror"
										id="dialplan_description"
										name="dialplan_description"
										rows="3"
										placeholder="Enter domain description"
										wire:model="dialplan_description"
									>{{ old('dialplan_description', $dialplan->dialplan_description ?? '') }}</textarea>
									@error('dialplan_description')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label class="form-label d-block">Continue</label>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="dialplan_continue" name="dialplan_continue" wire:model="dialplan_continue" value="true" {{ old('dialplan_continue', $dialplan->dialplan_continue ?? false) ? 'checked' : 'checked' }}>
										<label class="form-check-label" for="dialplan_continue">{{ __('Continue') }}</label>
									</div>
									@error('dialplan_continue')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label class="form-label d-block">Destination</label>
									<div class="form-check form-switch">
										<input class="form-check-input" type="checkbox" role="switch" id="dialplan_destination" name="dialplan_destination" wire:model="dialplan_destination" value="true" {{ old('dialplan_destination', $dialplan->dialplan_destination ?? false) ? 'checked' : '' }}>
										<label class="form-check-label" for="dialplan_destination">{{ __('Destination') }}</label>
									</div>
									@error('dialplan_destination')
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
										<input class="form-check-input" type="checkbox" role="switch" id="dialplan_enabled" name="dialplan_enabled" wire:model="dialplan_enabled" value="true" {{ old('dialplan_enabled', $dialplan->dialplan_enabled ?? false) ? 'checked' : '' }}>
										<label class="form-check-label" for="dialplan_enabled">{{ __('Enabled') }}</label>
									</div>
									@error('dialplan_enabled')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>
					</div>

                    <h5 class="mt-4 mb-3">Detail</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
									<thead>
										<tr>
											<th>{{ __('Tag') }}</th>
											<th>{{ __('Type') }}</th>
											<th>{{ __('Data') }}</th>
											<th>{{ __('Break') }}</th>
											<th>{{ __('Inline') }}</th>
											<th>{{ __('Group') }}</th>
											<th>{{ __('Order') }}</th>
											<th>{{ __('Enabled') }}</th>
											<th class="text-center">{{ __('Action') }}</th>
										</tr>
									</thead>
									<tbody>
										@if(!empty($dialplanDetails))
											@foreach($dialplanDetails as $index => $detail)
											<tr>
												<td>
													<select class="form-select @error('dialplanDetails.' . $index . '.dialplan_detail_tag') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_tag" required>
														<option value=""></option>
														<option value="condition">Condition</option>
														<option value="regex">Regular Expression</option>
														<option value="action">Action</option>
														<option value="anti-action">Anti-Action</option>
													</select>
												</td>
												<td>
													<input list="type_list" class="form-control @error('dialplanDetails.' . $index . '.dialplan_detail_type') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_type">
													<datalist id="type_list">
														@foreach($types as $type)
															<option value="{{ $type['key'] }}">{{ $type['value'] }}</option>
														@endforeach
													</datalist>
												</td>
												<td>
													<input type="text" class="form-control @error('dialplanDetails.' . $index . '.dialplan_detail_data') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_data">
												</td>
												<td>
													<select class="form-select @error('dialplanDetails.' . $index . '.dialplan_detail_break') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_break">
														<option value=""></option>
														<option value="on-true">On True</option>
														<option value="on-false">On False</option>
														<option value="always">Always</option>
														<option value="never">Never</option>
													</select>
												</td>
												<td>
													<select class="form-select @error('dialplanDetails.' . $index . '.dialplan_detail_inline') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_inline">
														<option value=""></option>
														<option value="true">True</option>
														<option value="false">False</option>
													</select>
												</td>
												<td>
													<input type="number" class="form-control @error('dialplanDetails.' . $index . '.dialplan_detail_group') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_group" required>
												</td>
												<td>
													<input type="number" step="1" min="0" max="1000" class="form-control @error('dialplanDetails.' . $index . '.dialplan_detail_order') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_order" required>
												</td>
												<td>
													<select class="form-select @error('dialplanDetails.' . $index . '.dialplan_detail_enabled') is-invalid @enderror" wire:model="dialplanDetails.{{ $index }}.dialplan_detail_enabled">
														<option value=""></option>
														<option value="true">True</option>
														<option value="false">False</option>
													</select>
												</td>
												<td class="text-center">
													@if (count($dialplanDetails) > 1)
													<button type="button" class="btn btn-sm btn-danger" wire:click="removeDialplanDetail({{ $index }})"><i class="fas fa-times"></i> Remove</button>
													@endif

													@if ($index === count($dialplanDetails) - 1)
														<button type="button" class="btn btn-sm btn-success" wire:click="addDialplanDetail"><i class="fas fa-plus"></i> Add</button>
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

					<div class="modal fade" id="dialplan_xml" tabindex="-1" aria-labelledby="dialplan_xml" aria-hidden="true">
						<div class="modal-dialog modal-lg" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title">Dialplan XML</h5>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
								</div>
								<div class="modal-body">
									<p>
<pre><code class="language-xml">{{ $dialplan->dialplan_xml ?? '' }}</code></pre>
									</p>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="card-footer">
						<button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
							{{ isset($dialplan) ? 'Update Dialplan' : 'Create Dialplan' }}
						</button>
						@if(isset($dialplan))
						<button type="button" class="btn btn-secondary px-4 py-2" data-bs-toggle="modal" data-bs-target="#dialplan_xml" style="border-radius: 4px;">View XML</button>
						@endif
						<a href="{{ route('dialplans.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
							Cancel
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
