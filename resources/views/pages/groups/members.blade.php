@extends('layouts.app')

@section('content')
@vite('resources/js/jquery.js')

<div class="container-fluid ">
    <div class="card card-primary mt-3">
        <div class="card-header">
            <h3 class="card-title">
                {{ 'Edit Group Members' }}
            </h3>
        </div>

		<form action="{{ route('usergroup.update', $group->group_uuid) }}"
              method="POST">
            @csrf
            @if(isset($group))
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
							<div class="bootstrap-duallistbox-container row moveonselect moveondoubleclick">
								<select name="members[]" class="duallistbox" multiple>
									@foreach($users as $user)
									<option value="{{ $user->user_uuid }}">{{ $user->username }}</option>
									@endforeach
									@foreach($members as $member)
									<option value="{{ $member->user_uuid }}" selected>{{ $member->username }}</option>
									@endforeach
								</select>
							</div>
                            @error('group_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary px-4 py-2" style="border-radius: 4px;">
                    {{ 'Update' }}
                </button>
                <a href="{{ route('groups.index') }}" class="btn btn-secondary ml-2 px-4 py-2" style="border-radius: 4px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
