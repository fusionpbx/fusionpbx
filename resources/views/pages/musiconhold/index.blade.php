@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Music On Hold Table')}}
            </h3>

            <div class="card-tools">
  				<div class="d-flex gap-2 align-items-center flex-wrap" role="group" aria-label="Music On Hold actions">
					@can('music_on_hold_add')
					<div class="upload-containter" style="display: none;">
						<form method="POST" action="{{ route('musiconhold.upload') }}" enctype="multipart/form-data">
							@csrf
							<div class="form-group">
								<label class="form-label">Category</label>
								<input list="type_list" name="music_on_hold_name" class="form-control form-control-sm" required>
								<datalist id="type_list">
									@foreach($categories as $category)
										<option value="{{ $category }}">{{ $category }}</option>
									@endforeach
								</datalist>
							</div>

							<div class="form-group mt-2">
								<label class="form-label">Rate</label>
								<select name="music_on_hold_rate" class="form-select form-select-sm" required>
									<option value=""></option>
									<option value="8000">8 kHz</option>
									<option value="16000">16 kHz</option>
									<option value="32000">32 kHz</option>
									<option value="485000">48 kHz</option>
								</select>
							</div>

							<div class="form-group mt-2">
								<label class="form-label">Audio file</label>
								<input class="form-control form-control-sm" type="file" accept=".mp3,.wav" name="music_on_hold_file" required>
							</div>

							<div class="form-group mt-2 text-center">
								<button type="submit" class="btn btn-primary btn-sm" style="border-radius: 4px;">{{ __('Upload') }}</button>
								<button type="button" class="btn btn-primary btn-sm btn-cancel-upload">{{ __('Cancel') }}</button>
							</div>
						</form>
					</div>

					<button type="button" class="btn btn-primary btn-sm btn-add-upload">
						<i class="fa fa-plus" aria-hidden="true"></i> {{ __('Add') }}
					</button>

					@endcan
                </div>
            </div>
        </div>

        <div class="card-body">
			<table class="table">
				<tbody>
				@foreach($list as $item)
					<tr>
						<td colspan="4" style="padding-top: 50px; color: #0d6efd;">{{ $item["name"] }}</td>
					</tr>
					<tr style="background-color:#e0e0e0;">
						<td style="background-color: inherit;">{{ $item["rate"] }} kHz</td>
						<td style="background-color: inherit;">Tools</td>
						<td style="background-color: inherit;">File size</td>
						<td style="background-color: inherit;">Uploaded</td>
					</tr>
					@foreach($item["files"] as $file)
						@php
						$play = route('musiconhold.play', [$item["id"], $file["name"]]);
						$download = route('musiconhold.download', [$item["id"], $file["name"]]);
						@endphp
						<tr>
							<td>{{ $file["name"] }}</td>
							<td>
								<div class='progress-bar' style='background-color: #0d6efd; width: 0; height: 3px; position: relative; margin: 5px 0;'></div>
								<audio style='display: none;' preload='none' src='{{ $play }}'></audio>
								<button type='button' alt='Play / Pause' title='Play / Pause' class='btn btn-secondary btn-play-audio'><i class='fas fa-play'></i></button>
								<a href='{{ $download }}' target='_self'><button alt='Download' title='Download' class='btn btn-secondary'><i class='fas fa-download'></i></button></a>
							</td>
							<td>{{ $file["size"] }}</td>
							<td>{{ $file["uploaded"] }}</td>
						</tr>
					@endforeach
				@endforeach
				</tbody>
			</table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function()
	{
        const btnAddUpload = document.querySelector(".btn-add-upload");
        const btnCancelUpload = document.querySelector(".btn-cancel-upload");
        const uploadContainter = document.querySelector(".upload-containter");

		btnAddUpload.addEventListener("click", function()
		{
			btnAddUpload.style.display = "none";
			uploadContainter.style.display = "block";
		});

		btnCancelUpload.addEventListener("click", function()
		{
			btnAddUpload.style.display = "inline";
			uploadContainter.style.display = "none";
		});
    });
</script>
@endpush
