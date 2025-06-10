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
                                <table class="table table-bordered phrase-detail">
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
													<select class="form-select form-select-function @error('phraseDetails.' . $index . '.phrase_detail_function') is-invalid @enderror" wire:model="phraseDetails.{{ $index }}.phrase_detail_function" required>
														<option value="play-file" @selected($phraseDetails[$index]['phrase_detail_function'] == 'play-file')>Play</option>
														<option value="pause-file" @selected($phraseDetails[$index]['phrase_detail_function'] == 'pause-file')>Pause</option>
														@if(auth()->user()->hasGroup('superadmin'))
														<option value="execute" @selected($phraseDetails[$index]['phrase_detail_function'] == 'execute')>Execute</option>
														@endif
													</select>
												</td>
												<td>
													<input list="sounds_play" type="{{ auth()->user()->hasGroup('superadmin') ? 'text' : 'hidden' }}" class="form-control form-control-action @error('phraseDetails.' . $index . '.phrase_detail_data') is-invalid @enderror" wire:model="phraseDetails.{{ $index }}.phrase_detail_data" />

													@if(auth()->user()->hasGroup('superadmin'))
													<datalist id="sounds_play">
													@else
													<select class="form-select sounds_play @if(in_array($detail['phrase_detail_function'], ['pause-file', 'execute'])) d-none @endif">
													@endif
														<option value=""></option>
														<optgroup label="Sounds">
															@foreach($sounds as $sound)
																<option value="{{ $sound }}" @selected($sound == $phraseDetails[$index]['phrase_detail_data'])>{{ $sound }}</option>
															@endforeach
														</optgroup>
													@if(auth()->user()->hasGroup('superadmin'))
													</datalist>
													@else
													</select>
													@endif

													@if(auth()->user()->hasGroup('superadmin'))
													<datalist id="sounds_pause">
													@else
													<select class="form-select sounds_pause @if(in_array($detail['phrase_detail_function'], ['play-file', 'execute'])) d-none @endif">
													@endif

													@for($s = 0.1; $s <= 5; $s = $s + 0.1)
														@php
															$sleep = "sleep(" . ($s * 1000) . ")";
														@endphp
														<option value="{{ $sleep }}" @selected($sleep == $phraseDetails[$index]['phrase_detail_data'])>{{ number_format($s, 1) }}</option>
													@endfor

													@if(auth()->user()->hasGroup('superadmin'))
													</datalist>
													@else
													</select>
													@endif
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


@push("scripts")
<script>

document.addEventListener('DOMContentLoaded', function()
{
	document.addEventListener("change", function(e)
	{
  		if(e.target && e.target.classList.contains('form-select-function'))
		{
    		const select = e.target;
			const row = select.closest("tr");
			const input = row.querySelector(".form-control-action");
			const sounds_play = row.querySelector(".sounds_play");
			const sounds_pause = row.querySelector(".sounds_pause");

			input.value = "";

			let list = null;

			switch(select.selectedIndex)
			{
				case 0:
					list = "sounds_play";
					if(sounds_play) sounds_play.classList.remove("d-none");
					if(sounds_pause) sounds_pause.classList.add("d-none");
					break;
				case 1:
					list = "sounds_pause";
					if(sounds_play) sounds_play.classList.add("d-none");
					if(sounds_pause) sounds_pause.classList.remove("d-none");
					break;
				case 2:
					list = null;
					break;
			}

			input.setAttribute("list", list);
		}
	});

    window.addEventListener('newRow', function(e)
	{
		setTimeout(function()
		{
			const row = document.querySelector(".phrase-detail tbody").lastElementChild;

			row.querySelector(".sounds_pause").classList.add("d-none");
		}, 0);
    });
});

</script>

@endpush

