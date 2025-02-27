@extends('layouts.app', ["page_title"=> "Domain Groups"])

@section('content')
<!-- Start Content-->
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Domain Groups</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-xl-4">
                            <label class="form-label">Showing {{ $groups->count() ?? 0 }}  results for Domain Groups</label>
                        </div>
                        <div class="col-xl-8">
                            <div class="text-xl-end mt-xl-0 mt-2">
                                @if ($permissions['add_new'])
                                    <a href="{{ route('domaingroups.create') }}" class="btn btn-success mb-2 me-2">
                                        <i class="mdi mdi-plus-circle me-1"></i>
                                        Add New
                                    </a>
                                @endif

                                @if ($permissions['delete'])
                                    <a href="javascript:confirmDeleteAction('{{ route('domaingroups.destroy', ':id') }}');" id="deleteMultipleActionButton" class="btn btn-danger mb-2 me-2 disabled">
                                        Delete Selected
                                    </a>
                                @endif
                                {{-- <button type="button" class="btn btn-light mb-2">Export</button> --}}
                            </div>
                        </div><!-- end col-->
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20px;">
                                        @if ($permissions['delete'])
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="selectallCheckbox">
                                                <label class="form-check-label" for="selectallCheckbox">&nbsp;</label>
                                            </div>
                                        @endif
                                    </th>
                                    <th>Name</th>
                                    <th style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $group)
                                    <tr id="id{{ $group->domain_group_uuid  }}">
                                        <td>
                                            <div class="form-check"
                                                style="@if (!$permissions['delete']) display: none; @endif">
                                                <input type="checkbox" name="action_box[]" value="{{ $group->domain_group_uuid }}" class="form-check-input action_checkbox">
                                                <label class="form-check-label" >&nbsp;</label>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($permissions['edit']) 
                                                <a href="{{ route('domaingroups.edit',$group) }}" class="text-body fw-bold">{{ $group['group_name'] }}</a> 
                                                {{-- <a href="core/groups/group_edit.php?id={{ $group->group_uuid }}" class="text-body fw-bold">{{ $group['group_name'] }}</a>  --}}
                                            @else
                                                <span class="text-body fw-bold">{{ $group['group_name'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                             {{-- Action Buttons --}}
                                             <div id="tooltip-container-actions">
                                                @if ($permissions['edit'])
                                                    <a href="{{ route('domaingroups.edit',$group) }}" class="action-icon" title="Edit"> 
                                                        <i class="mdi mdi-lead-pencil" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit group"></i>
                                                    </a>
                                                @endif
                                                @if ($permissions['delete'])
                                                    <a href="javascript:confirmDeleteAction('{{ route('domaingroups.destroy', ':id') }}','{{ $group->domain_group_uuid }}');" class="action-icon"> 
                                                        <i class="mdi mdi-delete" data-bs-container="#tooltip-container-actions" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            {{-- End of action buttons --}}

                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>
    <!-- end row -->

</div> <!-- container -->




@endsection


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {


    });


</script>
@endpush