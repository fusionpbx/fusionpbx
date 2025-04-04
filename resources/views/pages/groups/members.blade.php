@extends('layouts.app')

@section('content')
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/jquery.bootstrap-duallistbox.min.js" integrity="sha512-l/BJWUlogVoiA2Pxj3amAx2N7EW9Kv6ReWFKyJ2n6w7jAQsjXEyki2oEVsE6PuNluzS7MvlZoUydGrHMIg33lw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap4-duallistbox/4.0.2/bootstrap-duallistbox.css" integrity="sha512-8TCY/k+p0PQ/9+htlHFRy3AVINVaFKKAxZADSPH3GSu3UWo2eTv9FML0hJZrvNQbATtPM4fAw3IS31Yywn91ig==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script>
$(function()
{
    $(".duallistbox").bootstrapDualListbox({
		infoText: false,
        nonSelectedListLabel: 'Users',
        selectedListLabel: 'Current members',
	});
})
</script>

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
