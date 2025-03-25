@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-layer-group mr-2"></i> {{__('Groups Table')}}
            </h3>

            <div class="card-tools">
                <div class="btn-group btn-group-sm" role="group" aria-label="Group actions">

                    <a href="{{ route('users.index') }}" class="btn btn-secondary">

                        <i class="fas fa-users mr-1"></i> {{__('Users')}}
                    </a>

                    <button type="button" class="btn btn-warning" onclick="restoreDefault()">
                        <i class="fas fa-undo mr-1"></i> {{__('Restore Default')}}
                    </button>

                    <a href="{{ route('groups.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> {{__('Add')}}
                    </a>

                    <button class="btn btn-info" data-toggle="modal" data-target="#shareModal">
                        <i class="fas fa-share-alt mr-1"></i> {{__('Share')}}
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <livewire:groups-table />
        </div>
    </div>
</div>

<div class="modal fade" id="bulkUpdateModal" tabindex="-1" role="dialog" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUpdateModalLabel">{{__('Bulk Update Groups')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="bulkUpdateForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bulk_group_level">{{__('Group Level')}}</label>
                        <select class="form-control" id="bulk_group_level" name="group_level">
                            <option value="">Select Level</option>
                            @foreach(range(10, 90, 10) as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bulk_group_description">{{__('Group Description')}}</label>
                        <textarea class="form-control" id="bulk_group_description" name="group_description" rows="3" placeholder="Enter group description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('Cancel')}}</button>
                    <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('bulkUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Get form data as an object
        const formData = Object.fromEntries(new FormData(this));

        // Use $wire.dispatch for modern Livewire
        Livewire.dispatch('bulk-update', formData);

        // Close the modal (assuming you're using Bootstrap)
        $('#bulkUpdateModal').modal('hide');
    });
});

document.addEventListener('livewire:init', () => {
    Livewire.on('show-bulk-update-modal', () => {
        $('#bulkUpdateModal').modal('show');
    });
});
</script>
@endpush
