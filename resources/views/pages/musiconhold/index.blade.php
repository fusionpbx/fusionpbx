@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mt-3 card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Music On Hold Table')}}
            </h3>

            <div class="card-tools">
                <div class="d-flex gap-2 " role="group" aria-label="Group actions">
					<form method="POST" action="{{ route('musiconhold.upload') }}" enctype="multipart/form-data">
						@csrf
						<input list="type_list" name="music_on_hold_name" class="form-control">
						<datalist id="type_list">
							@foreach($categories as $category)
								<option value="{{ $category }}">{{ $category }}</option>
							@endforeach
						</datalist>
						<select name="music_on_hold_rate" class="form-select">
							<option value="">Default</option>
							<option value="8000">8 kHz</option>
							<option value="16000">16 kHz</option>
							<option value="32000">32 kHz</option>
							<option value="48000">48 kHz</option>
						</select>
						<div class="mb-3">
						<label for="formFile" class="form-label"></label>
						<input class="form-control" type="file" name="music_on_hold_file">
						</div>
						<button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">{{ __('Upload') }}</button>

					</form>
                </div>
            </div>
        </div>

        <div class="card-body">
			<table class="table">
				<tbody>
				@foreach($list as $item)
					<tr style="background-color:#e0e0e0;">
						<td style="background-color: inherit;">{{ $item["name"] }} kHz</td>
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

