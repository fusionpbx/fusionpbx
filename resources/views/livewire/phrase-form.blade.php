<div>
	<div class="container-fluid">
		<div class="card card-primary mt-3 card-outline">
			<div class="card-header">
				<div class="d-flex justify-content-between align-items-center">
					<h3 class="card-title">
						{{ isset($phrase) ? 'Edit Phrase' : 'Create Phrase' }}
					</h3>
				</div>
			</div>

			<div class="card-body">
				<form wire:submit.prevent="save">

					<div class="card-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label for="phrase_name" class="form-label">Name</label>
									<input
										type="text"
										class="form-control @error('phrase_name') is-invalid @enderror"
										id="phrase_name"
										name="phrase_name"
										placeholder="Enter phrase name"
										value="{{ old('phrase_name', $phrase->phrase_name ?? '') }}"
										required
										wire:model="phrase_name"
									>
									@error('phrase_name')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

						<div class="row mt-3">
							<div class="col-md-6">
								<div class="form-group">
									<label for="phrase_language" class="form-label">Language</label>
									<input
										type="text"
										class="form-control @error('phrase_language') is-invalid @enderror"
										id="phrase_language"
										name="phrase_language"
										placeholder="Enter phrase language"
										value="{{ old('phrase_language', $phrase->phrase_language ?? '') }}"
										wire:model="phrase_language"
									>
									@error('phrase_language')
										<div class="invalid-feedback d-block">{{ $message }}</div>
									@enderror
								</div>
							</div>
						</div>

					@can('phrase_domain')
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
							<div class="col-md-6">
								<div class="form-group">
									<label for="phrase_description" class="form-label">Description</label>
									<textarea
										class="form-control @error('phrase_description') is-invalid @enderror"
										id="phrase_description"
										name="phrase_description"
										rows="3"
										placeholder="Enter domain description"
										wire:model="phrase_description"
									>{{ old('phrase_description', $phrase->phrase_description ?? '') }}</textarea>
									@error('phrase_description')
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
										<input class="form-check-input" type="checkbox" role="switch" id="phrase_enabled" name="phrase_enabled" wire:model="phrase_enabled" value="true" {{ old('phrase_enabled', $phrase->phrase_enabled ?? false) ? 'checked' : '' }}>
										<label class="form-check-label" for="phrase_enabled">{{ __('Enabled') }}</label>
									</div>
									@error('phrase_enabled')
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
											<th>{{ __('Function') }}</th>
											<th>{{ __('Action') }}</th>
											<th>{{ __('Order') }}</th>
											<th class="text-center">{{ __('Action') }}</th>
										</tr>
									</thead>
									<tbody>
										@if(!empty($phraseDetails))
											@foreach($phraseDetails as $index => $detail)
											<tr>
												<td>
													<select class="form-select @error('phraseDetails.' . $index . '.phrase_detail_function') is-invalid @enderror" wire:model="phraseDetails.{{ $index }}.phrase_detail_function" required>
														<option value=""></option>
													</select>
												</td>
												<td>
													<input type="text" class="form-control @error('phraseDetails.' . $index . '.phrase_detail_action') is-invalid @enderror" wire:model="phraseDetails.{{ $index }}.phrase_detail_action">
												</td>
												<td>
													<input type="number" step="1" min="0" max="1000" class="form-control @error('phraseDetails.' . $index . '.phrase_detail_order') is-invalid @enderror" wire:model="phraseDetails.{{ $index }}.phrase_detail_order" required>
												</td>
												<td class="text-center">
													@if (count($phraseDetails) > 1)
													<button type="button" class="btn btn-sm btn-danger" wire:click="removePhraseDetail({{ $index }})"><i class="fas fa-times"></i> <i class="bi bi-trash"></i> </button>
													@endif

													@if ($index === count($phraseDetails) - 1)
														<button type="button" class="btn btn-sm btn-success" wire:click="addPhraseDetail"><i class="fas fa-plus"></i> Add</button>
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
							{{ isset($phrase) ? 'Update Phrase' : 'Create Phrase' }}
						</button>
						<a href="{{ route('phrases.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
							Cancel
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
