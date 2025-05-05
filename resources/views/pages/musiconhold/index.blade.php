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

                </div>
            </div>
        </div>

        <div class="card-body">
			@foreach($list as $item)
			<table class="table table-striped">
				<thead>
					<tr>
						<th>{{ $item["name"] }} kHz</th>
						<th>Tools</th>
						<th>File size</th>
						<th>Uploaded</th>
					</tr>
				</thead>
				<tbody>
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
				</tbody>
			</table>
			@endforeach
        </div>
    </div>
</div>
@endsection

