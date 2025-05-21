<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6>Relations <i class="fa fa-users" aria-hidden="true"></i></h6>
            @can('contact_relation_add')
            <button type="button" class="btn btn-sm btn-primary" wire:click="addRelation"> <i class="fa fa-plus" aria-hidden="true"></i></button>
            @endcan
        </div>
        <div class="card-body">
            @foreach($relations as $index => $relation)
            <div class="row mb-3">
                <div class="col-md-5">
                    <label for="relations.{{ $index }}.relation_label" class="form-label">Label</label>
                    <select wire:model="relations.{{ $index }}.relation_label" class="form-select">
                        <option value="">Select a label</option>
                        <option value="associate">Associate</option>
                        <option value="child">Child</option>
                        <option value="employee">Employee</option>
                        <option value="member">Member</option>
                        <option value="other">Other</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="contact_search_{{ $index }}" class="form-label">Contact</label>
                    <div class="position-relative" wire:ignore>
                        <select id="contact_search_{{ $index }}" 
                               class="form-control select2-contact-search"
                               data-index="{{ $index }}"
                               data-value="{{ $relations[$index]['relation_contact_uuid'] ?? '' }}"
                               data-placeholder="Search contact...">
                            @if(!empty($relations[$index]['relation_contact_uuid']) && !empty($relations[$index]['contact_name']))
                                <option value="{{ $relations[$index]['relation_contact_uuid'] }}" selected>
                                    {{ $relations[$index]['contact_name'] }}
                                </option>
                            @endif
                        </select>
                    </div>
                </div>
                @can('contact_relation_delete')
                <div class="col-md-1 align-self-end">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger ms-3" wire:click="removeRelation({{ $index }})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                @endcan
            </div>
            @endforeach
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded, required for Select2');
                return;
            }
            
            if (typeof jQuery.fn.select2 === 'undefined') {
                console.error('Select2 is not loaded');
                return;
            }
        });
    </script>
    @endpush
</div>